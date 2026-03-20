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

$categoriasPadrao = [
    'Alimentação',
    'Transportes',
    'Habitação',
    'Saúde',
    'Educação',
    'Entretenimento',
    'Compras',
    'Utilities',
    'Seguros',
    'Outro'
];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM categorias WHERE user_id = :user_id AND tipo = 'despesa'");
    $stmt->execute(['user_id' => $user_id]);
    $temCategorias = (int)($stmt->fetch()['total'] ?? 0) > 0;

    if (!$temCategorias) {
        $insertPadrao = $pdo->prepare(
            "INSERT INTO categorias (nome, tipo, descricao, user_id, ativa)
             VALUES (:nome, 'despesa', NULL, :user_id, 1)"
        );

        foreach ($categoriasPadrao as $nome) {
            $insertPadrao->execute([
                'nome' => $nome,
                'user_id' => $user_id
            ]);
        }
    }
} catch (PDOException $e) {
    $error = 'Nao foi possivel inicializar categorias padrao.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            $error = 'Nome da categoria e obrigatorio.';
        } elseif (mb_strlen($nome) > 100) {
            $error = 'Nome da categoria demasiado longo.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "SELECT id FROM categorias
                     WHERE user_id = :user_id AND tipo = 'despesa' AND LOWER(nome) = LOWER(:nome)
                     LIMIT 1"
                );
                $stmt->execute([
                    'user_id' => $user_id,
                    'nome' => $nome
                ]);

                if ($stmt->fetch()) {
                    $error = 'Esta categoria ja existe.';
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO categorias (nome, tipo, descricao, user_id, ativa)
                         VALUES (:nome, 'despesa', NULL, :user_id, 1)"
                    );
                    $stmt->execute([
                        'nome' => $nome,
                        'user_id' => $user_id
                    ]);
                    $success = 'Categoria adicionada com sucesso.';
                }
            } catch (PDOException $e) {
                $error = 'Erro ao adicionar categoria.';
            }
        }
    }

    if ($action === 'delete') {
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);

        if ($categoriaId > 0) {
            try {
                $stmt = $pdo->prepare(
                    "DELETE FROM categorias WHERE id = :id AND user_id = :user_id AND tipo = 'despesa'"
                );
                $stmt->execute([
                    'id' => $categoriaId,
                    'user_id' => $user_id
                ]);
                $success = 'Categoria removida com sucesso.';
            } catch (PDOException $e) {
                $error = 'Erro ao remover categoria.';
            }
        }
    }
}

$categorias = [];
try {
    $stmt = $pdo->prepare(
        "SELECT c.id, c.nome,
                COUNT(t.id) AS total_movimentos,
                COALESCE(SUM(t.valor), 0) AS total_gasto
         FROM categorias c
         LEFT JOIN transacoes t
            ON t.user_id = c.user_id
           AND t.tipo = 'despesa'
           AND t.categoria = c.nome
         WHERE c.user_id = :user_id
           AND c.tipo = 'despesa'
         GROUP BY c.id, c.nome
         ORDER BY c.nome ASC"
    );
    $stmt->execute(['user_id' => $user_id]);
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erro ao carregar categorias.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Minhas Economias</title>
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
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .btn-back {
            color: white;
            text-decoration: none;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.18);
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: grid;
            gap: 1.4rem;
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
        }

        .panel-body {
            padding: 1.2rem 1.4rem;
        }

        .message {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.92rem;
        }

        .error-message {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .success-message {
            background: var(--success-bg);
            color: var(--success);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.8rem;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.75rem 0.9rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            background: white;
            font-size: 0.95rem;
        }

        button {
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-add {
            background: var(--brand);
            color: white;
        }

        .btn-delete {
            background: #fff;
            color: var(--danger);
            border: 1px solid #f1c6c6;
            padding: 0.45rem 0.75rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--gray-200);
            text-align: left;
            font-size: 0.92rem;
        }

        th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-600);
            background: var(--gray-100);
        }

        .amount {
            font-weight: 700;
            color: var(--danger);
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            th, td {
                padding: 0.7rem;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="header-content">
        <div>
            <h1>Categorias de Despesa</h1>
            <p>Organize melhor os seus gastos</p>
        </div>
        <a class="btn-back" href="dashboard.php">Voltar</a>
    </div>
</header>

<div class="container">
    <section class="panel">
        <div class="panel-header">Nova categoria</div>
        <div class="panel-body">
            <?php if ($error !== ''): ?>
                <div class="message error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
                <div class="message success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <input type="text" name="nome" maxlength="100" placeholder="Ex: Pets, Farmacia, Assinaturas" required>
                    <button class="btn-add" type="submit">Adicionar</button>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">Categorias atuais</div>
        <div class="panel-body" style="padding:0;">
            <?php if (!empty($categorias)): ?>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Movimentos</th>
                            <th>Total gasto</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td><?= htmlspecialchars($cat['nome']) ?></td>
                                <td><?= (int)$cat['total_movimentos'] ?></td>
                                <td class="amount"><?= number_format((float)$cat['total_gasto'], 2, ',', '.') ?> EUR</td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Apagar categoria?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="categoria_id" value="<?= (int)$cat['id'] ?>">
                                        <button type="submit" class="btn-delete">Apagar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div style="padding: 1.2rem; color: var(--gray-600);">Sem categorias registadas.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>
