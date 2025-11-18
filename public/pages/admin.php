<?php
require_once __DIR__ . '/../../app/lib/Auth.php';
$auth = new Auth();
if (!$auth->check()) { header('Location: ./login.php'); exit; }
if (!$auth->isAdmin()) { header('Location: ./erro.php'); exit; }
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BiblioControl - Painel Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="shortcut icon" href="../assets/img/logo-itc.png" type="image/x-icon">
</head>
<body>

  <!-- Header -->
  <header class="topbar">
    <div class="container">
      <div class="logo">
        <div class="logo-img">
          <img src="../assets/img/logo-itc.png" alt="Logo do ITC">
        </div>
        <div class="logo-text">
          <h1>BiblioControl Admin</h1>
          <p>Gestão completa do sistema</p>
        </div>
      </div>
      <button class="logout-button" aria-label="Sair do sistema">
        <img src="../assets/img/icons/log-out.svg" alt="Sair" class="icon"> Sair
      </button>
    </div>
  </header>

  <main class="container">

    <!-- Estatísticas Admin -->
    <section class="stats-cards">
      <div class="card success">
        <div class="info">
          <h3>Total de Visitantes</h3>
          <p class="value">—</p>
          <p class="description">Visitantes cadastrados</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/people.svg" alt="Visitantes" class="icon">
        </div>
      </div>
      <div class="card primary">
        <div class="info">
          <h3>Visitantes Hoje</h3>
          <p class="value">—</p>
          <p class="description">Check-ins realizados hoje</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/time.svg" alt="Hoje" class="icon">
        </div>
      </div>
      <div class="card default">
        <div class="info">
          <h3>Admins Ativos</h3>
          <p class="value">—</p>
          <p class="description">Usuários com permissão de admin</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/shield-checkmark.svg" alt="Admins" class="icon">
        </div>
      </div>
    </section>

    <!-- Painéis -->
    <section class="main-content">

      <!-- Gerenciar Usuários -->
      <div class="admin-panel">
        <h2>
          <div class="icon">
            <img src="../assets/img/icons/settings.svg" alt="Gerenciar Usuários" class="icon">
          </div>
          Gerenciar Usuários
        </h2>
        <p>Adicionar, editar ou remover administradores e bibliotecários</p>
        <div class="user-list">
          <!-- Exemplo de usuário -->
          <div class="user-item">
            <p class="user-name">Maria Silva <span class="badge">Bibliotecária</span></p>
            <div class="actions">
              <button class="btn-edit">Editar</button>
              <button class="btn-delete">Remover</button>
            </div>
          </div>
        </div>
        <button class="btn-add">Adicionar Usuário</button>
      </div>

     <!-- Modal de cadastro/edição de usuário -->
     <div id="user-modal" class="modal hidden" aria-hidden="true">
      <div class="modal-content">
        <h2 id="user-modal-title">Novo usuário</h2>

        <form id="user-form">
          <input type="hidden" id="user-id">

          <label for="user-nome">Nome completo</label>
          <input type="text" id="user-nome" required>

          <label for="user-email">Email</label>
          <input type="email" id="user-email" required>

          <div class="form-row">
            <div class="field">
              <label for="user-role">Perfil</label>
              <select id="user-role">
                <option value="bibliotecario">Bibliotecário</option>
                <option value="admin">Administrador</option>
              </select>
            </div>

            <div class="field field-checkbox">
              <label>
                <input type="checkbox" id="user-ativo" checked>
                Usuário ativo
              </label>
            </div>
          </div>

          <div id="user-senha-group">
            <label for="user-senha">Senha</label>
            <input type="password" id="user-senha" minlength="4" placeholder="Defina uma senha">
          </div>

          <div class="modal-actions">
            <button type="button" id="user-cancel" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>

      <!-- Logs de ações -->
      <div class="admin-panel">
        <h2>
          <div class="icon">
            <img src="../assets/img/icons/library.svg" alt="Logs" class="icon">
          </div>
          Logs de Ação
        </h2>
        <p>Histórico de operações realizadas no sistema</p>
        <div class="log-list">
          <div class="log-item">
            <p>Maria Silva cadastrou novo visitante às 10:15</p>
          </div>
        </div>
      </div>
    </section>
  </main>
  <script src="../assets/js/lib/jquery.js"></script>
  <script src="../assets/js/admin.js"></script>
</body>
</html>
