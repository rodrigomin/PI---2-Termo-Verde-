<?php
// Lista demandas aptas ao envio para o CEPE, respeitando o acesso por empresa.
function ep_listar_demandas_para_envio(
    string $pesquisa = '',
    string $ordenacao = 'antigos',
    ?int $empresaId = null
): array {
    $direcao = $ordenacao === 'recentes' ? 'DESC' : 'ASC';

    $sql = 'SELECT p.`proposta_id`, p.`título`, p.`descricão_problema`,
                   p.`data_cadastro`, e.`nome_fantasia`
            FROM `Propostas_Externas` p
            LEFT JOIN `Empresas_Parceiras` e ON e.`empresa_id` = p.`empresa_id`
            WHERE p.`status` = :status
              AND NOT EXISTS (
                  SELECT 1 FROM `Encaminhamentos_Projetos` enc
                  WHERE enc.`proposta_id` = p.`proposta_id`
              )';
    $parametros = ['status' => 'Em triagem'];

    if ($pesquisa !== '') {
        $sql .= ' AND LOCATE(:pesquisa, p.`título`) > 0';
        $parametros['pesquisa'] = $pesquisa;
    }

    if ($empresaId !== null) {
        $sql .= ' AND p.`empresa_id` = :empresa_id';
        $parametros['empresa_id'] = $empresaId;
    }

    // A direção é escolhida exclusivamente entre dois valores conhecidos.
    $sql .= ' ORDER BY p.`data_cadastro` ' . $direcao
          . ', p.`proposta_id` ' . $direcao;

    $consulta = ep_db()->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->fetchAll();
}

/**
 * Registra o encaminhamento uma única vez e muda o status para Submetido.
 * A transação bloqueia a proposta para impedir dois envios concorrentes.
 */
function ep_encaminhar_demanda(int $propostaId, array $usuario): int
{
    if ($propostaId <= 0) {
        throw new InvalidArgumentException('Selecione uma demanda válida.');
    }

    $perfil = (string) ($usuario['perfil'] ?? '');
    $empresaUsuario = (int) ($usuario['empresa_id'] ?? 0);
    if ($perfil !== 'administrador' && ($perfil !== 'representante' || $empresaUsuario <= 0)) {
        throw new DomainException('Você não possui permissão para enviar demandas.');
    }

    $db = ep_db();
    $db->beginTransaction();

    try {
        $consulta = $db->prepare(
            'SELECT `empresa_id`, `status`
             FROM `Propostas_Externas`
             WHERE `proposta_id` = :id
             FOR UPDATE'
        );
        $consulta->execute(['id' => $propostaId]);
        $demanda = $consulta->fetch();

        if (!$demanda || (
            $perfil !== 'administrador'
            && (int) $demanda['empresa_id'] !== $empresaUsuario
        )) {
            throw new DomainException('Demanda não encontrada ou sem permissão de acesso.');
        }

        if ($demanda['status'] !== 'Em triagem') {
            throw new DomainException('Esta demanda não está disponível para envio.');
        }

        $consulta = $db->prepare(
            'SELECT `encaminhamento_id`
             FROM `Encaminhamentos_Projetos`
             WHERE `proposta_id` = :id LIMIT 1'
        );
        $consulta->execute(['id' => $propostaId]);
        if ($consulta->fetchColumn() !== false) {
            throw new DomainException('Esta demanda já foi encaminhada.');
        }

        // Unidade e projeto CEPE não constam no formulário: permanecem NULL.
        $consulta = $db->prepare(
            'INSERT INTO `Encaminhamentos_Projetos`
                (`unidade_id`, `destino_cepe`, `projeto_cepe_id`,
                 `data_encaminhamento`, `proposta_id`)
             VALUES (NULL, :destino, NULL, NOW(2), :proposta_id)'
        );
        $consulta->execute([
            'destino' => 'CEPE',
            'proposta_id' => $propostaId,
        ]);
        $encaminhamentoId = (int) $db->lastInsertId();

        $consulta = $db->prepare(
            'UPDATE `Propostas_Externas`
             SET `status` = :status
             WHERE `proposta_id` = :id AND `status` = :status_anterior'
        );
        $consulta->execute([
            'status' => 'Submetido',
            'id' => $propostaId,
            'status_anterior' => 'Em triagem',
        ]);

        if ($consulta->rowCount() !== 1) {
            throw new RuntimeException('Não foi possível atualizar o status da demanda.');
        }

        $db->commit();
        return $encaminhamentoId;
    } catch (Throwable $erro) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $erro;
    }
}
