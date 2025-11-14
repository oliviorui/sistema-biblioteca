/* Kiosk com verificação de código + cadastro rápido */

$(function () {
  const $form  = $('#kiosk-form');
  const $code  = $('#student-code');
  const $name  = $('#student-name');
  const $toast = $('#toast');

  // Elementos do modal de cadastro rápido
  const $modal    = $('#quick-register-modal');
  const $qrForm   = $('#quick-register-form');
  const $qrCode   = $('#qr-code');
  const $qrNome   = $('#qr-nome');
  const $qrCurso  = $('#qr-curso');
  const $qrTurma  = $('#qr-turma');
  const $qrCancel = $('#qr-cancel');

  let ultimoCodigoChecado = null;
  let ultimoEstudante     = null;

  // ------------------------------------------------------------
  // Funções utilitárias
  // ------------------------------------------------------------

  function fmtHora(dstr) {
    const d = dstr ? new Date(dstr) : new Date();
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  // === MESMA LÓGICA ANTIGA DE TOAST ===
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

  // ------------------------------------------------------------
  // Modal de cadastro rápido
  // ------------------------------------------------------------

  function abrirModalCadastroRapido(codigo, nome) {
    if (!$modal.length) {
      showToast('Cadastro rápido indisponível. Contacte o administrador.', 'error');
      return;
    }

    $qrCode.val(codigo || '');
    $qrNome.val(nome || '');
    $qrCurso.val('');
    $qrTurma.val('');

    $modal.removeClass('hidden').attr('aria-hidden', 'false');
  }

  function fecharModalCadastroRapido() {
    if (!$modal.length) return;
    $modal.addClass('hidden').attr('aria-hidden', 'true');
  }

  // ------------------------------------------------------------
  // Verificação de código (antes de registrar entrada/saída)
  // ------------------------------------------------------------

  /**
   * Verifica se o código já existe na base.
   * callback(status, student)
   *   status: 'exists' | 'missing'
   *   student: dados do estudante (se existir)
   */
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

          // Se o nome digitado estiver vazio ou diferente, sincroniza com o banco
          if (
            !nomeDigitado ||
            nomeDigitado.toLowerCase() !== (resp.student.nome || '').toLowerCase()
          ) {
            $name.val(resp.student.nome || '');
            showToast('Estudante encontrado: ' + (resp.student.nome || ''), 'info');
          }

          if (typeof callback === 'function') {
            callback('exists', resp.student);
          }
        } else {
          ultimoEstudante = null;

          if (typeof callback === 'function') {
            callback('missing', null);
          }
        }
      },
      error: function () {
        showToast('Erro de rede ao verificar código.', 'error');
      }
    });
  }

  // Verificação automática ao sair do campo código
  $code.on('blur', function () {
    const valor = $code.val().trim();
    if (valor !== '') {
      verificarCodigo(); // sem callback, só feedback
    }
  });

  // ------------------------------------------------------------
  // Fluxo padrão: registrar entrada / saída
  // ------------------------------------------------------------

  function submitEntradaSaida() {
    const payload = {
      codigo: $code.val().trim().toUpperCase(),
      nome:   $name.val().trim()
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
      success: function (data) {
        if (!data || !data.success) {
          showToast(
            (data && data.message) || 'Falha ao registar. Tente novamente.',
            'error'
          );
          return;
        }

        const hora   = data.when ? fmtHora(data.when) : fmtHora();
        const nome   = $('<div>').text(payload.nome).html();
        const codigo = $('<div>').text(payload.codigo).html();

        if (data.mode === 'entrada') {
          showToast(`Entrada às <b>${hora}</b> — ${nome} (${codigo})`, 'success');
        } else if (data.mode === 'saida') {
          showToast(`Saída às <b>${hora}</b> — ${nome} (${codigo})`, 'success');
        } else {
          showToast('Operação concluída.', 'info');
        }

        // Limpa campos para o próximo registo
        $code.val('').focus();
        $name.val('');
      },
      error: function () {
        showToast('Erro de rede. Verifique a ligação.', 'error');
      }
    });
  }

  // ------------------------------------------------------------
  // Submit do formulário principal do kiosk
  // ------------------------------------------------------------

  $form.on('submit', function (e) {
    e.preventDefault();

    verificarCodigo(function (status /*, student */) {
      const codigo = $code.val().trim().toUpperCase();
      const nome   = $name.val().trim();

      if (status === 'exists') {
        // Estudante já cadastrado → segue fluxo normal
        submitEntradaSaida();
      } else {
        // Código não existe → abre modal de cadastro rápido
        abrirModalCadastroRapido(codigo, nome);
      }
    });
  });

  // ------------------------------------------------------------
  // Submit do modal de cadastro rápido
  // ------------------------------------------------------------

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
          if (!resp || !resp.success) {
            showToast(
              (resp && resp.message) || 'Falha ao cadastrar estudante.',
              'error'
            );
            return;
          }

          fecharModalCadastroRapido();
          showToast('Estudante cadastrado com sucesso! Registrando entrada...', 'success');

          // Atualiza o formulário principal e registra entrada
          $code.val(payload.codigo);
          $name.val(payload.nome);
          submitEntradaSaida();
        },
        error: function () {
          showToast('Erro de rede ao cadastrar estudante.', 'error');
        }
      });
    });
  }

  // Botão cancelar do modal
  if ($qrCancel.length) {
    $qrCancel.on('click', function () {
      fecharModalCadastroRapido();
    });
  }

  // ESC fecha modal
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      fecharModalCadastroRapido();
    }
  });

  // Atalho: Enter no campo código envia o formulário
  $code.on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      $form.trigger('submit');
    }
  });
});
