<!DOCTYPE html>
<html lang="pt-pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BiblioControl - Kiosk</title>
  <link rel="stylesheet" href="public/assets/css/kiosk.css">
  <link rel="shortcut icon" href="public/assets/img/logo-itc.png" type="image/x-icon"> 
</head>
<body>
  <div class="kiosk-container">
    <header class="kiosk-header">
      <img src="public/assets/img/logo-itc.png" alt="Logo ITC" class="logo">
      <h1>BiblioControl Kiosk</h1>
      <p>Registe a sua entrada rapidamente</p>
    </header>

    <main class="kiosk-main">
      <!-- Formulário de check-in -->
      <div class="checkin-card">
        <h2>Preencha os dados abaixo</h2>
        <form id="kiosk-form">
          <label for="student-code">Código do Estudante</label>
          <input
            type="text"
            id="student-code"
            placeholder="Digite o código"
            autofocus
            required
          >

          <label for="student-name">Nome Completo</label>
          <input
            type="text"
            id="student-name"
            placeholder="Digite o nome completo"
            required
          >

          <button type="submit">Registrar Entrada/Saída</button>
        </form>

        <!-- Container dos toasts (topo-direito) -->
        <div id="toast" class="toast" aria-live="polite" aria-atomic="true"></div>
      </div>

      <p class="kiosk-footer-info">
        Bibliotecário/Admin? <a href="public/pages/login.php">Clique Aqui</a>
      </p>
    </main>
  </div>

  <p id="kiosk-message" class="kiosk-message" aria-live="polite"></p>

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

  <script src="public/assets/js/lib/jquery.js"></script>
  <script src="public/assets/js/kiosk.js"></script>
</body>
</html>
