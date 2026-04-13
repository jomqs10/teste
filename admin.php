<?php
require_once 'auth.php';
requireLogin();

$user = currentUser();
if (!userCanManageUsers($user)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pdo = getDB();
$msg = '';
$msgType = 'success';

function adminPostedPermissions(): array
{
    $raw = $_POST['permissions'] ?? [];
    if (!is_array($raw)) {
        $raw = [$raw];
    }

    return dashboardNormalizePermissions($raw);
}

function adminRightsSummary(array $permissions): string
{
    if ($permissions === []) {
        return 'Nenhuma area liberada';
    }

    $labels = [];
    foreach ($permissions as $permission) {
        $labels[] = dashboardSectionLabel($permission);
    }

    return implode(', ', $labels);
}

function adminFindUserForEdit(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT u.*,
               (SELECT COUNT(*) FROM log_acessos WHERE usuario_id = u.id) AS total_logins
        FROM usuarios u
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $row['permissions_array'] = authPermissionsFromRow($row);
    return $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_user') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $login = authNormalizeLogin((string) ($_POST['login'] ?? ''));
        $setor = (string) ($_POST['setor'] ?? '');
        $loja = trim((string) ($_POST['loja'] ?? ''));
        $senhaInicial = (string) ($_POST['initial_password'] ?? DEFAULT_INITIAL_PASSWORD);
        $permissions = adminPostedPermissions();
        $isAdmin = !empty($_POST['is_admin']) ? 1 : 0;

        if (strlen($nome) < 2) {
            $msg = 'Informe um nome valido.';
            $msgType = 'error';
        } elseif ($login === '') {
            $msg = 'Informe um login valido.';
            $msgType = 'error';
        } elseif (!in_array($setor, SETORES, true)) {
            $msg = 'Selecione um setor valido.';
            $msgType = 'error';
        } elseif (strlen($senhaInicial) < authMinPasswordLength()) {
            $msg = 'A senha inicial precisa ter pelo menos ' . authMinPasswordLength() . ' caracteres.';
            $msgType = 'error';
        } elseif ($permissions === []) {
            $msg = 'Selecione pelo menos uma area do sistema.';
            $msgType = 'error';
        } else {
            $exists = $pdo->prepare("SELECT id FROM usuarios WHERE login = ?");
            $exists->execute([$login]);
            if ($exists->fetch()) {
                $msg = 'Esse login ja existe.';
                $msgType = 'error';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, login, email, senha, setor, loja, permissoes, is_admin, must_change_password, ativo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)
                ");
                $stmt->execute([
                    $nome,
                    $login,
                    authSyntheticEmail($login),
                    password_hash($senhaInicial, PASSWORD_DEFAULT),
                    $setor,
                    $loja !== '' ? $loja : null,
                    json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $isAdmin,
                ]);

                $msg = 'Conta criada com sucesso.';
                $msgType = 'success';
            }
        }
    }

    if ($action === 'update_user' && !empty($_POST['id'])) {
        $id = (int) $_POST['id'];
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $login = authNormalizeLogin((string) ($_POST['login'] ?? ''));
        $setor = (string) ($_POST['setor'] ?? '');
        $loja = trim((string) ($_POST['loja'] ?? ''));
        $permissions = adminPostedPermissions();
        $isAdmin = !empty($_POST['is_admin']) ? 1 : 0;
        $ativo = !empty($_POST['ativo']) ? 1 : 0;

        if (strlen($nome) < 2) {
            $msg = 'Informe um nome valido.';
            $msgType = 'error';
        } elseif ($login === '') {
            $msg = 'Informe um login valido.';
            $msgType = 'error';
        } elseif (!in_array($setor, SETORES, true)) {
            $msg = 'Selecione um setor valido.';
            $msgType = 'error';
        } elseif ($permissions === []) {
            $msg = 'Selecione pelo menos uma area do sistema.';
            $msgType = 'error';
        } elseif ($id === (int) $user['id'] && !$isAdmin) {
            $msg = 'Voce nao pode remover seus proprios direitos de admin.';
            $msgType = 'error';
        } elseif ($id === (int) $user['id'] && !$ativo) {
            $msg = 'Voce nao pode desativar sua propria conta.';
            $msgType = 'error';
        } else {
            $exists = $pdo->prepare("SELECT id FROM usuarios WHERE login = ? AND id <> ?");
            $exists->execute([$login, $id]);
            if ($exists->fetch()) {
                $msg = 'Esse login ja esta em uso.';
                $msgType = 'error';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET nome = ?, login = ?, setor = ?, loja = ?, permissoes = ?, is_admin = ?, ativo = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $nome,
                    $login,
                    $setor,
                    $loja !== '' ? $loja : null,
                    json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $isAdmin,
                    $ativo,
                    $id,
                ]);

                if ($id === (int) $user['id']) {
                    refreshCurrentUser();
                    $user = currentUser();
                }

                $msg = 'Conta atualizada.';
                $msgType = 'success';
            }
        }
    }

    if ($action === 'reset_password' && !empty($_POST['id'])) {
        $id = (int) $_POST['id'];
        $senha = (string) ($_POST['new_password'] ?? '');

        if (strlen($senha) < authMinPasswordLength()) {
            $msg = 'A nova senha precisa ter pelo menos ' . authMinPasswordLength() . ' caracteres.';
            $msgType = 'error';
        } else {
            updateUserPassword($id, $senha, true);
            $msg = 'Senha redefinida. O usuario sera obrigado a trocar no proximo login.';
            $msgType = 'success';
        }
    }

    if ($action === 'delete_user' && !empty($_POST['id'])) {
        $id = (int) $_POST['id'];

        if ($id === (int) $user['id']) {
            $msg = 'Voce nao pode excluir sua propria conta.';
            $msgType = 'error';
        } else {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Conta excluida com sucesso.';
            $msgType = 'warning';
        }
    }
}

