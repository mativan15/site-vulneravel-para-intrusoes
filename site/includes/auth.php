<?php
require_once __DIR__ . '/db.php';

function usuario_atual()
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }
    $conexao = db();
    if (!$conexao) {
        return null;
    }
    $id = (int) $_SESSION['usuario_id'];
    $consulta = $conexao->prepare('SELECT id, nome, email FROM usuarios WHERE id = ?');
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $consulta->bind_result($uid, $nome, $email);
    $achou = $consulta->fetch();
    $consulta->close();
    if (!$achou) {
        return null;
    }
    return array('id' => $uid, 'nome' => $nome, 'email' => $email);
}

function exigir_login()
{
    if (!usuario_atual()) {
        header('Location: login.php');
        exit;
    }
}

function entrar($email, $senha)
{
    $conexao = db();
    if (!$conexao || !is_string($email) || !is_string($senha)) {
        return false;
    }
    $consulta = $conexao->prepare('SELECT id, senha_hash FROM usuarios WHERE email = ?');
    $consulta->bind_param('s', $email);
    $consulta->execute();
    $consulta->bind_result($id, $hash);
    $achou = $consulta->fetch();
    $consulta->close();
    if (!$achou || !password_verify($senha, $hash)) {
        return false;
    }
    $_SESSION['usuario_id'] = $id;
    return true;
}

function definir_sessao_por_email($email)
{
    $conexao = db();
    if (!$conexao || !is_string($email) || $email === '') {
        return false;
    }
    $consulta = $conexao->prepare('SELECT id FROM usuarios WHERE email = ?');
    $consulta->bind_param('s', $email);
    $consulta->execute();
    $consulta->bind_result($id);
    $achou = $consulta->fetch();
    $consulta->close();
    if (!$achou) {
        return false;
    }
    $_SESSION['usuario_id'] = $id;
    return true;
}

function sair()
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
