<?php
session_start();
require 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (strlen($password) < 6) {
        $error = "A palavra-passe deve ter pelo menos 6 caracteres.";
    } else {
        try {
            // Verifica se o utilizador já existe
            $stmt = $pdo->prepare(
                "SELECT id FROM utilizadores WHERE username = :username"
            );
            $stmt->execute(['username' => $username]);

            if ($stmt->fetch()) {
                $error = "Esse utilizador já existe.";
            } else {
                // HASH DA PASSWORD
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Cria utilizador
                $stmt = $pdo->prepare(
                    "INSERT INTO utilizadores (username, password)
                     VALUES (:username, :password)"
                );
                $stmt->execute([
                    'username' => $username,
                    'password' => $hash
                ]);

                $success = "Conta criada com sucesso. Faça login.";
            }
        } catch (PDOException $e) {
            $error = "Erro ao criar conta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Criar Conta</title>
</head>
<body>

<h2>Criar Conta</h2>

<?php if (!empty($error)): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <p style="color:green"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<form method="POST">
    <input type="text" name="username" placeholder="Utilizador" required>
    <input type="password" name="password" placeholder="Palavra-passe" required>
    <button type="submit">Registar</button>
</form>

<p>Já tem conta? <a href="login.php">Login</a></p>

</body>
</html>
