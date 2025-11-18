// === Logout ===
$(function() {
  $(".logout-button").on("click", function() {
    if (!confirm("Deseja realmente sair?")) return;

    $.getJSON("../../app/api/auth.php?action=logout", function(resp) {
      if (resp.success) {
        alert("Sessão encerrada com sucesso!");
        window.location.href = "./login.php";
      } else {
        alert("Erro ao sair: " + (resp.message || "Tente novamente"));
      }
    }).fail(() => {
      alert("Erro ao tentar sair!");
    });
  });
});

// === Stats de Visitas (cards do topo) ===
$(function() {
  function atualizarStats() {
    $.getJSON("../../app/api/visitas.php?action=stats", function(data) {
      if (!data.success) {
        console.error("Erro:", data.error || data.message);
        return;
      }

      $(".card.success .value").text(data.totalVisitantes);
      $(".card.primary .value").text(data.visitantesHoje);
      $(".card.default .value").text(data.adminsAtivos);
    }).fail(function() {
      console.error("Falha ao carregar estatísticas");
    });
  }

  atualizarStats();
  setInterval(atualizarStats, 15000);
  $(document).on("userChanged", atualizarStats);
});

// === Stats de Usuários (Admins Ativos – se tiver card específico) ===
$(function() {
  async function carregarStatsUsuarios() {
    try {
      const r = await fetch("../../app/api/usuarios.php?action=stats");
      const data = await r.json();
      if (data.success) {
        // Ajusta o seletor conforme o teu HTML
        $('h3:contains("Admins Ativos")')
          .closest(".card")
          .find(".value")
          .text(data.adminsAtivos);
      }
    } catch (e) {
      console.error(e);
    }
  }

  carregarStatsUsuarios();
  setInterval(carregarStatsUsuarios, 10000);
});

// === CRUD de Usuários via MODAL ===
$(function() {
  // refs do modal de usuário
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

  let modoUsuario = "add"; // "add" ou "edit"

  function abrirUserModal() {
    $userModal.removeClass("hidden").attr("aria-hidden", "false");
  }

  function fecharUserModal() {
    $userModal.addClass("hidden").attr("aria-hidden", "true");
  }

  // 1) Carregar usuários
  function carregarUsuarios() {
    $.getJSON("../../app/api/usuarios.php?action=listar", function(resp) {
      if (!resp.success) {
        console.error(resp.error || "Erro ao listar usuários");
        return;
      }

      const $list = $(".user-list").empty();

      resp.usuarios.forEach(function(u) {
        const badge      = u.role === "admin" ? "Administrador" : "Bibliotecário";
        const ativoClass = parseInt(u.ativo, 10) === 1 ? "" : "inativo";

        $list.append(`
          <div class="user-item ${ativoClass}">
            <p class="user-name">
              ${u.nome} <span class="badge">${badge}</span>
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
              <button class="btn-delete" data-id="${u.id}">Remover</button>
            </div>
          </div>
        `);
      });
    });
  }

  carregarUsuarios();

  // 2) Remover usuário
  $(document).on("click", ".btn-delete", function() {
    const id = $(this).data("id");
    if (!confirm("Tem certeza que deseja remover este usuário?")) return;

    $.ajax({
      url: `../../app/api/usuarios.php?action=remover&id=${id}`,
      method: "GET",
      cache: false,
      success: function(resp) {
        if (resp.success) {
          alert("Usuário removido!");
          carregarUsuarios();
          $(document).trigger("userChanged");
        } else {
          alert("Erro ao remover: " + (resp.error || resp.message));
        }
      },
      error: function() {
        alert("Erro de rede ao remover usuário.");
      }
    });
  });

  // 3) Abrir modal para ADICIONAR usuário
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

  // 4) Abrir modal para EDITAR usuário
  $(document).on("click", ".btn-edit", function() {
    modoUsuario = "edit";
    const $btn = $(this);

    $userModalTitle.text("Editar usuário");

    $userId.val($btn.data("id"));
    $userNome.val($btn.data("nome"));
    $userEmail.val($btn.data("email"));
    $userRole.val($btn.data("role"));
    $userAtivo.prop(parseInt($btn.data("ativo"), 10) === 1);

    // Em edição não mexemos na senha
    $userSenha.val("");
    $userSenhaGroup.hide();
    $userSenha.prop("required", false);

    abrirUserModal();
  });

  // 5) Submeter formulário do modal
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
      payload.id = parseInt($userId.val(), 10) || 0;
      if (!payload.id) {
        alert("ID do usuário inválido.");
        return;
      }
      url = "../../app/api/usuarios.php?action=editar";
    }

    $.ajax({
      url: url,
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify(payload),
      success: function(resp) {
        if (!resp || !resp.success) {
          alert(
            (resp && (resp.error || resp.message)) ||
            "Erro ao salvar usuário."
          );
          return;
        }

        fecharUserModal();
        carregarUsuarios();
        $(document).trigger("userChanged");

        alert(
          modoUsuario === "add"
            ? "Usuário criado com sucesso!"
            : "Usuário atualizado com sucesso!"
        );
      },
      error: function() {
        alert("Erro de rede ao salvar usuário.");
      }
    });
  });

  // 6) Cancelar / ESC fecha modal
  $userCancel.on("click", function() {
    fecharUserModal();
  });

  $(document).on("keydown", function(e) {
    if (e.key === "Escape") {
      fecharUserModal();
    }
  });
});

// === Logs de ações (linguagem amigável) ===
$(function() {
  // Mapeia os códigos técnicos para textos amigáveis
  function formatAction(action) {
    switch (action) {
      case "login":
        return "Sessão iniciada";
      case "logout":
        return "Sessão terminada";
      case "login.falha":
        return "Tentativa de login falhada";
      case "checkin.manual":
        return "Check-in registado manualmente";
      case "checkin.kiosk":
        return "Check-in registado no kiosk";
      case "checkout.kiosk":
        return "Check-out registado no kiosk";
      case "visit.close":
        return "Visita encerrada";
      case "user.add":
        return "Utilizador criado";
      case "user.update":
        return "Dados de utilizador atualizados";
      case "user.delete":
        return "Utilizador removido";
      default:
        return (action || "").replace(/\./g, " › ") || "Ação";
    }
  }

  function atualizarLogs() {
    $.getJSON("../../app/api/logs.php?action=ultimos", function(resp) {
      if (!resp.success) {
        console.error(resp.error || "Erro ao listar logs");
        return;
      }

      const $list = $(".log-list").empty();

      resp.logs.forEach(function(l) {
        const hora = new Date(l.criado_em).toLocaleTimeString("pt-PT", {
          hour: "2-digit",
          minute: "2-digit"
        });

        const actor = l.actor_label || l.user_name || "—";
        const acao  = formatAction(l.action);

        $list.append(
          `<div class="log-item">
             <p>${acao} por ${actor} às ${hora}</p>
           </div>`
        );
      });
    });
  }

  atualizarLogs();
  setInterval(atualizarLogs, 10000);
  $(document).on("userChanged", atualizarLogs);
});
