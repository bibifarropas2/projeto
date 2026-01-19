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
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    
    <style>
        :root {
            --primary:    #3b82f6;     /* azul principal vibrante */
            --primary-dark: #2563eb;   /* hover / active */
            --primary-light: #60a5fa;
            --bg-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #475569;
            --border: #e2e8f0;
            --error: #ef4444;
            --error-bg: #fef2f2;
            --radius: 16px;
            --shadow: 0 15px 35px rgba(30, 58, 138, 0.18);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-dark);
        }

        .login-container {
            background: var(--card-bg);
            width: 100%;
            max-width: 440px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
            animation: fadeInUp 0.7s ease-out;
        }

        .login-header {
            background: var(--primary);
            color: white;
            padding: 2.4rem 2rem;
            text-align: center;
        }

        .login-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .login-body {
            padding: 2.8rem 2.4rem 2.4rem;
        }

        .error-message {
            background: var(--error-bg);
            color: var(--error);
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 1.8rem;
            font-size: 0.96rem;
            border-left: 5px solid var(--error);
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 1.9rem;
        }

        label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.97rem;
        }

        input {
            width: 100%;
            padding: 15px 18px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 1.03rem;
            background: #f8fafc;
            transition: all 0.25s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.18);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.4rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.35);
        }

        .register-link {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.98rem;
        }

        .register-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .register-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to   { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        @media (max-width: 480px) {
            .login-body {
                padding: 2.2rem 1.8rem;
            }
            .login-header {
                padding: 2rem 1.6rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h2>Entrar</h2>
    </div>

    <div class="login-body">

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Utilizador</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Seu nome de utilizador"
                    required 
                    autocomplete="username"
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

            <button type="submit" class="btn">Entrar</button>
        </form>

        <div class="register-link">
            Ainda não tens conta? <a href="registar.php">Criar conta</a>
        </div>

    </div>
</div>

</body>
</html>