<?php
require 'config/app.php';
require 'config/db.php';

require_auth();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Buscar categorias de despesa do utilizador
try {
    $stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE user_id = :user_id AND tipo = 'despesa' ORDER BY nome");
    $stmt->execute(['user_id' => $user_id]);
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Sessao expirada. Atualize a pagina e tente novamente.';
    }

    $descricao = trim($_POST['descricao'] ?? '');
    $valor = trim($_POST['valor'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $data = trim($_POST['data'] ?? '');

    // Validações
    if ($error === '' && (empty($descricao) || empty($valor) || $categoria === '' || empty($data))) {
        $error = "Todos os campos são obrigatórios.";
    } elseif ($error === '' && (!is_numeric($valor) || $valor <= 0)) {
        $error = "O valor deve ser um número positivo.";
    } elseif ($error === '' && strlen($descricao) > 255) {
        $error = "A descrição não pode ter mais de 255 caracteres.";
    } elseif ($error === '') {
        try {
            $valor = floatval($valor);
            $categoria_id = intval($categoria);

            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = :categoria_id AND user_id = :user_id AND tipo = 'despesa' LIMIT 1");
            $stmt->execute([
                'categoria_id' => $categoria_id,
                'user_id' => $user_id
            ]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('Categoria invalida para o utilizador.');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO transacoes (user_id, tipo, descricao, valor, categoria_id, data)
                 VALUES (:user_id, 'despesa', :descricao, :valor, :categoria_id, :data)"
            );
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
            $stmt->bindParam(':valor', $valor, PDO::PARAM_STR);
            $stmt->bindParam(':categoria_id', $categoria_id, PDO::PARAM_INT);
            $stmt->bindParam(':data', $data, PDO::PARAM_STR);
            $stmt->execute();

            $success = "Despesa adicionada com sucesso!";
            header("refresh:2;url=dashboard.php");
        } catch (Throwable $e) {
            error_log('Erro ao adicionar despesa: ' . $e->getMessage());
            $error = "Erro ao adicionar despesa. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Despesa - Minhas Economias</title>
    <link rel="stylesheet" href="assets/css/site-enhancements.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap');

        :root {
            --brand: #0f7a4d;
            --brand-dark: #0c6c45;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
            --success: #0f9b4d;
            --success-bg: #f0fdf4;
            --text-dark: #1f1a17;
            --text-muted: #5b524a;
            --border: #e7ddcf;
            --bg-light: #f8f3ea;
            --radius: 14px;
            --shadow-sm: 0 8px 18px rgba(31, 26, 23, 0.08);
            --shadow-md: 0 18px 40px rgba(31, 26, 23, 0.14);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lexend', "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top, rgba(214, 162, 27, 0.14), transparent 45%),
                        var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            line-height: 1.5;
        }

        header {
            background: linear-gradient(135deg, var(--danger) 0%, #991b1b 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 1.6rem;
            font-weight: 600;
            margin: 0;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .btn-back {
            color: white;
            text-decoration: none;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .form-card {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .form-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .form-subtitle {
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
            background: var(--danger-bg);
            color: var(--danger);
            border-left-color: var(--danger);
        }

        .success-message {
            background: var(--success-bg);
            color: var(--success);
            border-left-color: var(--success);
        }

        .form-group {
            margin-bottom: 1.8rem;
        }

        label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--bg-light);
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--danger);
            background: white;
            box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.12);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .help-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.4rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 13px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--danger) 0%, #991b1b 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(185, 28, 28, 0.28);
        }

        .btn-cancel {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1.5px solid var(--border);
        }

        .btn-cancel:hover {
            border-color: var(--text-dark);
            background: white;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .form-card {
                padding: 1.5rem;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <h1>💸 Adicionar Despesa</h1>
        <a href="dashboard.php" class="btn-back">← Voltar</a>
    </div>
</header>

<div class="container">
    <div class="form-card">
        <h2 class="form-title">Nova Despesa</h2>
        <p class="form-subtitle">Registe uma nova despesa na sua conta</p>

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
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="form-group">
                <label for="descricao">Descrição da Despesa *</label>
                <input 
                    type="text" 
                    id="descricao" 
                    name="descricao" 
                    placeholder="Ex: Compras no supermercado"
                    required
                    maxlength="255"
                    autofocus
                >
                <div class="help-text">Máximo 255 caracteres</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="valor">Valor (€) *</label>
                    <input 
                        type="number" 
                        id="valor" 
                        name="valor" 
                        placeholder="0.00"
                        required
                        min="0.01"
                        step="0.01"
                    >
                </div>

                <div class="form-group">
                    <label for="categoria">Categoria *</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Escolha uma categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['id']) ?>">
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="data">Data da Despesa *</label>
                <input 
                    type="date" 
                    id="data" 
                    name="data" 
                    required
                    value="<?= date('Y-m-d') ?>"
                >
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-submit">
                    💸 Adicionar Despesa
                </button>
                <a href="dashboard.php" class="btn btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
