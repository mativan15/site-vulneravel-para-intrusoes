<?php
require __DIR__ . '/../includes/db.php';

$conexao = db();
if (!$conexao) {
    fwrite(STDERR, "banco ainda não aceita conexão\n");
    exit(1);
}

$emails_validos = array('ivan@avisos.br', 'daniel@avisos.br', 'pedro@avisos.br');

$pessoas = array(
    array('Ivan', 'ivan@avisos.br'),
    array('Daniel', 'daniel@avisos.br'),
    array('Pedro', 'pedro@avisos.br'),
);

foreach ($pessoas as $pessoa) {
    $consulta = $conexao->prepare('SELECT id FROM usuarios WHERE email = ?');
    $consulta->bind_param('s', $pessoa[1]);
    $consulta->execute();
    $consulta->store_result();
    if ($consulta->num_rows > 0) {
        $consulta->close();
        continue;
    }
    $consulta->close();
    $hash = password_hash('aviso123', PASSWORD_DEFAULT);
    $insere = $conexao->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
    $insere->bind_param('sss', $pessoa[0], $pessoa[1], $hash);
    $insere->execute();
    $insere->close();
}

$ivan_id = null;
$busca = $conexao->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
$email_ivan = 'ivan@avisos.br';
$busca->bind_param('s', $email_ivan);
$busca->execute();
$busca->bind_result($ivan_id);
$busca->fetch();
$busca->close();

if ($ivan_id) {
    $lista = "'" . implode("','", array_map(array($conexao, 'real_escape_string'), $emails_validos)) . "'";
    $conexao->query(
        "UPDATE avisos a INNER JOIN usuarios u ON a.usuario_id = u.id "
        . "SET a.usuario_id = " . (int) $ivan_id . " WHERE u.email NOT IN ($lista)"
    );
    $conexao->query("DELETE FROM usuarios WHERE email NOT IN ($lista)");
}

$conexao->query(
    "UPDATE avisos SET corpo = 'texto interpretado' "
    . "WHERE titulo = 'Marcacao' AND corpo LIKE '%<%'"
);

function id_usuario_por_email($conexao, $email)
{
    $consulta = $conexao->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $consulta->bind_param('s', $email);
    $consulta->execute();
    $consulta->bind_result($id);
    if (!$consulta->fetch()) {
        $consulta->close();
        return null;
    }
    $consulta->close();
    return (int) $id;
}

$contagem = $conexao->query('SELECT COUNT(*) AS n FROM avisos');
$row = $contagem ? $contagem->fetch_assoc() : null;
$total_avisos = $row ? (int) $row['n'] : 0;
if ($contagem) {
    $contagem->free();
}

if ($total_avisos === 0) {
    $exemplos = array(
        array('ivan@avisos.br', 'Aviso: laboratório', 'O laboratório fica aberto para testes do projeto nesta semana.'),
        array('daniel@avisos.br', 'Aviso: senha de demo', 'A senha aviso123 vale apenas neste mural de laboratório.'),
        array('pedro@avisos.br', 'Aviso: impressora', 'Teste a impressora do corredor pelo menu Impressora.'),
        array('ivan@avisos.br', 'Aviso: publicar', 'Use Publicar para colocar um comunicado novo na lista.'),
        array('pedro@avisos.br', 'Aviso: anexos', 'Arquivos enviados em Anexos ficam disponíveis no endereço do site.'),
    );
    $insere_aviso = $conexao->prepare(
        'INSERT INTO avisos (usuario_id, titulo, corpo) VALUES (?, ?, ?)'
    );
    foreach ($exemplos as $exemplo) {
        $uid = id_usuario_por_email($conexao, $exemplo[0]);
        if (!$uid) {
            continue;
        }
        $insere_aviso->bind_param('iss', $uid, $exemplo[1], $exemplo[2]);
        $insere_aviso->execute();
    }
    $insere_aviso->close();
}

exit(0);
