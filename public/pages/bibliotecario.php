<?php
require_once __DIR__ . '/../../app/lib/Auth.php';

$auth = new Auth();
$user = $auth->check();

if (!$user) {
  header('Location: ./login.php');
  exit;
}

if (!$auth->hasRole('bibliotecario')) {
  header('Location: ./error.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BiblioControl - Painel do Bibliotecário</title>
  <link rel="stylesheet" href="../assets/css/bibliotecario.css">
  <link rel="shortcut icon" href="../assets/img/logo-itc.png" type="image/x-icon">
</head>
<body>
  <header class="topbar">
    <div class="container">
      <div class="logo">
        <div class="logo-img">
          <img src="../assets/img/logo-itc.png" alt="Logo do ITC">
        </div>
        <div class="logo-text">
          <h1>BiblioControl</h1>
          <p>Sistema de Gestão de Visitantes</p>
        </div>
      </div>

      <button class="logout-button" aria-label="Sair do sistema">
        <img src="../assets/img/icons/log-out.svg" alt="Sair" class="icon">
        Sair
      </button>
    </div>
  </header>

  <main class="container">

    <!-- Estatísticas -->
    <section class="stats-cards">

      <!-- Card Visitantes Presentes -->
      <div class="card success">
        <div class="info">
          <h3>Visitantes Presentes</h3>
          <p class="value">—</p>
          <p class="description">No momento na biblioteca</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/people.svg" alt="Visitantes Presentes" class="icon">
        </div>
      </div>

      <!-- Card Visitas Hoje -->
      <div class="card primary">
        <div class="info">
          <h3>Visitas Hoje</h3>
          <p class="value">—</p>
          <p class="description">Total de check-ins hoje</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/time.svg" alt="Visitas Hoje" class="icon">
        </div>
      </div>

      <!-- Card Total de Visitantes -->
      <div class="card default">
        <div class="info">
          <h3>Total de Visitantes</h3>
          <p class="value">—</p>
          <p class="description">Cadastrados no sistema</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/trending-up.svg" alt="Total de Visitantes" class="icon">
        </div>
      </div>
    </section>

    <!-- Formulário e Visitantes Ativos -->
    <section class="main-content">

      <!-- Formulário de Check-in -->
      <div class="visitor-form">
        <h2>
          <div class="icon">
            <img src="../assets/img/icons/person-add.svg" alt="Novo Check-in" class="icon">
          </div>
          Novo Check-in
        </h2>
        <p>Registre a entrada de um visitante na biblioteca</p>

        <form>
          <label for="student-id">Código de Estudante</label>
          <input
            id="student-id"
            type="text"
            placeholder="Digite código de estudante"
            required
          >

          <label for="full-name">Nome Completo</label>
          <input
            id="full-name"
            type="text"
            placeholder="Digite o nome"
            required
          >

          <div class="actions">
            <button type="submit">Realizar Check-in</button>
          </div>
        </form>
      </div>

      <!-- Visitantes Ativos -->
      <div class="active-visitors">
        <h2>
          <div class="icon">
            <img src="../assets/img/icons/person.svg" alt="Visitantes Presentes" class="icon">
          </div>
          Visitantes Presentes
        </h2>
        <p id="presentes-count">1 pessoa na biblioteca</p>

        <div class="visitor-list" id="ativos-list">
          <div class="visitor-item">
            <div class="visitor-meta">
              <p class="name">
                Olívio Rui Cumbe
                <span class="badge">24281</span>
              </p>
              <span class="time">Entrada registada às 7:38</span>
              <span class="reason">Registada em: Kiosk</span>
            </div>
            <button class="btn-checkout">
              <img src="../assets/img/icons/log-out.svg" alt="Sair" class="icon">
              Check-out
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Histórico de Visitas -->
    <section class="visit-history">
      <h2>
        <div class="icon">
          <img src="../assets/img/icons/timer.svg" alt="Histórico Recente" class="icon">
        </div>
        Histórico Recente
      </h2>
      <p>Últimas visitas concluídas</p>

      <div class="visitor-list" id="historico-list">
        <div class="visitor-item">
          <div class="visitor-meta">
            <p class="name">
              Olívio Rui Cumbe
              <span class="badge">24281</span>
            </p>
            <span class="time">Entrada: 13/10/2025 09:36</span>
            <span class="time">Saída: 13/10/2025 09:57</span>
            <span class="reason">Registada em: Kiosk</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Modal de cadastro rápido de estudante -->
    <div id="quick-register-modal" class="modal hidden" aria-hidden="true">
      <div class="modal-content">
        <h2>Cadastro rápido de estudante</h2>
        <p>Esse código ainda não existe. Preencha os dados para criar o cadastro.</p>

        <form id="quick-register-form">
          <label for="qr-code">Código</label>
          <input id="qr-code" type="text" readonly>

          <label for="qr-nome">Nome Completo</label>
          <input id="qr-nome" type="text" required>

          <div class="modal-grid">
            <div>
              <label for="qr-curso">Curso</label>
              <input id="qr-curso" type="text" placeholder="Opcional">
            </div>
            <div>
              <label for="qr-turma">Turma</label>
              <input id="qr-turma" type="text" placeholder="Opcional">
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" id="qr-cancel" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary">Salvar e registrar entrada</button>
          </div>
        </form>
      </div>
    </div>

  </main>

  <script src="../assets/js/lib/jquery.js"></script>
  <script src="../assets/js/bibliotecario.js"></script>
</body>
</html>