$usuarios = $pdo->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM log_acessos WHERE usuario_id = u.id) AS total_logins
    FROM usuarios u
    ORDER BY u.nome ASC
")->fetchAll();

foreach ($usuarios as &$usuarioRow) {
    $usuarioRow['permissions_array'] = authPermissionsFromRow($usuarioRow);
}
unset($usuarioRow);

$totalUsuarios = count($usuarios);
$totalAtivos = count(array_filter($usuarios, fn($item) => !empty($item['ativo'])));
$totalAdmins = count(array_filter($usuarios, fn($item) => !empty($item['is_admin'])));
$totalPrimeiroAcesso = count(array_filter($usuarios, fn($item) => !empty($item['must_change_password'])));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administracao - Dashboard Holistica</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #f4f6fb;
    --surface: #ffffff;
    --border: #e2e8f0;
    --text: #0f172a;
    --muted: #64748b;
    --primary: #892042;
    --primary-strong: #6f1735;
    --success: #15803d;
    --warning: #b45309;
    --danger: #b91c1c;
    --sidebar-bg: #892042;
    --sidebar-active: #6b1330;
    --radius: 16px;
    --shadow: 0 10px 30px rgba(15, 23, 42, .08);
}
body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
}
.layout { display: flex; min-height: 100vh; }
.sidebar {
    width: 240px;
    background: var(--sidebar-bg);
    color: #fff;
    position: fixed;
    inset: 0 auto 0 0;
    display: flex;
    flex-direction: column;
}
.brand {
    padding: 22px 20px 16px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.brand h2 { font-size: 15px; font-weight: 700; }
.brand p { color: rgba(255,255,255,.55); font-size: 12px; margin-top: 4px; }
.nav { flex: 1; padding: 16px 12px; }
.nav-label {
    color: rgba(255,255,255,.45);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding: 0 8px;
    margin: 10px 0 8px;
}
.nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    color: rgba(255,255,255,.78);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 600;
}
.nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
.nav-link.active { background: var(--sidebar-active); color: #fff; }
.sidebar-footer {
    padding: 14px 12px;
    border-top: 1px solid rgba(255,255,255,.08);
}
.account-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px;
    border-radius: 12px;
    background: rgba(255,255,255,.06);
}
.account-name { font-size: 13px; font-weight: 700; }
.account-meta { font-size: 11px; color: rgba(255,255,255,.55); margin-top: 2px; }
.main {
    margin-left: 240px;
    flex: 1;
    min-width: 0;
}
.topbar {
    height: 68px;
    background: rgba(255,255,255,.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 0 28px;
    position: sticky;
    top: 0;
    z-index: 20;
}
.topbar h1 { font-size: 20px; font-weight: 700; }
.topbar p { color: var(--muted); font-size: 13px; margin-top: 4px; }
.actions { display: flex; gap: 10px; }
.btn, .btn-link {
    border: none;
    border-radius: 12px;
    padding: 11px 16px;
    font-size: 13px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-strong); }
