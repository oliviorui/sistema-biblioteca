<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/User.php';
require_once __DIR__ . '/../lib/Logger.php';

try {
    // Somente admin
    $auth = new Auth();
    if (!$auth->check() || !$auth->isAdmin()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit;
    }

    $userModel     = new User();
    $logger        = new Logger();
    $currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

    // aliases aceites pelo front
    $action = $_GET['action'] ?? 'list';
    $aliases = [
        'listar'   => 'list',
        'adicionar'=> 'add',
        'editar'   => 'update',
        'remover'  => 'delete',
    ];
    if (isset($aliases[$action])) $action = $aliases[$action];

    switch ($action) {
        case 'stats': {
            $userModel = new User();
            $all = $userModel->getAll();

            $adminsAtivos = 0;
            $bibliosAtivos = 0;

            foreach ($all as $u) {
                if ((int)$u['ativo'] === 1) {
                    if ($u['role'] === 'admin') $adminsAtivos++;
                    if ($u['role'] === 'bibliotecario') $bibliosAtivos++;
                }
            }

            echo json_encode([
                'success' => true,
                'adminsAtivos' => $adminsAtivos,
                'bibliotecariosAtivos' => $bibliosAtivos,
                'totalUsuarios' => count($all)
            ]);
            break;
        }

        case 'list': {
            $items = $userModel->getAll();
            // compatibilidade: front espera { success, usuarios: [...] }
            echo json_encode(['success' => true, 'usuarios' => $items, 'items' => $items]);
            break;
        }

        case 'add': {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) $data = $_POST;

            $nome  = trim($data['nome']  ?? '');
            $email = trim($data['email'] ?? '');
            $senha = (string)($data['senha'] ?? '');
            $role  = trim($data['role']  ?? 'bibliotecario');

            if ($nome === '' || $email === '' || $senha === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Campos obrigatórios: nome, email, senha']);
                break;
            }

            $ok = $userModel->add($nome, $email, $senha, $role);
            if ($ok && $currentUserId) {
                $logger->byUserId($currentUserId, 'user.add', json_encode(['email' => $email, 'role' => $role]));
            }
            echo json_encode(['success' => $ok]);
            break;
        }

        case 'update': {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) $data = $_POST;

            $id    = (int)($data['id'] ?? 0);
            $nome  = trim($data['nome']  ?? '');
            $email = trim($data['email'] ?? '');
            $role  = trim($data['role']  ?? 'bibliotecario');
            $ativo = (int)($data['ativo'] ?? 1);

            if ($id <= 0 || $nome === '' || $email === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Campos obrigatórios: id, nome, email']);
                break;
            }

            $ok = $userModel->update($id, $nome, $email, $role, $ativo);
            if ($ok && $currentUserId) {
                $logger->byUserId($currentUserId, 'user.update', json_encode([
                    'id' => $id, 'email' => $email, 'role' => $role, 'ativo' => $ativo
                ]));
            }
            echo json_encode(['success' => $ok]);
            break;
        }

        case 'delete': {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }

            $toLog = $userModel->getById($id);
            $ok = $userModel->delete($id);
            if ($ok && $currentUserId) {
                $logger->byUserId($currentUserId, 'user.delete', json_encode($toLog ?: ['id' => $id]));
            }
            echo json_encode(['success' => $ok]);
            break;
        }

        default: {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
