<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/Logger.php';

class Auth {
    private Database $db;
    private PDO $conn;

    // Config do cookie/token
    private string $cookieName   = 'bc_token';
    private int    $cookieDays   = COOKIE_DAYS;     // define em config/env.php
    private bool   $cookieSecure = COOKIE_SECURE;   // define em config/env.php
    private string $sameSite     = COOKIE_SAMESITE; // define em config/env.php

    private Logger $logger;

    public function __construct() {
        $this->db     = new Database();
        $this->conn   = $this->db->connect();
        $this->logger = new Logger();

        // Endurece cookie de sessão PHP (opcional, mas recomendado)
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => $this->sameSite,
                // 'secure' => true, // em produção (HTTPS)
            ]);
            session_start();
        }
    }

    private function gerarToken(): string {
        return bin2hex(random_bytes(32)); // 64 hex chars
    }

    private function setSessionCookie(string $token): void {
        $expires = time() + ($this->cookieDays * 24 * 60 * 60);
        setcookie($this->cookieName, $token, [
            'expires'  => $expires,
            'path'     => '/',
            'httponly' => true,
            'samesite' => $this->sameSite,
            'secure'   => $this->cookieSecure,
        ]);
    }

    private function limparSessionCookie(): void {
        setcookie($this->cookieName, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => $this->sameSite,
            'secure'   => $this->cookieSecure,
        ]);
    }

    /** Encaminha para Logger por ID (usuário) ou system */
    private function registrarLog(?int $userId, string $action, ?string $details = null): void {
        if ($userId) {
            $this->logger->byUserId($userId, $action, $details);
        } else {
            $this->logger->system($action, $details);
        }
    }

    /** Login (cria sessão PHP + token persistido em sessoes + cookie) */
    public function login(string $email, string $senha): array {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = :email AND ativo = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha_hash'])) {
            // Inicia sessão do usuário
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => (int)$user['id'],
                'nome'  => $user['nome'],
                'email' => $user['email'],
                'role'  => $user['role']
            ];

            // Gera token e persiste em sessoes
            $token = $this->gerarToken();
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR']      ?? null;

            $sql = "INSERT INTO sessoes (usuario_id, token, user_agent, ip, data_expiracao, estado)
                    VALUES (:uid, :token, :ua, :ip, DATE_ADD(NOW(), INTERVAL :days DAY), 'ativa')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':uid'   => (int)$user['id'],
                ':token' => $token,
                ':ua'    => $ua,
                ':ip'    => $ip,
                ':days'  => $this->cookieDays
            ]);

            // Define cookie do token
            $this->setSessionCookie($token);

            // Log
            $this->registrarLog((int)$user['id'], 'login', 'Usuário autenticado');

            return [
                'success' => true,
                'message' => 'Login bem-sucedido',
                'user'    => $_SESSION['user']
            ];
        }

        $this->registrarLog(null, 'login.falha', json_encode(['email'=>$email]));

        return [
            'success' => false,
            'message' => 'Email ou senha inválidos'
        ];
    }

    /** Verifica sessão usando cookie de token (e rehidrata $_SESSION['user'] se válido) */
    public function verificarSessao(): bool {
        if (!empty($_SESSION['user'])) {
            return true;
        }

        $token = $_COOKIE[$this->cookieName] ?? null;
        if (!$token) return false;

        $sql = "SELECT u.*
                  FROM sessoes s
                  JOIN usuarios u ON u.id = s.usuario_id
                 WHERE s.token = :token
                   AND s.estado = 'ativa'
                   AND s.data_expiracao > NOW()
                 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':token' => $token]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($u) {
            $_SESSION['user'] = [
                'id'    => (int)$u['id'],
                'nome'  => $u['nome'],
                'email' => $u['email'],
                'role'  => $u['role']
            ];
            return true;
        }

        return false;
    }

    /** Logout (encerra sessão + invalida token + limpa cookie) */
    public function logout(): array {
        if (isset($_SESSION['user']['id'])) {
            $this->registrarLog((int)$_SESSION['user']['id'], 'logout', 'Usuário saiu');
        }

        $token = $_COOKIE[$this->cookieName] ?? null;
        if ($token) {
            $up = $this->conn->prepare("UPDATE sessoes SET estado='desativada' WHERE token=:t");
            $up->execute([':t' => $token]);
        }

        $this->limparSessionCookie();
        session_unset();
        session_destroy();

        return ['success' => true, 'message' => 'Logout realizado'];
    }

    /** Retorna dados do usuário logado (ou rehidrata via cookie) */
    public function check() {
        if (!empty($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        return $this->verificarSessao() ? $_SESSION['user'] : null;
    }

    /** Helpers de role */
    public function isAdmin(): bool {
        $u = $this->check();
        return !empty($u) && ($u['role'] === 'admin');
    }

    public function hasRole(string $role): bool {
        $u = $this->check();
        return !empty($u) && ($u['role'] === $role);
    }
}
