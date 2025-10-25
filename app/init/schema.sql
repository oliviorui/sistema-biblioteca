-- LIMPA (opcional para dev)
DROP DATABASE IF EXISTS sistema_biblioteca;

SET NAMES utf8mb4;
SET time_zone = '+02:00';

CREATE DATABASE sistema_biblioteca
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE sistema_biblioteca;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','bibliotecario') NOT NULL DEFAULT 'bibliotecario',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE estudantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(16) NOT NULL UNIQUE,
  nome VARCHAR(160) NOT NULL,
  curso VARCHAR(160) NULL,
  turma VARCHAR(40) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE visitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  estudante_id INT NOT NULL,
  data_visita DATE NOT NULL,
  hora_entrada DATETIME NOT NULL,
  hora_saida DATETIME NULL,
  origem ENUM('kiosk','bibliotecario') NOT NULL DEFAULT 'kiosk',
  fechado_por INT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_visitas_estudante FOREIGN KEY (estudante_id) REFERENCES estudantes(id),
  CONSTRAINT fk_visitas_user FOREIGN KEY (fechado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX (data_visita),
  INDEX (hora_entrada),
  INDEX (hora_saida)
) ENGINE=InnoDB;

-- Substitui apenas a tabela LOGS
DROP TABLE IF EXISTS logs;

CREATE TABLE logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_type ENUM('usuario','estudante','sistema') NOT NULL DEFAULT 'sistema',
  actor_user_id    INT NULL,
  actor_student_id INT NULL,

  action  VARCHAR(80) NOT NULL,
  details TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX (actor_type),
  INDEX idx_logs_actor_user    (actor_user_id),
  INDEX idx_logs_actor_student (actor_student_id),
  INDEX (action),

  CONSTRAINT fk_logs_user FOREIGN KEY (actor_user_id)
    REFERENCES usuarios(id) ON DELETE SET NULL,

  CONSTRAINT fk_logs_student FOREIGN KEY (actor_student_id)
    REFERENCES estudantes(id) ON DELETE SET NULL,

  -- Regra de consistência:
  --  usuario   => actor_user_id    NOT NULL e actor_student_id NULL
  --  estudante => actor_student_id NOT NULL e actor_user_id    NULL
  --  sistema   => ambos NULL
  CONSTRAINT chk_logs_actor_valid
  CHECK (
    (actor_type = 'usuario'   AND actor_user_id IS NOT NULL    AND actor_student_id IS NULL) OR
    (actor_type = 'estudante' AND actor_student_id IS NOT NULL AND actor_user_id IS NULL)    OR
    (actor_type = 'sistema'   AND actor_user_id IS NULL        AND actor_student_id IS NULL)
  )
) ENGINE=InnoDB;

CREATE TABLE sessoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  user_agent VARCHAR(255),
  ip VARCHAR(45),
  data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_expiracao DATETIME NOT NULL,
  estado ENUM('ativa','desativada','expirada') NOT NULL DEFAULT 'ativa',
  CONSTRAINT fk_sessoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_sessoes_usuario ON sessoes(usuario_id);

-- ============================================
-- SEED DE DADOS PARA TESTE - ITC Biblioteca
-- ============================================

