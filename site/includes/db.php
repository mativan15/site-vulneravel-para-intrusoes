<?php
function db()
{
    static $mysqli = null;
    if ($mysqli instanceof mysqli) {
        return $mysqli;
    }
    $mysqli = new mysqli('db', 'app', 'app', 'app');
    if ($mysqli->connect_errno) {
        return null;
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}
