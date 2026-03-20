<?php
$_db_host = 'localhost';
$_db_name = 'projeto_tig';
$_db_user = 'projeto_tig_usr';
$_db_pass = 'Q69N]ik->(}5U+HO';

try {
    $pdo = new PDO(
        "mysql:host=$_db_host;dbname=$_db_name;charset=utf8mb4",
        $_db_user,
        $_db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro na ligação à base de dados.");
}

// Limpa variáveis sensíveis da memória
unset($_db_host, $_db_name, $_db_user, $_db_pass);