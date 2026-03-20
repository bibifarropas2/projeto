<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM transacoes WHERE id = :id AND user_id = :user_id");
                $stmt->execute([
                    'id' => $id,
                    'user_id' => $user_id
                ]);
                $success = 'Transação apagada com sucesso.';
            } catch (PDOException $e) {
                $error = 'Erro ao apagar transação.';
            }
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $valor = trim($_POST['valor'] ?? '');
        $data = trim($_POST['data'] ?? '');

        if ($id <= 0 || $descricao === '' || $tipo === '' || $categoria === '' || $valor === '' || $data === '') {
            $error = 'Todos os campos de edição são obrigatórios.';
        } elseif (!in_array($tipo, ['receita', 'despesa'], true)) {
            $error = 'Tipo de transação inválido.';
        } elseif (!is_numeric($valor) || (float)$valor <= 0) {
            $error = 'Valor inválido.';
        } elseif (!DateTime::createFromFormat('Y-m-d', $data)) {
            $error = 'Data inválida.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE transacoes
                     SET descricao = :descricao,
                         tipo = :tipo,
                         categoria = :categoria,
                         valor = :valor,
                         data = :data
                     WHERE id = :id AND user_id = :user_id"
                );
                $stmt->execute([
                    'descricao' => $descricao,
                    'tipo' => $tipo,
                    'categoria' => $categoria,
                    'valor' => (float)$valor,
                    'data' => $data,
                    'id' => $id,
                    'user_id' => $user_id
                ]);
                $success = 'Transação atualizada.';
            } catch (PDOException $e) {
                $error = 'Erro ao atualizar transação.';
            }
        }
    }
}

$filtro_mes = trim($_GET['mes'] ?? '');
$filtro_tipo = trim($_GET['tipo'] ?? '');
$filtro_categoria = trim($_GET['categoria'] ?? '');
$sort = trim($_GET['sort'] ?? 'recentes');
$edit_id = (int)($_GET['edit'] ?? 0);

$allowedSort = ['recentes', 'antigas', 'maior_valor', 'menor_valor'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'recentes';
}

$where = ["user_id = :user_id"];
$params = ['user_id' => $user_id];

if ($filtro_mes !== '' && preg_match('/^\d{4}-\d{2}$/', $filtro_mes)) {
    $where[] = "DATE_FORMAT(data, '%Y-%m') = :filtro_mes";
    $params['filtro_mes'] = $filtro_mes;
}

if ($filtro_tipo !== '' && in_array($filtro_tipo, ['receita', 'despesa'], true)) {
    $where[] = "tipo = :filtro_tipo";
    $params['filtro_tipo'] = $filtro_tipo;
}

if ($filtro_categoria !== '') {
    $where[] = "categoria = :filtro_categoria";
    $params['filtro_categoria'] = $filtro_categoria;
}

$orderSql = "data DESC, id DESC";
if ($sort === 'antigas') {
    $orderSql = "data ASC, id ASC";
}
if ($sort === 'maior_valor') {
    $orderSql = "valor DESC, data DESC";
}
if ($sort === 'menor_valor') {
    $orderSql = "valor ASC, data DESC";
}

$sqlBase = "FROM transacoes WHERE " . implode(' AND ', $where);

$transacoes = [];
$categorias = [];
$transacao_edicao = null;

try {
    $stmt = $pdo->prepare("SELECT DISTINCT categoria FROM transacoes WHERE user_id = :user_id AND categoria IS NOT NULL AND categoria <> '' ORDER BY categoria ASC");
    $stmt->execute(['user_id' => $user_id]);
    $categorias = array_column($stmt->fetchAll(), 'categoria');

    $stmt = $pdo->prepare("SELECT id, user_id, descricao, tipo, categoria, valor, data " . $sqlBase . " ORDER BY " . $orderSql);
    $stmt->execute($params);
    $transacoes = $stmt->fetchAll();

    if ($edit_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM transacoes WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute(['id' => $edit_id, 'user_id' => $user_id]);
        $transacao_edicao = $stmt->fetch();
    }
} catch (PDOException $e) {
    $error = 'Erro ao carregar transações.';
}

