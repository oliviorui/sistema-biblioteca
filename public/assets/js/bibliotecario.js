/* ==== Extracted from public/pages/bibliotecario.php ==== */

/* Block #1 */
$(function () {
      // ===== 0) Logout (igual ao admin) =====
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

      // ===== 1) Estatísticas =====
      function atualizarStats() {
        $.getJSON("../../app/api/visitas.php?action=stats", function(data) {
          if (!data.success) {
            console.error("Erro:", data.error || data.message);
            return;
          }
          // Mapeamos como no admin.php:
          // .card.success  -> Visitantes Presentes (ao vivo)
          // .card.primary  -> Visitas Hoje
          // .card.default  -> Total de Visitantes cadastrados
          $(".card.success .value").text(data.presentes || 0);
          $(".card.primary .value").text(data.visitantesHoje || 0);
          $(".card.default .value").text(data.totalVisitantes || 0);

          // Ajusta o subtítulo "X pessoas na biblioteca"
          const n = data.presentes || 0;
          $("#presentes-count").text(n === 1 ? "1 pessoa na biblioteca" : (n + " pessoas na biblioteca"));
        }).fail(function() {
          console.error("Falha ao carregar estatísticas");
        });
      }

      // ===== 2) Carregar Visitantes Presentes =====
      function carregarAtivos() {
        $.getJSON("../../app/api/visitas.php?action=ativos", function(resp) {
          if (!resp.success) return console.error(resp.error || "Erro ao listar ativos");
          const $list = $("#ativos-list").empty();

          // Esperamos itens no formato:
          // { id, estudante_id, nome, hora_entrada, origem }
          resp.ativos.forEach(v => {
            const hora = v.hora_entrada ? new Date(v.hora_entrada).toLocaleTimeString() : "--:--";
            const origem = v.origem ? v.origem.charAt(0).toUpperCase() + v.origem.slice(1) : "—";
            $list.append(`
              <div class="visitor-item" data-visita-id="${v.id}">
                <div class="visitor-meta">
                  <p class="name">${v.nome || "(Sem nome)"} <span class="badge">${v.codigo || ""}</span></p>
                  <span class="time">Entrada registada às ${hora}</span>
                  <span class="reason">Registada em: ${origem}</span>
                </div>
                <button class="btn-checkout">
                  <img src="../assets/img/icons/log-out.svg" alt="Sair" class="icon">Check-out
                </button>
              </div>
            `);
          });

          // Atualiza o contador textual também (fallback caso stats ainda não tenha vindo)
          const n = resp.ativos.length;
          $("#presentes-count").text(n === 1 ? "1 pessoa na biblioteca" : (n + " pessoas na biblioteca"));
        });
      }

      // Evento do botão de checkout (delegado)
      $(document).on("click", ".btn-checkout", function () {
        const $item = $(this).closest(".visitor-item");
        const visitaId = $item.data("visita-id");
        if (!visitaId) return;

        if (!confirm("Confirmar check-out deste visitante?")) return;

        $.ajax({
          url: "../../app/api/visitas.php?action=checkout",
          type: "POST",
          contentType: "application/json",
          data: JSON.stringify({ visita_id: visitaId }),
          success: function (resp) {
            if (resp.success) {
              // Atualiza listas e estatísticas
              carregarAtivos();
              carregarHistorico();
              atualizarStats();
            } else {
              alert("Erro ao fazer check-out: " + (resp.error || resp.message || "Tente novamente."));
            }
          },
          error: function () {
            alert("Falha no check-out, tente novamente.");
          }
        });
      });

      // ===== 3) Check-in pelo formulário =====
      $(".visitor-form form").on("submit", function (e) {
        e.preventDefault();
        const estudante_id = $("#student-id").val().trim();
        const nome = $("#full-name").val().trim();
        if (!estudante_id || !nome) return alert("Preencha código de estudante e nome.");

        $.ajax({
          url: "../../app/api/visitas.php?action=checkin",
          type: "POST",
          contentType: "application/json",
          data: JSON.stringify({ estudante_id, nome, origem: "bibliotecario" }),
          success: function (resp) {
            if (resp.success) {
              // Limpa campos e recarrega
              $("#student-id").val("");
              $("#full-name").val("");
              carregarAtivos();
              atualizarStats();
              alert("Check-in registado com sucesso!");
            } else {
              alert("Erro no check-in: " + (resp.error || resp.message || "Tente novamente."));
            }
          },
          error: function () {
            alert("Falha ao comunicar com o servidor.");
          }
        });
      });

      // ===== 4) Histórico recente =====
      function carregarHistorico() {
        $.getJSON("../../app/api/visitas.php?action=historico", function (resp) {
          if (!resp.success) return console.error(resp.error || "Erro ao listar histórico");
          const $list = $("#historico-list").empty();

          // Esperamos itens no formato:
          // { estudante_id, nome, data_entrada, hora_entrada, data_saida, hora_saida, origem }
          resp.visitas.forEach(v => {
            const entrada = (v.data_entrada || "") + " " + (v.hora_entrada || "");
            const saida   = (v.data_saida   || "") + " " + (v.hora_saida   || "");
            const origem  = v.origem ? v.origem.charAt(0).toUpperCase() + v.origem.slice(1) : "—";
            $list.append(`
              <div class="visitor-item">
                <div class="visitor-meta">
                  <p class="name">${v.nome || "(Sem nome)"} <span class="badge">${v.codigo || ""}</span></p>
                  <span class="time">Entrada: ${entrada.trim()}</span>
                  <span class="time">Saída: ${saida.trim()}</span>
                  <span class="reason">Registada em: ${origem}</span>
                </div>
              </div>
            `);
          });
        });
      }

      // ===== 5) Inicialização e atualização periódica =====
      function init() {
        atualizarStats();
        carregarAtivos();
        carregarHistorico();
        // Atualizações periódicas simples
        setInterval(atualizarStats,   15000);  // 15s
        setInterval(carregarAtivos,   12000);  // 12s
        setInterval(carregarHistorico,20000);  // 20s
      }

      init();
    });
