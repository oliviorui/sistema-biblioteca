/* ============================================================
   KIOSK – Registo de Entrada/Saída + Cadastro Rápido
   Código refatorado, limpo e organizado para máxima clareza.
   ============================================================ */

$(function () {

  /* ------------------------------------------------------------
     ELEMENTOS
  ------------------------------------------------------------ */

  const $form  = $('#kiosk-form');
  const $code  = $('#student-code');
  const $name  = $('#student-name');
  const $toast = $('#toast');

  const $modal    = $('#quick-register-modal');
  const $qrForm   = $('#quick-register-form');
  const $qrCode   = $('#qr-code');
  const $qrNome   = $('#qr-nome');
  const $qrCurso  = $('#qr-curso');
  const $qrTurma  = $('#qr-turma');
  const $qrCancel = $('#qr-cancel');

  let ultimoCodigoChecado = null;
  let ultimoEstudante     = null;

  /* ------------------------------------------------------------
     UTILITÁRIOS
  ------------------------------------------------------------ */

  function fmtHora(dstr) {
    const d = dstr ? new Date(dstr) : new Date();
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function showToast(msg, type = 'info', ttl = 4000) {
    const icon =
      type === 'success' ? '✅' :
      type === 'error'   ? '⚠️' :
                           'ℹ️';

    const $t = $(`
      <div class="t ${type}">
        <span class="icon">${icon}</span>
        <span class="msg">${msg}</span>
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

  /* ------------------------------------------------------------
     MODAL – Cadastro rápido de estudante
  ------------------------------------------------------------ */

  function abrirModalCadastroRapido(codigo, nome) {
    if (!$modal.length) {
      showToast('Cadastro rápido indisponível.', 'error');
      return;
    }

    $qrCode.val(codigo || '');
    $qrNome.val(nome || '');
    $qrCurso.val('');
    $qrTurma.val('');

    $modal.removeClass('hidden').attr('aria-hidden', 'false');
  }

  function fecharModalCadastroRapido() {
    if ($modal.length) {
      $modal.addClass('hidden').attr('aria-hidden', 'true');
    }
  }

  /* ------------------------------------------------------------
     VERIFICAR CÓDIGO DO ESTUDANTE
  ------------------------------------------------------------ */

  function verificarCodigo(callback) {
    const codigo       = $code.val().trim().toUpperCase();
    const nomeDigitado = $name.val().trim();

    if (!codigo) {
      showToast('Informe o código do estudante.', 'error');
      return;
    }

    $.ajax({
      url: 'app/api/kiosk.php?action=check_codigo',
      method: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({ codigo }),

      success: function (resp) {
        if (!resp.success) {
          showToast(resp.message || 'Falha ao verificar código.', 'error');
          return;
        }

        ultimoCodigoChecado = codigo;

        if (resp.exists && resp.student) {
          ultimoEstudante = resp.student;

          const nomeBanco = resp.student.nome || '';

          // Sincroniza nome digitado com o DB
          if (!nomeDigitado || nomeDigitado.toLowerCase() !== nomeBanco.toLowerCase()) {
            $name.val(nomeBanco);
            showToast('Estudante encontrado: ' + nomeBanco, 'info');
          }

          if (typeof callback === 'function') callback('exists', resp.student);
        } 
        else {
          ultimoEstudante = null;
          if (typeof callback === 'function') callback('missing', null);
        }
      },

      error: function () {
        showToast('Erro de rede ao verificar código.', 'error');
      }
    });
  }

  // Verifica automaticamente ao sair do campo
  $code.on('blur', function () {
    if ($code.val().trim() !== '') {
      verificarCodigo();
    }
  });

  /* ------------------------------------------------------------
     REGISTRAR ENTRADA / SAÍDA
  ------------------------------------------------------------ */

  function submitEntradaSaida() {
    const codigo = $code.val().trim().toUpperCase();
    const nome   = $name.val().trim();

    if (!codigo || !nome) {
      showToast('Informe código e nome.', 'error');
      return;
    }

    const payload = { codigo, nome };

    $.ajax({
      url: 'app/api/kiosk.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      dataType: 'json',

      success: function (data) {
        if (!data || !data.success) {
          showToast(data?.message || 'Falha ao registar.', 'error');
          return;
        }

        const horaFmt  = fmtHora(data.when);
        const nomeEsc  = $('<div>').text(nome).html();
        const codigoEsc = $('<div>').text(codigo).html();

        if (data.mode === 'entrada') {
          showToast(`Entrada às <b>${horaFmt}</b> — ${nomeEsc} (${codigoEsc})`, 'success');
        } 
        else if (data.mode === 'saida') {
          showToast(`Saída às <b>${horaFmt}</b> — ${nomeEsc} (${codigoEsc})`, 'success');
        } 
        else {
          showToast('Operação concluída.', 'info');
        }

        // Reset
        $code.val('').focus();
        $name.val('');
      },

      error: function () {
        showToast('Erro de rede.', 'error');
      }
    });
  }

  /* ------------------------------------------------------------
     SUBMIT DO FORMULÁRIO PRINCIPAL
  ------------------------------------------------------------ */

  $form.on('submit', function (e) {
    e.preventDefault();

    verificarCodigo(function (status) {
      const codigo = $code.val().trim().toUpperCase();
      const nome   = $name.val().trim();

      if (status === 'exists') {
        submitEntradaSaida();
      } else {
        abrirModalCadastroRapido(codigo, nome);
      }
    });
  });

  /* ------------------------------------------------------------
     SUBMIT DO CADASTRO RÁPIDO
  ------------------------------------------------------------ */

  if ($qrForm.length) {
    $qrForm.on('submit', function (e) {
      e.preventDefault();

      const payload = {
        codigo: $qrCode.val().trim().toUpperCase(),
        nome:   $qrNome.val().trim(),
        curso:  $qrCurso.val().trim(),
        turma:  $qrTurma.val().trim()
      };

      if (!payload.codigo || !payload.nome) {
        showToast('Código e nome são obrigatórios.', 'error');
        return;
      }

      $.ajax({
        url: 'app/api/estudantes.php',
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(payload),

        success: function (resp) {
          if (!resp?.success) {
            showToast(resp?.message || 'Falha ao cadastrar.', 'error');
            return;
          }

          fecharModalCadastroRapido();
          showToast('Estudante cadastrado! Registrando entrada...', 'success');

          $code.val(payload.codigo);
          $name.val(payload.nome);

          submitEntradaSaida();
        },

        error: function () {
          showToast('Erro ao cadastrar estudante.', 'error');
        }
      });
    });
  }

  /* ------------------------------------------------------------
     BOTÃO CANCELAR DO MODAL
  ------------------------------------------------------------ */

  if ($qrCancel.length) {
    $qrCancel.on('click', fecharModalCadastroRapido);
  }

  /* ------------------------------------------------------------
     TECLA ESC FECHA MODAL
  ------------------------------------------------------------ */

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') fecharModalCadastroRapido();
  });

  /* ------------------------------------------------------------
     Enter no campo código = enviar
  ------------------------------------------------------------ */

  $code.on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      $form.trigger('submit');
    }
  });

});
