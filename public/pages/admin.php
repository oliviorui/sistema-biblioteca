<?php
require_once __DIR__ . '/../../app/lib/Auth.php';
$auth = new Auth();
if (!$auth->check()) { header('Location: ./login.php'); exit; }
if (!$auth->isAdmin()) { header('Location: ./erro.php'); exit; }
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BiblioControl - Painel Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="shortcut icon" href="../assets/img/logo-itc.png" type="image/x-icon">
</head>
<body>

  <!-- Header -->
  <header class="topbar">
    <div class="container">
      <div class="logo">
        <div class="logo-img">
          <img src="../assets/img/logo-itc.png" alt="Logo do ITC">
        </div>
        <div class="logo-text">
          <h1>BiblioControl Admin</h1>
          <p>Gestão completa do sistema</p>
        </div>
      </div>
      <button class="logout-button" aria-label="Sair do sistema">
        <img src="../assets/img/icons/log-out.svg" alt="Sair" class="icon"> Sair
      </button>
    </div>
  </header>

  <main class="container">

    <!-- Estatísticas Admin -->
    <section class="stats-cards">
      <div class="card success">
        <div class="info">
          <h3>Total de Visitantes</h3>
          <p class="value">—</p>
          <p class="description">Visitantes cadastrados</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/people.svg" alt="Visitantes" class="icon">
        </div>
      </div>
      <div class="card primary">
        <div class="info">
          <h3>Visitantes Hoje</h3>
          <p class="value">—</p>
          <p class="description">Check-ins realizados hoje</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/time.svg" alt="Hoje" class="icon">
        </div>
      </div>
      <div class="card default">
        <div class="info">
          <h3>Admins Ativos</h3>
          <p class="value">—</p>
          <p class="description">Usuários com permissão de admin</p>
        </div>
        <div class="icon">
          <img src="../assets/img/icons/shield-checkmark.svg" alt="Admins" class="icon">
        </div>
      </div>
    </section>

    <!-- Painéis -->
    <section class="main-content">

      <!-- Gerenciar Usuários -->
      <div class="admin-panel">
        <h2>
          <div class="icon">
            <img src="../assets/img/icons/settings.svg" alt="Gerenciar Usuários" class="icon">
          </div>
          Gerenciar Usuários
        </h2>
        <p>Adicionar, editar ou remover administradores e bibliotecários</p>
        <div class="user-list">
          <!-- Exemplo de usuário -->
          <div class="user-item">
            <p class="user-name">Maria Silva <span class="badge">Bibliotecária</span></p>
            <div class="actions">
              <button class="btn-edit">Editar</button>
              <button class="btn-delete">Remover</button>
            </div>
          </div>
        </div>
        <button class="btn-add">Adicionar Usuário</button>
      </div>

      <!-- Logs de ações -->
      <div class="admin-panel">
        <h2>
          <div class="icon">
            <img src="../assets/img/icons/library.svg" alt="Logs" class="icon">
          </div>
          Logs de Ação
        </h2>
        <p>Histórico de operações realizadas no sistema</p>
        <div class="log-list">
          <div class="log-item">
            <p>Maria Silva cadastrou novo visitante às 10:15</p>
          </div>
        </div>
      </div>
    </section>
  </main>
  <script src="../assets/js/lib/jquery.js"></script>
  <script>
    $(function() {
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
    });
  </script>

  <script>
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

      // Chama uma vez ao abrir a página
      atualizarStats();

      // Atualiza a cada 15 segundos (ajustável)
      setInterval(atualizarStats, 15000);

      // Também atualiza manualmente sempre que muda usuários
      $(document).on("userChanged", atualizarStats);
    });
  </script>

  <script>
  $(function(){
    async function carregarStatsUsuarios(){
      try{
        const r = await fetch('../../app/api/usuarios.php?action=stats');
        const data = await r.json();
        if (data.success){
          // Seleciona o card de “Admins Ativos” e coloca o valor
          // Ajusta o seletor conforme teu HTML (abaixo é um exemplo):
          $('h3:contains("Admins Ativos")')
            .closest('.card').find('.value').text(data.adminsAtivos);
        }
      }catch(e){ console.error(e); }
    }

    carregarStatsUsuarios();
    setInterval(carregarStatsUsuarios, 15000); // atualiza a cada 15s, se quiser
  });
  </script>


  <script>
    $(function() {
      // === 1️⃣ Carregar usuários ===
      function carregarUsuarios() {
        $.getJSON("../../app/api/usuarios.php?action=listar", function(resp) {
          if (!resp.success) return console.error(resp.error || "Erro ao listar");
          const $list = $(".user-list").empty();

          resp.usuarios.forEach(u => {
            const badge = u.role === 'admin' ? 'Administrador' : 'Bibliotecário';
            const ativo = u.ativo == 1 ? '' : 'inativo';
            $list.append(`
              <div class="user-item ${ativo}">
                <p class="user-name">${u.nome} <span class="badge">${badge}</span></p>
                <div class="actions">
                  <button class="btn-edit" data-id="${u.id}" data-nome="${u.nome}" data-email="${u.email}" data-role="${u.role}" data-ativo="${u.ativo}">Editar</button>
                  <button class="btn-delete" data-id="${u.id}">Remover</button>
                </div>
              </div>
            `);
          });
        });
      }

      carregarUsuarios();

      // === 2️⃣ Remover usuário ===
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
            } else {
              alert("Erro ao remover: " + (resp.error || resp.message));
            }
          }
        });
      });

      // === 3️⃣ Adicionar usuário ===
      $(".btn-add").on("click", function() {
        const nome = prompt("Nome do usuário:");
        const email = prompt("Email:");
        const senha = prompt("Senha:");
        const role = confirm("Este usuário é admin?") ? 'admin' : 'bibliotecario';

        if (!nome || !email || !senha) return alert("Preencha todos os campos!");

        $.ajax({
          url: "../../app/api/usuarios.php?action=adicionar",
          type: "POST",
          contentType: "application/json",
          data: JSON.stringify({ nome, email, senha, role }),
          success: function(resp) {
            if (resp.success) {
              alert("Usuário adicionado!");
              carregarUsuarios();
            } else {
              alert("Erro ao adicionar: " + (resp.error || resp.message));
            }
          }
        });
      });

      // === 4️⃣ Editar usuário ===
      $(document).on("click", ".btn-edit", function() {
        const id = $(this).data("id");
        const nomeAtual = $(this).data("nome");
        const emailAtual = $(this).data("email");
        const roleAtual = $(this).data("role");
        const ativoAtual = $(this).data("ativo");

        const nome = prompt("Nome:", nomeAtual);
        const email = prompt("Email:", emailAtual);
        const role = confirm("Tornar este usuário administrador?") ? "admin" : "bibliotecario";
        const ativo = confirm("Usuário ativo?") ? 1 : 0;

        if (!nome || !email) return;

        $.ajax({
          url: "../../app/api/usuarios.php?action=editar",
          type: "POST",
          contentType: "application/json",
          data: JSON.stringify({ id, nome, email, role, ativo }),
          success: function(resp) {
            if (resp.success) {
              alert("Usuário atualizado!");
              carregarUsuarios();
            } else {
              alert("Erro ao atualizar: " + (resp.error || resp.message));
            }
          }
        });
      });
    });
  </script>

  <script>
    $(function() {
      function atualizarLogs() {
        $.getJSON("../../app/api/logs.php?action=ultimos", function(resp) {
          if (!resp.success) return console.error(resp.error || "Erro ao listar logs");
          const $list = $(".log-list").empty();
          resp.logs.forEach(l => {
            const hora = new Date(l.criado_em).toLocaleTimeString();
            $list.append(`<div class="log-item"><p>${l.action} por ${l.user_name} às ${hora}</p></div>`);
          });
        });
      }

      atualizarLogs();
      setInterval(atualizarLogs, 10000); // atualizar a cada 10s
      $(document).on("userChanged", atualizarLogs); // atualiza sempre que usuários mudam
    });
  </script>
</body>
</html>
