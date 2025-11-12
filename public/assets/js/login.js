/* ==== Extracted from public/pages/login.php ==== */

/* Block #1 */
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
