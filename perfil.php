<?php
require_once 'auth.php';
requireLogin();

$user = currentUser();
$pdo = getDB();
$msg = '';
$msgType = 'success';

if (($_POST['action'] ?? '') === 'update_nome') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    if (strlen($nome) < 2) {
        $msg = 'Informe um nome valido.';
        $msgType = 'error';
    } else {
        $pdo->prepare("UPDATE usuarios SET nome = ? WHERE id = ?")->execute([$nome, $user['id']]);
        refreshCurrentUser();
        $user = currentUser();
        $msg = 'Nome atualizado com sucesso.';
        $msgType = 'success';
    }
}

if (($_POST['action'] ?? '') === 'change_senha') {
    $atual = (string) ($_POST['senha_atual'] ?? '');
    $nova = (string) ($_POST['nova_senha'] ?? '');
    $confirmar = (string) ($_POST['confirmar'] ?? '');

    $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([$user['id']]);
    $dbUser = $stmt->fetch();

    if (!$dbUser || !password_verify($atual, (string) $dbUser['senha'])) {
        $msg = 'Senha atual incorreta.';
        $msgType = 'error';
    } elseif (strlen($nova) < authMinPasswordLength()) {
        $msg = 'A nova senha precisa ter pelo menos ' . authMinPasswordLength() . ' caracteres.';
        $msgType = 'error';
    } elseif ($nova !== $confirmar) {
        $msg = 'As senhas nao coincidem.';
        $msgType = 'error';
    } else {
        updateUserPassword((int) $user['id'], $nova, false);
        refreshCurrentUser();
        $user = currentUser();
        $msg = 'Senha alterada com sucesso.';
        $msgType = 'success';
    }
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user['id']]);
$fullUser = $stmt->fetch();

