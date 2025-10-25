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
  <script>
    $(document).ready(function () {
      $("#auth-form").on("submit", async function (e) {
        e.preventDefault();

        const email = $("#email").val();
        const senha = $("#password").val();

        // desativa o botão enquanto faz o login
        const $btn = $("#submit-button");
        $btn.prop("disabled", true).text("Aguarde...");

        try {
          const res = await fetch("../../app/api/auth.php?action=login", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            credentials: "same-origin",
            body: JSON.stringify({ email, senha })
          });

          const data = await res.json();
          console.log(data);

          if (data.success) {
            // Redirecionamento de acordo com a role
            const destino = data.user.role === "admin"
              ? "./admin.php"
              : "./bibliotecario.php";

            // Transição suave
            $("body").fadeOut(300, function() {
              window.location.href = destino;
            });
          } else {
            alert(data.message);
          }

        } catch (err) {
          console.error("Erro:", err);
          alert("Erro de conexão com o servidor.");
        }

        $btn.prop("disabled", false).text("Entrar");
      });
    });
  </script>

</body>
</html>
