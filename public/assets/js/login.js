$(document).ready(function () {

  const $form   = $("#login-form");
  const $email  = $("#email");
  const $senha  = $("#senha");
  const $msg    = $("#login-message");
  const $button = $form.find("button[type=submit]");

  function setLoading(isLoading) {
    if (isLoading) {
      $button.prop("disabled", true).text("Aguarde...");
    } else {
      $button.prop("disabled", false).text("Entrar");
    }
  }

  function showMessage(text, type = "error") {
    if (!$msg.length) {
      alert(text);
      return;
    }
    $msg
      .text(text)
      .removeClass("ok error")
      .addClass(type === "ok" ? "ok" : "error");
  }

  $form.on("submit", async function (e) {
    e.preventDefault();

    const email = $email.val().trim();
    const senha = $senha.val().trim();

    if (!email || !senha) {
      showMessage("Informe email e senha.");
      return;
    }

    setLoading(true);
    showMessage("");

    try {
      const res = await fetch("../../app/api/auth.php?action=login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ email, senha })
      });

      const data = await res.json();
      console.log("Login response:", data);

      if (data.success) {
        // decide destino pela role
        const role    = data.user && data.user.role;
        let destino   = "./bibliotecario.php";

        if (role === "admin") {
          destino = "./admin.php";
        }

        showMessage("Login bem-sucedido. Redirecionando...", "ok");

        $("body").fadeOut(300, function () {
          window.location.href = destino;
        });
      } else {
        showMessage(data.message || "Credenciais inválidas.");
      }

    } catch (err) {
      console.error("Erro:", err);
      showMessage("Erro de conexão com o servidor.");
    }

    setLoading(false);
  });
});
