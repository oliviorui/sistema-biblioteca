/* ==== Extracted from index.php ==== */

/* Block #1 */
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
