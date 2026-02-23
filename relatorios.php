<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes WHERE user_id = :user_id AND tipo = 'receita'");
    $stmt->execute(['user_id' => $user_id]);
    $total_receitas = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes WHERE user_id = :user_id AND tipo = 'despesa'");
    $stmt->execute(['user_id' => $user_id]);
    $total_despesas = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT categoria, SUM(valor) as total
        FROM transacoes
        WHERE user_id = :user_id AND tipo = 'despesa'
        GROUP BY categoria
        ORDER BY total DESC
        LIMIT 8
    ");
    $stmt->execute(['user_id' => $user_id]);
    $top_categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    $total_receitas = 0;
    $total_despesas = 0;
    $top_categorias = [];
}

$saldo = $total_receitas - $total_despesas;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Minhas Economias</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap');

        :root {
            --brand: #0f7a4d;
            --brand-dark: #0c6c45;
            --accent: #d6a21b;
            --success: #0f9b4d;
            --danger: #b91c1c;
            --gray-50: #fffdfa;
            --gray-100: #f8f3ea;
            --gray-200: #e7ddcf;
            --gray-600: #5b524a;
            --gray-800: #1f1a17;
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
            background: radial-gradient(circle at top, rgba(214, 162, 27, 0.12), transparent 45%),
                        var(--gray-100);
            color: var(--gray-800);
            min-height: 100vh;
            line-height: 1.5;
        }

        header {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
        }

        .header-left h1 {
            font-size: 1.6rem;
            font-weight: 700;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .header-left p {
            font-size: 0.9rem;
            opacity: 0.95;
        }

        .btn-back {
            color: white;
            text-decoration: none;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.4rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            padding: 1.4rem;
        }

        .card h3 {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-600);
            margin-bottom: 0.8rem;
        }

        .card .value {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .positive { color: var(--success); }
        .negative { color: var(--danger); }

        .panel {
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .panel-header {
            padding: 1rem 1.4rem;
            background: #f3eadb;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 700;
            color: var(--gray-800);
        }

        .panel-body {
            padding: 1.2rem 1.4rem;
        }

        .category-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .category-row:last-child {
            border-bottom: none;
        }

        .category-row span {
            color: var(--gray-600);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-left">
            <h1>Relatórios</h1>
            <p>Resumo financeiro e categorias principais</p>
        </div>
        <a href="dashboard.php" class="btn-back">← Voltar</a>
    </div>
</header>

<div class="container">
    <div class="grid">
        <div class="card">
            <h3>Total de entradas</h3>
            <div class="value positive"><?= number_format($total_receitas, 2, ',', '.') ?> €</div>
        </div>
        <div class="card">
            <h3>Total de saídas</h3>
            <div class="value negative"><?= number_format($total_despesas, 2, ',', '.') ?> €</div>
        </div>
        <div class="card">
            <h3>Saldo geral</h3>
            <div class="value <?= $saldo >= 0 ? 'positive' : 'negative' ?>">
                <?= number_format($saldo, 2, ',', '.') ?> €
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">Top categorias de despesas</div>
        <div class="panel-body">
            <?php if (!empty($top_categorias)): ?>
                <?php foreach ($top_categorias as $cat): ?>
                    <div class="category-row">
                        <span><?= htmlspecialchars($cat['categoria'] ?? 'Sem categoria') ?></span>
                        <strong class="negative"><?= number_format($cat['total'], 2, ',', '.') ?> €</strong>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--gray-600);">Sem dados suficientes para exibir categorias.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
