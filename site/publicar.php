<?php
session_start();
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/avisos.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $corpo = isset($_POST['corpo']) ? trim($_POST['corpo']) : '';
    if ($titulo === '' || $corpo === '') {
        $erro = 'Título e texto são obrigatórios.';
    } else {
        $usuario = usuario_atual();
        publicar_aviso($usuario['id'], $titulo, $corpo);
        header('Location: avisos.php');
        exit;
    }
}

layout_inicio('Publicar');
if ($erro !== '') {
    echo '<p class="erro">' . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</p>';
}
echo '<form method="post" action="publicar.php">';
echo '<label>Título <input name="titulo" required></label>';
echo '<label>Texto <textarea name="corpo" rows="6" required></textarea></label>';
echo '<button type="submit">Publicar</button></form>';
layout_fim();
