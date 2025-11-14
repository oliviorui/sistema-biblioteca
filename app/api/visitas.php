<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Visit.php';
require_once __DIR__ . '/../lib/Student.php';
require_once __DIR__ . '/../lib/Logger.php';

try {
    $auth = new Auth();
    $user = $auth->check();

    // permitir ao BIBLIOTECÁRIO
    if (!$user || (! $auth->isAdmin() && ! $auth->hasRole('bibliotecario'))) {
        http_response_code(401);
        echo json_encode(['success'=>false, 'message'=>'Acesso não autorizado']);
        exit;
    }

    $action = $_GET['action'] ?? 'recent';
    $visit  = new Visit();
    $conn   = (new Database())->connect();
    $logger = new Logger();

    switch ($action) {

        // ---------- Estatísticas para cards ----------
        // bibliotecario.php espera: presentes, visitantesHoje, totalVisitantes
        case 'stats': {
            $presentes = (int)$conn->query("
                SELECT COUNT(*) FROM visitas WHERE hora_saida IS NULL
            ")->fetchColumn();

            $visitantesHoje = (int)$conn->query("
                SELECT COUNT(*) FROM visitas WHERE data_visita = CURRENT_DATE()
            ")->fetchColumn();

            $totalVisitantes = (int)$conn->query("
                SELECT COUNT(*) FROM estudantes
            ")->fetchColumn();

            echo json_encode([
                'success'          => true,
                'presentes'        => $presentes,
                'visitantesHoje'   => $visitantesHoje,
                'totalVisitantes'  => $totalVisitantes
            ]);
            break;
        }

        // ---------- Lista de visitantes com visita em aberto ----------
        // bibliotecario.php espera: { id, estudante_id, nome, hora_entrada, origem }
        case 'ativos': {
            $stmt = $conn->prepare("
                SELECT v.id, v.estudante_id, v.hora_entrada, v.origem,
                    e.nome,
                    e.codigo AS codigo
                FROM visitas v
                JOIN estudantes e ON e.id = v.estudante_id
                WHERE v.hora_saida IS NULL
                ORDER BY v.hora_entrada DESC
                LIMIT 100
            ");
            $stmt->execute();
            $ativos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success'=>true, 'ativos'=>$ativos]);
            break;
        }

        // ---------- Histórico de visitas concluídas ----------
        // bibliotecario.php espera: { estudante_id, nome, data_entrada, hora_entrada, data_saida, hora_saida, origem }
        case 'historico': {
            $stmt = $conn->prepare("
                SELECT
                    v.estudante_id,
                    e.nome,
                    e.codigo AS codigo,
                    v.data_visita AS data_entrada,
                    TIME(v.hora_entrada) AS hora_entrada,
                    DATE(v.hora_saida)   AS data_saida,
                    TIME(v.hora_saida)   AS hora_saida,
                    v.origem
                FROM visitas v
                JOIN estudantes e ON e.id = v.estudante_id
                WHERE v.hora_saida IS NOT NULL
                ORDER BY v.hora_saida DESC
                LIMIT 50
            ");
            $stmt->execute();
            $visitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success'=>true, 'visitas'=>$visitas]);
            break;
        }

        // ---------- Check-in manual (bibliotecário) ----------
        // Recebe JSON: { estudante_id: <codigo>, nome: <nome>, origem: "bibliotecario" }
        case 'checkin': {
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            $codigo = trim($data['estudante_id'] ?? ''); // no form chama-se "estudante_id" mas é o CÓDIGO
            $nome   = trim($data['nome'] ?? '');
            $origem = 'bibliotecario';

            if ($codigo === '' || $nome === '') {
                http_response_code(400);
                echo json_encode(['success'=>false, 'message'=>'Informe código de estudante e nome.']);
                break;
            }

            $studentLib = new Student();
            // Garante cadastro se não existir
            $estudante = $studentLib->getOrCreate($codigo, $nome);
            if (!$estudante) {
                http_response_code(500);
                echo json_encode(['success'=>false, 'message'=>'Falha ao localizar/criar estudante.']);
                break;
            }

            // Se já tem visita aberta, não duplica — devolve estado informativo
            $open = $visit->getOpenVisitForStudent((int)$estudante['id']);
            if ($open) {
                echo json_encode(['success'=>true, 'message'=>'Visita já estava aberta.', 'visita_id'=>(int)$open['id']]);
                break;
            }

            $visitaId = $visit->registerEntry((int)$estudante['id'], $origem);
            $logger->byUserId((int)$user['id'], 'checkin.manual', json_encode(['estudante_id'=>$estudante['id'], 'codigo'=>$codigo, 'visita'=>$visitaId]));

            echo json_encode(['success'=>true, 'visita_id'=>$visitaId]);
            break;
        }

        // ---------- Checkout manual (bibliotecário) ----------
        // Recebe JSON: { visita_id }
        case 'checkout': {
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $visitaId = (int)($data['visita_id'] ?? 0);

            if ($visitaId <= 0) {
                http_response_code(400);
                echo json_encode(['success'=>false, 'message'=>'visita_id inválido']);
                break;
            }

            $ok = $visit->registerExitByVisitId($visitaId);
            if ($ok) {
                $logger->byUserId((int)$user['id'], 'checkout.manual', json_encode(['visita'=>$visitaId]));
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(400);
                echo json_encode(['success'=>false, 'message'=>'Não foi possível concluir o check-out (já encerrada?).']);
            }
            break;
        }

        // ---------- Recentes / Agregados (mantidos p/ admin) ----------
        case 'recent': {
            $limit = max(1, (int)($_GET['limit'] ?? 20));
            echo json_encode(['success'=>true, 'items'=>$visit->listRecent($limit)]);
            break;
        }

        case 'aggregates': {
            $from = $_GET['from'] ?? null; // YYYY-MM-DD
            $to   = $_GET['to']   ?? null; // YYYY-MM-DD
            echo json_encode(['success'=>true, 'data'=>$visit->aggregates($from, $to)]);
            break;
        }

        default: {
            http_response_code(400);
            echo json_encode(['success'=>false, 'message'=>'Ação inválida']);
            break;
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
