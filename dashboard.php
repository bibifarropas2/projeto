<?php
session_start();
require 'config/db.php';

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Meses em português
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$current_month = date('m');
$current_year = date('Y');
$mes_atual = $meses[(int)$current_month];

// ==== SALDO TOTAL ====
try {
    $stmt = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes WHERE user_id = :user_id AND tipo = 'receita'");
    $stmt->execute(['user_id' => $user_id]);
    $total_receitas = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes WHERE user_id = :user_id AND tipo = 'despesa'");
    $stmt->execute(['user_id' => $user_id]);
    $total_despesas = $stmt->fetch()['total'] ?? 0;

    $saldo_atual = $total_receitas - $total_despesas;
} catch (PDOException $e) {
    $total_receitas = 0;
    $total_despesas = 0;
    $saldo_atual = 0;
}

// ==== RESUMO DO MÊS ====
try {
    $stmt = $pdo->prepare("
        SELECT SUM(valor) as total FROM transacoes 
        WHERE user_id = :user_id AND tipo = 'receita' 
        AND MONTH(data) = :month AND YEAR(data) = :year
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'month' => $current_month,
        'year' => $current_year
    ]);
    $receitas_mes = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT SUM(valor) as total FROM transacoes 
        WHERE user_id = :user_id AND tipo = 'despesa' 
        AND MONTH(data) = :month AND YEAR(data) = :year
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'month' => $current_month,
        'year' => $current_year
    ]);
    $despesas_mes = $stmt->fetch()['total'] ?? 0;

    $saldo_mes = $receitas_mes - $despesas_mes;
} catch (PDOException $e) {
    $receitas_mes = 0;
    $despesas_mes = 0;
    $saldo_mes = 0;
}

// ==== ÚLTIMAS TRANSAÇÕES ====
try {
    $stmt = $pdo->prepare("SELECT * FROM transacoes WHERE user_id = :user_id ORDER BY data DESC LIMIT 8");
    $stmt->execute(['user_id' => $user_id]);
    $transacoes = $stmt->fetchAll();
    $count_transacoes = count($transacoes);
} catch (PDOException $e) {
    $transacoes = [];
    $count_transacoes = 0;
}

