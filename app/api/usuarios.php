<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/User.php';
require_once __DIR__ . '/../lib/Logger.php';

/* --------------------------
   Helpers JSON
--------------------------- */

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $msg, int $code = 400, array $extra = []): void {
    json_response(array_merge([
        'success' => false,
        'message' => $msg,
    ], $extra), $code);
}

function read_body(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST ?? [];
}

/* --------------------------
   Autenticação
--------------------------- */

try {
    $auth = new Auth();

    if (!$auth->check() || !$auth->isAdmin()) {
        json_error('Acesso negado', 401);
    }

    $userModel     = new User();
    $logger        = new Logger();
    $currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

    // aliases aceites pelo front
    $action  = $_GET['action'] ?? 'list';
    $aliases = [
        'listar'    => 'list',
        'adicionar' => 'add',
        'editar'    => 'update',
        'remover'   => 'delete',
    ];
    if (isset($aliases[$action])) {
        $action = $aliases[$action];
    }

    switch ($action) {

        case 'stats': {
            $all = $userModel->getAll();

            $adminsAtivos  = 0;
            $bibliosAtivos = 0;

            foreach ($all as $u) {
                if ((int)($u['ativo'] ?? 0) === 1) {
                    if ($u['role'] === 'admin') {
                        $adminsAtivos++;
                    }
                    if ($u['role'] === 'bibliotecario') {
                        $bibliosAtivos++;
                    }
                }
            }

            json_response([
                'success'              => true,
                'adminsAtivos'         => $adminsAtivos,
                'bibliotecariosAtivos' => $bibliosAtivos,
                'totalUsuarios'        => count($all),
            ]);
            break;
        }

        case 'list': {
            $items = $userModel->getAll();
            // compat: front espera { success, usuarios: [...] }
            json_response([
                'success'  => true,
                'usuarios' => $items,
                'items'    => $items,
            ]);
            break;
        }

        case 'add': {
            $data = read_body();

            $nome  = trim((string)($data['nome']  ?? ''));
            $email = trim((string)($data['email'] ?? ''));
            $senha = (string)($data['senha'] ?? '');
            $role  = trim((string)($data['role']  ?? 'bibliotecario'));

            if ($nome === '' || $email === '' || $senha === '') {
                json_error('Campos obrigatórios: nome, email, senha', 400);
            }

            $ok = $userModel->add($nome, $email, $senha, $role);
            if ($ok && $currentUserId) {
                $logger->byUserId(
                    $currentUserId,
                    'user.add',
                    json_encode(['email' => $email, 'role' => $role])
                );
            }

            json_response(['success' => (bool)$ok]);
            break;
        }

        case 'update': {
            $data = read_body();

            $id    = (int)($data['id'] ?? 0);
            $nome  = trim((string)($data['nome']  ?? ''));
            $email = trim((string)($data['email'] ?? ''));
            $role  = trim((string)($data['role']  ?? 'bibliotecario'));
            $ativo = (int)($data['ativo'] ?? 1);

            if ($id <= 0 || $nome === '' || $email === '') {
                json_error('Campos obrigatórios: id, nome, email', 400);
            }

            $ok = $userModel->update($id, $nome, $email, $role, $ativo);
            if ($ok && $currentUserId) {
                $logger->byUserId(
                    $currentUserId,
                    'user.update',
                    json_encode([
                        'id'    => $id,
                        'email' => $email,
                        'role'  => $role,
                        'ativo' => $ativo,
                    ])
                );
            }

            json_response(['success' => (bool)$ok]);
            break;
        }

        case 'delete': {
            $id = (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                json_error('ID inválido', 400);
            }

            $toLog = $userModel->getById($id);
            $ok    = $userModel->delete($id);

            if ($ok && $currentUserId) {
                $logger->byUserId(
                    $currentUserId,
                    'user.delete',
                    json_encode($toLog ?: ['id' => $id])
                );
            }

            json_response(['success' => (bool)$ok]);
            break;
        }

        default: {
            json_error('Ação inválida', 400, ['action' => $action]);
        }
    }

} catch (Throwable $e) {
    json_error('Erro interno: ' . $e->getMessage(), 500);
}
