<?php
require_once __DIR__ . '/../../app/lib/Auth.php';

$auth = new Auth();
$user = $auth->check();

// Se já está logado, redireciona conforme o papel
if ($user) {
  if ($auth->isAdmin()) {
    header('Location: admin.php');
    exit;
  }

  if ($auth->hasRole('bibliotecario')) {
    header('Location: bibliotecario.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BiblioControl - Login</title>

  <link rel="stylesheet" href="../assets/css/login.css">
  <link rel="shortcut icon" href="../assets/img/logo-itc.png" type="image/x-icon">
</head>
<body>

  <div class="auth-container">
    <div class="card">

      <div class="card-header">
        <img src="../assets/img/logo-itc.png" alt="Logo ITC">
        <h1 class="card-title">Acesso ao Sistema</h1>
        <p class="card-description">Bibliotecário / Administrador</p>
      </div>

      <div class="card-content">
        <form id="login-form">
          <div class="input-group">
            <label for="email">Email</label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="exemplo@itc.ac.mz"
              autocomplete="username"
              required
              autofocus
            >
          </div>

          <div class="input-group">
            <label for="senha">Senha</label>
            <input
              type="password"
              id="senha"
              name="senha"
              placeholder="********"
              autocomplete="current-password"
              required
            >
          </div>

          <button type="submit" class="btn">Entrar</button>
        </form>

        <p id="login-message" class="login-message"></p>

        <!-- Botão / link para voltar ao Kiosk -->
        <div class="toggle-auth">
          <a href="../../index.php" class="back-link">← Voltar ao Kiosk</a>
        </div>
      </div>

    </div>
  </div>

  <script src="../assets/js/lib/jquery.js"></script>
  <script src="../assets/js/login.js"></script>
</body>
</html>
