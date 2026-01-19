<?php
$host = 'localhost';
$dbname = 'projeto_tig';
$username = 'projeto_tig_usr';
$password = 'Q69N]ik->(}5U+HO';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro na ligação à base de dados.");
}
