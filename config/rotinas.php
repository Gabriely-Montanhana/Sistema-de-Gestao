<?php

declare(strict_types=1);

function garantir_rotinas_sql(PDO $pdo): void
{
    $pdo->exec('DROP FUNCTION IF EXISTS fn_nivel_estoque');
    $pdo->exec("
        CREATE FUNCTION fn_nivel_estoque(p_quantidade INT)
        RETURNS VARCHAR(10) CHARSET utf8mb4
        DETERMINISTIC
        BEGIN
            IF p_quantidade <= 5 THEN
                RETURN 'baixo';
            END IF;
            RETURN 'normal';
        END
    ");

    // Recria a procedure de entrada/saída de estoque (o PHP chama com CALL).
    $pdo->exec('DROP PROCEDURE IF EXISTS sp_registrar_movimento');
    $pdo->exec("
        CREATE PROCEDURE sp_registrar_movimento(
            IN p_id_estoque INT,
            IN p_tipo VARCHAR(10),
            IN p_quantidade INT
        )
        BEGIN
            DECLARE v_atual INT DEFAULT NULL;
            DECLARE v_novo INT;

            IF p_quantidade <= 0 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Informe uma quantidade maior que zero.';
            END IF;

            IF p_tipo NOT IN ('entrada', 'saida') THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tipo de movimentação inválido.';
            END IF;

            SELECT quantidade INTO v_atual
            FROM estoque
            WHERE id_estoque = p_id_estoque
            LIMIT 1;

            IF v_atual IS NULL THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto não encontrado.';
            END IF;

            IF p_tipo = 'entrada' THEN
                SET v_novo = v_atual + p_quantidade;
            ELSE
                SET v_novo = v_atual - p_quantidade;
            END IF;

            IF v_novo < 0 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quantidade insuficiente em estoque para esta saída.';
            END IF;

            UPDATE estoque
            SET quantidade = v_novo
            WHERE id_estoque = p_id_estoque;

            INSERT INTO estoque_movimentos (id_estoque, tipo, quantidade)
            VALUES (p_id_estoque, p_tipo, p_quantidade);
        END
    ");
}
