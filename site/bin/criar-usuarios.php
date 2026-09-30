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

exit(0);
