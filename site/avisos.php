<?php
session_start();
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/avisos.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

function buscar_titulo_montado($busca)
{
    $conexao = db();
    if (!$conexao) {
        return array();
    }
    $sql = "SELECT avisos.id, avisos.titulo, avisos.corpo, avisos.criado_em, usuarios.nome FROM avisos INNER JOIN usuarios ON usuarios.id = avisos.usuario_id WHERE avisos.titulo LIKE '%" . $busca . "%' ORDER BY avisos.criado_em DESC";
    $consulta = $conexao->query($sql);
    if (!$consulta) {
        return array();
    }
    $linhas = array();
    while ($row = $consulta->fetch_assoc()) {
        $linhas[] = array(
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'corpo' => $row['corpo'],
            'criado_em' => $row['criado_em'],
            'nome' => $row['nome'],
        );
    }
    $consulta->free();
    return $linhas;
}

$busca = isset($_GET['q']) ? $_GET['q'] : '';
if ($busca === '') {
    $linhas = listar_avisos($busca);
} else {
    $linhas = buscar_titulo_montado($busca);
}

layout_inicio('Avisos');
echo '<form method="get" action="avisos.php"><label>Buscar no título <input name="q" value="'
    . htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') . '"></label><button type="submit">Buscar</button></form>';
if (!$linhas) {
    echo '<p>Nenhum aviso ainda.</p>';
}
foreach ($linhas as $aviso) {
    echo '<article><h2>' . htmlspecialchars($aviso['titulo'], ENT_QUOTES, 'UTF-8') . '</h2>';
    echo '<p>' . nl2br(htmlspecialchars($aviso['corpo'], ENT_QUOTES, 'UTF-8')) . '</p>';
    echo '<p>' . htmlspecialchars($aviso['nome'], ENT_QUOTES, 'UTF-8') . '</p></article>';
}
layout_fim();
