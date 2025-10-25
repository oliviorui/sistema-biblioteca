<?php
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->connect();

// Cria senha hash
$adminSenha = password_hash('admin123', PASSWORD_DEFAULT);
$bibliotecarioSenha = password_hash('biblio123', PASSWORD_DEFAULT);

// Inserir admin
$conn->exec("INSERT IGNORE INTO usuarios (nome,email,senha_hash,role) VALUES 
    ('Administrador Geral', 'admin@itc.ac.mz', '$adminSenha', 'admin'),
    ('Bibliotecário', 'biblio@itc.ac.mz', '$bibliotecarioSenha', 'bibliotecario'),
    ('Olívio Cumbe', 'ocumbe@itc.ac.mz', '$adminSenha', 'admin'),
    ('Andrea da Clara', 'atembe@itc.ac.mz', '$bibliotecarioSenha', 'bibliotecario')"
    );

echo "Usuários criados com sucesso!";