$totalLoginsStmt = $pdo->prepare("SELECT COUNT(*) FROM log_acessos WHERE usuario_id = ?");
$totalLoginsStmt->execute([$user['id']]);
$totalLogins = (int) $totalLoginsStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meu Perfil - Dashboard Holistica</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #f4f6fb;
    --surface: #ffffff;
    --border: #e2e8f0;
    --text: #0f172a;
    --muted: #64748b;
    --primary: #892042;
    --danger: #b91c1c;
    --success: #15803d;
    --sidebar-bg: #892042;
    --sidebar-active: #6b1330;
    --radius: 16px;
    --shadow: 0 10px 28px rgba(15, 23, 42, .08);
}
body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); }
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
.main { margin-left: 240px; flex: 1; }
.topbar {
    height: 68px;
    background: rgba(255,255,255,.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 0 28px;
}
.topbar h1 { font-size: 20px; font-weight: 700; }
.content { padding: 28px; max-width: 760px; }
.btn, .btn-link {
    border: none;
    border-radius: 12px;
    padding: 10px 16px;
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
.btn-gray { background: #e2e8f0; color: #334155; }
.btn-danger { background: #fee2e2; color: var(--danger); }
.alert {
    padding: 14px 16px;
    border-radius: 14px;
    font-size: 14px;
    margin-bottom: 18px;
}
.alert.success { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
.alert.error { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
.hero, .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}
.hero {
    padding: 24px;
    display: flex;
    gap: 18px;
    align-items: center;
    margin-bottom: 20px;
}
.avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #b03463 0%, #892042 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    font-weight: 800;
    flex-shrink: 0;
}
.hero h2 { font-size: 22px; font-weight: 700; }
.hero p { color: var(--muted); margin-top: 6px; line-height: 1.6; }
.meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}
.badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}
.badge.blue { background: #dbeafe; color: #1d4ed8; }
.badge.orange { background: #ffedd5; color: #c2410c; }
.badge.purple { background: #ede9fe; color: #6d28d9; }
.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.stat {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 16px;
    text-align: center;
    box-shadow: var(--shadow);
}
.stat strong { display: block; font-size: 26px; font-weight: 800; }
.stat span { display: block; margin-top: 5px; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; }
.card { padding: 22px; margin-bottom: 20px; }
.card h3 { font-size: 16px; font-weight: 700; margin-bottom: 18px; }
.form-group { margin-bottom: 14px; }
label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 6px;
}
input {
    width: 100%;
    padding: 11px 13px;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    font-size: 14px;
    font-family: inherit;
    background: #f8fafc;
    color: var(--text);
    outline: none;
}
input:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(137, 32, 66, .08);
}
input[disabled] {
    background: #f1f5f9;
    color: var(--muted);
}
@media (max-width: 860px) {
    .layout { display: block; }
    .sidebar { position: static; width: 100%; }
    .main { margin-left: 0; }
    .content { padding: 20px; }
    .hero { flex-direction: column; align-items: flex-start; }
    .stats { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <h2>Dashboard Holistica</h2>
            <p>Minha conta</p>
        </div>
        <nav class="nav">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="perfil.php" class="nav-link active">Meu perfil</a>
            <?php if (userCanManageUsers($user)): ?>
                <a href="admin.php" class="nav-link">Administracao</a>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Meu perfil</h1>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="index.php" class="btn-link btn-gray">Voltar ao dashboard</a>
                <a href="logout.php" class="btn-link btn-danger">Sair</a>
            </div>
        </header>

        <div class="content">
            <?php if ($msg !== ''): ?>
                <div class="alert <?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <section class="hero">
                <div class="avatar"><?= htmlspecialchars(strtoupper(substr((string) $user['nome'], 0, 1))) ?></div>
                <div>
                    <h2><?= htmlspecialchars((string) $user['nome']) ?></h2>
                    <p>Login: <?= htmlspecialchars((string) ($user['login'] ?? '')) ?><?= !empty($user['email']) ? ' | Email: ' . htmlspecialchars((string) $user['email']) : '' ?></p>
                    <div class="meta">
                        <span class="badge <?= ($user['setor'] ?? '') === 'Diretoria' ? 'purple' : (($user['setor'] ?? '') === 'Marketing' ? 'blue' : 'orange') ?>"><?= htmlspecialchars((string) ($user['setor'] ?? '')) ?></span>
                        <?php if (!empty($user['loja'])): ?><span class="badge orange"><?= htmlspecialchars((string) $user['loja']) ?></span><?php endif; ?>
                        <?php if (!empty($user['must_change_password'])): ?><span class="badge orange">Primeiro acesso pendente</span><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="stats">
                <article class="stat">
                    <strong><?= number_format($totalLogins) ?></strong>
                    <span>Total de logins</span>
                </article>
                <article class="stat">
                    <strong><?= !empty($fullUser['ultimo_login']) ? date('d/m', strtotime((string) $fullUser['ultimo_login'])) : '-' ?></strong>
                    <span>Ultimo acesso</span>
                </article>
                <article class="stat">
                    <strong><?= !empty($fullUser['ativo']) ? 'Ativa' : 'Desativada' ?></strong>
                    <span>Status da conta</span>
                </article>
            </section>

            <section class="card">
                <h3>Dados da conta</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_nome">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="nome" value="<?= htmlspecialchars((string) $user['nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Login</label>
                        <input type="text" value="<?= htmlspecialchars((string) ($user['login'] ?? '')) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Setor</label>
                        <input type="text" value="<?= htmlspecialchars((string) ($user['setor'] ?? '')) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Loja</label>
                        <input type="text" value="<?= htmlspecialchars((string) ($user['loja'] ?? 'Todas as lojas')) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar nome</button>
                </form>
            </section>

            <section class="card">
                <h3>Alterar senha</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_senha">
                    <div class="form-group">
                        <label>Senha atual</label>
                        <input type="password" name="senha_atual" required>
                    </div>
                    <div class="form-group">
                        <label>Nova senha (min. <?= authMinPasswordLength() ?> caracteres)</label>
                        <input type="password" name="nova_senha" minlength="<?= authMinPasswordLength() ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmar nova senha</label>
                        <input type="password" name="confirmar" minlength="<?= authMinPasswordLength() ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar nova senha</button>
                </form>
            </section>
        </div>
    </main>
</div>
</body>
</html>
