<?php
// =============================================================
// GERENCIAMENTO DE CACHE — Apenas Diretoria
// =============================================================
require_once 'auth.php';
requireLogin();
$user = currentUser();
if (!userCanManageUsers($user)) {
    header('Location: ' . BASE_URL . '/index.php'); exit;
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aba = $_POST['aba'] ?? 'all';
    $deleted = 0;

    $pattern = sys_get_temp_dir() . '/hol_*.cache';
    $files = glob($pattern);

    if ($files) {
        foreach ($files as $f) {
            // Filtra por aba específica se necessário
            if ($aba !== 'all') {
                if (!str_contains(basename($f), md5($aba))) continue;
            }
            if (@unlink($f)) $deleted++;
        }
    }

    $msg = "Cache limpo! $deleted arquivo(s) removido(s).";
    $msgType = 'success';
}

// Lista arquivos de cache
$cacheFiles = glob(sys_get_temp_dir() . '/hol_*.cache') ?: [];
$cacheInfo = [];
foreach ($cacheFiles as $f) {
    $cacheInfo[] = [
        'file' => basename($f),
        'size' => filesize($f),
        'age'  => time() - filemtime($f),
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cache — Dashboard Holística</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--bg:#f0f4f8;--surface:#fff;--border:#e2e8f0;--text:#0f172a;--text-muted:#64748b;--primary:#2563eb;--danger:#dc2626;--success:#16a34a;--sidebar-bg:#0f172a;--radius:12px;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);}
.layout{display:flex;min-height:100vh;}
.sidebar{width:220px;background:var(--sidebar-bg);position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;}
.sb-brand{padding:20px 16px;border-bottom:1px solid rgba(255,255,255,.08);}
.sb-brand h2{color:white;font-size:14px;font-weight:700;}
.sb-nav{flex:1;padding:12px;}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:8px;color:rgba(255,255,255,.65);font-size:13px;font-weight:500;text-decoration:none;margin-bottom:2px;transition:.15s;}
.sb-link:hover{background:rgba(255,255,255,.07);color:white;}
.sb-link.active{background:var(--primary);color:white;}
.sb-link svg{width:16px;height:16px;}
.main{margin-left:220px;flex:1;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 28px;height:60px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50;}
.topbar h1{font-size:16px;font-weight:700;flex:1;}
.content{padding:24px 28px;max-width:720px;}
.btn-sm{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-gray{background:#e2e8f0;color:var(--text);}
.btn-danger{background:var(--danger);color:white;}
.btn-primary{background:var(--primary);color:white;}
.alert{padding:12px 16px;border-radius:10px;font-size:13.5px;margin-bottom:20px;}
.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.card{background:var(--surface);border-radius:var(--radius);padding:24px;border:1px solid var(--border);margin-bottom:20px;}
.card h3{font-size:15px;font-weight:700;margin-bottom:6px;}
.card p{font-size:13.5px;color:var(--text-muted);margin-bottom:16px;}
select,input{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;outline:none;margin-bottom:12px;}
select:focus,input:focus{border-color:var(--primary);}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);border-bottom:1px solid var(--border);background:#f8fafc;}
td{padding:9px 12px;border-bottom:1px solid #f1f5f9;font-family:monospace;font-size:12px;}
</style>
</head>
<body>
<div class="layout">
<nav class="sidebar">
    <div class="sb-brand"><h2>Holística BI</h2></div>
    <div class="sb-nav">
        <a class="sb-link" href="index.php"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>Dashboard</a>
        <a class="sb-link" href="admin.php"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>Usuários</a>
        <a class="sb-link" href="logs.php"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.72L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95zM11 2.05C5.95 2.55 2 6.82 2 12c0 3.76 2.09 7.07 5.22 8.78L6 22h5V2.05z"/></svg>Logs</a>
        <a class="sb-link active" href="cache.php"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>Cache</a>
    </div>
</nav>
<div class="main">
    <header class="topbar">
        <h1>🔄 Gerenciamento de Cache</h1>
        <a href="admin.php" class="btn-sm btn-gray">← Admin</a>
    </header>
    <div class="content">
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>🔄 Limpar Cache do Google Sheets</h3>
            <p>Os dados são cacheados por 5 minutos para reduzir chamadas ao Google Sheets. Use esta opção para forçar a atualização imediata.</p>
            <form method="POST">
                <select name="aba">
                    <option value="all">Todos os dados</option>
                    <option value="vendas">Somente Vendas</option>
                    <option value="formulas">Somente Orçamentos</option>
                    <option value="trafego_pago">Somente Tráfego Pago</option>
                    <option value="vendedores">Somente Vendedores</option>
                </select>
                <button type="submit" class="btn-sm btn-primary">🗑️ Limpar Cache Agora</button>
            </form>
        </div>

        <div class="card">
            <h3>📁 Arquivos de Cache Ativos</h3>
            <p><?= count($cacheFiles) ?> arquivo(s) de cache no servidor.</p>
            <?php if ($cacheInfo): ?>
            <table>
                <thead><tr><th>Arquivo</th><th>Tamanho</th><th>Idade</th></tr></thead>
                <tbody>
                <?php foreach ($cacheInfo as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['file']) ?></td>
                    <td><?= number_format($c['size'] / 1024, 1) ?> KB</td>
                    <td><?= $c['age'] < 60 ? $c['age'].'s' : round($c['age']/60).'min' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color:var(--text-muted);font-size:13px">Nenhum arquivo de cache ativo.</p>
            <?php endif; ?>
        </div>

        <div class="card" style="background:#fffbeb;border-color:#fde68a;">
            <h3 style="color:#92400e;">ℹ️ Informações sobre o Cache</h3>
            <p style="color:#92400e;margin-bottom:0;">
                O cache é armazenado em arquivos temporários no servidor com TTL de 5 minutos.
                Isso reduz o número de requisições ao Google Sheets e melhora a performance do dashboard.
                Após limpar o cache, a próxima visita a cada seção buscará dados frescos.
            </p>
        </div>
    </div>
</div>
</div>
</body>
</html>
