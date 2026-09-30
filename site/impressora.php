<?php
session_start();
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
exigir_login();

$saida = '';
$host = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = isset($_POST['host']) ? $_POST['host'] : '';
    if ($host !== '') {
        $saida = shell_exec('ping -c 2 ' . $host . ' 2>&1');
    }
}

layout_inicio('Impressora');
echo '<p>Teste de alcance da impressora interna. Informe o endereço do servidor de fila.</p>';
if ($saida !== '') {
    echo '<pre class="saida">' . htmlspecialchars($saida, ENT_QUOTES, 'UTF-8') . '</pre>';
}
echo '<form method="post" action="impressora.php">';
echo '<label>Servidor <input name="host" value="' . htmlspecialchars($host, ENT_QUOTES, 'UTF-8') . '" required></label>';
echo '<button type="submit">Testar</button></form>';
layout_fim();
