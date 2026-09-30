<?php
function layout_inicio($titulo)
{
    $usuario = function_exists('usuario_atual') ? usuario_atual() : null;
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . htmlspecialchars($titulo . ' — USP Avisos', ENT_QUOTES, 'UTF-8') . '</title>';
    echo '<link rel="stylesheet" href="css/estilo.css"></head><body>';
    echo '<header class="topo"><div class="miolo">';
    echo '<p class="marca">USP Avisos</p><h1>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>';
    if ($usuario) {
        echo '<nav><a href="avisos.php">Avisos</a> <a href="publicar.php">Publicar</a> <a href="impressora.php">Impressora</a> <a href="modelo.php">Modelos</a> <a href="anexo.php">Anexos</a> <a href="usuario.php">Meu usuário</a> <a href="sair.php">Sair</a></nav>';
    }
    echo '</div></header><main class="miolo">';
}

function layout_fim()
{
    echo '</main></body></html>';
}
