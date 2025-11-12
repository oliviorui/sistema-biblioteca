<!DOCTYPE html>
<html lang="pt-pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login / Criar Conta</title>
  <link rel="stylesheet" href="../assets/css/login.css">
  <link rel="shortcut icon" href="../assets/img/logo-itc.png" type="image/x-icon">
</head>
<body>
  <div class="auth-container">
    <div class="card">
      <div class="card-header">
        <div class="logo-container">
            <img src="../assets/img/logo-itc.png" alt="Logo do ITC">
        </div>
        <h1 class="card-title" id="card-title">Bem-vindo</h1>
        <p class="card-description" id="card-description">Entre com suas credenciais para acessar o sistema</p>
      </div>
      <div class="card-content">
        <form id="auth-form" action="" method="POST">
          <div class="input-group">
            <label for="email">Email</label>
            <input id="email" type="email" placeholder="exemplo@itc.ac.mz" required autofocus/>
          </div>
          <div class="input-group">
            <label for="password">Senha</label>
            <input id="password" type="password" placeholder="••••••••" required minlength="6" />
          </div>
          <button type="submit" class="btn" id="submit-button">Entrar</button>
        </form>
        <div class="toggle-auth">
          <button id="toggle-button">Não tem uma conta? <a href="mailto:admin@transcom.co.mz">Contacte o admin</a></button>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/lib/jquery.js"></script>
  

  <script src="../assets/js/login.js"></script>
</body>
</html>
