<?php
require_once __DIR__ . '/db.php';

function listar_avisos($busca)
{
    $conexao = db();
    if ($busca === '') {
        $sql = 'SELECT avisos.id, avisos.titulo, avisos.corpo, avisos.criado_em, usuarios.nome
                FROM avisos
                INNER JOIN usuarios ON usuarios.id = avisos.usuario_id
                ORDER BY avisos.criado_em DESC';
        $consulta = $conexao->prepare($sql);
    } else {
        $sql = 'SELECT avisos.id, avisos.titulo, avisos.corpo, avisos.criado_em, usuarios.nome
                FROM avisos
                INNER JOIN usuarios ON usuarios.id = avisos.usuario_id
                WHERE avisos.titulo LIKE ?
                ORDER BY avisos.criado_em DESC';
        $consulta = $conexao->prepare($sql);
        $termo = '%' . $busca . '%';
        $consulta->bind_param('s', $termo);
    }
    $consulta->execute();
    $consulta->bind_result($id, $titulo, $corpo, $criado_em, $nome);
    $linhas = array();
    while ($consulta->fetch()) {
        $linhas[] = array(
            'id' => $id,
            'titulo' => $titulo,
            'corpo' => $corpo,
            'criado_em' => $criado_em,
            'nome' => $nome,
        );
    }
    $consulta->close();
    return $linhas;
}

function publicar_aviso($usuario_id, $titulo, $corpo)
{
    $conexao = db();
    $consulta = $conexao->prepare('INSERT INTO avisos (usuario_id, titulo, corpo) VALUES (?, ?, ?)');
    $consulta->bind_param('iss', $usuario_id, $titulo, $corpo);
    $ok = $consulta->execute();
    $consulta->close();
    return $ok;
}
