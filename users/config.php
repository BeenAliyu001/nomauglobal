<?php

// preparing  database information

$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "nomauglobal";
// $charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$dbname;";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO ($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    // message on connection error
    echo "Connection Failed" . $e->getMessage();
}

?>