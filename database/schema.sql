CREATE DATABASE IF NOT EXISTS gestao_tornearia
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestao_tornearia;

-- CRUD 1: CADASTRO DE EMPRESAS/CLIENTES
CREATE TABLE IF NOT EXISTS empresas (
  id_empresa INT AUTO_INCREMENT PRIMARY KEY,
  nome_empresa VARCHAR(255) NOT NULL,
  cnpj VARCHAR(20) NOT NULL UNIQUE,
  cidade VARCHAR(100),
  endereco VARCHAR(255),
  telefone VARCHAR(20),
  email VARCHAR(100),
  status ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo'
);

-- CRUD 2: SERVICOS / PEDIDOS
CREATE TABLE IF NOT EXISTS servicos (
  id_servico INT AUTO_INCREMENT PRIMARY KEY,
  id_empresa INT NOT NULL,
  data_servico DATE NOT NULL,
  valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  descricao TEXT,
  status ENUM('Pendente', 'Em Andamento', 'Concluído', 'Cancelado') NOT NULL DEFAULT 'Pendente',
  FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa)
);

-- CRUD 3: ESTOQUE
CREATE TABLE IF NOT EXISTS estoque (
  id_estoque INT AUTO_INCREMENT PRIMARY KEY,
  nome_produto VARCHAR(255) NOT NULL,
  preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  quantidade INT NOT NULL DEFAULT 0
);

-- Tabela auxiliar: produtos usados em cada servico
CREATE TABLE IF NOT EXISTS servico_produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_servico INT NOT NULL,
  id_estoque INT NOT NULL,
  quantidade INT NOT NULL DEFAULT 1,
  FOREIGN KEY (id_servico) REFERENCES servicos(id_servico) ON DELETE CASCADE,
  FOREIGN KEY (id_estoque) REFERENCES estoque(id_estoque)
);

-- ============================================================
-- CTEs 

CREATE OR REPLACE VIEW vw_dashboard_indicadores AS
WITH receita_por_status AS (
  SELECT
    status,
    COUNT(*) AS total_servicos,
    COALESCE(SUM(valor_total), 0) AS receita
  FROM servicos
  GROUP BY status
),
produtos_mais_usados AS (
  SELECT
    e.nome_produto,
    COALESCE(SUM(sp.quantidade), 0) AS total_utilizado
  FROM estoque e
  LEFT JOIN servico_produtos sp ON sp.id_estoque = e.id_estoque
  GROUP BY e.id_estoque, e.nome_produto
),
empresas_ranking AS (
  SELECT
    emp.nome_empresa,
    COUNT(s.id_servico) AS total_servicos
  FROM empresas emp
  LEFT JOIN servicos s ON s.id_empresa = emp.id_empresa
  GROUP BY emp.id_empresa, emp.nome_empresa
)
SELECT
  (SELECT COALESCE(SUM(receita), 0) FROM receita_por_status) AS receita_total,
  (SELECT COALESCE(SUM(total_servicos), 0) FROM receita_por_status) AS total_servicos,
  (SELECT COALESCE(SUM(total_servicos), 0) FROM receita_por_status WHERE status = 'Pendente') AS servicos_pendentes,
  (SELECT COUNT(*) FROM estoque WHERE quantidade <= 5) AS produtos_estoque_baixo,
  (SELECT nome_produto FROM produtos_mais_usados ORDER BY total_utilizado DESC, nome_produto ASC LIMIT 1) AS produto_mais_usado,
  (SELECT nome_empresa FROM empresas_ranking ORDER BY total_servicos DESC, nome_empresa ASC LIMIT 1) AS empresa_destaque;

CREATE OR REPLACE VIEW vw_servicos_por_status AS
SELECT
  status,
  COUNT(*) AS quantidade,
  COALESCE(SUM(valor_total), 0) AS receita
FROM servicos
GROUP BY status
ORDER BY FIELD(status, 'Pendente', 'Em Andamento', 'Concluído', 'Cancelado');
-- ============================================================

-- ===========================================================
-- TRIGGERS BEFORE UPDATE
-- Padroniza valores positivos ao atualizar registros

DROP TRIGGER IF EXISTS trg_estoque_before_update;
DROP TRIGGER IF EXISTS trg_servicos_before_update;
DROP TRIGGER IF EXISTS trg_servico_produtos_before_update;

DELIMITER $$

CREATE TRIGGER trg_estoque_before_update
BEFORE UPDATE ON estoque
FOR EACH ROW
BEGIN
    IF NEW.preco <= 0 THEN
        SET NEW.preco = OLD.preco;
    END IF;

    IF NEW.quantidade < 0 THEN
        SET NEW.quantidade = 0;
    END IF;
END$$

CREATE TRIGGER trg_servicos_before_update
BEFORE UPDATE ON servicos
FOR EACH ROW
BEGIN
    IF NEW.valor_total < 0 THEN
        SET NEW.valor_total = OLD.valor_total;
    END IF;
END$$

CREATE TRIGGER trg_servico_produtos_before_update
BEFORE UPDATE ON servico_produtos
FOR EACH ROW
BEGIN
    IF NEW.quantidade <= 0 THEN
        SET NEW.quantidade = 1;
    END IF;
END$$

DELIMITER ;
-- ============================================================

-- ============================================================
-- DADOS INICIAIS (mockup do dashboard)

DELETE FROM servico_produtos;
DELETE FROM servicos;
DELETE FROM estoque;
DELETE FROM empresas;

INSERT INTO empresas (nome_empresa, cnpj, endereco, telefone, email) VALUES
('Metalúrgica Silva', '12.345.678/0001-90', 'Rua das Indústrias, 500', '(11) 3456-7890', 'contato@metalurgicasilva.com.br'),
('Indústria Mecânica Oliveira', '98.765.432/0001-10', 'Av. Brasil, 1200', '(41) 99887-6543', 'compras@imec.com.br');

INSERT INTO estoque (nome_produto, preco, quantidade) VALUES
('Eixo usinado 50mm', 85.00, 25),
('Flange 100mm', 120.00, 3),
('Rotor usinado', 350.00, 2),
('Bucha de bronze', 45.50, 50);

INSERT INTO servicos (id_empresa, data_servico, status, valor_total, descricao) VALUES
(1, '2026-07-10', 'Concluído', 1500.00, 'Usinagem de eixos e flanges'),
(1, '2026-07-15', 'Em Andamento', 1500.00, 'Fabricação de buchas especiais'),
(2, '2026-07-20', 'Pendente', 1000.00, 'Orçamento para usinagem de rotores');

INSERT INTO servico_produtos (id_servico, id_estoque, quantidade) VALUES
(1, 1, 10),
(1, 2, 3),
(2, 4, 8),
(3, 3, 1);
