<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$current_month = (int)date('m');
$current_year = (int)date('Y');
$mes_atual = $meses[$current_month];

$dashboardError = '';
$metaSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_meta') {
    $objetivo = trim($_POST['objetivo_mensal'] ?? '0');

    if (!is_numeric($objetivo) || (float)$objetivo < 0) {
        $dashboardError = 'Meta inválida.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO metas_poupanca (user_id, objetivo_mensal)
                 VALUES (:user_id, :objetivo)
                 ON DUPLICATE KEY UPDATE objetivo_mensal = VALUES(objetivo_mensal)"
            );
            $stmt->execute([
                'user_id' => $user_id,
                'objetivo' => (float)$objetivo
            ]);
            $metaSuccess = 'Meta mensal atualizada.';
        } catch (PDOException $e) {
            $dashboardError = 'Não foi possível guardar a meta.';
        }
    }
}

$total_receitas = 0.0;
$total_despesas = 0.0;
$saldo_atual = 0.0;
$receitas_mes = 0.0;
$despesas_mes = 0.0;
$saldo_mes = 0.0;
$meta_mensal = 0.0;
$ultima_transacao = null;
$maior_despesa_mes = null;
$transacoes = [];
$despesas_categoria = [];
$evolucao_labels = [];
$evolucao_saldos = [];
$categorias_labels = [];
$categorias_totais = [];

try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) AS total FROM transacoes WHERE user_id = :user_id AND tipo = 'receita'");
    $stmt->execute(['user_id' => $user_id]);
    $total_receitas = (float)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) AS total FROM transacoes WHERE user_id = :user_id AND tipo = 'despesa'");
    $stmt->execute(['user_id' => $user_id]);
    $total_despesas = (float)($stmt->fetch()['total'] ?? 0);

    $saldo_atual = $total_receitas - $total_despesas;

    $stmt = $pdo->prepare("SELECT objetivo_mensal FROM metas_poupanca WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $meta_mensal = (float)($stmt->fetch()['objetivo_mensal'] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(valor),0) AS total FROM transacoes
         WHERE user_id = :user_id AND tipo = 'receita' AND MONTH(data) = :mes AND YEAR(data) = :ano"
    );
    $stmt->execute([
        'user_id' => $user_id,
        'mes' => $current_month,
        'ano' => $current_year
    ]);
    $receitas_mes = (float)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(valor),0) AS total FROM transacoes
         WHERE user_id = :user_id AND tipo = 'despesa' AND MONTH(data) = :mes AND YEAR(data) = :ano"
    );
    $stmt->execute([
        'user_id' => $user_id,
        'mes' => $current_month,
        'ano' => $current_year
    ]);
    $despesas_mes = (float)($stmt->fetch()['total'] ?? 0);

    $saldo_mes = $receitas_mes - $despesas_mes;

    $stmt = $pdo->prepare(
        "SELECT * FROM transacoes WHERE user_id = :user_id ORDER BY data DESC, id DESC LIMIT 1"
    );
    $stmt->execute(['user_id' => $user_id]);
    $ultima_transacao = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT * FROM transacoes
         WHERE user_id = :user_id
           AND tipo = 'despesa'
           AND MONTH(data) = :mes
           AND YEAR(data) = :ano
         ORDER BY valor DESC, id DESC
         LIMIT 1"
    );
    $stmt->execute([
        'user_id' => $user_id,
        'mes' => $current_month,
        'ano' => $current_year
    ]);
    $maior_despesa_mes = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT * FROM transacoes
         WHERE user_id = :user_id
         ORDER BY data DESC, id DESC
         LIMIT 8"
    );
    $stmt->execute(['user_id' => $user_id]);
    $transacoes = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT COALESCE(categoria, 'Sem categoria') AS categoria, SUM(valor) AS total
         FROM transacoes
         WHERE user_id = :user_id
           AND tipo = 'despesa'
           AND MONTH(data) = :mes
           AND YEAR(data) = :ano
         GROUP BY COALESCE(categoria, 'Sem categoria')
         ORDER BY total DESC"
    );
    $stmt->execute([
        'user_id' => $user_id,
        'mes' => $current_month,
        'ano' => $current_year
    ]);
    $despesas_categoria = $stmt->fetchAll();

    foreach ($despesas_categoria as $item) {
        $categorias_labels[] = $item['categoria'];
        $categorias_totais[] = (float)$item['total'];
    }

    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(data, '%Y-%m') AS periodo,
                SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) AS saldo_periodo
         FROM transacoes
         WHERE user_id = :user_id
           AND data >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY DATE_FORMAT(data, '%Y-%m')
         ORDER BY periodo ASC"
    );
    $stmt->execute(['user_id' => $user_id]);
    $linhasEvolucao = $stmt->fetchAll();

    $saldoAcumulado = 0.0;
    foreach ($linhasEvolucao as $linha) {
        $partes = explode('-', $linha['periodo']);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));
        $evolucao_labels[] = ($meses[$mes] ?? 'Mês') . '/' . substr((string)$ano, -2);
        $saldoAcumulado += (float)$linha['saldo_periodo'];
        $evolucao_saldos[] = round($saldoAcumulado, 2);
    }
} catch (PDOException $e) {
    $dashboardError = 'Erro ao carregar os dados do dashboard.';
}

