<?php
// Configurações do banco de dados
$host = 'localhost'; // Endereço do servidor do banco de dados
$dbname = 'projeto_tig'; // Nome do banco de dados
$username = 'projeto_tig_usr'; // Usuário do banco de dados
$password = 'Q69N]ik->(}5U+HO'; // Senha do banco de dados

try {
    // Cria uma nova conexão PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Configura o modo de erro do PDO para exceções
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Em caso de erro, exibe a mensagem e encerra o script
    die("Erro ao conectar ao banco de dados: " . htmlspecialchars($e->getMessage()));
}
?>