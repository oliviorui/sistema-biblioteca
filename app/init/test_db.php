<?php
require '../config/database.php';

$db = new Database();
$conn = $db->connect();

if ($conn) {
    echo "✅ Conexão bem-sucedida!";
}
