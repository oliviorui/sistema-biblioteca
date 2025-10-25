<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../lib/Auth.php';

try {
    $auth = new Auth();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'POST' && $action === 'login') {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if (!isset($data['email'], $data['senha'])) {
            http_response_code(400);
            echo json_encode(['success'=>false, 'message'=>'Dados inválidos']);
            exit;
        }
        echo json_encode($auth->login($data['email'], $data['senha']));
        exit;
    }

    if ($action === 'logout') {
        echo json_encode($auth->logout());
        exit;
    }

    echo json_encode(['success'=>false, 'message'=>'Ação inválida']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
