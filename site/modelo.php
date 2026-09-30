<?php
session_start();
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$modelo = isset($_GET['modelo']) ? $_GET['modelo'] : 'padrao.php';

layout_inicio('Modelo de aviso');
echo '<p>Visualização de modelos usados na publicação.</p>';
echo '<p><a href="modelo.php?modelo=padrao.php">Modelo padrão</a> ';
echo '<a href="modelo.php?modelo=urgente.php">Modelo urgente</a></p>';
if (strpos($modelo, 'php://') === 0) {
    include $modelo;
} else {
    include __DIR__ . '/modelos/' . $modelo;
}
layout_fim();
