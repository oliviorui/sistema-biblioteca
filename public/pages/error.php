<?php
require_once __DIR__ . '/../../app/lib/Auth.php';
$auth = new Auth();
$user = $auth->check();
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acesso Negado - BiblioControl</title>

  <link rel="stylesheet" href="../assets/css/error.css">
  <link rel="stylesheet" href="../assets/css/bibliotecario.css">
  <link rel="shortcut icon" href="../assets/img/logo-itc.png" type="image/x-icon">
</head>
<body>

  <div class="error-container">
    <div class="error-card">
      <img src="../assets/img/logo-itc.png" class="error-logo" alt="ITC">

      <h1>Acesso Negado</h1>
      <p class="desc">
        <?php if ($user): ?>
          O seu perfil não tem permissão para acessar esta área.
        <?php else: ?>
          Precisa iniciar sessão para continuar.
        <?php endif; ?>
      </p>

      <?php if ($user): ?>
        <a href="./bibliotecario.php" class="btn">Ir para o painel permitido</a>
      <?php else: ?>
        <a href="./login.php" class="btn">Voltar para Login</a>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
