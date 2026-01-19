<?php
session_start();

// Se já estiver logado, redireciona para o dashboard
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão Financeira Simplificada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f0f2f5;
            padding: 50px;
        }
        h1 {
            color: #333;
        }
        a {
            display: inline-block;
            margin: 20px;
            padding: 12px 25px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
        a:hover {
            background-color: #45a049;
        }
        p {
            font-size: 18px;
            color: #555;
        }
    </style>
</head>
<body>

    <h1>Bem-vindo ao Sistema de Gestão Financeira</h1>
    
    <p>Registe as suas receitas e despesas e controle o seu saldo de forma simples!</p>
    
    <a href="login.php">Entrar no Sistema</a>
    
</body>
</html>
