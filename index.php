<?php
session_start();

// Se já estiver logado, redireciona para o dashboard
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

// Redireciona para a landing page
header("Location: landing.php");
exit();
?>