// ==== DESPESAS POR CATEGORIA (últimos 30 dias) ====
try {
    $stmt = $pdo->prepare("
        SELECT categoria, SUM(valor) as total 
        FROM transacoes 
        WHERE user_id = :user_id AND tipo = 'despesa' AND data >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY categoria
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->execute(['user_id' => $user_id]);
    $despesas_categoria = $stmt->fetchAll();
} catch (PDOException $e) {
    $despesas_categoria = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Minhas Economias</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap');

        :root {
            --brand: #0f7a4d;
            --brand-dark: #0c6c45;
            --accent: #d6a21b;
            --success: #0f9b4d;
            --danger: #b91c1c;
            --warning: #c27803;
            --info: #2563eb;
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
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .header-left h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .header-left p {
            font-size: 0.9rem;
            opacity: 0.95;
            margin-top: 0.2rem;
        }

        nav {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        nav a:hover {
            background: rgba(255,255,255,0.2);
        }

        nav a.btn-logout {
            background: rgba(214, 162, 27, 0.3);
        }

        nav a.btn-logout:hover {
            background: rgba(214, 162, 27, 0.45);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            align-items: start;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .sidebar-card {
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            padding: 1.2rem 1.4rem;
        }

        .sidebar-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .sidebar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.55rem 0;
            border-bottom: 1px dashed var(--gray-200);
            font-size: 0.92rem;
            color: var(--gray-600);
        }

        .sidebar-row:last-child {
            border-bottom: none;
        }

        .sidebar-row strong {
            color: var(--gray-800);
            font-weight: 700;
        }

        .sidebar-actions {
            display: grid;
            gap: 0.7rem;
        }

        .sidebar-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 0.9rem;
            border-radius: 10px;
            border: 1px solid var(--gray-200);
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 600;
            background: white;
            transition: all 0.2s ease;
        }

        .sidebar-actions a:hover {
            border-color: var(--brand);
            color: var(--brand);
            box-shadow: var(--shadow-sm);
        }

        .main-area {
            display: flex;
            flex-direction: column;
            gap: 1.6rem;
        }

        .panel-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.6rem;
        }

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
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .panel-body {
            padding: 1.2rem 1.4rem;
        }

        .row-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .row-item:last-child {
            border-bottom: none;
        }

        .row-item span {
            color: var(--gray-600);
        }

        .row-item strong {
            font-size: 1.05rem;
        }

        .panel-header .view-all {
            color: var(--brand);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 600;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 1.2rem;
            font-family: 'Sora', 'Lexend', sans-serif;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .card {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 1.8rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .card.balance::before { background: var(--brand); }
        .card.income::before { background: var(--success); }
        .card.expense::before { background: var(--danger); }
        .card.monthly::before { background: var(--info); }

        .card-label {
            font-size: 0.85rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.8rem;
            font-weight: 600;
        }

        .card-value {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .positive { color: var(--success); }
        .negative { color: var(--danger); }

        /* QUICK ACTIONS */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2.5rem;
        }

        .action-btn {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.2rem;
            text-align: center;
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            border-color: var(--brand);
            color: var(--brand);
            box-shadow: var(--shadow-sm);
        }

        .action-btn-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        /* CONTENT GRID */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .transactions-section, .categories-section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--gray-800);
        }

        .view-all {
            color: var(--brand);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        tr {
            border-bottom: 1px solid var(--gray-200);
            transition: background 0.15s;
        }

        tr:hover {
            background: var(--gray-50);
        }

        tr:last-child {
            border-bottom: none;
        }

        th {
            background: var(--gray-50);
            padding: 1rem 2rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-600);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1rem 2rem;
        }

        .tipo-receita { 
            color: var(--success); 
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .tipo-despesa { 
            color: var(--danger); 
            font-weight: 600;
            background: rgba(239, 68, 68, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .valor {
            font-weight: 600;
            text-align: right;
        }

        .data {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .no-transactions {
            padding: 3rem 2rem;
            text-align: center;
            color: var(--gray-600);
        }

        /* CATEGORIES */
        .category-item {
            padding: 1.2rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-name {
            font-weight: 500;
            color: var(--gray-800);
        }

        .category-amount {
            font-weight: 600;
            color: var(--danger);
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .panel-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            nav {
                gap: 0.3rem;
            }

            nav a {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }

            th, td {
                padding: 0.8rem 1rem;
                font-size: 0.9rem;
            }

            .panel-header {
                padding: 0.9rem 1.1rem;
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 1rem;
            }

            th, td {
                padding: 0.7rem 0.8rem;
            }

            .panel-body {
                padding: 1rem 1.1rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-left">
            <h1>💰 Minhas Economias</h1>
            <p>Bem-vindo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
        </div>
        <nav>
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="adicionar_receitas.php">➕ Receita</a>
            <a href="adicionar_despesa.php">➖ Despesa</a>
            <a href="logout.php" class="btn-logout">🚪 Sair</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-card">
                <div class="sidebar-title">Contas</div>
                <div class="sidebar-row">
                    <span>Saldo atual</span>
                    <strong class="<?= $saldo_atual >= 0 ? 'positive' : 'negative' ?>">
                        <?= number_format($saldo_atual, 2, ',', '.') ?> €
                    </strong>
                </div>
                <div class="sidebar-row">
                    <span>Entradas</span>
                    <strong class="positive"><?= number_format($total_receitas, 2, ',', '.') ?> €</strong>
                </div>
                <div class="sidebar-row">
                    <span>Saídas</span>
                    <strong class="negative"><?= number_format($total_despesas, 2, ',', '.') ?> €</strong>
                </div>
            </div>

            <div class="sidebar-card">
                <div class="sidebar-title">Ações rápidas</div>
                <div class="sidebar-actions">
                    <a href="adicionar_receitas.php">➕ Adicionar receita</a>
                    <a href="adicionar_despesa.php">➖ Adicionar despesa</a>
                    <a href="relatorios.php">📈 Ver relatórios</a>
                </div>
            </div>

            <div class="sidebar-card">
                <div class="sidebar-title">Resumo do mês</div>
                <div class="sidebar-row">
                    <span>Entradas</span>
                    <strong class="positive"><?= number_format($receitas_mes, 2, ',', '.') ?> €</strong>
                </div>
                <div class="sidebar-row">
                    <span>Saídas</span>
                    <strong class="negative"><?= number_format($despesas_mes, 2, ',', '.') ?> €</strong>
                </div>
                <div class="sidebar-row">
                    <span>Saldo</span>
                    <strong class="<?= $saldo_mes >= 0 ? 'positive' : 'negative' ?>">
                        <?= number_format($saldo_mes, 2, ',', '.') ?> €
                    </strong>
                </div>
            </div>
        </aside>

        <main class="main-area">
            <div class="panel-grid">
                <section class="panel">
                    <div class="panel-header">Entradas e saídas</div>
                    <div class="panel-body">
                        <div class="row-item">
                            <span>Total de entradas</span>
                            <strong class="positive"><?= number_format($total_receitas, 2, ',', '.') ?> €</strong>
                        </div>
                        <div class="row-item">
                            <span>Total de saídas</span>
                            <strong class="negative"><?= number_format($total_despesas, 2, ',', '.') ?> €</strong>
                        </div>
                        <div class="row-item">
                            <span>Saldo total</span>
                            <strong class="<?= $saldo_atual >= 0 ? 'positive' : 'negative' ?>">
                                <?= number_format($saldo_atual, 2, ',', '.') ?> €
                            </strong>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">Este mês (<?= $mes_atual ?>)</div>
                    <div class="panel-body">
                        <div class="row-item">
                            <span>Entradas do mês</span>
                            <strong class="positive"><?= number_format($receitas_mes, 2, ',', '.') ?> €</strong>
                        </div>
                        <div class="row-item">
                            <span>Saídas do mês</span>
                            <strong class="negative"><?= number_format($despesas_mes, 2, ',', '.') ?> €</strong>
                        </div>
                        <div class="row-item">
                            <span>Saldo do mês</span>
                            <strong class="<?= $saldo_mes >= 0 ? 'positive' : 'negative' ?>">
                                <?= number_format($saldo_mes, 2, ',', '.') ?> €
                            </strong>
                        </div>
                    </div>
                </section>
            </div>

            <section class="panel">
                <div class="panel-header">
                    <span>Transações recentes</span>
                    <a href="transacoes.php" class="view-all">Ver todas →</a>
                </div>
                <?php if ($count_transacoes > 0): ?>
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
                                    <td class="data"><?= date('d/m/Y', strtotime($t['data'])) ?></td>
                                    <td><?= htmlspecialchars(substr($t['descricao'], 0, 30)) ?></td>
                                    <td>
                                        <span class="<?= $t['tipo'] === 'receita' ? 'tipo-receita' : 'tipo-despesa' ?>">
                                            <?= ucfirst($t['tipo']) ?>
                                        </span>
                                    </td>
                                    <td class="valor <?= $t['tipo'] === 'receita' ? 'positive' : 'negative' ?>">
                                        <?= ($t['tipo'] === 'receita' ? '+' : '-') . number_format($t['valor'], 2, ',', '.') ?> €
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-transactions">
                        <p>📭 Nenhuma transação registrada ainda.</p>
                        <p>Comece a adicionar receitas e despesas!</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel-header">Despesas por categoria</div>
                <?php if (!empty($despesas_categoria)): ?>
                    <?php foreach($despesas_categoria as $cat): ?>
                        <div class="category-item">
                            <span class="category-name"><?= htmlspecialchars($cat['categoria']) ?></span>
                            <span class="category-amount"><?= number_format($cat['total'], 2, ',', '.') ?> €</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; color: var(--gray-600);">
                        <p>📊 Sem dados de despesas</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

</body>
</html>
