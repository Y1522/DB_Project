<?php

$config = require __DIR__ . '/config.php';

$mysqli = new mysqli(
    $config['host'],
    $config['username'],
    $config['password'],
    $config['database'],
    $config['port']
);

if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

