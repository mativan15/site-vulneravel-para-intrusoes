<?php
session_start();
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
exigir_login();
$usuario = usuario_atual();
layout_inicio('Meu usuário');
echo '<p>' . htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') . '</p>';
echo '<p>' . htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') . '</p>';
layout_fim();