if (isset($_GET['export'])) {
    $tipoExport = $_GET['export'];

    if ($tipoExport === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="transacoes.csv"');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Data', 'Tipo', 'Categoria', 'Descricao', 'Valor']);

        foreach ($transacoes as $t) {
            fputcsv($output, [
                date('d/m/Y', strtotime($t['data'])),
                $t['tipo'],
                $t['categoria'] ?? '-',
                $t['descricao'],
                number_format((float)$t['valor'], 2, ',', '.')
            ]);
        }

        fclose($output);
        exit;
    }

    if ($tipoExport === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="transacoes.xls"');
        echo "\xEF\xBB\xBF";
        echo "Data\tTipo\tCategoria\tDescricao\tValor\n";

        foreach ($transacoes as $t) {
            echo date('d/m/Y', strtotime($t['data'])) . "\t";
            echo $t['tipo'] . "\t";
            echo ($t['categoria'] ?? '-') . "\t";
            echo str_replace(["\t", "\n", "\r"], ' ', $t['descricao']) . "\t";
            echo number_format((float)$t['valor'], 2, ',', '.') . "\n";
        }
        exit;
    }

    if ($tipoExport === 'pdf') {
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <title>Relatório de Transações</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 24px; }
                h1 { margin-bottom: 4px; }
                p { color: #555; margin-top: 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 16px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                th { background: #f6f6f6; }
            </style>
        </head>
        <body>
            <h1>Relatório de Transações</h1>
            <p>Utilizador: <?= htmlspecialchars($_SESSION['username']) ?> | Gerado em <?= date('d/m/Y H:i') ?></p>

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
                        <td><?= htmlspecialchars($t['tipo']) ?></td>
                        <td><?= htmlspecialchars($t['categoria'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($t['descricao']) ?></td>
                        <td><?= number_format((float)$t['valor'], 2, ',', '.') ?> EUR</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <script>window.print();</script>
        </body>
        </html>
        <?php
        exit;
    }
}

$queryString = $_GET;
unset($queryString['export']);
$baseQuery = http_build_query($queryString);
$prefix = $baseQuery ? '&' : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações - Minhas Economias</title>
    <link rel="stylesheet" href="assets/css/site-enhancements.css">
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
        }

        .header-content {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-back {
            color: white;
            text-decoration: none;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            background: rgba(255,255,255,0.16);
        }

        .container {
            max-width: 1300px;
            margin: 1.5rem auto;
            padding: 0 1.4rem;
            display: grid;
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
        }

        .panel-body { padding: 1rem 1.1rem; }

        .filters {
            display: grid;
            grid-template-columns: repeat(4, 1fr) auto;
            gap: 0.7rem;
            align-items: end;
        }

        label { font-size: 0.8rem; color: var(--gray-600); display: block; margin-bottom: 0.2rem; }

        input, select {
            width: 100%;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 0.55rem 0.7rem;
            font-size: 0.92rem;
            background: white;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 0.55rem 0.75rem;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            font-size: 0.9rem;
        }

        .btn-primary { background: var(--brand); color: white; }
        .btn-light { background: #fff; color: var(--gray-800); border: 1px solid var(--gray-200); }
        .btn-danger { background: #fff; color: var(--danger); border: 1px solid #fecaca; }

        .export-row {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 0.75rem 0.65rem;
            border-bottom: 1px solid var(--gray-200);
            text-align: left;
            font-size: 0.9rem;
        }

        th {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--gray-600);
            letter-spacing: 0.05em;
            background: var(--gray-100);
        }

        .tipo-pill {
            font-size: 0.78rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            display: inline-block;
        }

        .tipo-receita { color: #14532d; background: #dcfce7; }
        .tipo-despesa { color: #7f1d1d; background: #fee2e2; }

        .positive { color: var(--success); font-weight: 700; }
        .negative { color: var(--danger); font-weight: 700; }

        .actions {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .message {
            padding: 0.75rem 0.9rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .message-error { background: #fef2f2; color: #7f1d1d; }
        .message-success { background: #f0fdf4; color: #14532d; }

        @media (max-width: 980px) {
            .filters {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 680px) {
            th, td { font-size: 0.84rem; }
            .filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="header-content">
        <div>
            <h1>Transações</h1>
            <p>Data, tipo, categoria e valor com edição e filtros</p>
        </div>
        <a href="dashboard.php" class="btn-back">Voltar ao Dashboard</a>
    </div>
</header>

<div class="container">
    <?php if ($error !== ''): ?>
        <div class="message message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="message message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($transacao_edicao): ?>
        <section class="panel">
            <div class="panel-header">Editar transação #<?= (int)$transacao_edicao['id'] ?></div>
            <div class="panel-body">
                <form method="post" class="filters" style="grid-template-columns: 1.6fr 1fr 1fr 1fr auto;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$transacao_edicao['id'] ?>">

                    <div>
                        <label>Descrição</label>
                        <input type="text" name="descricao" maxlength="255" value="<?= htmlspecialchars($transacao_edicao['descricao']) ?>" required>
                    </div>
                    <div>
                        <label>Tipo</label>
                        <select name="tipo" required>
                            <option value="receita" <?= $transacao_edicao['tipo'] === 'receita' ? 'selected' : '' ?>>Receita</option>
                            <option value="despesa" <?= $transacao_edicao['tipo'] === 'despesa' ? 'selected' : '' ?>>Despesa</option>
                        </select>
                    </div>
                    <div>
                        <label>Categoria</label>
                        <input type="text" name="categoria" maxlength="100" value="<?= htmlspecialchars($transacao_edicao['categoria'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label>Valor</label>
                        <input type="number" name="valor" min="0.01" step="0.01" value="<?= number_format((float)$transacao_edicao['valor'], 2, '.', '') ?>" required>
                    </div>
                    <div>
                        <label>Data</label>
                        <input type="date" name="data" value="<?= htmlspecialchars($transacao_edicao['data']) ?>" required>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="transacoes.php" class="btn btn-light" style="margin-top:0.4rem;">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-header">Filtros e exportação</div>
        <div class="panel-body" style="display:grid; gap:1rem;">
            <form method="get" class="filters">
                <div>
                    <label>Mês</label>
                    <input type="month" name="mes" value="<?= htmlspecialchars($filtro_mes) ?>">
                </div>
                <div>
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="receita" <?= $filtro_tipo === 'receita' ? 'selected' : '' ?>>Receita</option>
                        <option value="despesa" <?= $filtro_tipo === 'despesa' ? 'selected' : '' ?>>Despesa</option>
                    </select>
                </div>
                <div>
                    <label>Categoria</label>
                    <select name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $filtro_categoria === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Ordenação</label>
                    <select name="sort">
                        <option value="recentes" <?= $sort === 'recentes' ? 'selected' : '' ?>>Mais recente</option>
                        <option value="antigas" <?= $sort === 'antigas' ? 'selected' : '' ?>>Mais antiga</option>
                        <option value="maior_valor" <?= $sort === 'maior_valor' ? 'selected' : '' ?>>Maior valor</option>
                        <option value="menor_valor" <?= $sort === 'menor_valor' ? 'selected' : '' ?>>Menor valor</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Aplicar</button>
                    <a href="transacoes.php" class="btn btn-light" style="margin-top:0.4rem;">Limpar</a>
                </div>
            </form>

            <div class="export-row">
                <a class="btn btn-light" href="transacoes.php?<?= $baseQuery ?><?= $prefix ?>export=csv">Exportar CSV</a>
                <a class="btn btn-light" href="transacoes.php?<?= $baseQuery ?><?= $prefix ?>export=excel">Exportar Excel</a>
                <a class="btn btn-light" href="transacoes.php?<?= $baseQuery ?><?= $prefix ?>export=pdf" target="_blank">Exportar PDF</a>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">Tabela de transações</div>
        <div class="panel-body" style="padding:0;">
            <?php if (!empty($transacoes)): ?>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Ações</th>
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
                                <td><?= htmlspecialchars($t['categoria'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($t['descricao']) ?></td>
                                <td class="<?= $t['tipo'] === 'receita' ? 'positive' : 'negative' ?>">
                                    <?= ($t['tipo'] === 'receita' ? '+' : '-') . number_format((float)$t['valor'], 2, ',', '.') ?> EUR
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-light" href="transacoes.php?<?= $baseQuery ?><?= $prefix ?>edit=<?= (int)$t['id'] ?>">Editar</a>
                                        <form method="post" onsubmit="return confirm('Apagar esta transação?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Apagar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div style="padding: 1rem; color: var(--gray-600);">Sem transações para os filtros selecionados.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>
