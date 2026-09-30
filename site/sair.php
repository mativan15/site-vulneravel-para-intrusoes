<?php
session_start();
require __DIR__ . '/includes/auth.php';
sair();
header('Location: login.php');
exit;
