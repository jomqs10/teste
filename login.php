<?php
require_once 'auth.php';

$currentUser = currentUser();
if ($currentUser !== null) {
    if (!empty($currentUser['must_change_password'])) {
        header('Location: ' . BASE_URL . '/primeiro-acesso.php');
        exit;
    }

    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
if (!authBootstrapReady()) {
    $error = 'O dashboard nao conseguiu preparar o sistema de login. Atualize os arquivos publicados e tente novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = doLogin($_POST['login'] ?? '', $_POST['senha'] ?? '');
    if ($result['success']) {
        $user = $result['user'] ?? currentUser();
        if (!empty($user['must_change_password'])) {
            header('Location: ' . BASE_URL . '/primeiro-acesso.php');
            exit;
        }

        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    $error = $result['message'] ?? 'Nao foi possivel entrar.';
}

$timeout = !empty($_GET['timeout']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Dashboard Holistica</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    background: linear-gradient(160deg, #faf7f8 0%, #f3e7ec 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.login-wrapper { width: 100%; max-width: 430px; }
.logo-area { text-align: center; margin-bottom: 20px; }
.logo-icon { height: 96px; display: inline-block; }
.logo-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }
.card {
    background: rgba(255,255,255,.97);
    border-radius: 22px;
    padding: 34px;
    box-shadow: 0 22px 60px rgba(76, 18, 39, .18);
    border: 1px solid rgba(137, 32, 66, .08);
}
.eyebrow {
    display: inline-flex;
    align-items: center;
    padding: 6px 11px;
    border-radius: 999px;
    background: #f7e7ee;
    color: #892042;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: 14px;
}
h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}
.subtitle {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 24px;
}
.alert {
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 13.5px;
    margin-bottom: 18px;
}
.alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.alert-warning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.form-grid { display: grid; gap: 16px; }
.form-group { display: grid; gap: 6px; }
label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}
input {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-family: inherit;
    color: #0f172a;
    background: #f8fafc;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
}
input:focus {
    border-color: #892042;
    box-shadow: 0 0 0 4px rgba(137, 32, 66, .12);
    background: #fff;
}
.btn {
    border: none;
    border-radius: 12px;
    padding: 13px 18px;
    font-size: 15px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
}
.btn-primary {
    background: linear-gradient(135deg, #892042 0%, #b03463 100%);
    color: #fff;
    box-shadow: 0 16px 26px rgba(137, 32, 66, .22);
}
.help {
    margin-top: 18px;
    padding: 13px 14px;
    border-radius: 12px;
    background: #f8fafc;
    color: #64748b;
    font-size: 13px;
    line-height: 1.6;
}
</style>
</head>
<body>
<div class="login-wrapper">
    <div class="logo-area">
        <div class="logo-icon">
            <img src="logo grupo holistica.png" alt="Logotipo Grupo Holistica">
        </div>
    </div>

    <div class="card">
        <div class="eyebrow">Dashboard</div>
        <h1>Acesso protegido</h1>
        <p class="subtitle">Entre com seu login para acessar o dashboard. No primeiro acesso, o sistema vai pedir a troca da senha inicial.</p>

        <?php if ($timeout): ?>
            <div class="alert alert-warning">Sua sessao expirou. Entre novamente para continuar.</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="form-grid" autocomplete="off">
            <div class="form-group">
                <label for="login">Login</label>
                <input type="text" id="login" name="login" placeholder="admin, alexandre, debora..." value="<?= htmlspecialchars((string) ($_POST['login'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
            </div>

            <button type="submit" class="btn btn-primary">Entrar no sistema</button>
        </form>

        <div class="help">
            Contas iniciais criadas neste fluxo: Admin, Alexandre, Debora, Marketing e Katbe. O cadastro publico foi removido para simplificar o acesso.
        </div>
    </div>
</div>
</body>
</html>
