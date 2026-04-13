<?php
// =============================================================
// LOGS DE ACESSO — Apenas Diretoria
// =============================================================
require_once 'auth.php';
requireLogin();
$user = currentUser();
if (!userCanManageUsers($user)) {
    header('Location: ' . BASE_URL . '/index.php'); exit;
}

$pdo = getDB();

// Paginação
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM log_acessos")->fetchColumn();
$pages = (int)ceil($total / $limit);

$logs = $pdo->prepare("
    SELECT l.*, u.nome, u.login, u.email, u.setor
    FROM log_acessos l
    LEFT JOIN usuarios u ON u.id = l.usuario_id
    ORDER BY l.criado_em DESC
    LIMIT ? OFFSET ?
");
$logs->execute([$limit, $offset]);
$logs = $logs->fetchAll();

// Estatísticas
$statsHoje = $pdo->query("SELECT COUNT(*) FROM log_acessos WHERE DATE(criado_em) = CURDATE()")->fetchColumn();
$statsSemana = $pdo->query("SELECT COUNT(*) FROM log_acessos WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$statsUnicos = $pdo->query("SELECT COUNT(DISTINCT usuario_id) FROM log_acessos WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logs de Acesso — Dashboard Holística</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #f0f4f8; --surface: #fff; --border: #e2e8f0;
    --text: #0f172a; --text-muted: #64748b;
    --primary: #2563eb; --danger: #dc2626;
    --sidebar-bg: #0f172a; --radius: 12px;
}
body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); }
.layout { display: flex; min-height: 100vh; }
.sidebar { width: 220px; background: var(--sidebar-bg); position: fixed; top:0;left:0;bottom:0; display:flex;flex-direction:column; }
.sb-brand { padding: 20px 16px; border-bottom: 1px solid rgba(255,255,255,.08); }
.sb-brand h2 { color:white;font-size:14px;font-weight:700; }
.sb-nav { flex:1; padding:12px; }
.sb-link { display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:8px;color:rgba(255,255,255,.65);font-size:13px;font-weight:500;text-decoration:none;margin-bottom:2px;transition:.15s; }
.sb-link:hover{background:rgba(255,255,255,.07);color:white;}
.sb-link.active{background:var(--primary);color:white;}
.sb-link svg{width:16px;height:16px;}
.main { margin-left:220px; flex:1; }
.topbar { background:var(--surface);border-bottom:1px solid var(--border);padding:0 28px;height:60px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50; }
.topbar h1 { font-size:16px;font-weight:700;flex:1; }
.content { padding:24px 28px; }
.btn-sm { padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:6px; }
.btn-gray { background:#e2e8f0;color:var(--text); }
.btn-danger { background:var(--danger);color:white; }
.stats { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px; }
.stat-card { background:var(--surface);border-radius:var(--radius);padding:18px;border:1px solid var(--border); }
.stat-card .label { font-size:11.5px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px; }
.stat-card .value { font-size:26px;font-weight:800;color:var(--text); }
.table-card { background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);overflow:hidden; }
.table-head-bar { padding:14px 18px;border-bottom:1px solid var(--border);font-size:14px;font-weight:700; }
table { width:100%;border-collapse:collapse;font-size:13px; }
th { padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);border-bottom:1px solid var(--border);background:#f8fafc; }
td { padding:10px 16px;border-bottom:1px solid #f1f5f9; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#f8fafc; }
.badge { display:inline-flex;align-items:center;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600; }
.badge-blue  { background:#dbeafe;color:#1d4ed8; }
.badge-purple { background:#ede9fe;color:#7c3aed; }
.badge-orange { background:#ffedd5;color:#ea580c; }
.badge-green  { background:#dcfce7;color:#16a34a; }
.badge-gray   { background:#f1f5f9;color:#475569; }
.pagination { display:flex;gap:6px;margin-top:16px;flex-wrap:wrap; }
.page-btn { padding:6px 12px;border-radius:7px;background:var(--surface);border:1px solid var(--border);font-size:13px;cursor:pointer;text-decoration:none;color:var(--text); }
.page-btn.active { background:var(--primary);color:white;border-color:var(--primary); }
</style>
</head>
<body>
<div class="layout">
<nav class="sidebar">
    <div class="sb-brand"><h2>Holística BI</h2></div>
    <div class="sb-nav">
        <a class="sb-link" href="index.php"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>Dashboard</a>
        <a class="sb-link" href="admin.php"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>Usuários</a>
        <a class="sb-link active" href="logs.php"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.72L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95zM11 2.05C5.95 2.55 2 6.82 2 12c0 3.76 2.09 7.07 5.22 8.78L6 22h5V2.05z"/></svg>Logs</a>
        <a class="sb-link" href="cache.php"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>Cache</a>
    </div>
</nav>
<div class="main">
    <header class="topbar">
        <h1>📋 Logs de Acesso</h1>
        <a href="admin.php" class="btn-sm btn-gray">← Admin</a>
        <a href="logout.php" class="btn-sm btn-danger">Sair</a>
    </header>
    <div class="content">
        <div class="stats">
            <div class="stat-card">
                <div class="label">Acessos Hoje</div>
                <div class="value"><?= number_format($statsHoje) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Últimos 7 dias</div>
                <div class="value"><?= number_format($statsSemana) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Usuários Únicos (30d)</div>
                <div class="value"><?= number_format($statsUnicos) ?></div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-head-bar">📋 <?= number_format($total) ?> registros — Página <?= $page ?> de <?= $pages ?></div>
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Usuário</th><th>Setor</th>
                        <th>IP</th><th>Data/Hora</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:11px"><?= $log['id'] ?></td>
                    <td>
                        <div><strong><?= htmlspecialchars($log['nome'] ?? '—') ?></strong></div>
                        <div style="font-size:11.5px;color:var(--text-muted)">Login: <?= htmlspecialchars((string) (($log['login'] ?? '') !== '' ? $log['login'] : '—')) ?></div>
                    </td>
                    <td><?php
                        $bc = ['Diretoria'=>'badge-purple','Marketing'=>'badge-blue','Coordenador de Loja'=>'badge-orange','Gerente'=>'badge-green'];
                        $s  = $log['setor'] ?? 'N/A';
                        echo '<span class="badge '.($bc[$s]??'badge-gray').'">'.$s.'</span>';
                    ?></td>
                    <td style="font-size:12px;color:var(--text-muted);font-family:monospace"><?= htmlspecialchars($log['ip'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y H:i:s', strtotime($log['criado_em'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a class="page-btn <?= $p===$page?'active':'' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</body>
</html>