$meta_percent = 0;
if ($meta_mensal > 0) {
    $meta_percent = max(0, min(100, ($saldo_mes / $meta_mensal) * 100));
}

$alerta_excesso = $despesas_mes > $receitas_mes && $despesas_mes > 0;
$mensagem_categoria = '';
if (!empty($despesas_categoria) && $despesas_mes > 0) {
    $top = $despesas_categoria[0];
    $share = ((float)$top['total'] / $despesas_mes) * 100;
    if ($share >= 40) {
        $mensagem_categoria = 'Gastaste muito em ' . $top['categoria'] . ' (' . number_format((float)$top['total'], 2, ',', '.') . ' EUR).';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Minhas Economias</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/site-enhancements.css">
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Lexend', "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top, rgba(214, 162, 27, 0.12), transparent 45%), var(--gray-100);
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
            gap: 1rem;
        }

        nav {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.55rem 0.85rem;
            border-radius: 8px;
            background: rgba(255,255,255,0.12);
            font-size: 0.9rem;
        }

        nav a:hover { background: rgba(255,255,255,0.24); }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.6rem;
            display: grid;
            gap: 1.4rem;
        }

        .alerts {
            display: grid;
            gap: 0.8rem;
        }

        .alert {
            padding: 0.9rem 1rem;
            border-radius: 10px;
            border: 1px solid;
            font-size: 0.92rem;
            font-weight: 500;
        }

        .alert-danger { color: #7f1d1d; background: #fef2f2; border-color: #fecaca; }
        .alert-info { color: #1e3a8a; background: #eff6ff; border-color: #bfdbfe; }
        .alert-success { color: #14532d; background: #f0fdf4; border-color: #bbf7d0; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 1.2rem;
        }

        .card .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--gray-600);
            margin-bottom: 0.45rem;
            font-weight: 700;
        }

        .card .value {
            font-size: 1.7rem;
            font-weight: 800;
        }

        .positive { color: var(--success); }
        .negative { color: var(--danger); }

        .layout {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 1rem;
        }

        .panel {
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .panel-header {
            background: #f3eadb;
            border-bottom: 1px solid var(--gray-200);
            padding: 0.9rem 1.1rem;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
        }

        .panel-body { padding: 1rem 1.1rem; }

        .row-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.55rem 0;
            border-bottom: 1px dashed var(--gray-200);
            font-size: 0.93rem;
        }

        .row-item:last-child { border-bottom: none; }

        .meta-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.5rem;
            margin-top: 0.8rem;
        }

        .meta-form input {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 0.55rem 0.75rem;
            font-size: 0.9rem;
        }

        .meta-form button {
            border: none;
            border-radius: 8px;
            background: var(--brand);
            color: white;
            padding: 0.55rem 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }

        .progress {
            margin-top: 0.7rem;
            height: 10px;
            border-radius: 999px;
            background: #f0e7d8;
            overflow: hidden;
        }

        .progress span {
            display: block;
            height: 100%;
            background: linear-gradient(135deg, var(--brand) 0%, #19a765 100%);
        }

        table { width: 100%; border-collapse: collapse; }
        th, td {
            text-align: left;
            padding: 0.8rem 0.7rem;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.9rem;
        }
        th {
            font-size: 0.74rem;
            text-transform: uppercase;
            color: var(--gray-600);
            letter-spacing: 0.06em;
            background: var(--gray-100);
        }

        .tipo-pill {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
        }

        .tipo-receita { color: #14532d; background: #dcfce7; }
        .tipo-despesa { color: #7f1d1d; background: #fee2e2; }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .chart-wrap {
            min-height: 260px;
        }

        @media (max-width: 1080px) {
            .layout { grid-template-columns: 1fr; }
            .charts-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            nav { width: 100%; }
            nav a { flex: 1 1 auto; text-align: center; }
            .meta-form { grid-template-columns: 1fr; }
            th, td { font-size: 0.84rem; }
        }
    </style>
</head>
<body>
<header>
    <div class="header-content">
        <div>
            <h1>Minhas Economias</h1>
            <p>Bem-vindo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="transacoes.php">Transações</a>
            <a href="categorias.php">Categorias</a>
            <a href="adicionar_receitas.php">Nova Receita</a>
            <a href="adicionar_despesa.php">Nova Despesa</a>
            <a href="logout.php">Sair</a>
        </nav>
    </div>
</header>

<div class="container">
    <?php if ($dashboardError !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($dashboardError) ?></div>
    <?php endif; ?>

    <?php if ($metaSuccess !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($metaSuccess) ?></div>
    <?php endif; ?>

    <section class="alerts">
        <?php if ($alerta_excesso): ?>
            <div class="alert alert-danger">Aviso: este mês as despesas estão acima das receitas.</div>
        <?php endif; ?>

        <?php if ($mensagem_categoria !== ''): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensagem_categoria) ?></div>
        <?php endif; ?>
    </section>

    <section class="stats-grid">
        <article class="card">
            <div class="label">Saldo atual</div>
            <div class="value <?= $saldo_atual >= 0 ? 'positive' : 'negative' ?>"><?= number_format($saldo_atual, 2, ',', '.') ?> EUR</div>
        </article>
        <article class="card">
            <div class="label">Total ganho</div>
            <div class="value positive"><?= number_format($total_receitas, 2, ',', '.') ?> EUR</div>
        </article>
        <article class="card">
            <div class="label">Total gasto</div>
            <div class="value negative"><?= number_format($total_despesas, 2, ',', '.') ?> EUR</div>
        </article>
        <article class="card">
            <div class="label">Resumo do mês</div>
            <div class="value <?= $saldo_mes >= 0 ? 'positive' : 'negative' ?>"><?= number_format($saldo_mes, 2, ',', '.') ?> EUR</div>
            <small><?= number_format($receitas_mes, 2, ',', '.') ?> ganho vs <?= number_format($despesas_mes, 2, ',', '.') ?> gasto</small>
        </article>
    </section>

    <section class="layout">
        <article class="panel">
            <div class="panel-header">Últimas transações</div>
            <div class="panel-body" style="padding:0;">
                <?php if (!empty($transacoes)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transacoes as $t): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($t['data'])) ?></td>
                                    <td>
                                        <span class="tipo-pill <?= $t['tipo'] === 'receita' ? 'tipo-receita' : 'tipo-despesa' ?>">
                                            <?= ucfirst($t['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($t['categoria'] ?? 'Sem categoria') ?></td>
                                    <td><?= htmlspecialchars($t['descricao']) ?></td>
                                    <td class="<?= $t['tipo'] === 'receita' ? 'positive' : 'negative' ?>">
                                        <?= ($t['tipo'] === 'receita' ? '+' : '-') . number_format((float)$t['valor'], 2, ',', '.') ?> EUR
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="padding: 1rem;">Sem transações ainda.</div>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel">
            <div class="panel-header">Destaques do mês (<?= $mes_atual ?>)</div>
            <div class="panel-body">
                <div class="row-item">
                    <span>Última transação</span>
                    <strong>
                        <?php if ($ultima_transacao): ?>
                            <?= htmlspecialchars($ultima_transacao['descricao']) ?> (<?= number_format((float)$ultima_transacao['valor'], 2, ',', '.') ?> EUR)
                        <?php else: ?>
                            Sem dados
                        <?php endif; ?>
                    </strong>
                </div>
                <div class="row-item">
                    <span>Maior despesa do mês</span>
                    <strong class="negative">
                        <?php if ($maior_despesa_mes): ?>
                            <?= htmlspecialchars($maior_despesa_mes['descricao']) ?> (<?= number_format((float)$maior_despesa_mes['valor'], 2, ',', '.') ?> EUR)
                        <?php else: ?>
                            Sem despesas no mês
                        <?php endif; ?>
                    </strong>
                </div>
                <div class="row-item">
                    <span>Meta de poupança</span>
                    <strong><?= number_format($meta_mensal, 2, ',', '.') ?> EUR</strong>
                </div>
                <div class="progress">
                    <span style="width: <?= number_format($meta_percent, 2, '.', '') ?>%;"></span>
                </div>
                <small style="display:block; margin-top:0.4rem; color: var(--gray-600);">
                    <?= number_format($meta_percent, 1, ',', '.') ?>% da meta atingida neste mês
                </small>

                <form method="post" class="meta-form">
                    <input type="hidden" name="action" value="save_meta">
                    <input type="number" step="0.01" min="0" name="objetivo_mensal" value="<?= number_format($meta_mensal, 2, '.', '') ?>" placeholder="Definir meta mensal">
                    <button type="submit">Guardar meta</button>
                </form>
            </div>
        </article>
    </section>

    <section class="charts-grid">
        <article class="panel">
            <div class="panel-header">Gráfico de despesas por categoria</div>
            <div class="panel-body chart-wrap">
                <canvas id="chartCategorias"></canvas>
            </div>
        </article>

        <article class="panel">
            <div class="panel-header">Evolução do saldo</div>
            <div class="panel-body chart-wrap">
                <canvas id="chartSaldo"></canvas>
            </div>
        </article>
    </section>
</div>

<script>
const categoriasLabels = <?= json_encode($categorias_labels, JSON_UNESCAPED_UNICODE) ?>;
const categoriasValores = <?= json_encode($categorias_totais) ?>;
const evolucaoLabels = <?= json_encode($evolucao_labels, JSON_UNESCAPED_UNICODE) ?>;
const evolucaoSaldos = <?= json_encode($evolucao_saldos) ?>;

const pieCtx = document.getElementById('chartCategorias');
if (pieCtx) {
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: categoriasLabels.length ? categoriasLabels : ['Sem dados'],
            datasets: [{
                data: categoriasValores.length ? categoriasValores : [1],
                backgroundColor: ['#b91c1c', '#c27803', '#0f7a4d', '#2563eb', '#d97706', '#0f766e', '#be185d', '#334155']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

const lineCtx = document.getElementById('chartSaldo');
if (lineCtx) {
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: evolucaoLabels.length ? evolucaoLabels : ['Sem dados'],
            datasets: [{
                label: 'Saldo acumulado',
                data: evolucaoSaldos.length ? evolucaoSaldos : [0],
                borderColor: '#0f7a4d',
                backgroundColor: 'rgba(15, 122, 77, 0.15)',
                borderWidth: 3,
                fill: true,
                tension: 0.25
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    ticks: {
                        callback: function(value) { return value + ' EUR'; }
                    }
                }
            }
        }
    });
}
</script>
</body>
</html>
