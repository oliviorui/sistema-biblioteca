/* ============================================================
   ADMIN.JS – Painel Administrativo BiblioControl
   Refatorado, organizado e consistente com o admin.php
============================================================ */

$(function() {

  /* ============================================================
     LOGOUT
  ============================================================ */
  $(".logout-button").on("click", function() {
    if (!confirm("Deseja realmente sair?")) return;

    $.getJSON("../../app/api/auth.php?action=logout", function(resp) {
      if (resp.success) {
        alert("Sessão encerrada com sucesso!");
        window.location.href = "./login.php";
      } else {
        alert("Erro ao sair: " + (resp.message || "Tente novamente"));
      }
    }).fail(() => alert("Erro ao tentar sair!"));
  });

  /* ============================================================
     ESTATÍSTICAS (VISITAS E OUTROS)
  ============================================================ */

  function atualizarStatsVisitantes() {
    $.getJSON("../../app/api/visitas.php?action=stats", function(data) {
      if (!data.success) {
        console.error("Erro:", data.message);
        return;
      }
      $(".card.success .value").text(data.totalVisitantes);
      $(".card.primary .value").text(data.visitantesHoje);
    }).fail(() => console.error("Falha ao carregar estatísticas de visitas"));
  }

  // Admins Ativos
  function atualizarStatsUsuarios() {
    $.getJSON("../../app/api/usuarios.php?action=stats", function(data) {
      if (data.success) {
        $(".card.default .value").text(data.adminsAtivos);
      }
    }).fail(() => console.error("Erro ao carregar estatísticas de usuários"));
  }

  atualizarStatsVisitantes();
  atualizarStatsUsuarios();

  setInterval(atualizarStatsVisitantes, 15000);
  setInterval(atualizarStatsUsuarios, 10000);

  $(document).on("userChanged", function() {
    atualizarStatsUsuarios();
    atualizarStatsVisitantes();
  });

  /* ============================================================
     CRUD DE USUÁRIOS (MODAL)
  ============================================================ */

  const $userModal      = $("#user-modal");
  const $userForm       = $("#user-form");
  const $userModalTitle = $("#user-modal-title");
  const $userId         = $("#user-id");
  const $userNome       = $("#user-nome");
  const $userEmail      = $("#user-email");
  const $userRole       = $("#user-role");
  const $userAtivo      = $("#user-ativo");
  const $userSenhaGroup = $("#user-senha-group");
  const $userSenha      = $("#user-senha");
  const $userCancel     = $("#user-cancel");

  let modoUsuario = "add";

  function abrirUserModal() {
    $userModal.removeClass("hidden").attr("aria-hidden", "false");
  }

  function fecharUserModal() {
    $userModal.addClass("hidden").attr("aria-hidden", "true");
  }

  /* -------------------------
     LISTAR USUÁRIOS
  ------------------------- */
  function carregarUsuarios() {
    $.getJSON("../../app/api/usuarios.php?action=listar", function(resp) {
      if (!resp.success) {
        console.error("Erro:", resp.message);
        return;
      }

      const $list = $(".user-list").empty();

      resp.usuarios.forEach(function(u) {
        const badge = u.role === "admin" ? "Administrador" : "Bibliotecário";
        const inativo = u.ativo == 1 ? "" : "inativo";

        $list.append(`
          <div class="user-item ${inativo}">
            <p class="user-name">
              ${u.nome}
              <span class="badge">${badge}</span>
            </p>
            <div class="actions">
              <button class="btn-edit"
                      data-id="${u.id}"
                      data-nome="${u.nome}"
                      data-email="${u.email}"
                      data-role="${u.role}"
                      data-ativo="${u.ativo}">
                Editar
              </button>
              <button class="btn-delete"
                      data-id="${u.id}">
                Remover
              </button>
            </div>
          </div>
        `);
      });
    });
  }

  carregarUsuarios();

  /* -------------------------
     REMOVER USUÁRIO
  ------------------------- */

  $(document).on("click", ".btn-delete", function() {
    const id = $(this).data("id");

    if (!confirm("Tem certeza que deseja remover este usuário?")) return;

    $.getJSON(`../../app/api/usuarios.php?action=remover&id=${id}`, function(resp) {
      if (resp.success) {
        alert("Usuário removido!");
        carregarUsuarios();
        $(document).trigger("userChanged");
      } else {
        alert("Erro ao remover: " + resp.message);
      }
    }).fail(() => alert("Erro de rede ao remover usuário."));
  });

  /* -------------------------
     ADICIONAR USUÁRIO
  ------------------------- */

  $(".btn-add").on("click", function() {
    modoUsuario = "add";

    $userModalTitle.text("Adicionar usuário");

    $userId.val("");
    $userNome.val("");
    $userEmail.val("");
    $userRole.val("bibliotecario");
    $userAtivo.prop("checked", true);

    $userSenha.val("");
    $userSenhaGroup.show();
    $userSenha.prop("required", true);

    abrirUserModal();
  });

  /* -------------------------
     EDITAR USUÁRIO
  ------------------------- */

  $(document).on("click", ".btn-edit", function() {
    modoUsuario = "edit";

    const $btn = $(this);

    $userModalTitle.text("Editar usuário");

    $userId.val($btn.data("id"));
    $userNome.val($btn.data("nome"));
    $userEmail.val($btn.data("email"));
    $userRole.val($btn.data("role"));
    $userAtivo.prop($btn.data("ativo") == 1);

    $userSenha.val("");
    $userSenhaGroup.hide();
    $userSenha.prop("required", false);

    abrirUserModal();
  });

  /* -------------------------
     SUBMIT MODAL
  ------------------------- */

  $userForm.on("submit", function(e) {
    e.preventDefault();

    const payload = {
      nome:  $userNome.val().trim(),
      email: $userEmail.val().trim(),
      role:  $userRole.val(),
      ativo: $userAtivo.is(":checked") ? 1 : 0
    };

    if (!payload.nome || !payload.email) {
      alert("Preencha nome e email.");
      return;
    }

    let url;

    if (modoUsuario === "add") {
      const senha = $userSenha.val().trim();
      if (!senha) {
        alert("Defina uma senha para o usuário.");
        return;
      }

      payload.senha = senha;
      url = "../../app/api/usuarios.php?action=adicionar";
    } else {
      payload.id = $userId.val();
      url = "../../app/api/usuarios.php?action=editar";
    }

    $.ajax({
      url,
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify(payload),
      success: function(resp) {
        if (!resp.success) {
          alert(resp.message || "Erro ao salvar usuário.");
          return;
        }

        fecharUserModal();
        carregarUsuarios();
        $(document).trigger("userChanged");

        alert(modoUsuario === "add" ? "Usuário criado!" : "Usuário atualizado!");
      },
      error: () => alert("Erro de rede ao salvar usuário.")
    });
  });

  /* -------------------------
     Cancelar modal
  ------------------------- */

  $userCancel.on("click", fecharUserModal);

  $(document).on("keydown", function(e) {
    if (e.key === "Escape") fecharUserModal();
  });

  /* ============================================================
     LOGS DE AÇÃO
  ============================================================ */

  function traduzirAcao(action) {
    const mapa = {
      "login": "Sessão iniciada",
      "logout": "Sessão terminada",
      "login.falha": "Tentativa de login falhada",
      "checkin.manual": "Check-in manual",
      "checkin.kiosk": "Check-in via kiosk",
      "checkout.kiosk": "Check-out via kiosk",
      "visit.close": "Visita encerrada",
      "user.add": "Usuário criado",
      "user.update": "Usuário atualizado",
      "user.delete": "Usuário removido"
    };
    return mapa[action] || action?.replace(/\./g, " › ") || "Ação";
  }

  function atualizarLogs() {
    $.getJSON("../../app/api/logs.php?action=ultimos", function(resp) {
      if (!resp.success) return;

      const $list = $(".log-list").empty();

      resp.logs.forEach(function(l) {
        const hora = new Date(l.criado_em).toLocaleTimeString("pt-PT", {
          hour: "2-digit",
          minute: "2-digit"
        });

        const ator = l.actor_label || l.user_name || "—";
        const acao = traduzirAcao(l.action);

        $list.append(`
          <div class="log-item">
            <p>${acao} por ${ator} às ${hora}</p>
          </div>
        `);
      });
    });
  }

  atualizarLogs();
  setInterval(atualizarLogs, 10000);
  $(document).on("userChanged", atualizarLogs);

});
