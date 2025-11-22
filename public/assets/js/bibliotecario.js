/* ============================================================
   BIBLIOTECARIO.JS – Painel do Bibliotecário
   Refatorado totalmente: limpo, modular, coerente.
============================================================ */

$(function () {

  /* ============================================================
     SHORTCUTS
  ============================================================ */

  const API_VISITAS = "../../app/api/visitas.php";
  const API_AUTH    = "../../app/api/auth.php";
  const API_KIOSK   = "../../app/api/kiosk.php";
  const API_ESTUD   = "../../app/api/estudantes.php";

  const $presentesCount = $("#presentes-count");
  const $ativosList     = $("#ativos-list");
  const $historicoList  = $("#historico-list");
  const $formCheckin    = $(".visitor-form form");

  /* Modal */
  const $modal       = $("#quick-register-modal");
  const $qrForm      = $("#quick-register-form");
  const $qrCodigo    = $("#qr-code");
  const $qrNome      = $("#qr-nome");
  const $qrCurso     = $("#qr-curso");
  const $qrTurma     = $("#qr-turma");
  const $qrCancel    = $("#qr-cancel");

  /* ============================================================
     LOGOUT
  ============================================================ */

  $(".logout-button").on("click", function () {
    if (!confirm("Deseja realmente sair?")) return;

    $.getJSON(API_AUTH + "?action=logout", function (resp) {
      if (resp.success) {
        alert("Sessão encerrada.");
        window.location.href = "./login.php";
      } else {
        alert("Erro ao sair: " + (resp.message || "Tente novamente"));
      }
    }).fail(() => alert("Erro ao tentar sair!"));
  });

  /* ============================================================
     1) ESTATÍSTICAS
  ============================================================ */

  function atualizarStats() {
    $.getJSON(API_VISITAS + "?action=stats", function (data) {

      if (!data.success) {
        console.error("Erro:", data.message);
        return;
      }

      $(".card.success .value").text(data.presentes || 0);
      $(".card.primary .value").text(data.visitantesHoje || 0);
      $(".card.default .value").text(data.totalVisitantes || 0);

      const n = data.presentes || 0;
      $presentesCount.text(n === 1 ? "1 pessoa na biblioteca" : `${n} pessoas na biblioteca`);
    });
  }

  /* ============================================================
     2) LISTAR VISITANTES ATIVOS
  ============================================================ */

  function carregarAtivos() {
    $.getJSON(API_VISITAS + "?action=ativos", function (resp) {
      if (!resp.success) return;

      $ativosList.empty();

      resp.ativos.forEach(v => {
        const hora = v.hora_entrada
          ? new Date(v.hora_entrada).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
          : "--:--";

        $ativosList.append(`
          <div class="visitor-item" data-visita-id="${v.id}">
            <div class="visitor-meta">
              <p class="name">${v.nome} <span class="badge">${v.codigo}</span></p>
              <span class="time">Entrada registada às ${hora}</span>
              <span class="reason">Registada em: ${v.origem}</span>
            </div>
            <button class="btn-checkout">
              <img src="../assets/img/icons/log-out.svg" alt="Sair" class="icon">Check-out
            </button>
          </div>
        `);
      });

      const total = resp.ativos.length;
      $presentesCount.text(total === 1 ? "1 pessoa na biblioteca" : `${total} pessoas na biblioteca`);
    });
  }

  /* CHECK-OUT */
  $(document).on("click", ".btn-checkout", function () {
    const visitaId = $(this).closest(".visitor-item").data("visita-id");

    if (!confirm("Confirmar check-out?")) return;

    $.ajax({
      url: API_VISITAS + "?action=checkout",
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify({ visita_id: visitaId }),

      success: function (resp) {
        if (resp.success) {
          carregarAtivos();
          carregarHistorico();
          atualizarStats();
        } else {
          alert("Erro ao fazer check-out: " + resp.message);
        }
      }
    });
  });

  /* ============================================================
     3) CHECK-IN (FORMULÁRIO)
  ============================================================ */

  function abrirModal(codigo, nome) {
    $qrCodigo.val(codigo);
    $qrNome.val(nome);
    $qrCurso.val("");
    $qrTurma.val("");
    $modal.removeClass("hidden").attr("aria-hidden", "false");
  }

  function fecharModal() {
    $modal.addClass("hidden").attr("aria-hidden", "true");
  }

  $qrCancel.on("click", fecharModal);
  $(document).on("keydown", e => e.key === "Escape" && fecharModal());

  function verificarCodigo(codigo, callback) {
    $.ajax({
      url: API_KIOSK + "?action=check_codigo",
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify({ codigo }),

      success: resp => callback(resp.exists, resp.student),
      error:   ()   => alert("Erro ao verificar código."),
    });
  }

  $formCheckin.on("submit", function (e) {
    e.preventDefault();

    const codigo = $("#student-id").val().trim().toUpperCase();
    const nome   = $("#full-name").val().trim();

    if (!codigo || !nome) return alert("Informe código e nome.");

    verificarCodigo(codigo, function (existe, estudante) {

      // Já existe no sistema → check-in direto
      if (existe) {

        $.ajax({
          url: API_VISITAS + "?action=checkin",
          type: "POST",
          contentType: "application/json",
          data: JSON.stringify({
            estudante_id: codigo,
            nome,
            origem: "bibliotecario"
          }),

          success: function (resp) {
            if (resp.success) {
              $("#student-id").val("");
              $("#full-name").val("");
              carregarAtivos();
              atualizarStats();
              alert("Check-in registado!");
            } else {
              alert("Erro: " + resp.message);
            }
          }
        });

      } else {
        // Não existe → abrir modal de cadastro rápido
        abrirModal(codigo, nome);
      }
    });
  });

  /* ============================================================
     4) CADASTRO RÁPIDO DE ESTUDANTE
  ============================================================ */

  $qrForm.on("submit", function (e) {
    e.preventDefault();

    const payload = {
      codigo: $qrCodigo.val().trim().toUpperCase(),
      nome:   $qrNome.val().trim(),
      curso:  $qrCurso.val().trim(),
      turma:  $qrTurma.val().trim()
    };

    if (!payload.codigo || !payload.nome) {
      return alert("Código e nome são obrigatórios.");
    }

    // Cadastrar estudante
    $.ajax({
      url: API_ESTUD,
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify(payload),

      success: function (resp) {
        if (!resp.success) {
          alert(resp.message || "Erro ao cadastrar estudante.");
          return;
        }

        // Fechar modal
        fecharModal();

        // Após cadastro → check-in automático
        $.ajax({
          url: API_VISITAS + "?action=checkin",
          type: "POST",
          contentType: "application/json",
          data: JSON.stringify({
            estudante_id: payload.codigo,
            nome: payload.nome,
            origem: "bibliotecario"
          }),

          success: function (resp2) {
            if (resp2.success) {
              $("#student-id").val("");
              $("#full-name").val("");
              carregarAtivos();
              atualizarStats();
              alert("Estudante cadastrado e check-in registado!");
            } else {
              alert("Erro no check-in após cadastro.");
            }
          }
        });
      },

      error: () => alert("Erro ao criar estudante.")
    });
  });

  /* ============================================================
     5) HISTÓRICO RECENTE
  ============================================================ */

  function carregarHistorico() {
    $.getJSON(API_VISITAS + "?action=historico", function (resp) {
      if (!resp.success) return;

      $historicoList.empty();

      resp.visitas.forEach(v => {
        $historicoList.append(`
          <div class="visitor-item">
            <div class="visitor-meta">
              <p class="name">${v.nome} <span class="badge">${v.codigo}</span></p>
              <span class="time">Entrada: ${v.data_entrada} às ${v.hora_entrada}</span>
              <span class="time">Saída: ${v.data_saida} às ${v.hora_saida}</span>
              <span class="reason">Registada em: ${v.origem}</span>
            </div>
          </div>
        `);
      });
    });
  }

  /* ============================================================
     6) INICIALIZAÇÃO
  ============================================================ */

  function init() {
    atualizarStats();
    carregarAtivos();
    carregarHistorico();

    setInterval(atualizarStats,    15000);
    setInterval(carregarAtivos,    12000);
    setInterval(carregarHistorico, 20000);
  }

  init();
});
