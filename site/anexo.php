<?php
session_start();
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$dir = __DIR__ . '/anexos';
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Envio do arquivo falhou.';
    } else {
        $nome = basename($_FILES['arquivo']['name']);
        $destino = $dir . '/' . $nome;
        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
            $mensagem = 'Arquivo salvo em anexos/' . $nome;
        } else {
            $erro = 'Não foi possível gravar o arquivo.';
        }
    }
}

layout_inicio('Anexos');
if ($mensagem !== '') {
    echo '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>';
}
if ($erro !== '') {
    echo '<p class="erro">' . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</p>';
}
echo '<p>Envie documentos de apoio aos avisos. O arquivo fica disponível no mesmo endereço do site.</p>';
echo '<form method="post" action="anexo.php" enctype="multipart/form-data">';
echo '<label>Arquivo <input type="file" name="arquivo" required></label>';
echo '<button type="submit">Enviar</button></form>';
layout_fim();
