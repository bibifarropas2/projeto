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
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    // Validações
    if (strlen($username) < 3) {
        $error = "O nome de utilizador deve ter pelo menos 3 caracteres.";
    } elseif (strlen($password) < 6) {
        $error = "A palavra-passe deve ter pelo menos 6 caracteres.";
    } elseif ($password !== $password_confirm) {
        $error = "As palavras-passe não coincidem.";
    } else {
        try {
            // Verifica se o utilizador já existe
            $stmt = $pdo->prepare(
                "SELECT id FROM utilizadores WHERE username = :username"
            );
            $stmt->execute(['username' => $username]);

            if ($stmt->fetch()) {
                $error = "Esse nome de utilizador já existe.";
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

                $success = "Conta criada com sucesso! Redirecionando para login...";
                header("refresh:2;url=login.php");
            }
        } catch (PDOException $e) {
            $error = "Erro ao criar conta. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar - Minhas Economias</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap');

        :root {
            --brand: #0f7a4d;
            --brand-dark: #0c6c45;
            --accent: #d6a21b;
            --error: #b91c1c;
            --error-bg: #fef2f2;
            --success: #0f9b4d;
            --success-bg: #f0fdf4;
            --text-dark: #1f1a17;
            --text-muted: #5b524a;
            --border: #e7ddcf;
            --bg-light: #f8f3ea;
            --radius: 14px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lexend', "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top, rgba(214, 162, 27, 0.16), transparent 45%),
                        linear-gradient(135deg, #f8f3ea 0%, #f1e7d7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-dark);
        }

        .register-wrapper {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: #fffdfa;
            border-radius: var(--radius);
            box-shadow: 0 20px 60px rgba(31, 26, 23, 0.16);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            border: 1px solid var(--border);
        }

        .register-graphic {
            flex: 1;
            background: linear-gradient(135deg, #0f7a4d 0%, #0c6c45 100%);
            color: white;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .register-graphic h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .register-graphic p {
            font-size: 1.05rem;
            opacity: 0.95;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .graphic-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }

        .register-form-section {
            flex: 1;
            padding: 3.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-form-section h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 700;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .register-form-section > p {
            color: var(--text-muted);
            margin-bottom: 1.8rem;
            font-size: 0.95rem;
        }

        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 4px solid;
            line-height: 1.4;
        }

        .error-message {
            background: var(--error-bg);
            color: var(--error);
            border-left-color: var(--error);
        }

        .success-message {
            background: var(--success-bg);
            color: var(--success);
            border-left-color: var(--success);
        }

        .form-group {
            margin-bottom: 1.6rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--bg-light);
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:focus {
            outline: none;
            border-color: var(--brand);
            background: white;
            box-shadow: 0 0 0 3px rgba(15, 122, 77, 0.12);
        }

        input::placeholder {
            color: #999;
        }

        .password-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.4rem;
        }

        .btn-register {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #0f7a4d 0%, #0c6c45 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(15, 122, 77, 0.28);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .login-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            text-align: center;
        }

        .login-section p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .login-section a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .login-section a:hover {
            color: var(--brand-dark);
            text-decoration: underline;
        }

        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: var(--brand);
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.2s;
            background: rgba(15, 122, 77, 0.08);
        }

        .back-to-home:hover {
            background: rgba(15, 122, 77, 0.15);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .register-wrapper {
                flex-direction: column;
            }

            .register-graphic {
                padding: 2rem;
            }

            .register-graphic h2 {
                font-size: 1.6rem;
            }

            .register-form-section {
                padding: 2rem;
            }

            .back-to-home {
                position: static;
                display: inline-block;
                margin-bottom: 1rem;
                background: rgba(15, 122, 77, 0.1);
                color: var(--brand);
            }

            .back-to-home:hover {
                background: rgba(15, 122, 77, 0.2);
            }
        }
    </style>
</head>
<body>

<a href="landing.php" class="back-to-home">← Voltar</a>

<div class="register-wrapper">
    <div class="register-graphic">
        <div class="graphic-icon">🎉</div>
        <h2>Bem-vindo!</h2>
        <p>Crie sua conta gratuita e comece a gerir suas finanças de forma inteligente.</p>
</div>

</body>
</html>
    <div class="register-form-section">
        <h3>Criar Conta</h3>
        <p>Insira os dados para registar-se</p>

        <?php if (!empty($error)): ?>
            <div class="message error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="message success-message">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Nome de Utilizador</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Escolha um nome de utilizador"
                    required 
                    minlength="3"
                    autocomplete="username"
                    autofocus
                >
                <div class="password-hint">Mínimo 3 caracteres</div>
            </div>

            <div class="form-group">
                <label for="password">Palavra-passe</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••"
                    required 
                    minlength="6"
                    autocomplete="new-password"
                >
                <div class="password-hint">Mínimo 6 caracteres</div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmar Palavra-passe</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm" 
                    placeholder="••••••••"
                    required 
                    minlength="6"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-register">Registar-se Gratuitamente</button>
        </form>

        <div class="login-section">
            <p>Já tem conta?</p>
            <a href="login.php">Fazer login aqui</a>
        </div>
    </div>
</div>
