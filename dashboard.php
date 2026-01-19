<?php
session_start();
require 'config/db.php';

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ==== SALDO TOTAL ====
// Total de receitas
$stmt = $pdo->prepare("SELECT SUM(valor) FROM transacoes WHERE user_id = :user_id AND tipo = 'receita'");
$stmt->execute(['user_id' => $user_id]);
$total_receitas = $stmt->fetchColumn() ?? 0;

// Total de despesas
$stmt = $pdo->prepare("SELECT SUM(valor) FROM transacoes WHERE user_id = :user_id AND tipo = 'despesa'");
$stmt->execute(['user_id' => $user_id]);
$total_despesas = $stmt->fetchColumn() ?? 0;

// Saldo atual
$saldo_atual = $total_receitas - $total_despesas;

// ==== RESUMO DO MÊS ==== 
$current_month = date('m');
$current_year = date('Y');

$stmt = $pdo->prepare("SELECT SUM(valor) FROM transacoes WHERE user_id = :user_id AND tipo = 'receita' AND MONTH(data) = :month AND YEAR(data) = :year");
$stmt->execute([
    'user_id' => $user_id,
    'month' => $current_month,
    'year' => $current_year
]);
$receitas_mes = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT SUM(valor) FROM transacoes WHERE user_id = :user_id AND tipo = 'despesa' AND MONTH(data) = :month AND YEAR(data) = :year");
$stmt->execute([
    'user_id' => $user_id,
    'month' => $current_month,
    'year' => $current_year
]);
$despesas_mes = $stmt->fetchColumn() ?? 0;

$saldo_mes = $receitas_mes - $despesas_mes;

// ==== ÚLTIMAS TRANSAÇÕES ====
$stmt = $pdo->prepare("SELECT * FROM transacoes WHERE user_id = :user_id ORDER BY data DESC LIMIT 10");
$stmt->execute(['user_id' => $user_id]);
$transacoes = $stmt->fetchAll();

?><!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financeiro</title>
    
    <style>
        :root {
            --primary: #2563eb;         /* azul principal */
            --primary-dark: #1d4ed8;
            --success: #10b981;         /* verde receitas */
            --danger: #ef4444;          /* vermelho despesas */
            --warning: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
            --radius: 12px;
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-100);
            color: var(--gray-800);
            min-height: 100vh;
            line-height: 1.5;
        }

        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.2rem 2rem;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        header h1 {
            font-size: 1.6rem;
            font-weight: 600;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        nav a:hover {
            background: rgba(255,255,255,0.15);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .card {
            background: white;
            border-radius: var(--radius);
            padding: 1.8rem;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }

        .card h3 {
            font-size: 1.05rem;
            color: var(--gray-600);
            margin-bottom: 0.8rem;
            font-weight: 500;
        }

        .card p {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .positive { color: var(--success); }
        .negative { color: var(--danger); }
        .neutral   { color: var(--primary); }

        .transactions-section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .transactions-section h2 {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1.1rem 2rem;
            text-align: left;
        }

        th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tr {
            border-bottom: 1px solid var(--gray-200);
            transition: background 0.15s;
        }

        tr:hover {
            background: var(--gray-50);
        }

        .tipo-receita { color: var(--success); font-weight: 600; }
        .tipo-despesa { color: var(--danger);  font-weight: 600; }

        .valor {
            font-weight: 600;
            text-align: right;
        }

        .data {
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            nav {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            nav a {
                padding: 0.5rem 0.9rem;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 600px) {
            .container { padding: 0 1rem; }
            th, td { padding: 1rem 1.2rem; }
            .card p { font-size: 1.7rem; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <h1>Olá, <?= htmlspecialchars($_SESSION['username']) ?></h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="add_transacao.php">+ Transação</a>
            <a href="fundos.php">Fundos</a>
            <a href="relatorios.php">Relatórios</a>
            <a href="logout.php">Sair</a>
        </nav>
    </div>
</header>

<div class="container">

    <h2>Resumo Financeiro</h2>
    <div class="stats-grid">
        <div class="card">
            <h3>Saldo Atual</h3>
            <p class="<?= $saldo_atual >= 0 ? 'positive' : 'negative' ?>">
                <?= number_format($saldo_atual, 2, ',', '.') ?> €
            </p>
        </div>

        <div class="card">
            <h3>Total Entradas</h3>
            <p class="positive"><?= number_format($total_receitas, 2, ',', '.') ?> €</p>
        </div>

        <div class="card">
            <h3>Total Saídas</h3>
            <p class="negative"><?= number_format($total_despesas, 2, ',', '.') ?> €</p>
        </div>

        <div class="card">
            <h3>Saldo de <?= date('F Y') ?></h3>
            <p class="<?= $saldo_mes >= 0 ? 'positive' : 'negative' ?>">
                <?= number_format($saldo_mes, 2, ',', '.') ?> €
            </p>
        </div>
    </div>

    <div class="transactions-section">
        <h2>Últimas 10 Transações</h2>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th class="valor">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transacoes as $t): ?>
                    <tr>
                        <td class="data"><?= htmlspecialchars($t['data']) ?></td>
                        <td><?= htmlspecialchars($t['descricao']) ?></td>
                        <td class="<?= $t['tipo'] === 'receita' ? 'tipo-receita' : 'tipo-despesa' ?>">
                            <?= ucfirst($t['tipo']) ?>
                        </td>
                        <td class="valor <?= $t['tipo'] === 'receita' ? 'positive' : 'negative' ?>">
                            <?= number_format($t['valor'], 2, ',', '.') ?> €
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>