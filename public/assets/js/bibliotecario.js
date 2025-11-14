$(function () {
      // === Botão de Logout ===
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
      // Elementos do modal de cadastro rápido (no painel do bibliotecário)
      const $modalCadastro   = $("#quick-register-modal");
      const $qrForm          = $("#quick-register-form");
      const $qrCodigo        = $("#qr-code");
      const $qrNome          = $("#qr-nome");
      const $qrCurso         = $("#qr-curso");
      const $qrTurma         = $("#qr-turma");
      const $qrCancelar      = $("#qr-cancel");

      function abrirModalCadastroRapidoBiblioteca(codigo, nome) {
        if (!$modalCadastro.length) {
          alert("Cadastro rápido indisponível. Contacte o administrador.");
          return;
        }

        $qrCodigo.val(codigo || "");
        $qrNome.val(nome || "");
        $qrCurso.val("");
        $qrTurma.val("");

        $modalCadastro.removeClass("hidden").attr("aria-hidden", "false");
      }

      function fecharModalCadastroRapidoBiblioteca() {
        if (!$modalCadastro.length) return;
        $modalCadastro.addClass("hidden").attr("aria-hidden", "true");
      }

      // Verifica se o código já existe (reutiliza o endpoint público do kiosk)
      function verificarCodigoBiblioteca(codigo, callback) {
        const cod = (codigo || "").trim().toUpperCase();
        if (!cod) {
          alert("Informe o código do estudante.");
          return;
        }

        $.ajax({
          url: "../../app/api/kiosk.php?action=check_codigo",
          type: "POST",
          contentType: "application/json",
          dataType: "json",
          data: JSON.stringify({ codigo: cod }),
          success: function (resp) {
            if (!resp.success) {
              alert(resp.message || "Falha ao verificar código.");
              return;
            }
            if (typeof callback === "function") {
              callback(resp.exists, resp.student || null);
            }
          },
          error: function () {
            alert("Erro de rede ao verificar código.");
          }
        });
      }

      // Submissão do formulário principal de check-in
      $(".visitor-form form").on("submit", function (e) {
        e.preventDefault();
        const codigo = $("#student-id").val().trim();
        const nome   = $("#full-name").val().trim();

        if (!codigo || !nome) {
          alert("Preencha código de estudante e nome.");
          return;
        }

        verificarCodigoBiblioteca(codigo, function (existe, estudante) {
          if (existe) {
            // Se já existe, podemos opcionalmente sincronizar o nome exibido
            if (estudante && estudante.nome && estudante.nome.toLowerCase() !== nome.toLowerCase()) {
              if (confirm('Este código já está cadastrado para "' + estudante.nome + '". Deseja usar este nome?')) {
                $("#full-name").val(estudante.nome);
              }
            }

            // Faz o check-in diretamente
            $.ajax({
              url: "../../app/api/visitas.php?action=checkin",
              type: "POST",
              contentType: "application/json",
              data: JSON.stringify({
                estudante_id: codigo,
                nome: $("#full-name").val().trim(),
                origem: "bibliotecario"
              }),
              success: function (resp) {
                if (resp.success) {
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
          } else {
            // Código não existe → abre modal de cadastro rápido
            abrirModalCadastroRapidoBiblioteca(codigo, nome);
          }
        });
      });

      // Tratamento do formulário de cadastro rápido
      if ($qrForm.length) {
        $qrForm.on("submit", function (e) {
          e.preventDefault();

          const payload = {
            codigo: ($qrCodigo.val() || "").trim().toUpperCase(),
            nome:   ($qrNome.val()   || "").trim(),
            curso:  ($qrCurso.val()  || "").trim(),
            turma:  ($qrTurma.val()  || "").trim()
          };

          if (!payload.codigo || !payload.nome) {
            alert("Código e nome são obrigatórios.");
            return;
          }

          $.ajax({
            url: "../../app/api/estudantes.php",
            type: "POST",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify(payload),
            success: function (resp) {
              if (!resp || !resp.success) {
                alert((resp && (resp.error || resp.message)) || "Falha ao cadastrar estudante.");
                return;
              }

              fecharModalCadastroRapidoBiblioteca();

              // Após cadastrar, realiza imediatamente o check-in com os mesmos dados
              $.ajax({
                url: "../../app/api/visitas.php?action=checkin",
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
                    alert("Estudante cadastrado e check-in registado com sucesso!");
                  } else {
                    alert("Estudante cadastrado, mas houve erro ao fazer o check-in: " + (resp2.error || resp2.message || "Tente novamente."));
                  }
                },
                error: function () {
                  alert("Estudante cadastrado, mas falha ao comunicar com o servidor para o check-in.");
                }
              });
            },
            error: function () {
              alert("Erro de rede ao cadastrar estudante.");
            }
          });
        });
      }

      if ($qrCancelar.length) {
        $qrCancelar.on("click", function () {
          fecharModalCadastroRapidoBiblioteca();
        });
      }

      // ===== 4) Histórico recente =====
      function carregarHistorico() {
        $.getJSON("../../app/api/visitas.php?action=historico", function (resp) {
          if (!resp.success) return console.error(resp.error || "Erro ao listar histórico");
          const $list = $("#historico-list").empty();

          // Esperamos itens no formato:
          // { estudante_id, nome, data_entrada, hora_entrada, data_saida, hora_saida, origem }
          resp.visitas.forEach(v => {
            const entrada = (v.data_entrada || "") + " às " + (v.hora_entrada || "");
            const saida   = (v.data_saida   || "") + " às " + (v.hora_saida   || "");
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