.btn-soft { background: #eef2ff; color: #3730a3; }
.btn-danger { background: #fef2f2; color: var(--danger); }
.btn-warning { background: #fff7ed; color: var(--warning); }
.btn-gray { background: #e2e8f0; color: #334155; }
.content {
    padding: 28px;
    display: grid;
    gap: 24px;
}
.alert {
    padding: 14px 16px;
    border-radius: 14px;
    font-size: 14px;
    border: 1px solid transparent;
}
.alert.success { background: #f0fdf4; color: var(--success); border-color: #bbf7d0; }
.alert.warning { background: #fff7ed; color: var(--warning); border-color: #fed7aa; }
.alert.error { background: #fef2f2; color: var(--danger); border-color: #fecaca; }
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}
.stat {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px;
    box-shadow: var(--shadow);
}
.stat-label {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}
.stat-value { font-size: 30px; font-weight: 800; }
.grid-2 {
    display: grid;
    grid-template-columns: minmax(320px, 430px) minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.card-header {
    padding: 20px 22px 0;
}
.card-header h2 { font-size: 18px; font-weight: 700; }
.card-header p { color: var(--muted); font-size: 13px; margin-top: 6px; }
.card-body { padding: 22px; }
.form-grid { display: grid; gap: 14px; }
.row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-group { display: grid; gap: 6px; }
label {
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
}
input, select {
    width: 100%;
    padding: 11px 13px;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    font-size: 14px;
    font-family: inherit;
    color: var(--text);
    background: #f8fafc;
    outline: none;
}
input:focus, select:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(137, 32, 66, .08);
}
.hint {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.5;
}
.permission-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
.check {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: #fff;
    font-size: 13px;
}
.check input { width: auto; }
.list-card {
    display: grid;
    gap: 14px;
}
.user-panel {
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
}
.user-panel summary {
    list-style: none;
    cursor: pointer;
    padding: 16px 18px;
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(0, .9fr) minmax(0, .9fr) auto;
    gap: 14px;
    align-items: center;
}
.user-panel summary::-webkit-details-marker { display: none; }
.user-panel[open] summary { border-bottom: 1px solid var(--border); background: #fcfcfd; }
.user-title strong { display: block; font-size: 15px; }
.user-title span, .user-meta, .user-rights {
    color: var(--muted);
    font-size: 12.5px;
    line-height: 1.5;
}
.badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}
.badge.green { background: #dcfce7; color: #166534; }
.badge.red { background: #fee2e2; color: #b91c1c; }
.badge.blue { background: #dbeafe; color: #1d4ed8; }
.badge.orange { background: #ffedd5; color: #c2410c; }
.user-panel-body {
    padding: 18px;
    display: grid;
    gap: 18px;
}
.panel-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
@media (max-width: 1100px) {
    .grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 860px) {
    .layout { display: block; }
    .sidebar { position: static; width: 100%; }
    .main { margin-left: 0; }
    .user-panel summary { grid-template-columns: 1fr; }
    .permission-grid, .row-2 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <h2>Dashboard Holistica</h2>
            <p>Administracao de contas</p>
        </div>

        <nav class="nav">
            <div class="nav-label">Sistema</div>
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="admin.php" class="nav-link active">Usuarios</a>
            <a href="logs.php" class="nav-link">Logs</a>
            <a href="cache.php" class="nav-link">Cache</a>
        </nav>

        <div class="sidebar-footer">
            <div class="account-box">
                <div>
                    <div class="account-name"><?= htmlspecialchars($user['nome']) ?></div>
                    <div class="account-meta">Login: <?= htmlspecialchars((string) ($user['login'] ?? '')) ?></div>
                </div>
                <a href="logout.php" class="btn-link btn-danger" style="padding:8px 10px">Sair</a>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h1>Contas e direitos do sistema</h1>
                <p>Crie acessos, ajuste permissoes e force a troca de senha no primeiro login.</p>
            </div>
            <div class="actions">
                <a href="index.php" class="btn-link btn-gray">Voltar ao dashboard</a>
            </div>
        </header>

        <div class="content">
            <?php if ($msg !== ''): ?>
                <div class="alert <?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <section class="stats">
                <article class="stat">
                    <div class="stat-label">Total de contas</div>
                    <div class="stat-value"><?= $totalUsuarios ?></div>
                </article>
                <article class="stat">
                    <div class="stat-label">Ativas</div>
                    <div class="stat-value"><?= $totalAtivos ?></div>
                </article>
                <article class="stat">
                    <div class="stat-label">Admins</div>
                    <div class="stat-value"><?= $totalAdmins ?></div>
                </article>
                <article class="stat">
                    <div class="stat-label">Primeiro acesso</div>
                    <div class="stat-value"><?= $totalPrimeiroAcesso ?></div>
                </article>
            </section>

            <section class="grid-2">
                <article class="card">
                    <div class="card-header">
                        <h2>Criar nova conta</h2>
                        <p>As contas novas saem com senha inicial e troca obrigatoria no primeiro acesso.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="create_user">

                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input id="nome" name="nome" type="text" placeholder="Nome da conta" required>
                            </div>

                            <div class="row-2">
                                <div class="form-group">
                                    <label for="login">Login</label>
                                    <input id="login" name="login" type="text" placeholder="ex: debora" required>
                                </div>
                                <div class="form-group">
                                    <label for="initial_password">Senha inicial</label>
                                    <input id="initial_password" name="initial_password" type="text" value="<?= htmlspecialchars(DEFAULT_INITIAL_PASSWORD) ?>" required>
                                </div>
                            </div>

                            <div class="row-2">
                                <div class="form-group">
                                    <label for="setor">Setor</label>
                                    <select id="setor" name="setor" required>
                                        <?php foreach (SETORES as $setor): ?>
                                            <option value="<?= htmlspecialchars($setor) ?>"><?= htmlspecialchars($setor) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="loja">Loja</label>
                                    <select id="loja" name="loja">
                                        <option value="">Todas as lojas</option>
                                        <?php foreach (LOJAS as $loja): ?>
                                            <option value="<?= htmlspecialchars($loja) ?>"><?= htmlspecialchars($loja) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Direitos do sistema</label>
                                <div class="permission-grid">
                                    <?php foreach (DASHBOARD_SECTIONS as $key => $label): ?>
                                        <label class="check">
                                            <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($key) ?>" <?= in_array($key, ['orcamentos', 'lojas', 'vendedores', 'produtos'], true) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <label class="check">
                                <input type="checkbox" name="is_admin" value="1">
                                <span>Permitir acesso administrativo a usuarios, logs e cache</span>
                            </label>

                            <div class="hint">
                                Senha inicial padrao sugerida: <strong><?= htmlspecialchars(DEFAULT_INITIAL_PASSWORD) ?></strong>. Se a loja ficar vazia, o usuario enxerga todas as lojas liberadas pelas permissoes.
                            </div>

                            <button type="submit" class="btn btn-primary">Criar conta</button>
                        </form>
                    </div>
                </article>

                <article class="card">
                    <div class="card-header">
                        <h2>Contas existentes</h2>
                        <p>Abra cada usuario para editar direitos, status e senha.</p>
                    </div>
                    <div class="card-body list-card">
                        <?php foreach ($usuarios as $item): ?>
                            <?php
                            $rightsSummary = adminRightsSummary($item['permissions_array']);
                            $statusClass = !empty($item['ativo']) ? 'green' : 'red';
                            $statusText = !empty($item['ativo']) ? 'Ativa' : 'Desativada';
                            ?>
                            <details class="user-panel">
                                <summary>
                                    <div class="user-title">
                                        <strong><?= htmlspecialchars($item['nome']) ?></strong>
                                        <span>Login: <?= htmlspecialchars((string) ($item['login'] ?? '')) ?></span>
                                    </div>
                                    <div class="user-meta">
                                        <div><?= htmlspecialchars((string) ($item['setor'] ?? '')) ?><?= !empty($item['loja']) ? ' | ' . htmlspecialchars((string) $item['loja']) : ' | Todas as lojas' ?></div>
                                        <div>Ultimo login: <?= !empty($item['ultimo_login']) ? date('d/m/Y H:i', strtotime((string) $item['ultimo_login'])) : 'Nunca' ?></div>
                                    </div>
                                    <div class="user-rights">
                                        <div><?= htmlspecialchars($rightsSummary) ?></div>
                                        <div><?= (int) $item['total_logins'] ?> login(s)</div>
                                    </div>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
                                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        <?php if (!empty($item['is_admin'])): ?><span class="badge blue">Admin</span><?php endif; ?>
                                        <?php if (!empty($item['must_change_password'])): ?><span class="badge orange">Primeiro acesso</span><?php endif; ?>
                                    </div>
                                </summary>

                                <div class="user-panel-body">
                                    <form method="POST" class="form-grid">
                                        <input type="hidden" name="action" value="update_user">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

                                        <div class="row-2">
                                            <div class="form-group">
                                                <label>Nome</label>
                                                <input type="text" name="nome" value="<?= htmlspecialchars((string) $item['nome']) ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Login</label>
                                                <input type="text" name="login" value="<?= htmlspecialchars((string) ($item['login'] ?? '')) ?>" required>
                                            </div>
                                        </div>

                                        <div class="row-2">
                                            <div class="form-group">
                                                <label>Setor</label>
                                                <select name="setor" required>
                                                    <?php foreach (SETORES as $setor): ?>
                                                        <option value="<?= htmlspecialchars($setor) ?>" <?= (($item['setor'] ?? '') === $setor) ? 'selected' : '' ?>><?= htmlspecialchars($setor) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Loja</label>
                                                <select name="loja">
                                                    <option value="">Todas as lojas</option>
                                                    <?php foreach (LOJAS as $loja): ?>
                                                        <option value="<?= htmlspecialchars($loja) ?>" <?= (($item['loja'] ?? '') === $loja) ? 'selected' : '' ?>><?= htmlspecialchars($loja) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Direitos do sistema</label>
                                            <div class="permission-grid">
                                                <?php foreach (DASHBOARD_SECTIONS as $key => $label): ?>
                                                    <label class="check">
                                                        <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($key) ?>" <?= in_array($key, $item['permissions_array'], true) ? 'checked' : '' ?>>
                                                        <span><?= htmlspecialchars($label) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="panel-actions">
                                            <label class="check">
                                                <input type="checkbox" name="is_admin" value="1" <?= !empty($item['is_admin']) ? 'checked' : '' ?>>
                                                <span>Conta admin</span>
                                            </label>
                                            <label class="check">
                                                <input type="checkbox" name="ativo" value="1" <?= !empty($item['ativo']) ? 'checked' : '' ?>>
                                                <span>Conta ativa</span>
                                            </label>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                                    </form>

                                    <form method="POST" class="form-grid">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <div class="row-2">
                                            <div class="form-group">
                                                <label>Redefinir senha</label>
                                                <input type="text" name="new_password" value="<?= htmlspecialchars(DEFAULT_INITIAL_PASSWORD) ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button type="submit" class="btn btn-warning">Resetar e forcar troca</button>
                                            </div>
                                        </div>
                                    </form>

                                    <?php if ((int) $item['id'] !== (int) $user['id']): ?>
                                        <form method="POST" onsubmit="return confirm('Deseja excluir esta conta?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Excluir conta</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>
</body>
</html>
