<!DOCTYPE html>
<html lang="pt-pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BiblioControl - Kiosk</title>
  <link rel="stylesheet" href="public/assets/css/kiosk.css">
  <link rel="shortcut icon" href="public/assets/img/logo-itc.png" type="image/x-icon">
  <script src="public/assets/js/lib/jquery.js"></script>
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
        <form  id="kiosk-form">
          <label for="student-code">Código do Estudante</label>
          <input type="text" id="student-code" placeholder="Digite o código" autofocus required>

          <label for="student-name">Nome Completo</label>
          <input type="text" id="student-name" placeholder="Digite o nome completo" required>

          <button type="submit">Registrar Entrada/Saída</button>
        </form>
        <!-- Container dos toasts (topo-direito) -->
        <div id="toast" class="toast" aria-live="polite" aria-atomic="true"></div>

      </div>        
        <p>Bibliotecário/Admin? <a href="public/pages/login.php">Clique abaixo</a></p>
    </main>
  </div>
<p id="kiosk-message" class="kiosk-message" aria-live="polite"></p>



  <script src="public/assets/js/kiosk.js"></script>
</body>
</html>
