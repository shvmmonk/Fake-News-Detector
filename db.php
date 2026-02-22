<?php
$host     = "localhost";
$dbname   = "fakenews_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h3 style='color:red;font-family:monospace'>
        ❌ Database Connection Failed!<br><br>" 
        . $e->getMessage() . "
    </h3>");
}
?>