-- Estudantes
INSERT INTO estudantes (codigo, nome, curso, turma) VALUES
('23014', 'Adélio Zandamela', 'Programação de Aplicações Web', 'TPW1'),
('23025', 'Célia Macamo', 'Contabilidade', 'TC1'),
('23038', 'Mauro Uamusse', 'Gestão', 'TG1'),
('23049', 'Nélson Matusse', 'Electricidade de Manutenção Industrial', 'TEMI1'),
('23053', 'Tatiana Massingue', 'Técnico de Suporte Informático', 'TSI1'),
('23057', 'Ernesto Nhantumbo', 'Gestão de Recursos Humanos', 'TGRH1'),
('23065', 'Luís Cuambe', 'Administração de Sistemas de Redes', 'TAR1'),
('23071', 'Paula Cumbane', 'Gestão Patrimonial e Financeira', 'TGPF1'),
('23082', 'Leonor Mucavele', 'Contabilidade', 'TC2'),
('23096', 'Júlio Mabjaia', 'Electricidade Industrial', 'TEI1'),
('23102', 'Anselmo Uache', 'Programação de Aplicações Web', 'TPW2'),
('23114', 'Vânia Nhacale', 'Gestão de Recursos Humanos', 'TGRH1'),
('23123', 'Simão Chissano', 'Construção Civil', 'TCC1'),
('23136', 'Adélia Machava', 'Técnico de Suporte Informático', 'TSI2'),
('23147', 'Felismina Cuco', 'Gestão', 'TG2'),
('23151', 'Edgar Cossa', 'Administração de Sistemas de Redes', 'TAR1'),
('23167', 'Ilda Matavele', 'Contabilidade', 'TC3'),
('23175', 'Sérgio Mucopola', 'Electricidade e Electrónica Industrial', 'TEE1'),
('23188', 'Elísio Matusse', 'Electricidade de Manutenção Industrial', 'TEMI2'),
('23203', 'Helga Cuambe', 'Gestão Patrimonial e Financeira', 'TGPF2'),
('23215', 'Nuno Zimba', 'Gestão de Recursos Humanos', 'TGRH2'),
('23228', 'Carlitos Mambule', 'Programação de Aplicações Web', 'TPW2'),
('23239', 'Ivone Manjate', 'Técnico de Suporte Informático', 'TSI2'),
('23247', 'Felisberto Cuambe', 'Electricidade Industrial', 'TEI1'),
('23255', 'Marta Sitoe', 'Gestão', 'TG3'),
('23266', 'Armando Zandamela', 'Construção Civil', 'TCC2'),
('23272', 'Ofélia Machatine', 'Contabilidade', 'TC3'),
('23283', 'Gaspar Chongo', 'Administração de Sistemas de Redes', 'TAR2'),
('23295', 'Eugénia Mucavele', 'Electricidade e Electrónica Industrial', 'TEE1'),
('23302', 'Lucrécia Chiziane', 'Gestão Patrimonial e Financeira', 'TGPF2'),
('23319', 'Tobias Zimba', 'Gestão de Recursos Humanos', 'TGRH3'),
('23324', 'Délcio Machava', 'Electricidade Industrial', 'TEI2'),
('23335', 'Nélia Mucavel', 'Programação de Aplicações Web', 'TPW3'),
('23348', 'Isabel Cuco', 'Contabilidade', 'TC4'),
('23351', 'Daniel Mucavele', 'Técnico de Suporte Informático', 'TSI3'),
('23366', 'Cátia Massango', 'Administração de Sistemas de Redes', 'TAR3'),
('23374', 'Anabela Mavale', 'Gestão', 'TG4'),
('23389', 'Manuel Zavale', 'Electricidade Industrial', 'TEI2'),
('23402', 'Verónica Cossa', 'Gestão Patrimonial e Financeira', 'TGPF3'),
('23415', 'Gerson Uamusse', 'Electricidade e Electrónica Industrial', 'TEE2'),
('23421', 'Domingos Cumbane', 'Construção Civil', 'TCC3'),
('23433', 'Tânia Nhantumbo', 'Gestão de Recursos Humanos', 'TGRH3'),
('23447', 'Salvador Cuco', 'Programação de Aplicações Web', 'TPW4'),
('23455', 'Clara Matusse', 'Contabilidade', 'TC4'),
('23468', 'Lázaro Mabote', 'Técnico de Suporte Informático', 'TSI3'),
('23472', 'Cremildo Zandamela', 'Administração de Sistemas de Redes', 'TAR3'),
('23485', 'Celina Chiziane', 'Gestão', 'TG4'),
('23496', 'Mateus Manjate', 'Electricidade Industrial', 'TEI3'),
('23504', 'Dina Mavale', 'Gestão Patrimonial e Financeira', 'TGPF3');

-- === Visitas (exemplo aleatório) ===
INSERT INTO visitas (estudante_id, data_visita, hora_entrada, hora_saida, origem)
VALUES
(1, '2025-01-15', '2025-01-15 08:10:00', '2025-01-15 09:05:00', 'kiosk'),
(2, '2025-02-20', '2025-02-20 10:00:00', '2025-02-20 11:20:00', 'bibliotecario'),
(3, '2025-03-05', '2025-03-05 09:15:00', '2025-03-05 09:45:00', 'kiosk'),
(4, '2025-03-17', '2025-03-17 14:00:00', '2025-03-17 15:30:00', 'kiosk'),
(5, '2025-04-02', '2025-04-02 07:50:00', '2025-04-02 08:40:00', 'bibliotecario'),
(6, '2025-04-10', '2025-04-10 09:30:00', '2025-04-10 10:15:00', 'kiosk'),
(7, '2025-05-12', '2025-05-12 08:00:00', '2025-05-12 09:10:00', 'kiosk'),
(8, '2025-05-22', '2025-05-22 10:20:00', '2025-05-22 11:00:00', 'bibliotecario'),
(9, '2025-06-03', '2025-06-03 09:00:00', '2025-06-03 09:40:00', 'kiosk'),
(10, '2025-06-18', '2025-06-18 14:15:00', '2025-06-18 15:05:00', 'kiosk');

-- Sessões de login (exemplo)
INSERT INTO sessoes (usuario_id, token, user_agent, ip, data_expiracao)
VALUES
(1, SHA2('adminsession1', 256), 'Mozilla/5.0', '127.0.0.1', DATE_ADD(NOW(), INTERVAL 1 DAY)),
(2, SHA2('helenasession1', 256), 'Mozilla/5.0', '127.0.0.1', DATE_ADD(NOW(), INTERVAL 1 DAY));


-- === Logs (ações de sistema) ===
INSERT INTO logs (actor_type, action, details) VALUES
('sistema', 'login_sucesso', 'Sessão iniciada no quiosque'),
('sistema', 'registro_visita', 'Visita de estudante código 23201'),
('sistema', 'registro_visita', 'Visita de estudante código 23203'),
('sistema', 'saida_registrada', 'Estudante 23204 saiu às 15h10'),
('sistema', 'erro_autenticacao', 'Tentativa de login falhada no quiosque'),
('sistema', 'registro_visita', 'Visita de estudante código 23207'),
('sistema', 'saida_registrada', 'Estudante 23205 saiu às 10h45'),
('sistema', 'registro_visita', 'Visita de estudante código 23208'),
('sistema', 'login_sucesso', 'Bibliotecário autenticado no sistema'),
('sistema', 'logout', 'Sessão encerrada com sucesso');
