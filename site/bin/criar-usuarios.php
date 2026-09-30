<?php
require __DIR__ . '/../includes/db.php';

$conexao = db();
if (!$conexao) {
    fwrite(STDERR, "banco ainda não aceita conexão\n");
    exit(1);
}

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

exit(0);
