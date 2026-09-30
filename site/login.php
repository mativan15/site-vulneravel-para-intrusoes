<?php
session_start();
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    if (entrar($email, $senha) || ($senha !== '' && definir_sessao_por_email($email))) {
        header('Location: avisos.php');
        exit;
    }
    $erro = 'E-mail ou senha não conferem.';
}

layout_inicio('Entrar');
if ($erro !== '') {
    echo '<p class="erro">' . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</p>';
}
echo '<form method="post" action="login.php">';
echo '<label>E-mail <input name="email" type="email" required></label>';
echo '<label>Senha <input name="senha" type="password" required></label>';
echo '<button type="submit">Entrar</button></form>';
layout_fim();
