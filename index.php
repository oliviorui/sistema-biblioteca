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

<script>
  $(function() {
    const $form  = $('#kiosk-form');
    const $code  = $('#student-code');
    const $name  = $('#student-name');
    const $toast = $('#toast');

    function fmtHora(dstr) {
      const d = dstr ? new Date(dstr) : new Date();
      return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function showToast(html, type = 'info', ttl = 4000) {
      const $t = $(`
        <div class="t ${type}">
          <span class="icon">${
            type === 'success' ? '✅' : type === 'error' ? '⚠️' : 'ℹ️'
          }</span>
          <span class="msg">${html}</span>
          <span class="time">${fmtHora()}</span>
        </div>
      `);

      $toast.append($t);
      const timer = setTimeout(close, ttl);

      function close() {
        $t.css('animation', 'toastOut .2s ease forwards');
        setTimeout(() => $t.remove(), 200);
        clearTimeout(timer);
      }

      $t.on('click', close);
    }

    function submitEntradaSaida() {
      const payload = {
        codigo: $code.val().trim().toUpperCase(),
        nome: $name.val().trim()
      };

      if (!payload.codigo || !payload.nome) {
        showToast('Informe código e nome do estudante.', 'error');
        return;
      }

      $.ajax({
        url: 'app/api/kiosk.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        dataType: 'json',
        success: function(data) {
          if (!data.success) {
            showToast(data.message || 'Falha ao registar. Tente novamente.', 'error');
            return;
          }

          const hora = fmtHora(data.when);
          const nome = $('<div>').text(payload.nome).html();   // sanitize
          const codigo = $('<div>').text(payload.codigo).html();

          if (data.mode === 'entrada') {
            showToast(`Entrada às <b>${hora}</b> — ${nome} (${codigo})`, 'success');
          } else if (data.mode === 'saida') {
            showToast(`Saída às <b>${hora}</b> — ${nome} (${codigo})`, 'success');
          } else {
            showToast('Operação concluída.', 'info');
          }

          $code.val('').focus();
          $name.val('');
        },
        error: function() {
          showToast('Erro de rede. Verifique a ligação.', 'error');
        }
      });
    }

    // Eventos
    $form.on('submit', function(e) {
      e.preventDefault();
      submitEntradaSaida();
    });

    $code.on('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitEntradaSaida();
      }
    });
  });
</script>

</body>
</html>
