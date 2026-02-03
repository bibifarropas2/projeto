<?php
session_start();
require 'config/db.php';

// Se já estiver logado
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        $stmt = $pdo->prepare(
            "SELECT id, username, password FROM utilizadores WHERE username = :username"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Utilizador ou palavra-passe inválidos.";
        }
    } catch (PDOException $e) {
        $error = "Erro ao fazer login.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Minhas Economias</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap');

        :root {
            --brand: #0f7a4d;
            --brand-dark: #0c6c45;
            --accent: #d6a21b;
            --error: #b91c1c;
            --error-bg: #fef2f2;
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

        .login-wrapper {
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

        .login-graphic {
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

        .login-graphic h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .login-graphic p {
            font-size: 1.05rem;
            opacity: 0.95;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .graphic-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }

        .login-form-section {
            flex: 1;
            padding: 3.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-section h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 700;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .login-form-section > p {
            color: var(--text-muted);
            margin-bottom: 1.8rem;
            font-size: 0.95rem;
        }

        .error-message {
            background: var(--error-bg);
            color: var(--error);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 4px solid var(--error);
            line-height: 1.4;
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

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(15, 122, 77, 0.28);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .register-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            text-align: center;
        }

        .register-section p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .register-section a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .register-section a:hover {
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
            .login-wrapper {
                flex-direction: column;
            }

            .login-graphic {
                padding: 2rem;
            }

            .login-graphic h2 {
                font-size: 1.6rem;
            }

            .login-form-section {
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

<div class="login-wrapper">
    <div class="login-graphic">
        <div class="graphic-icon">💰</div>
        <h2>Bem-vindo de volta!</h2>
        <p>Aceda à sua conta e gerencie suas finanças de forma inteligente.</p>
    </div>

    <div class="login-form-section">
        <h3>Fazer Login</h3>
        <p>Insira suas credenciais para continuar</p>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Nome de Utilizador</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="exemplo: joao2024"
                    required 
                    autocomplete="username"
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Palavra-passe</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••"
                    required 
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn-login">Entrar na Conta</button>
        </form>

        <div class="register-section">
            <p>Ainda não tens conta?</p>
            <a href="registar.php">Criar conta gratuita</a>
        </div>
    </div>
</div>

</body>
</html>
