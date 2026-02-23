<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM transacoes WHERE user_id = :user_id ORDER BY data DESC");
    $stmt->execute(['user_id' => $user_id]);
    $transacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    $transacoes = [];
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="transacoes.csv"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Data', 'Descricao', 'Categoria', 'Tipo', 'Valor']);

    foreach ($transacoes as $t) {
        fputcsv($output, [
            date('d/m/Y', strtotime($t['data'])),
            $t['descricao'],
            $t['categoria'] ?? '-',
            $t['tipo'],
            number_format($t['valor'], 2, ',', '.')
        ]);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações - Minhas Economias</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap');

        :root {
            --brand: #0f7a4d;
            --brand-dark: #0c6c45;
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

        .export-btn {
            background: white;
            border: 1px solid var(--gray-200);
            color: var(--brand);
            padding: 0.45rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .export-btn:hover {
            border-color: var(--brand);
            box-shadow: var(--shadow-sm);
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
            background: #fef7ec;
        }

        th {
            background: var(--gray-100);
            padding: 1rem 1.4rem;
            text-align: left;
            font-weight: 700;
            color: var(--gray-600);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1rem 1.4rem;
        }

        .tipo-receita {
            color: var(--success);
            font-weight: 600;
            background: rgba(15, 155, 77, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .tipo-despesa {
            color: var(--danger);
            font-weight: 600;
            background: rgba(185, 28, 28, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .valor {
            font-weight: 700;
            text-align: right;
        }

        .positive { color: var(--success); }
        .negative { color: var(--danger); }

        .empty {
            padding: 3rem 2rem;
            text-align: center;
            color: var(--gray-600);
        }

        @media (max-width: 768px) {
            th, td {
                padding: 0.8rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-left">
            <h1>Transações</h1>
            <p>Histórico completo de entradas e saídas</p>
        </div>
        <a href="dashboard.php" class="btn-back">← Voltar</a>
    </div>
</header>

<div class="container">
    <div class="panel">
        <div class="panel-header">
            <span>Todas as transações</span>
            <a class="export-btn" href="transacoes.php?export=csv">Exportar CSV</a>
        </div>
        <?php if (!empty($transacoes)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Tipo</th>
                        <th class="valor">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transacoes as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($t['data'])) ?></td>
                            <td><?= htmlspecialchars($t['descricao']) ?></td>
                            <td><?= htmlspecialchars($t['categoria'] ?? '-') ?></td>
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
            <div class="empty">
                <p>📭 Ainda não existem transações registradas.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
