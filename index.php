<?php
require_once 'auth.php';
requireLogin();
$user = currentUser();
$allowedSections = userAllowedSections($user);
$canManageUsers = userCanManageUsers($user);
if ($canManageUsers && !in_array('holtec', $allowedSections, true)) {
    $allowedSections[] = 'holtec';
}
$canSeeAllLojas = userCanSeeAllLojas($user);
$fullAccess = userHasFullAccess();
$lojaRestrita = userLoja();
$defaultSection = userDefaultSection($user);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Grupo Holística</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ============================================================
   RESET & BASE
============================================================ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --sidebar-w: 240px;
    --header-h: 64px;
    --bg: #f0f4f8;
    --surface: #ffffff;
    --border: #e2e8f0;
    --text: #0f172a;
    --text-muted: #64748b;
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #eff6ff;
    --success: #16a34a;
    --warning: #d97706;
    --danger: #dc2626;
    --sidebar-bg: #892042;
    --sidebar-text: rgba(255,255,255,.7);
    --sidebar-active: #6b1330;
    --radius: 12px;
    --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,.1);
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    line-height: 1.5;
    overflow-x: hidden;
}

/* ============================================================
   LAYOUT
============================================================ */
#app { display: flex; min-height: 100vh; }

/* SIDEBAR */
#sidebar {
    width: var(--sidebar-w);
    min-height: 100vh;
    background: var(--sidebar-bg);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 100;
    transition: transform .25s ease;
}

.sidebar-brand {
    padding: 20px 20px 16px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.sidebar-brand .brand-logo {
    display: flex;
    align-items: center;
    gap: 10px;
}


.brand-text h2 {
    color: white;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.2;
}

.brand-text p {
    color: rgba(255,255,255,.4);
    font-size: 10.5px;
    font-weight: 400;
    margin-top: 1px;
}

.sidebar-nav {
    flex: 1;
    padding: 16px 12px;
    overflow-y: auto;
}

.nav-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: rgba(255,255,255,.3);
    padding: 0 8px;
    margin: 16px 0 6px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 9px;
    cursor: pointer;
    transition: background .15s, color .15s;
    color: var(--sidebar-text);
    font-size: 13.5px;
    font-weight: 500;
    margin-bottom: 2px;
    user-select: none;
}

.nav-item:hover { background: rgba(255,255,255,.07); color: white; }

.nav-item.active {
    background: var(--sidebar-active);
    color: white;
}

.nav-item svg { width: 17px; height: 17px; flex-shrink: 0; opacity: .75; }
.nav-item.active svg { opacity: 1; }

.sidebar-footer {
    padding: 14px 12px;
    border-top: 1px solid rgba(255,255,255,.08);
}

.user-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 9px;
    background: rgba(255,255,255,.05);
}

.user-avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: white;
    flex-shrink: 0;
}

.user-info { flex: 1; min-width: 0; }
.user-info .name {
    font-size: 12.5px; font-weight: 600; color: white;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-info .role {
    font-size: 11px; color: rgba(255,255,255,.4);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.logout-btn {
    background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,.4); padding: 4px;
    border-radius: 6px; transition: color .15s, background .15s;
    display: flex; align-items: center;
}
.logout-btn:hover { color: #ef4444; background: rgba(239,68,68,.1); }
.logout-btn svg { width: 16px; height: 16px; }

/* MAIN */
#main {
    margin-left: var(--sidebar-w);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

/* TOPBAR */
#topbar {
    height: var(--header-h);
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    padding: 0 28px;
    gap: 16px;
    position: sticky;
    top: 0;
    z-index: 50;
}

#topbar h1 {
    font-size: 17px;
    font-weight: 700;
    color: var(--text);
    flex: 1;
    min-width: 0;
}

.topbar-filters {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    min-width: 0;
}

.topbar-filters > * {
    min-width: 0;
}

.filter-select {
    padding: 7px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: var(--text);
    background: var(--bg);
    outline: none;
    cursor: pointer;
    transition: border-color .2s;
}
.filter-select:focus { border-color: var(--primary); }

.filter-stack {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 170px;
}

.filter-stack .filter-select {
    min-height: 48px;
}

.filter-multi {
    position: relative;
}

.filter-multi > summary {
    list-style: none;
}

.filter-multi > summary::-webkit-details-marker {
    display: none;
}

.filter-multi-summary {
    min-width: 150px;
    padding: 7px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    background: var(--bg);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    min-height: 48px;
}

.filter-multi[open] .filter-multi-summary {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}

.filter-multi-label {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.filter-multi-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--text-muted);
}

.filter-multi-value {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 170px;
}

.filter-multi-chevron {
    color: var(--text-muted);
    font-size: 11px;
    transition: transform .2s ease;
    flex-shrink: 0;
}

.filter-multi[open] .filter-multi-chevron {
    transform: rotate(180deg);
}

.filter-multi-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 260px;
    max-height: 280px;
    overflow: auto;
    padding: 10px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--surface);
    box-shadow: var(--shadow-md);
    z-index: 60;
}

.filter-multi-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.filter-multi-action {
    border: 1px solid var(--border);
    background: var(--bg);
    color: var(--text-muted);
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.filter-multi-options {
    display: grid;
    gap: 6px;
}

.filter-multi-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 8px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text);
}

.filter-multi-option:hover {
    background: var(--primary-light);
}

.filter-multi-option input {
    margin: 0;
    accent-color: var(--primary);
}

.filter-input {
    padding: 7px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: var(--text);
    background: var(--bg);
    outline: none;
    min-width: 190px;
    max-width: 100%;
}
.filter-input:focus { border-color: var(--primary); }

.btn-filter {
    padding: 7px 16px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s;
}
.btn-filter:hover { background: var(--primary-dark); }

.marketing-shell {
    display: grid;
    gap: 24px;
}

.marketing-shell-card {
    background: linear-gradient(135deg, #ffffff 0%, #eef4ff 52%, #f8fafc 100%);
    border: 1px solid #dbeafe;
    border-radius: var(--radius);
    padding: 22px;
    box-shadow: var(--shadow);
}

.marketing-shell-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 18px;
    flex-wrap: wrap;
}

.marketing-shell-kicker {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: uppercase;
    color: var(--primary);
    margin-bottom: 8px;
}

.marketing-shell-copy h2 {
    font-size: 22px;
    font-weight: 800;
    color: var(--text);
    margin-bottom: 6px;
}

.marketing-shell-copy p {
    font-size: 13px;
    color: var(--text-muted);
    max-width: 720px;
}

.marketing-tab-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.marketing-tab {
    min-width: 196px;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: rgba(255,255,255,.92);
    color: var(--text);
    cursor: pointer;
    text-align: left;
    transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease, background .15s ease;
    box-shadow: 0 8px 18px rgba(15, 23, 42, .05);
    font-family: inherit;
}

.marketing-tab:hover {
    transform: translateY(-1px);
    border-color: #93c5fd;
}

.marketing-tab.active {
    background: linear-gradient(135deg, #892042 0%, #a92e58 100%);
    border-color: #892042;
    color: #fff;
    box-shadow: 0 14px 28px rgba(137, 32, 66, .24);
}

.marketing-tab span {
    display: block;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 4px;
}

.marketing-tab small {
    display: block;
    font-size: 12px;
    line-height: 1.45;
    color: var(--text-muted);
}

.marketing-tab.active small {
    color: rgba(255,255,255,.82);
}

.marketing-spotlight {
    background: linear-gradient(135deg, #fff7ed 0%, #ffffff 48%, #f8fafc 100%);
    border: 1px solid #fed7aa;
    border-radius: var(--radius);
    padding: 22px;
    box-shadow: var(--shadow);
}

.marketing-spotlight-head {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.marketing-spotlight-head h3 {
    font-size: 22px;
    font-weight: 800;
    color: var(--text);
    margin-bottom: 6px;
}

.marketing-spotlight-head p {
    font-size: 13px;
    color: var(--text-muted);
    max-width: 700px;
}

.marketing-spotlight-badge {
    align-self: flex-start;
    background: rgba(217,119,6,.12);
    color: #9a3412;
    border: 1px solid rgba(217,119,6,.2);
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.spotlight-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.spotlight-card {
    background: rgba(255,255,255,.88);
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, .04);
}

.spotlight-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 10px;
}

.spotlight-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--text);
    line-height: 1.1;
    margin-bottom: 6px;
}

.spotlight-sub {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.45;
}

.spotlight-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 10px 18px;
}

.spotlight-item {
    display: flex;
    gap: 8px;
    font-size: 13px;
    line-height: 1.5;
    color: var(--text);
}

.spotlight-item::before {
    content: '•';
    color: var(--warning);
    font-weight: 700;
}

.mobile-menu-btn {
    display: none;
    background: none; border: none; cursor: pointer;
    color: var(--text); padding: 6px;
}
.mobile-menu-btn svg { width: 22px; height: 22px; }

/* CONTENT AREA */
#content {
    padding: 28px;
    flex: 1;
}

/* LOADING */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    color: var(--text-muted);
    gap: 16px;
}

.spinner {
    width: 40px; height: 40px;
    border: 3px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.error-state {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: var(--radius);
    padding: 24px;
    color: var(--danger);
    text-align: center;
}

/* CARDS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--primary);
}

.stat-card.green::before { background: var(--success); }
.stat-card.orange::before { background: var(--warning); }
.stat-card.red::before { background: var(--danger); }
.stat-card.purple::before { background: #7c3aed; }

.stat-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.stat-label svg { width: 14px; height: 14px; }

.stat-value {
    font-size: 26px;
    font-weight: 800;
    color: var(--text);
    line-height: 1;
    margin-bottom: 4px;
}

.stat-sub {
    font-size: 12px;
    color: var(--text-muted);
}

/* CHARTS */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}

.chart-card {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 22px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.chart-card.wide { grid-column: 1 / -1; }

.chart-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.chart-title svg { width: 16px; height: 16px; color: var(--primary); }

.chart-subtitle {
    margin-top: -10px;
    margin-bottom: 16px;
    font-size: 12px;
    color: var(--text-muted);
}

.chart-wrap { position: relative; height: 340px; }
.chart-card.wide .chart-wrap { height: 380px; }
.chart-card.compact .chart-wrap { height: 300px; }
.chart-wrap canvas { width: 100% !important; height: 100% !important; max-height: none; }

.insight-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.insight-card {
    border: 1px solid var(--border);
    border-left: 4px solid var(--primary);
    border-radius: var(--radius);
    padding: 16px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.insight-card.green { border-left-color: var(--success); }
.insight-card.orange { border-left-color: var(--warning); }
.insight-card.red { border-left-color: var(--danger); }
.insight-card.purple { border-left-color: #7c3aed; }

.insight-title {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 8px;
}

.insight-text {
    font-size: 13px;
    line-height: 1.6;
    color: var(--text);
}

.insight-highlight {
    font-weight: 800;
    color: var(--text);
}

.summary-box {
    border: 1px solid #dbeafe;
    border-radius: var(--radius);
    padding: 18px;
    background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
}

.summary-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 12px;
}

.summary-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 10px 18px;
}

.summary-item {
    font-size: 13px;
    line-height: 1.5;
    color: var(--text);
    display: flex;
    gap: 8px;
}

.summary-item::before {
    content: '•';
    color: var(--primary);
    font-weight: 700;
}

/* TABLES */
.table-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 18px;
}

.table-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.table-header svg { width: 16px; height: 16px; color: var(--primary); }

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

th {
    padding: 11px 16px;
    text-align: left;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
    background: #f8fafc;
}

td {
    padding: 11px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: var(--text);
}

.table-dropdown {
    min-width: 220px;
}

.table-dropdown > summary {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    min-width: 0;
    border-radius: 10px;
}

.table-dropdown > summary::-webkit-details-marker {
    display: none;
}

.table-dropdown > summary:hover .table-dropdown-label {
    color: var(--primary);
}

.table-dropdown > summary:focus-visible {
    outline: 2px solid rgba(37,99,235,.22);
    outline-offset: 2px;
}

.table-dropdown-label {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--text);
}

.table-dropdown-meta {
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--primary);
}

.table-dropdown-chevron {
    flex-shrink: 0;
    font-size: 11px;
    color: var(--text-muted);
    transition: transform .18s ease;
}

.table-dropdown[open] .table-dropdown-chevron {
    transform: rotate(180deg);
}

.table-dropdown-panel {
    margin-top: 10px;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: #f8fafc;
    display: grid;
    gap: 8px;
    max-width: 420px;
    box-shadow: 0 10px 24px rgba(15,23,42,.08);
}

.table-dropdown-item {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    line-height: 1.45;
}

.table-dropdown-index {
    flex-shrink: 0;
    font-size: 12px;
    font-weight: 700;
    color: var(--primary);
}

tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fafc; }

.badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 600;
}

.badge-green { background: #dcfce7; color: #16a34a; }
.badge-red   { background: #fee2e2; color: #dc2626; }
.badge-blue  { background: #dbeafe; color: #1d4ed8; }
.badge-orange { background: #ffedd5; color: #ea580c; }

.rank-num {
    width: 26px; height: 26px;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}

.rank-num.gold   { background: #fef9c3; color: #b45309; }
.rank-num.silver { background: #f1f5f9; color: #475569; }
.rank-num.bronze { background: #ffedd5; color: #92400e; }

/* ABC */
.abc-a { background: #dcfce7; color: #16a34a; }
.abc-b { background: #dbeafe; color: #1d4ed8; }
.abc-c { background: #f3f4f6; color: #6b7280; }

/* PROGRESS BAR */
.progress-bar {
    height: 6px;
    background: var(--border);
    border-radius: 99px;
    overflow: hidden;
    margin-top: 6px;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), #7c3aed);
    border-radius: 99px;
    transition: width .5s ease;
}

/* SECTION TITLE */
.section-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 14px;
    margin-top: 4px;
}

/* RESTRICTED BANNER */
.restricted-banner {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: var(--radius);
    padding: 12px 18px;
    font-size: 13px;
    color: #92400e;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* OVERLAY */
#overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.4);
    z-index: 90;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    #sidebar {
        transform: translateX(-100%);
    }
    #sidebar.open {
        transform: translateX(0);
        box-shadow: 4px 0 20px rgba(0,0,0,.3);
    }
    #overlay.show { display: block; }
    #main { margin-left: 0; }
    .mobile-menu-btn { display: flex; }
    #content { padding: 14px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .charts-grid { grid-template-columns: 1fr; }
    .summary-list,
    .insight-grid { grid-template-columns: 1fr; }
    #topbar {
        height: auto;
        min-height: var(--header-h);
        padding: 12px 16px;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 10px;
    }
    #topbar h1 {
        font-size: 15px;
        flex: 1 1 calc(100% - 44px);
        align-self: center;
    }
    .topbar-filters {
        order: 3;
        width: 100%;
        gap: 8px;
        display: grid;
        grid-template-columns: 1fr;
    }
    .filter-multi { width: 100%; }
    .filter-multi-summary { width: 100%; }
    .filter-multi-panel {
        position: static;
        width: 100%;
        margin-top: 8px;
        max-height: 240px;
        box-shadow: none;
        border-style: dashed;
    }
    .filter-input {
        width: 100%;
        min-width: 0;
    }
    .filter-multi-action {
        flex: 1 1 calc(33.333% - 6px);
        text-align: center;
    }
    .marketing-shell-card,
    .marketing-spotlight {
        padding: 18px;
    }
    .marketing-tab-list {
        width: 100%;
    }
    .marketing-tab {
        flex: 1 1 calc(50% - 5px);
        min-width: 0;
    }
    .marketing-spotlight-head {
        align-items: flex-start;
    }
    #cache-btn {
        order: 4;
        width: 100%;
        justify-content: center;
    }
    .chart-card {
        padding: 16px;
    }
    .chart-wrap { height: 280px; }
    .chart-card.wide .chart-wrap { height: 300px; }
    .chart-card.compact .chart-wrap { height: 240px; }
    table {
        min-width: 640px;
    }
}

@media (max-width: 560px) {
    #content { padding: 12px; }
    .stats-grid { grid-template-columns: 1fr; }
    .stat-card,
    .chart-card { padding: 14px; }
    .marketing-shell-copy h2,
    .marketing-spotlight-head h3 {
        font-size: 19px;
    }
    .marketing-tab {
        flex-basis: 100%;
    }
    .table-header {
        padding: 14px 16px;
        font-size: 13px;
    }
    th,
    td {
        padding: 10px 12px;
    }
}
</style>
</head>
<body>
<div id="app">

<!-- SIDEBAR -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <div class="brand-text">
                <h2>Farmácia Holística</h2>
                <p>Dashboard</p>
            </div>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-label">Principal</div>

        <?php if (in_array('faturamento', $allowedSections, true)): ?><div class="nav-item <?= $defaultSection === 'faturamento' ? 'active' : '' ?>" data-section="faturamento">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            Faturamento
        </div><?php endif; ?>

        <?php if (in_array('orcamentos', $allowedSections, true)): ?><div class="nav-item <?= $defaultSection === 'orcamentos' ? 'active' : '' ?>" data-section="orcamentos">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l7.59-7.59L21 8l-9 9z"/></svg>
            Orçamentos
        </div><?php endif; ?>

        <?php if (in_array('lojas', $allowedSections, true)): ?><div class="nav-item <?= $defaultSection === 'lojas' ? 'active' : '' ?>" data-section="lojas">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4v2l8 5 8-5V4zM4 13v7h16v-7l-8 5-8-5z"/></svg>
            Lojas
        </div><?php endif; ?>

        <?php if (in_array('vendedores', $allowedSections, true)): ?><div class="nav-item <?= $defaultSection === 'vendedores' ? 'active' : '' ?>" data-section="vendedores">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            Vendedores
        </div><?php endif; ?>

        <?php if (in_array('produtos', $allowedSections, true)): ?><div class="nav-item <?= $defaultSection === 'produtos' ? 'active' : '' ?>" data-section="produtos">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/></svg>
            Produtos
        </div><?php endif; ?>

        <?php if (in_array('holtec', $allowedSections, true)): ?><div class="nav-item <?= $defaultSection === 'holtec' ? 'active' : '' ?>" data-section="holtec">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-4V4c0-1.1-.9-2-2-2h-4C8.9 2 8 2.9 8 4v2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM10 4h4v2h-4V4zm4 10v3h-4v-3H7v-4h3V7h4v3h3v4h-3z"/></svg>
            Holtec
        </div><?php endif; ?>

        <?php if (in_array('marketing', $allowedSections, true)): ?>
        <div class="nav-item <?= $defaultSection === 'marketing' ? 'active' : '' ?>" data-section="marketing">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/></svg>
            Marketing
        </div>
        <?php endif; ?>
        <?php if (in_array('impressoes', $allowedSections, true)): ?>
        <div class="nav-item <?= $defaultSection === 'impressoes' ? 'active' : '' ?>" data-section="impressoes">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 7V3h12v4h2c1.1 0 2 .9 2 2v7h-4v5H6v-5H2V9c0-1.1.9-2 2-2h2zm2-2v2h8V5H8zm8 14v-5H8v5h8zm2-9a1 1 0 100-2 1 1 0 000 2z"/></svg>
            Impressoes
        </div>
        <?php endif; ?>
        <?php if (in_array('operacao', $allowedSections, true)): ?>
        <div class="nav-item <?= $defaultSection === 'operacao' ? 'active' : '' ?>" data-section="operacao">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8v8H3v-8zm10-10h8v18h-8V3zM3 3h8v8H3V3z"/></svg>
            Operacao
        </div>
        <?php endif; ?>
        <?php if (in_array('crm', $allowedSections, true)): ?>
        <div class="nav-item <?= $defaultSection === 'crm' ? 'active' : '' ?>" data-section="crm">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            CRM
        </div>
        <?php endif; ?>

        <?php if ($canManageUsers): ?>
        <div class="nav-label" style="margin-top:20px">Sistema</div>
        <a href="admin.php" class="nav-item" style="text-decoration:none">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
            Administração
        </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <?php if ($canManageUsers): ?>
        <a href="admin.php" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;color:rgba(255,255,255,.55);font-size:12.5px;font-weight:500;text-decoration:none;transition:.15s;margin-bottom:4px;" onmouseover="this.style.background='rgba(255,255,255,.07)';this.style.color='white'" onmouseout="this.style.background='';this.style.color='rgba(255,255,255,.55)'">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
            Administração
        </a>
        <?php endif; ?>
        <div class="user-card">
            <a href="perfil.php" style="display:flex;align-items:center;gap:10px;flex:1;text-decoration:none">
                <div class="user-avatar"><?= strtoupper(substr($user['nome'], 0, 1)) ?></div>
                <div class="user-info">
                    <div class="name"><?= htmlspecialchars($user['nome']) ?></div>
                    <div class="role"><?= htmlspecialchars($user['setor']) ?><?= $lojaRestrita ? ' · ' . htmlspecialchars($lojaRestrita) : '' ?></div>
                </div>
            </a>
            <a href="logout.php" class="logout-btn" title="Sair">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            </a>
        </div>
    </div>
</nav>

<!-- OVERLAY mobile -->
<div id="overlay"></div>

<!-- MAIN -->
<div id="main">
    <!-- TOPBAR -->
    <header id="topbar">
        <button class="mobile-menu-btn" id="menuToggle">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
        <h1 id="page-title"><?= htmlspecialchars(dashboardSectionLabel($defaultSection)) ?></h1>
        <div class="topbar-filters" id="topbar-filters">
            <!-- filtros injetados dinamicamente -->
        </div>
        <?php if ($canManageUsers): ?>
        <button onclick="clearCache()" id="cache-btn" title="Limpar cache e recarregar dados"
            style="background:none;border:1.5px solid var(--border);padding:6px 10px;border-radius:8px;cursor:pointer;color:var(--text-muted);font-size:12px;display:flex;align-items:center;gap:5px;font-family:inherit;white-space:nowrap;flex-shrink:0">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
            Atualizar dados
        </button>
        <?php endif; ?>
    </header>

    <!-- CONTENT -->
    <main id="content">
        <div class="loading-state">
            <div class="spinner"></div>
            <span>Carregando dados...</span>
        </div>
    </main>
</div>
</div>

<script>
// ============================================================
// CONFIGURAÇÃO
// ============================================================
const BASE  = '<?= BASE_URL ?>';
const USER  = {
    nome:       <?= json_encode($user['nome']) ?>,
    login:      <?= json_encode($user['login'] ?? '') ?>,
    setor:      <?= json_encode($user['setor']) ?>,
    loja:       <?= json_encode($lojaRestrita) ?>,
    fullAccess: <?= $fullAccess ? 'true' : 'false' ?>,
    sections:   <?= json_encode(array_values($allowedSections)) ?>,
    defaultSection: <?= json_encode($defaultSection) ?>,
    canSeeAllLojas: <?= $canSeeAllLojas ? 'true' : 'false' ?>,
    canManageUsers: <?= $canManageUsers ? 'true' : 'false' ?>
};
const USER_SECTIONS = Array.isArray(USER.sections) ? USER.sections : [];

const LOJAS = ['BOQUEIRÃO','CAIÇARA','GONZAGA','APARECIDA','EPITÁCIO','COMERCIAL'];
const MESES = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
               'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

const COLORS = {
    primary:  '#2563eb',
    success:  '#16a34a',
    warning:  '#d97706',
    danger:   '#dc2626',
    purple:   '#7c3aed',
    cyan:     '#0891b2',
    palette:  ['#2563eb','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#ea580c']
};

// ============================================================
// STATE
// ============================================================
let currentSection  = USER.defaultSection || USER_SECTIONS[0] || 'orcamentos';
let activeCharts    = [];
let filterOptionCache = {};
let filterLoja      = USER.loja ? [USER.loja] : [];
let filterAno       = [];
let filterMes       = [];
let filterAbc       = [];
let filterTipoProduto = [];
let filterProdutoBusca = '';
let filterHoltecCategoria = [];
let filterHoltecGrupo = [];
let filterCampanha  = [];
let filterCupom     = [];
let filterMarketingFonte = 'trafego_pago';
let filterRepresentante = [];
let filterTipoPedido = [];
let filterTipoPapel = [];

// ============================================================
// UTILS
// ============================================================
const fmt = {
    currency: v => 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}),
    number:   v => Number(v).toLocaleString('pt-BR'),
    pct:      v => Number(v).toFixed(1) + '%',
    k:        v => v >= 1000 ? (v/1000).toFixed(1)+'k' : Number(v).toLocaleString('pt-BR'),
    short:    v => new Intl.NumberFormat('pt-BR', { notation: 'compact', maximumFractionDigits: 1 }).format(Number(v) || 0)
};

function toArray(value) {
    if (Array.isArray(value)) return value.filter(v => String(v ?? '').trim() !== '');
    if (value === null || value === undefined || value === '') return [];
    return String(value).split(',').map(v => String(v).trim()).filter(Boolean);
}

function hasSelection(value) {
    return toArray(value).length > 0;
}

function firstSelection(value) {
    return toArray(value)[0] || '';
}

function uniqueValues(values) {
    return [...new Set(toArray(values))];
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function filterLabelText(selected, options, allLabel) {
    const chosen = toArray(selected);
    if (!chosen.length) return allLabel;
    const labelsByValue = new Map(options.map(opt => [String(opt.value), String(opt.label)]));
    const labels = chosen.map(value => labelsByValue.get(String(value)) || String(value));
    return labels.length <= 2 ? labels.join(', ') : `${labels.length} selecionados`;
}

function rememberFilterOptions(id, options) {
    const scope = currentSection === 'marketing'
        ? `${currentSection}:${filterMarketingFonte}`
        : currentSection;
    const cacheKey = `${scope}:${id}`;
    const normalized = options.map(opt => ({
        value: String(opt.value),
        label: String(opt.label)
    }));
    const merged = new Map();

    (filterOptionCache[cacheKey] || []).forEach(opt => merged.set(String(opt.value), opt));
    normalized.forEach(opt => merged.set(String(opt.value), opt));

    filterOptionCache[cacheKey] = [...merged.values()];
    return filterOptionCache[cacheKey];
}

function updateMultiSelectSummary(filterId) {
    const box = document.querySelector(`.filter-multi[data-filter-box="${filterId}"]`);
    if (!box) return;

    const allLabel = box.dataset.allLabel || 'Todos';
    const selected = getCheckedValues(filterId);
    const options = [...box.querySelectorAll('input[data-filter-id]')].map(input => ({
        value: input.value,
        label: input.dataset.label || input.value
    }));
    const summary = filterLabelText(selected, options, allLabel);
    const valueEl = box.querySelector('.filter-multi-value');
    if (valueEl) valueEl.textContent = summary;
}

function buildMultiSelect(id, title, options, selected, allLabel = 'Todos') {
    const selectedValues = uniqueValues(selected).map(String);
    const selectedSet = new Set(selectedValues);
    const items = rememberFilterOptions(id, options.map(opt => ({
        value: String(opt.value),
        label: String(opt.label)
    })));
    const summary = filterLabelText(selectedValues, items, allLabel);
    const optionsHtml = items.map(opt => `
        <label class="filter-multi-option">
            <input type="checkbox" data-filter-id="${escapeHtml(id)}" data-label="${escapeHtml(opt.label)}" value="${escapeHtml(opt.value)}" ${selectedSet.has(opt.value) ? 'checked' : ''} onchange="updateMultiSelectSummary('${escapeHtml(id)}')">
            <span>${escapeHtml(opt.label)}</span>
        </label>
    `).join('');

    return `
        <details class="filter-multi" data-filter-box="${escapeHtml(id)}" data-all-label="${escapeHtml(allLabel)}">
            <summary class="filter-multi-summary">
                <span class="filter-multi-label">
                    <span class="filter-multi-title">${escapeHtml(title)}</span>
                    <span class="filter-multi-value">${escapeHtml(summary)}</span>
                </span>
                <span class="filter-multi-chevron">▼</span>
            </summary>
            <div class="filter-multi-panel">
                <div class="filter-multi-actions">
                    <button type="button" class="filter-multi-action" onclick="toggleFilterGroup('${escapeHtml(id)}', true)">Todos</button>
                    <button type="button" class="filter-multi-action" onclick="toggleFilterGroup('${escapeHtml(id)}', false)">Limpar</button>
                    <button type="button" class="filter-multi-action" onclick="applyFilters()">Aplicar</button>
                </div>
                <div class="filter-multi-options">${optionsHtml}</div>
            </div>
        </details>
    `;
}

function getCheckedValues(filterId) {
    return uniqueValues(
        [...document.querySelectorAll(`input[data-filter-id="${filterId}"]:checked`)].map(input => input.value)
    );
}

function toggleFilterGroup(filterId, checked) {
    document.querySelectorAll(`input[data-filter-id="${filterId}"]`).forEach(input => {
        input.checked = checked;
    });
    updateMultiSelectSummary(filterId);
}

function closeAllFilterPanels() {
    document.querySelectorAll('.filter-multi[open]').forEach(panel => {
        panel.open = false;
    });
}

function wireFilterPanels() {
    document.querySelectorAll('.filter-multi').forEach(panel => {
        panel.addEventListener('toggle', () => {
            if (!panel.open) return;
            document.querySelectorAll('.filter-multi').forEach(other => {
                if (other !== panel) other.open = false;
            });
        });
    });
}

function serializeFilterValue(value) {
    return uniqueValues(value).join(',');
}

function filterIncludes(value, candidate, normalizer = item => String(item ?? '').trim()) {
    const selected = uniqueValues(value).map(normalizer);
    if (!selected.length) return true;
    return selected.includes(normalizer(candidate));
}

function selectedLabels(value, formatter = item => String(item)) {
    const selected = uniqueValues(value);
    return selected.length ? selected.map(formatter).join(', ') : '';
}

function formatMonthSelectionLabel(year, month, compact = false) {
    const label = MESES[Number(month) - 1] || String(month);
    if (compact) {
        return `${label.slice(0, 3)}/${String(year).slice(-2)}`;
    }
    return `${label} ${year}`;
}

function resolveFocusedMonth(rows) {
    if (!rows.length) return null;

    const selectedYears = uniqueValues(filterAno);
    const selectedMonths = uniqueValues(filterMes);
    if (selectedYears.length === 1 && selectedMonths.length === 1) {
        return {
            year: selectedYears[0],
            month: selectedMonths[0],
            label: formatMonthSelectionLabel(selectedYears[0], selectedMonths[0]),
            source: 'selected'
        };
    }

    const now = new Date();
    const nowYear = String(now.getFullYear());
    const nowMonth = String(now.getMonth() + 1).padStart(2, '0');
    if (rows.some(row => row._ano === nowYear && row._mes === nowMonth)) {
        return {
            year: nowYear,
            month: nowMonth,
            label: formatMonthSelectionLabel(nowYear, nowMonth),
            source: 'current'
        };
    }

    const latest = [...rows].sort((a, b) => b._date - a._date)[0];
    if (!latest) return null;

    return {
        year: latest._ano,
        month: latest._mes,
        label: formatMonthSelectionLabel(latest._ano, latest._mes),
        source: 'latest'
    };
}

function rankClass(i) {
    if (i === 0) return 'gold';
    if (i === 1) return 'silver';
    if (i === 2) return 'bronze';
    return '';
}

function loading() {
    document.getElementById('content').innerHTML = `
        <div class="loading-state">
            <div class="spinner"></div>
            <span>Carregando dados...</span>
        </div>`;
}

function error(msg) {
    document.getElementById('content').innerHTML = `
        <div class="error-state">
            ⚠️ ${msg}<br>
            <small style="color:#9ca3af;margin-top:8px;display:block">
                Verifique se o Google Sheets está configurado e publicado como CSV.
            </small>
        </div>`;
}

function destroyCharts() {
    activeCharts.forEach(c => c.destroy());
    activeCharts = [];
}

function makeChart(id, config) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    const chartType = String(config?.type || '').toLowerCase();
    const userOptions = config?.options || {};
    const userPlugins = userOptions.plugins || {};
    const userTooltip = userPlugins.tooltip || {};
    const userLegend = userPlugins.legend || {};
    const defaultInteraction = ['bar', 'doughnut', 'pie', 'polararea', 'radar'].includes(chartType)
        ? { mode: 'nearest', intersect: true }
        : { mode: 'index', intersect: false };
    const interaction = { ...defaultInteraction, ...(userOptions.interaction || {}) };
    const merged = {
        ...config,
        options: {
            ...userOptions,
            responsive: userOptions.responsive ?? true,
            maintainAspectRatio: userOptions.maintainAspectRatio ?? false,
            interaction,
            hover: {
                mode: interaction.mode,
                intersect: interaction.intersect,
                ...(userOptions.hover || {})
            },
            plugins: {
                ...userPlugins,
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 10,
                    mode: interaction.mode,
                    intersect: interaction.intersect,
                    ...userTooltip,
                    callbacks: userTooltip.callbacks || {}
                },
                legend: {
                    ...userLegend,
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        ...(userLegend.labels || {})
                    }
                }
            }
        }
    };
    const c = new Chart(ctx, merged);
    activeCharts.push(c);
    return c;
}

async function apiFetch(endpoint, params = {}) {
    const url = new URL(BASE + '/api/' + endpoint + '.php');
    Object.entries(params).forEach(([k, v]) => {
        const serialized = Array.isArray(v) ? serializeFilterValue(v) : String(v ?? '').trim();
        if (serialized) url.searchParams.set(k, serialized);
    });
    const res = await fetch(url.toString());
    if (!res.ok) throw new Error(await res.text());
    const json = await res.json();
    if (json.error) throw new Error(json.error);
    return json.data || [];
}

async function fetchImpressoesData() {
    let primaryData = [];
    let primaryError = null;

    try {
        primaryData = await apiFetch('formularios_rotulos');
        if (primaryData.length) {
            return primaryData;
        }
    } catch (error) {
        primaryError = error;
    }

    const fallbackUrl = BASE.replace(/\/dashboard\/?$/, '') + '/formulario/dashboard_feed.php';

    try {
        const res = await fetch(fallbackUrl, { credentials: 'same-origin' });
        if (!res.ok) {
            throw new Error(await res.text());
        }

        const json = await res.json();
        if (json.error) {
            throw new Error(json.error);
        }

        const data = Array.isArray(json.data) ? json.data : [];
        if (data.length) {
            return data;
        }
    } catch (fallbackError) {
        if (primaryError) {
            throw primaryError;
        }
        throw fallbackError;
    }

    return primaryData;
}

function groupBy(arr, key) {
    return arr.reduce((acc, item) => {
        const k = item[key] || 'N/A';
        if (!acc[k]) acc[k] = [];
        acc[k].push(item);
        return acc;
    }, {});
}

function sumBy(arr, key) {
    return arr.reduce((s, i) => s + (Number(i[key]) || 0), 0);
}

function parseDateAny(value) {
    if (!value) return null;
    const raw = String(value).trim();
    if (!raw) return null;
    if (/^\d{4}$/.test(raw)) {
        const d = new Date(Number(raw), 0, 1);
        return isNaN(d) ? null : d;
    }
    if (/^\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}(?::\d{2})?(?:\.\d+)?(?:Z|[+\-]\d{2}:\d{2})$/.test(raw)) {
        const d = new Date(raw.replace(' ', 'T'));
        return isNaN(d) ? null : d;
    }
    if (/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$/.test(raw)) {
        const normalized = raw.length > 10 ? raw.replace(' ', 'T') : (raw + 'T00:00:00');
        const d = new Date(normalized);
        return isNaN(d) ? null : d;
    }
    if (/^\d{4}[/-]\d{2}$/.test(raw)) {
        const normalized = raw.replace('/', '-');
        const d = new Date(normalized + '-01T00:00:00');
        return isNaN(d) ? null : d;
    }
    let m = raw.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/);
    if (m) {
        const day = Number(m[1]);
        const month = Number(m[2]) - 1;
        const year = Number(m[3].length === 2 ? '20' + m[3] : m[3]);
        const d = new Date(year, month, day);
        return isNaN(d) ? null : d;
    }
    m = raw.match(/^(\d{1,2})[\/\-](\d{1,2})$/);
    if (m) {
        const day = Number(m[1]);
        const month = Number(m[2]) - 1;
        const year = Number(firstSelection(filterAno) || new Date().getFullYear());
        const d = new Date(year, month, day);
        return isNaN(d) ? null : d;
    }
    const normalized = normalizeText(raw);
    m = normalized.match(/^([a-z]+)\s+(\d{2,4})$/) || normalized.match(/^(\d{2,4})\s+([a-z]+)$/);
    if (m) {
        const monthText = /^[a-z]+$/.test(m[1]) ? m[1] : m[2];
        const yearText = /^\d+$/.test(m[1]) ? m[1] : m[2];
        const monthNum = monthNumberFromText(monthText);
        const year = normalizeYearValue(yearText);
        if (monthNum && year) {
            const d = new Date(Number(year), Number(monthNum) - 1, 1);
            return isNaN(d) ? null : d;
        }
    }
    const fallback = new Date(raw);
    return isNaN(fallback) ? null : fallback;
}

function byParsedDateAsc(a, b) {
    const da = parseDateAny(a);
    const db = parseDateAny(b);
    if (da && db) return da - db;
    if (da) return -1;
    if (db) return 1;
    return String(a).localeCompare(String(b));
}

function monthKeyFromDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}`;
}

function dayKeyFromDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
}

function formatMonthKey(key) {
    const [year, month] = String(key).split('-').map(Number);
    const mesesCurtos = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return `${mesesCurtos[(month || 1) - 1]}/${String(year || '').slice(-2)}`;
}

function formatDayKey(key) {
    const [year, month, day] = String(key).split('-').map(Number);
    const date = new Date(year || 2000, (month || 1) - 1, day || 1);
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}

function normalizeText(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9]+/g, ' ')
        .trim()
        .toLowerCase();
}

function normalizeYearValue(value) {
    const raw = String(value || '').trim();
    if (/^\d{4}$/.test(raw)) return raw;
    if (/^\d{2}$/.test(raw)) return `${Number(raw) >= 70 ? '19' : '20'}${raw.padStart(2, '0')}`;
    return '';
}

function monthNumberFromText(value) {
    const aliases = {
        janeiro: '01', jan: '01',
        fevereiro: '02', fev: '02',
        marco: '03', mar: '03',
        abril: '04', abr: '04',
        maio: '05', mai: '05',
        junho: '06', jun: '06',
        julho: '07', jul: '07',
        agosto: '08', ago: '08',
        setembro: '09', set: '09',
        outubro: '10', out: '10',
        novembro: '11', nov: '11',
        dezembro: '12', dez: '12'
    };
    return aliases[normalizeText(value)] || '';
}

function monthNumberFromValue(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';
    if (/^\d{1,2}$/.test(raw)) {
        const num = Number(raw);
        return num >= 1 && num <= 12 ? String(num).padStart(2, '0') : '';
    }
    if (/^\d{4}[/-]\d{2}$/.test(raw)) return raw.slice(5, 7);
    const dt = parseDateAny(raw);
    if (dt) return String(dt.getMonth() + 1).padStart(2, '0');
    const normalized = normalizeText(raw);
    const monthFromText = normalized.match(/^([a-z]+)\s+\d{2,4}$/)?.[1];
    if (monthFromText) return monthNumberFromText(monthFromText);
    const idx = MESES.findIndex(m => normalizeText(m) === normalized);
    return idx >= 0 ? String(idx + 1).padStart(2, '0') : '';
}

function buildAbcSelect(onChange) {
    return buildMultiSelect('f-abc', 'Curva ABC', [
        { value: 'A', label: 'Curva A' },
        { value: 'B', label: 'Curva B' },
        { value: 'C', label: 'Curva C' }
    ], filterAbc, 'Todas as curvas');
}

function buildTipoProdutoSelect(onChange, tipos = []) {
    const unique = [...new Set(tipos.filter(Boolean).map(v => String(v).trim()))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    return buildMultiSelect('f-tipo-produto', 'Tipo', unique.map(tipo => ({ value: tipo, label: tipo })), filterTipoProduto, 'Todos os tipos');
}

function buildHoltecCategoriaSelect(onChange, categorias = []) {
    const unique = [...new Set(categorias.filter(Boolean).map(v => String(v).trim()))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    return buildMultiSelect('f-holtec-categoria', 'Categorias', unique.map(categoria => ({ value: categoria, label: categoria })), filterHoltecCategoria, 'Todas as categorias');
}

function buildHoltecGrupoSelect(onChange, grupos = []) {
    const unique = [...new Set(grupos.filter(Boolean).map(v => String(v).trim()))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    return buildMultiSelect('f-holtec-grupo', 'Grupos', unique.map(grupo => ({ value: grupo, label: grupo })), filterHoltecGrupo, 'Todos os grupos');
}

// ============================================================
// FILTERS UI
// ============================================================
function setFiltersUI(html) {
    document.getElementById('topbar-filters').innerHTML = html;
    wireFilterPanels();
}

function buildLojaSelect(onChange) {
    if (!USER.canSeeAllLojas) return '';
    return buildMultiSelect('f-loja', 'Lojas', LOJAS.map(loja => ({ value: loja, label: loja })), filterLoja, 'Todas as lojas');
}

function buildAnoSelect(onChange, anos = []) {
    const base = anos.length ? anos : Array.from({ length: 6 }, (_, i) => String(new Date().getFullYear() - 4 + i));
    const unique = [...new Set(base.filter(Boolean).map(String))].sort();
    return buildMultiSelect('f-ano', 'Anos', unique.map(y => ({ value: y, label: y })), filterAno, 'Todos os anos');
}

function buildMesSelect(onChange, meses = []) {
    const unique = [...new Set(meses.map(monthNumberFromValue).filter(Boolean))].sort();
    return buildMultiSelect('f-mes', 'Meses', unique.map(m => ({ value: m, label: MESES[Number(m) - 1] || m })), filterMes, 'Todos os meses');
}

function buildCampanhaSelect(onChange, campanhas = []) {
    const unique = [...new Set(campanhas.filter(Boolean).map(v => String(v).trim()))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    return buildMultiSelect('f-campanha', 'Campanhas', unique.map(c => ({ value: c, label: c })), filterCampanha, 'Todas as campanhas');
}

function buildCupomSelect(onChange, cupons = []) {
    const unique = [...new Set(cupons.filter(Boolean).map(v => String(v).trim()))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    return buildMultiSelect('f-cupom', 'Cupons', unique.map(c => ({ value: c, label: c })), filterCupom, 'Todos os cupons');
}

function buildStackedSelect(id, title, options, selected, onChange) {
    const items = options.map(opt => ({
        value: String(opt.value),
        label: String(opt.label)
    }));
    const current = String(selected ?? items[0]?.value ?? '');
    return `
        <label class="filter-stack">
            <span class="filter-multi-title">${escapeHtml(title)}</span>
            <select class="filter-select" id="${escapeHtml(id)}" onchange="${onChange}">
                ${items.map(opt => `<option value="${escapeHtml(opt.value)}" ${current === opt.value ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`).join('')}
            </select>
        </label>
    `;
}

function buildMarketingFonteSelect() {
    return buildStackedSelect('f-marketing-fonte', 'Fonte', [
        { value: 'trafego_pago', label: 'Tráfego Pago' },
        { value: 'formularios_rotulos', label: 'Formulários' }
    ], filterMarketingFonte, "onMarketingFonteChange(this.value)");
}

function marketingSectionTitle() {
    return 'Marketing';
}

function sectionTitle(section = currentSection) {
    if (section === 'impressoes') {
        return 'Impressoes';
    }
    return section === 'marketing'
        ? marketingSectionTitle()
        : (sectionTitles[section] || section);
}

function updatePageTitle(section = currentSection) {
    const titleEl = document.getElementById('page-title');
    if (titleEl) {
        titleEl.textContent = sectionTitle(section);
    }
}

function buildMarketingSubnavCard() {
    const views = [
        {
            value: 'trafego_pago',
            label: 'Trafego Pago',
            description: 'Campanhas, cupons e desempenho de midia.',
            heading: 'Dashboard de trafego pago',
            copy: 'Acompanhe investimento, campanhas, cupons e eficiencia da midia paga em um unico lugar.'
        },
        {
            value: 'formularios_rotulos',
            label: 'Formularios',
            description: 'Solicitacoes, papeis, embalagens e produtos.',
            heading: 'Formularios de rotulos',
            copy: 'Veja os pedidos enviados pelo formulario, quantos rotulos entraram no mes e quais papeis estao puxando a demanda.'
        }
    ];
    const active = views.find(view => view.value === filterMarketingFonte) || views[0];

    return `
        <section class="marketing-shell-card">
            <div class="marketing-shell-head">
                <div class="marketing-shell-copy">
                    <div class="marketing-shell-kicker">Topico de marketing</div>
                    <h2>${escapeHtml(active.heading)}</h2>
                    <p>${escapeHtml(active.copy)}</p>
                </div>
                <div class="marketing-tab-list">
                    ${views.map(view => `
                        <button type="button" class="marketing-tab ${filterMarketingFonte === view.value ? 'active' : ''}" onclick="onMarketingFonteChange('${view.value}')">
                            <span>${escapeHtml(view.label)}</span>
                            <small>${escapeHtml(view.description)}</small>
                        </button>
                    `).join('')}
                </div>
            </div>
        </section>
    `;
}

function buildImpressoesHeaderCard() {
    return `
        <section class="marketing-shell-card">
            <div class="marketing-shell-head">
                <div class="marketing-shell-copy">
                    <div class="marketing-shell-kicker">Area de impressoes</div>
                    <h2>Dashboard de impressoes</h2>
                    <p>Veja as solicitacoes do formulario, o volume do mes, os tipos de papel e os itens mais pedidos em um painel proprio.</p>
                </div>
            </div>
        </section>
    `;
}

function sectionIsAccessible(section) {
    return USER_SECTIONS.includes(section);
}

function buildRepresentanteSelect(onChange, representantes = []) {
    const unique = [...new Set(representantes.filter(Boolean).map(v => String(v).trim()))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    return buildMultiSelect('f-representante', 'Representantes', unique.map(item => ({ value: item, label: item })), filterRepresentante, 'Todos os representantes');
}

function buildTipoPedidoSelect(onChange, tipos = []) {
    const unique = [...new Set(tipos.filter(Boolean).map(v => String(v).trim()))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    return buildMultiSelect('f-tipo-pedido', 'Tipo de Pedido', unique.map(item => ({ value: item, label: item })), filterTipoPedido, 'Todos os tipos');
}

function buildTipoPapelSelect(onChange, tipos = []) {
    const unique = [...new Set(tipos.filter(Boolean).map(v => String(v).trim()))]
        .filter(Boolean)
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    return buildMultiSelect('f-tipo-papel', 'Tipo de Papel', unique.map(item => ({ value: item, label: item })), filterTipoPapel, 'Todos os papéis');
}

function buildProdutoSearch(onChange) {
    return `<input class="filter-input" id="f-produto-busca" type="search" placeholder="Pesquisar produto..." value="${filterProdutoBusca.replace(/"/g, '&quot;')}" oninput="${onChange}">`;
}

function onMarketingFonteChange(value) {
    filterMarketingFonte = value || 'trafego_pago';
    filterCampanha = [];
    filterCupom = [];
    filterRepresentante = [];
    filterTipoPedido = [];
    filterTipoPapel = [];
    closeAllFilterPanels();
    loadSection('marketing');
}

function applyFilters() {
    filterLoja = USER.canSeeAllLojas ? getCheckedValues('f-loja') : (USER.loja ? [USER.loja] : []);
    filterAno  = getCheckedValues('f-ano');
    filterMes  = getCheckedValues('f-mes');
    filterAbc  = getCheckedValues('f-abc');
    filterTipoProduto = getCheckedValues('f-tipo-produto');
    filterProdutoBusca = document.getElementById('f-produto-busca')?.value || '';
    filterHoltecCategoria = getCheckedValues('f-holtec-categoria');
    filterHoltecGrupo = getCheckedValues('f-holtec-grupo');
    filterCampanha = getCheckedValues('f-campanha');
    filterCupom = getCheckedValues('f-cupom');
    filterRepresentante = getCheckedValues('f-representante');
    filterTipoPedido = getCheckedValues('f-tipo-pedido');
    filterTipoPapel = getCheckedValues('f-tipo-papel');
    const marketingFonteEl = document.getElementById('f-marketing-fonte');
    if (marketingFonteEl) filterMarketingFonte = marketingFonteEl.value || 'trafego_pago';
    closeAllFilterPanels();
    loadSection(currentSection);
}

// ============================================================
// SECTIONS
// ============================================================

// ——— FATURAMENTO ———
async function loadFaturamento() {
    if (!USER_SECTIONS.includes('faturamento')) {
        error('Acesso restrito para este usuario.');
        return;
    }

    const [vendas, formulas] = await Promise.all([
        apiFetch('vendas', { loja: filterLoja, ano: filterAno, mes: filterMes }),
        apiFetch('formulas', { loja: filterLoja, ano: filterAno, mes: filterMes })
    ]);

    const anos = [...new Set([
        ...vendas.map(r => parseDateAny(r.data)?.getFullYear()).filter(Boolean),
        ...formulas.map(r => String(r.ano || parseDateAny(r.data_ref)?.getFullYear() || '').trim()).filter(v => /^\d{4}$/.test(v)).map(Number)
    ])].sort();
    const meses = [
        ...vendas.map(r => r.data),
        ...formulas.map(r => r.mes_num || r.mes || r.data_ref)
    ];
    setFiltersUI(buildLojaSelect('applyFilters()') + buildAnoSelect('applyFilters()', anos) + buildMesSelect('applyFilters()', meses));

    if (!vendas.length && !formulas.length) { error('Nenhum dado encontrado para os filtros selecionados.'); return; }

    const totalProdutos = sumBy(vendas, 'valor');
    const totalAprovado = sumBy(formulas, 'valor_aprovado');
    const totalFaturamento = totalProdutos + totalAprovado;
    const totalUnidades = sumBy(vendas, 'unidades_vendidas');
    const totalAprovacoes = sumBy(formulas, 'aprovados');
    const ticketMedio = (totalUnidades + totalAprovacoes) > 0 ? totalFaturamento / (totalUnidades + totalAprovacoes) : 0;

    const porMes = {};
    vendas.forEach(r => {
        const d = parseDateAny(r.data);
        if (!d) return;
        const key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        if (!porMes[key]) porMes[key] = { produtos: 0, orcamentos: 0 };
        porMes[key].produtos += Number(r.valor || 0);
    });
    formulas.forEach(r => {
        const monthNumber = monthNumberFromValue(r.mes_num || r.mes || r.data_ref);
        const year = Number(r.ano || parseDateAny(r.data_ref)?.getFullYear() || firstSelection(filterAno) || new Date().getFullYear());
        if (monthNumber && year >= 2000 && year <= 2100) {
            const key = `${year}-${monthNumber}`;
            if (!porMes[key]) porMes[key] = { produtos: 0, orcamentos: 0 };
            porMes[key].orcamentos += Number(r.valor_aprovado || 0);
        }
    });
    let mesesOrdenados = Object.keys(porMes).sort();
    if (hasSelection(filterAno)) mesesOrdenados = mesesOrdenados.filter(k => filterIncludes(filterAno, k.slice(0, 4)));
    if (hasSelection(filterMes)) mesesOrdenados = mesesOrdenados.filter(k => filterIncludes(filterMes, k.slice(5, 7)));
    const labelsMes = mesesOrdenados.map(k => {
        const [y,m] = k.split('-');
        return new Date(Number(y), Number(m)-1, 1).toLocaleDateString('pt-BR', {month:'short', year:'numeric'});
    });
    const valoresMes = mesesOrdenados.map(k => (porMes[k].produtos || 0) + (porMes[k].orcamentos || 0));

    const porLojaVendas = groupBy(vendas, 'loja');
    const porLojaFormulas = groupBy(formulas, 'loja');
    const todasLojas = [...new Set([...Object.keys(porLojaVendas), ...Object.keys(porLojaFormulas)])];
    const rankLoja = todasLojas.map(loja => {
        const vendasLoja = porLojaVendas[loja] || [];
        const formulasLoja = porLojaFormulas[loja] || [];
        const produtos = sumBy(vendasLoja, 'valor');
        const orcamentos = sumBy(formulasLoja, 'valor_aprovado');
        return { loja, produtos, orcamentos, total: produtos + orcamentos };
    }).sort((a,b) => b.total - a.total);

    destroyCharts();
    document.getElementById('content').innerHTML = `
        ${!USER.canSeeAllLojas ? `<div class="restricted-banner">🔒 Visualizando dados da loja: <strong>${USER.loja}</strong></div>` : ''}
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">💰 Faturamento Total</div><div class="stat-value">${fmt.currency(totalFaturamento)}</div><div class="stat-sub">Produtos + orçados aprovados</div></div>
            <div class="stat-card green"><div class="stat-label">🛍️ Vendas de Produtos</div><div class="stat-value">${fmt.currency(totalProdutos)}</div><div class="stat-sub">${fmt.number(totalUnidades)} unidades vendidas</div></div>
            <div class="stat-card purple"><div class="stat-label">✅ Valores Aprovados</div><div class="stat-value">${fmt.currency(totalAprovado)}</div><div class="stat-sub">${fmt.number(totalAprovacoes)} aprovações</div></div>
            <div class="stat-card orange"><div class="stat-label">🎯 Ticket Médio</div><div class="stat-value">${fmt.currency(ticketMedio)}</div><div class="stat-sub">Base total combinada</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card wide"><div class="chart-title">📈 Evolução do Faturamento por Mês</div><div class="chart-wrap"><canvas id="ch-fat-mes"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🏪 Faturamento por Loja</div><div class="chart-wrap"><canvas id="ch-fat-loja"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">📊 Participação por Loja</div><div class="chart-wrap"><canvas id="ch-fat-pizza"></canvas></div></div>
        </div>
        <div class="table-card">
            <div class="table-header">🏆 Ranking de Lojas por Faturamento</div>
            <table><thead><tr><th>#</th><th>Loja</th><th>Faturamento</th><th>Produtos</th><th>Orçados Aprovados</th><th>Participação</th></tr></thead><tbody>
                ${rankLoja.map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td><strong>${r.loja}</strong></td><td>${fmt.currency(r.total)}</td><td>${fmt.currency(r.produtos)}</td><td>${fmt.currency(r.orcamentos)}</td><td>${fmt.pct(totalFaturamento > 0 ? (r.total/totalFaturamento)*100 : 0)}</td></tr>`).join('')}
            </tbody></table>
        </div>`;

    makeChart('ch-fat-mes', { type: 'line', data: { labels: labelsMes, datasets: [{ label: 'Faturamento', data: valoresMes, borderColor: COLORS.primary, backgroundColor: 'rgba(37,99,235,.10)', fill: true, tension: .32, pointBackgroundColor: COLORS.primary, pointRadius: 3, pointHoverRadius: 5 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } } } } });
    makeChart('ch-fat-loja', { type: 'bar', data: { labels: rankLoja.map(r => r.loja), datasets: [{ data: rankLoja.map(r => r.total), backgroundColor: COLORS.palette, borderRadius: 8, borderSkipped: false }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-fat-pizza', { type: 'doughnut', data: { labels: rankLoja.map(r => r.loja), datasets: [{ data: rankLoja.map(r => r.total), backgroundColor: COLORS.palette, borderWidth: 2, borderColor: '#fff' }] }, options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' } });
}


// ——— ORÇAMENTOS ———
async function loadOrcamentos() {
    const data = await apiFetch('formulas', { loja: filterLoja, ano: filterAno, mes: filterMes });
    const anos = [...new Set(data.map(r => String(r.ano || '').trim()).filter(v => /^\d{4}$/.test(v)))].sort();
    const meses = data.map(r => r.mes_num || r.mes || r.data_ref);
    setFiltersUI(buildLojaSelect('applyFilters()') + buildAnoSelect('applyFilters()', anos) + buildMesSelect('applyFilters()', meses));

    if (!data.length) { error('Nenhum dado de orçamento encontrado.'); return; }

    const totalOrc = sumBy(data, 'orcamentos_efetuados');
    const totalAprov = sumBy(data, 'aprovados');
    const totalRej = sumBy(data, 'rejeitados');
    const taxa = totalOrc > 0 ? (totalAprov / totalOrc) * 100 : 0;
    const totalValOrc = sumBy(data, 'valor_orcado');
    const totalValAprov = sumBy(data, 'valor_aprovado');

    const porMes = groupBy(data, 'mes_num');
    const mesesOrder = [...new Set(data.map(r => monthNumberFromValue(r.mes_num || r.mes || r.data_ref)).filter(Boolean))].sort();
    const aprovMes = mesesOrder.map(m => sumBy(porMes[m] || [], 'aprovados'));
    const rejMes = mesesOrder.map(m => sumBy(porMes[m] || [], 'rejeitados'));

    const porLoja = groupBy(data, 'loja');
    const rankLoja = Object.entries(porLoja).map(([l, rows]) => ({ loja: l, orcados: sumBy(rows, 'orcamentos_efetuados'), aprovados: sumBy(rows, 'aprovados'), rejeitados: sumBy(rows, 'rejeitados'), valor_orcado: sumBy(rows, 'valor_orcado'), valor_aprovado: sumBy(rows, 'valor_aprovado'), taxa: sumBy(rows, 'orcamentos_efetuados') > 0 ? (sumBy(rows, 'aprovados') / sumBy(rows, 'orcamentos_efetuados')) * 100 : 0 })).sort((a,b) => b.valor_aprovado - a.valor_aprovado);

    destroyCharts();
    document.getElementById('content').innerHTML = `
        ${!USER.canSeeAllLojas ? `<div class="restricted-banner">🔒 Visualizando dados da loja: <strong>${USER.loja}</strong></div>` : ''}
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">📋 Total de Orçamentos</div><div class="stat-value">${fmt.number(totalOrc)}</div><div class="stat-sub">Orçamentos efetuados</div></div>
            <div class="stat-card green"><div class="stat-label">✅ Aprovados</div><div class="stat-value">${fmt.number(totalAprov)}</div><div class="stat-sub">${fmt.currency(totalValAprov)} em valor</div></div>
            <div class="stat-card red"><div class="stat-label">❌ Rejeitados</div><div class="stat-value">${fmt.number(totalRej)}</div><div class="stat-sub">Não convertidos</div></div>
            <div class="stat-card ${taxa >= 70 ? 'green' : taxa >= 50 ? 'orange' : 'red'}"><div class="stat-label">🎯 Taxa de Conversão</div><div class="stat-value">${fmt.pct(taxa)}</div><div class="stat-sub">Aprovados / Total</div></div>
            <div class="stat-card purple"><div class="stat-label">💰 Valor Orçado</div><div class="stat-value">${fmt.currency(totalValOrc)}</div><div class="stat-sub">Total orçado</div></div>
            <div class="stat-card green"><div class="stat-label">💵 Valor Aprovado</div><div class="stat-value">${fmt.currency(totalValAprov)}</div><div class="stat-sub">${fmt.pct(totalValOrc > 0 ? (totalValAprov/totalValOrc)*100 : 0)} do orçado</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card wide"><div class="chart-title">📊 Evolução Mensal: Aprovados vs Rejeitados</div><div class="chart-wrap"><canvas id="ch-orc-mes"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🍩 Aprovados vs Rejeitados</div><div class="chart-wrap"><canvas id="ch-orc-pizza"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🏆 Conversão por Loja</div><div class="chart-wrap"><canvas id="ch-orc-conv"></canvas></div></div>
        </div>
        <div class="table-card">
            <div class="table-header">📋 Detalhamento por Loja</div>
            <table><thead><tr><th>Loja</th><th>Orçamentos</th><th>Aprovados</th><th>Rejeitados</th><th>Valor Orçado</th><th>Valor Aprovado</th><th>Taxa</th></tr></thead><tbody>
                ${rankLoja.map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span> <strong>${r.loja}</strong></td><td>${fmt.number(r.orcados)}</td><td><span class="badge badge-green">✓ ${fmt.number(r.aprovados)}</span></td><td><span class="badge badge-red">✗ ${fmt.number(r.rejeitados)}</span></td><td>${fmt.currency(r.valor_orcado)}</td><td>${fmt.currency(r.valor_aprovado)}</td><td><span class="badge ${r.taxa>=70?'badge-green':r.taxa>=50?'badge-orange':'badge-red'}">${fmt.pct(r.taxa)}</span></td></tr>`).join('')}
            </tbody></table>
        </div>`;

    makeChart('ch-orc-mes', { type: 'bar', data: { labels: mesesOrder.map(m => MESES[Number(m) - 1] || m), datasets: [{ label: 'Aprovados', data: aprovMes, backgroundColor: 'rgba(22,163,74,.8)', borderRadius: 6 }, { label: 'Rejeitados', data: rejMes, backgroundColor: 'rgba(220,38,38,.7)', borderRadius: 6 }] }, options: { plugins: { legend: { position: 'top' } }, scales: { y: { grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } } } });
    makeChart('ch-orc-pizza', { type: 'doughnut', data: { labels: ['Aprovados', 'Rejeitados'], datasets: [{ data: [totalAprov, totalRej], backgroundColor: ['rgba(22,163,74,.85)', 'rgba(220,38,38,.8)'], borderWidth: 2, borderColor: '#fff' }] }, options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' } });
    makeChart('ch-orc-conv', { type: 'bar', data: { labels: rankLoja.map(r => r.loja), datasets: [{ label: 'Taxa de Conversão (%)', data: rankLoja.map(r => Number(r.taxa.toFixed(1))), backgroundColor: rankLoja.map(r => r.taxa>=70?'rgba(22,163,74,.8)':r.taxa>=50?'rgba(217,119,6,.8)':'rgba(220,38,38,.7)'), borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { max: 100, ticks: { callback: v => v+'%' }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
}


// ——— LOJAS ———
async function loadLojas() {
    const [vendas, formulas] = await Promise.all([
        apiFetch('vendas', { loja: filterLoja, ano: filterAno, mes: filterMes }),
        apiFetch('formulas', { loja: filterLoja, ano: filterAno, mes: filterMes })
    ]);
    const anos = [...new Set([...vendas.map(r => parseDateAny(r.data)?.getFullYear()).filter(Boolean), ...formulas.map(r => String(r.ano || '').trim()).filter(v => /^\d{4}$/.test(v)).map(Number)])].sort();
    const meses = [
        ...vendas.map(r => r.data),
        ...formulas.map(r => r.mes_num || r.mes || r.data_ref)
    ];
    setFiltersUI(buildLojaSelect('applyFilters()') + buildAnoSelect('applyFilters()', anos) + buildMesSelect('applyFilters()', meses));

    const porLojaVendas = groupBy(vendas, 'loja');
    const porLojaFormulas = groupBy(formulas, 'loja');
    const todasLojas = [...new Set([...Object.keys(porLojaVendas), ...Object.keys(porLojaFormulas)])];

    const rankLojas = todasLojas.map(l => {
        const v = porLojaVendas[l] || [];
        const f = porLojaFormulas[l] || [];
        const produtos = sumBy(v, 'valor');
        const valorOrcado = sumBy(f, 'valor_orcado');
        const valorAprovado = sumBy(f, 'valor_aprovado');
        const orc = sumBy(f, 'orcamentos_efetuados');
        const apr = sumBy(f, 'aprovados');
        return { loja: l, faturamento: produtos + valorAprovado, produtos, valorOrcado, valorAprovado, orcamentos: orc, aprovados: apr, conversao: orc > 0 ? (apr / orc) * 100 : 0, vendas: v.length };
    }).sort((a,b) => b.faturamento - a.faturamento);

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="charts-grid">
            <div class="chart-card wide"><div class="chart-title">🏪 Comparativo de Faturamento por Loja</div><div class="chart-wrap"><canvas id="ch-lojas-fat"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🎯 Conversão por Loja</div><div class="chart-wrap"><canvas id="ch-lojas-conv"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">📊 Participação no Faturamento</div><div class="chart-wrap"><canvas id="ch-lojas-pizza"></canvas></div></div>
        </div>
        <div class="table-card">
            <div class="table-header">🏪 Ranking Geral de Lojas</div>
            <table><thead><tr><th>#</th><th>Loja</th><th>Faturamento</th><th>Produtos</th><th>Valor Orçado</th><th>Valor Aprovado</th><th>Orçamentos</th><th>Aprovados</th><th>Conversão</th></tr></thead><tbody>
                ${rankLojas.map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td><strong>${r.loja}</strong></td><td>${fmt.currency(r.faturamento)}</td><td>${fmt.currency(r.produtos)}</td><td>${fmt.currency(r.valorOrcado)}</td><td>${fmt.currency(r.valorAprovado)}</td><td>${fmt.number(r.orcamentos)}</td><td><span class="badge badge-green">${fmt.number(r.aprovados)}</span></td><td><span class="badge ${r.conversao>=70?'badge-green':r.conversao>=50?'badge-orange':'badge-red'}">${fmt.pct(r.conversao)}</span></td></tr>`).join('')}
            </tbody></table>
        </div>`;

    makeChart('ch-lojas-fat', { type: 'bar', data: { labels: rankLojas.map(r => r.loja), datasets: [{ label: 'Produtos', data: rankLojas.map(r => r.produtos), backgroundColor: 'rgba(37,99,235,.82)', borderRadius: 8 }, { label: 'Orçados aprovados', data: rankLojas.map(r => r.valorAprovado), backgroundColor: 'rgba(22,163,74,.78)', borderRadius: 8 }] }, options: { plugins: { legend: { position: 'top' } }, scales: { y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } } } });
    makeChart('ch-lojas-conv', { type: 'bar', data: { labels: rankLojas.map(r => r.loja), datasets: [{ label: 'Conversão (%)', data: rankLojas.map(r => +r.conversao.toFixed(1)), backgroundColor: rankLojas.map(r => r.conversao>=70?'rgba(22,163,74,.8)':r.conversao>=50?'rgba(217,119,6,.8)':'rgba(220,38,38,.75)'), borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { max: 100, ticks: { callback: v => v+'%' }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-lojas-pizza', { type: 'doughnut', data: { labels: rankLojas.map(r => r.loja), datasets: [{ data: rankLojas.map(r => r.faturamento), backgroundColor: COLORS.palette, borderWidth: 2, borderColor: '#fff' }] }, options: { plugins: { legend: { position: 'bottom' } }, cutout: '60%' } });
}


// ——— VENDEDORES ———
async function loadVendedores() {
    const data = await apiFetch('vendedores', { loja: filterLoja, ano: filterAno, mes: filterMes });
    const anos = [...new Set(data.map(r => Number(r.ano || parseDateAny(r.data_ref || r.data)?.getFullYear())).filter(Boolean))].sort();
    const meses = data.map(r => r.mes_num || r.data_ref || r.data);
    setFiltersUI(buildLojaSelect('applyFilters()') + buildAnoSelect('applyFilters()', anos) + buildMesSelect('applyFilters()', meses));

    if (!data.length) { error('Nenhum dado de vendedores encontrado.'); return; }

    const MIN_ORCADOS_CONVERSAO = 3000;
    const porVend = groupBy(data, 'vendedor');
    const rankVend = Object.entries(porVend).map(([v, rows]) => {
        const apr = sumBy(rows, 'aprovados');
        const rej = sumBy(rows, 'rejeitados');
        const totalOrcado = sumBy(rows, 'orcamentos_efetuados');
        const orcados = totalOrcado > 0 ? totalOrcado : (apr + rej);
        return { vendedor: v, aprovados: apr, rejeitados: rej, orcados, taxa: orcados > 0 ? (apr/orcados)*100 : 0 };
    }).filter(v => v.vendedor && v.vendedor !== '*** NAO CADASTRADO').sort((a,b) => b.aprovados - a.aprovados);

    const topAprov = [...rankVend].sort((a,b) => b.aprovados - a.aprovados).slice(0,10);
    const topConv = [...rankVend.filter(v => v.orcados >= MIN_ORCADOS_CONVERSAO)].sort((a,b) => b.taxa - a.taxa || b.aprovados - a.aprovados).slice(0,10);

    destroyCharts();
    document.getElementById('content').innerHTML = `
        ${!USER.canSeeAllLojas ? `<div class="restricted-banner">🔒 Visualizando dados da loja: <strong>${USER.loja}</strong></div>` : ''}
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">👥 Vendedores Ativos</div><div class="stat-value">${rankVend.length}</div><div class="stat-sub">No ano selecionado</div></div>
            <div class="stat-card green"><div class="stat-label">✅ Total Aprovados</div><div class="stat-value">${fmt.number(sumBy(rankVend, 'aprovados'))}</div></div>
            <div class="stat-card red"><div class="stat-label">❌ Total Rejeitados</div><div class="stat-value">${fmt.number(sumBy(rankVend, 'rejeitados'))}</div></div>
            <div class="stat-card purple"><div class="stat-label">🎯 Taxa Geral</div><div class="stat-value">${fmt.pct(sumBy(rankVend,'orcados') > 0 ? (sumBy(rankVend,'aprovados')/sumBy(rankVend,'orcados'))*100 : 0)}</div><div class="stat-sub">Conversão consolidada</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card compact"><div class="chart-title">🏆 Top 10 — Aprovados</div><div class="chart-wrap"><canvas id="ch-vend-aprov"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">⭐ Top 10 — Conversão (mín. 3.000 unidades orçadas)</div><div class="chart-wrap"><canvas id="ch-vend-conv"></canvas></div></div>
        </div>
        ${!topConv.length ? `<div class="error-state" style="margin-bottom:20px">Nenhum vendedor atingiu 3.000 unidades orçadas para aparecer no gráfico de conversão.</div>` : ''}
        <div class="table-card">
            <div class="table-header">👥 Ranking Completo de Vendedores</div>
            <table><thead><tr><th>#</th><th>Vendedor</th><th>Aprovados</th><th>Rejeitados</th><th>Orçados</th><th>Taxa de Conversão</th></tr></thead><tbody>
                ${rankVend.map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td>${r.vendedor}</td><td><span class="badge badge-green">${fmt.number(r.aprovados)}</span></td><td><span class="badge badge-red">${fmt.number(r.rejeitados)}</span></td><td>${fmt.number(r.orcados)}</td><td><span class="badge ${r.taxa>=70?'badge-green':r.taxa>=50?'badge-orange':'badge-red'}">${fmt.pct(r.taxa)}</span></td></tr>`).join('')}
            </tbody></table>
        </div>`;

    makeChart('ch-vend-aprov', { type: 'bar', data: { labels: topAprov.map(v => v.vendedor), datasets: [{ label: 'Aprovados', data: topAprov.map(v => v.aprovados), backgroundColor: 'rgba(22,163,74,.8)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    if (topConv.length) makeChart('ch-vend-conv', { type: 'bar', data: { labels: topConv.map(v => v.vendedor), datasets: [{ label: 'Conversão %', data: topConv.map(v => +v.taxa.toFixed(1)), backgroundColor: 'rgba(37,99,235,.82)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { max: 100, ticks: { callback: v => v+'%' }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
}


// ——— PRODUTOS ———
async function loadProdutos() {
    const data = await apiFetch('vendas', { loja: filterLoja, ano: filterAno });
    const anos = [...new Set(data.map(r => parseDateAny(r.data)?.getFullYear()).filter(Boolean))].sort();
    setFiltersUI(buildLojaSelect('applyFilters()') + buildAnoSelect('applyFilters()', anos) + buildAbcSelect('applyFilters()') + buildProdutoSearch('applyFilters()'));

    if (!data.length) { error('Nenhum dado de produtos encontrado.'); return; }

    const porProduto = groupBy(data, 'produto');
    let rankProdutos = Object.entries(porProduto).map(([p, rows]) => ({ produto: p, unidades: sumBy(rows, 'unidades_vendidas'), faturamento: sumBy(rows, 'valor'), tipo: rows[0].tipo_produto || 'N/A' })).sort((a,b) => b.faturamento - a.faturamento);
    const totalFat = sumBy(rankProdutos, 'faturamento');
    let acum = 0;
    rankProdutos = rankProdutos.map(p => { acum += p.faturamento; const pct = totalFat > 0 ? (acum / totalFat) * 100 : 0; return { ...p, abc: pct <= 80 ? 'A' : pct <= 95 ? 'B' : 'C' }; });
    const contABC = {A:0,B:0,C:0};
    rankProdutos.forEach(p => contABC[p.abc]++);
    let produtosFiltrados = hasSelection(filterAbc) ? rankProdutos.filter(p => filterIncludes(filterAbc, p.abc)) : rankProdutos;
    if (filterProdutoBusca.trim()) { const q = filterProdutoBusca.trim().toLowerCase(); produtosFiltrados = produtosFiltrados.filter(p => String(p.produto).toLowerCase().includes(q)); }
    const top10Fat = produtosFiltrados.slice(0,10);
    const top10Uni = [...produtosFiltrados].sort((a,b) => b.unidades - a.unidades).slice(0,10);

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="marketing-shell">
            ${buildMarketingSubnavCard()}
            <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">📦 Total de Produtos</div><div class="stat-value">${fmt.number(produtosFiltrados.length)}</div><div class="stat-sub">Após filtros</div></div>
            <div class="stat-card green"><div class="stat-label">⭐ Curva A</div><div class="stat-value">${contABC.A}</div><div class="stat-sub">80% do faturamento</div></div>
            <div class="stat-card"><div class="stat-label">🎯 Curva B</div><div class="stat-value">${contABC.B}</div><div class="stat-sub">95% acumulado</div></div>
            <div class="stat-card orange"><div class="stat-label">📉 Curva C</div><div class="stat-value">${contABC.C}</div><div class="stat-sub">Cauda longa</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card compact"><div class="chart-title">💰 Top 10 por Faturamento</div><div class="chart-wrap"><canvas id="ch-prod-fat"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">📦 Top 10 por Unidades Vendidas</div><div class="chart-wrap"><canvas id="ch-prod-uni"></canvas></div></div>
        </div>
        <div class="table-card">
            <div class="table-header">📊 Ranking de Produtos com Curva ABC ${hasSelection(filterAbc) ? `— Curvas ${selectedLabels(filterAbc)}` : ''}${filterProdutoBusca ? ` — busca: "${filterProdutoBusca}"` : ''}</div>
            <table><thead><tr><th>#</th><th>Produto</th><th>Tipo</th><th>Unidades</th><th>Faturamento</th><th>Curva</th></tr></thead><tbody>
                ${produtosFiltrados.slice(0,100).map((r,i) => `<tr><td>${i+1}</td><td style="max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${r.produto}">${r.produto}</td><td><span class="badge badge-blue" style="font-size:11px">${r.tipo}</span></td><td>${fmt.number(r.unidades)}</td><td>${fmt.currency(r.faturamento)}</td><td><span class="badge abc-${r.abc.toLowerCase()}">${r.abc}</span></td></tr>`).join('')}
            </tbody></table>
        </div>`;

    const shortLabel = arr => arr.map(r => r.produto.length > 34 ? r.produto.substring(0,34)+'…' : r.produto);
    makeChart('ch-prod-fat', { type: 'bar', data: { labels: shortLabel(top10Fat), datasets: [{ data: top10Fat.map(r => r.faturamento), backgroundColor: COLORS.palette, borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-prod-uni', { type: 'bar', data: { labels: shortLabel(top10Uni), datasets: [{ data: top10Uni.map(r => r.unidades), backgroundColor: 'rgba(124,58,237,.8)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
}


// ——— MARKETING ———
async function loadMarketing() {
    const data = await apiFetch('trafego_pago', { ano: filterAno });
    const anos = [...new Set(data.map(r => parseDateAny(r.data)?.getFullYear()).filter(Boolean))].sort();
    setFiltersUI(buildAnoSelect('applyFilters()', anos));

    if (!data.length) { error('Nenhum dado de tráfego pago encontrado.'); return; }

    const totalGasto = sumBy(data, 'valor_gasto');
    const totalCliques = sumBy(data, 'cliques');
    const totalImpres = sumBy(data, 'impressoes');
    const cpmMedio = totalImpres > 0 ? (totalGasto / totalImpres) * 1000 : 0;
    const cpc = totalCliques > 0 ? totalGasto / totalCliques : 0;
    const ctr = totalImpres > 0 ? (totalCliques / totalImpres) * 100 : 0;

    const porData = groupBy(data, 'data');
    const datasOrdenadas = Object.keys(porData).sort(byParsedDateAsc);
    const gastoData = datasOrdenadas.map(d => sumBy(porData[d], 'valor_gasto'));
    const cliquesData = datasOrdenadas.map(d => sumBy(porData[d], 'cliques'));
    const impresData = datasOrdenadas.map(d => sumBy(porData[d], 'impressoes'));

    const porCamp = groupBy(data, 'campanha');
    const rankCamp = Object.entries(porCamp).map(([c, rows]) => ({ campanha: c, gasto: sumBy(rows, 'valor_gasto'), cliques: sumBy(rows, 'cliques'), impressoes: sumBy(rows, 'impressoes'), cpc: sumBy(rows, 'cliques') > 0 ? sumBy(rows, 'valor_gasto') / sumBy(rows, 'cliques') : 0, ctr: sumBy(rows, 'impressoes') > 0 ? (sumBy(rows, 'cliques') / sumBy(rows, 'impressoes')) * 100 : 0 })).sort((a,b) => b.gasto - a.gasto);

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="marketing-shell">
            ${buildMarketingSubnavCard()}
            <div class="stats-grid">
            <div class="stat-card red"><div class="stat-label">💸 Total Gasto</div><div class="stat-value">${fmt.currency(totalGasto)}</div></div>
            <div class="stat-card"><div class="stat-label">👆 Total de Cliques</div><div class="stat-value">${fmt.k(totalCliques)}</div></div>
            <div class="stat-card purple"><div class="stat-label">👁️ Impressões</div><div class="stat-value">${fmt.k(totalImpres)}</div></div>
            <div class="stat-card orange"><div class="stat-label">📊 CPM Médio</div><div class="stat-value">R$ ${cpmMedio.toFixed(2)}</div><div class="stat-sub">Custo por mil impressões</div></div>
            <div class="stat-card green"><div class="stat-label">🖱️ CPC</div><div class="stat-value">R$ ${cpc.toFixed(2)}</div><div class="stat-sub">Custo por clique</div></div>
            <div class="stat-card"><div class="stat-label">🎯 CTR</div><div class="stat-value">${ctr.toFixed(2)}%</div><div class="stat-sub">Taxa de clique</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card wide"><div class="chart-title">💸 Evolução de Gastos</div><div class="chart-wrap"><canvas id="ch-mkt-gasto"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">👆 Cliques por Data</div><div class="chart-wrap"><canvas id="ch-mkt-cliques"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">👁️ Impressões por Data</div><div class="chart-wrap"><canvas id="ch-mkt-impres"></canvas></div></div>
        </div>
        <div class="table-card">
            <div class="table-header">📣 Desempenho por Campanha</div>
            <table><thead><tr><th>Campanha</th><th>Gasto</th><th>Impressões</th><th>Cliques</th><th>CPC</th><th>CTR</th></tr></thead><tbody>
                ${rankCamp.map(r => `<tr><td><strong>${r.campanha}</strong></td><td>${fmt.currency(r.gasto)}</td><td>${fmt.number(r.impressoes)}</td><td>${fmt.number(r.cliques)}</td><td>R$ ${r.cpc.toFixed(2)}</td><td>${r.ctr.toFixed(2)}%</td></tr>`).join('')}
            </tbody></table>
        </div>`;

    const dateLabels = datasOrdenadas.map(d => { const dt = parseDateAny(d); return !dt ? d : dt.toLocaleDateString('pt-BR', {day:'2-digit', month:'2-digit'}); });
    makeChart('ch-mkt-gasto', { type: 'line', data: { labels: dateLabels, datasets: [{ label: 'Gasto (R$)', data: gastoData, borderColor: COLORS.danger, backgroundColor: 'rgba(220,38,38,.08)', fill: true, tension: .28, pointRadius: 2.5, pointHoverRadius: 4 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 14 } } } } });
    makeChart('ch-mkt-cliques', { type: 'line', data: { labels: dateLabels, datasets: [{ label: 'Cliques', data: cliquesData, borderColor: COLORS.primary, backgroundColor: 'rgba(37,99,235,.08)', fill: true, tension: .2, pointRadius: 0 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { grid: { color: '#e2e8f0' } }, x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } } } } });
    makeChart('ch-mkt-impres', { type: 'line', data: { labels: dateLabels, datasets: [{ label: 'Impressões', data: impresData, borderColor: COLORS.purple, backgroundColor: 'rgba(124,58,237,.08)', fill: true, tension: .2, pointRadius: 0 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { grid: { color: '#e2e8f0' } }, x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } } } } });
}
async function loadMarketingFormularios() {
    const raw = await fetchImpressoesData();
    const base = raw.map(row => {
        const dt = parseDateAny(row.submitted_at || row.data_ref);
        if (!dt) return null;
        return {
            ...row,
            _date: dt,
            _ano: String(dt.getFullYear()),
            _mes: String(dt.getMonth() + 1).padStart(2, '0')
        };
    }).filter(Boolean);

    const anos = [...new Set(base.map(r => r._ano))].sort();
    filterAno = uniqueValues(filterAno).filter(value => anos.includes(String(value)));

    const dataAno = base.filter(r => !hasSelection(filterAno) || filterIncludes(filterAno, r._ano));
    const mesesDisponiveis = [...new Set(dataAno.map(r => r._mes))].sort();
    filterMes = uniqueValues(filterMes).filter(value => mesesDisponiveis.includes(String(value)));

    const dataPeriodo = dataAno.filter(r => !hasSelection(filterMes) || filterIncludes(filterMes, r._mes));

    const representantes = [...new Set(dataPeriodo.map(r => String(r.representative || '').trim()).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    filterRepresentante = uniqueValues(filterRepresentante).filter(value => representantes.some(item => normalizeText(item) === normalizeText(value)));

    const tiposPedido = [...new Set(dataPeriodo.map(r => String(r.order_type || '').trim()).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    filterTipoPedido = uniqueValues(filterTipoPedido).filter(value => tiposPedido.some(item => normalizeText(item) === normalizeText(value)));

    const tiposPapel = [...new Set(dataPeriodo.map(r => String(r.paper_type || '').trim()).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    filterTipoPapel = uniqueValues(filterTipoPapel).filter(value => tiposPapel.some(item => normalizeText(item) === normalizeText(value)));

    setFiltersUI(
        buildAnoSelect('applyFilters()', anos) +
        buildMesSelect('applyFilters()', mesesDisponiveis) +
        buildRepresentanteSelect('applyFilters()', representantes) +
        buildTipoPedidoSelect('applyFilters()', tiposPedido) +
        buildTipoPapelSelect('applyFilters()', tiposPapel)
    );

    const matchesMarketingFormFilters = row =>
        (!hasSelection(filterRepresentante) || filterIncludes(filterRepresentante, String(row.representative || '').trim(), normalizeText)) &&
        (!hasSelection(filterTipoPedido) || filterIncludes(filterTipoPedido, String(row.order_type || '').trim(), normalizeText)) &&
        (!hasSelection(filterTipoPapel) || filterIncludes(filterTipoPapel, String(row.paper_type || '').trim(), normalizeText));

    const data = dataPeriodo.filter(matchesMarketingFormFilters);

    if (!data.length) {
        document.getElementById('content').innerHTML = `
            <div class="marketing-shell">
                <div class="error-state">
                    Nenhum dado de formularios encontrado para os filtros selecionados.<br>
                    <small style="color:#9ca3af;margin-top:8px;display:block">
                        Verifique se existem envios salvos em /formulario e ajuste os filtros de ano, mes ou papel.
                    </small>
                </div>
            </div>`;
        return;
    }

    const buildPedidosFromRows = rows => Object.entries(groupBy(rows, 'submission_id')).map(([submissionId, groupedRows]) => {
        const first = groupedRows[0];
        const papeis = [...new Set(groupedRows.map(r => String(r.paper_type || '').trim()).filter(Boolean))];
        const embalagens = [...new Set(groupedRows.map(r => String(r.packaging || '').trim()).filter(Boolean))];
        const produtos = groupedRows.map(r => String(r.product_name || '').trim()).filter(Boolean);
        return {
            submissionId,
            submittedAt: first.submitted_at,
            _date: first._date,
            representative: String(first.representative || '').trim() || 'Nao informado',
            orderType: String(first.order_type || '').trim() || 'Nao informado',
            alterationType: String(first.alteration_type || '').trim(),
            prescriber: String(first.prescriber || '').trim() || 'Nao informado',
            totalProducts: groupedRows.length,
            totalAttachments: Number(first.total_attachments || 0),
            totalReferences: groupedRows.reduce((sum, item) => sum + Number(item.reference_files_count || 0), 0),
            papers: papeis,
            packagingList: embalagens,
            productsList: produtos,
            paperSummary: papeis.join(', ') || 'Nao informado',
            packagingSummary: embalagens.join(', ') || 'Nao informado'
        };
    }).sort((a, b) => b._date - a._date);

    const buildListDropdown = (values, options = {}) => {
        const items = Array.isArray(values) ? [...new Set(values.map(value => String(value || '').trim()).filter(Boolean))] : [];
        const emptyLabel = options.emptyLabel || 'Nao informado';
        const singularLabel = options.singularLabel || 'item';
        const pluralLabel = options.pluralLabel || 'itens';
        const shortLimit = Number(options.shortLimit || 36);

        if (!items.length) {
            return escapeHtml(emptyLabel);
        }

        const fullText = items.join(', ');
        if (items.length === 1 && fullText.length <= shortLimit) {
            return escapeHtml(fullText);
        }

        const countLabel = `${fmt.number(items.length)} ${items.length === 1 ? singularLabel : pluralLabel}`;

        return `
            <details class="table-dropdown">
                <summary title="${escapeHtml(fullText)}">
                    <span class="table-dropdown-label">${escapeHtml(fullText)}</span>
                    <span class="table-dropdown-meta">${escapeHtml(countLabel)}</span>
                    <span class="table-dropdown-chevron">▼</span>
                </summary>
                <div class="table-dropdown-panel">
                    ${items.map((item, itemIndex) => `
                        <div class="table-dropdown-item">
                            <span class="table-dropdown-index">${itemIndex + 1}.</span>
                            <span>${escapeHtml(item)}</span>
                        </div>
                    `).join('')}
                </div>
            </details>
        `;
    };

    const pedidos = buildPedidosFromRows(data);
    const totalPedidos = pedidos.length;
    const totalRotulos = data.length;
    const mediaRotulos = totalPedidos ? totalRotulos / totalPedidos : 0;
    const totalArquivos = pedidos.reduce((sum, item) => sum + Number(item.totalAttachments || 0), 0);

    const papelRank = Object.entries(groupBy(data.filter(row => String(row.paper_type || '').trim() !== ''), 'paper_type'))
        .map(([papel, groupedRows]) => ({
            papel,
            total: groupedRows.length,
            share: totalRotulos > 0 ? (groupedRows.length / totalRotulos) * 100 : 0
        }))
        .sort((a, b) => b.total - a.total);

    const representanteRank = Object.entries(groupBy(pedidos, 'representative'))
        .map(([representante, groupedRows]) => ({
            representante,
            pedidos: groupedRows.length,
            rotulos: groupedRows.reduce((sum, item) => sum + item.totalProducts, 0)
        }))
        .sort((a, b) => b.pedidos - a.pedidos || b.rotulos - a.rotulos);
    const totalRepresentantes = representanteRank.length;
    const orderTypeRank = Object.entries(groupBy(pedidos.filter(item => String(item.orderType || '').trim() !== ''), 'orderType'))
        .map(([tipo, groupedRows]) => ({
            tipo,
            total: groupedRows.length,
            share: totalPedidos > 0 ? (groupedRows.length / totalPedidos) * 100 : 0
        }))
        .sort((a, b) => b.total - a.total);

    const embalagemRank = Object.entries(groupBy(data.filter(row => String(row.packaging || '').trim() !== ''), 'packaging'))
        .map(([embalagem, groupedRows]) => ({ embalagem, total: groupedRows.length }))
        .sort((a, b) => b.total - a.total);

    const produtoRank = Object.entries(groupBy(data.filter(row => String(row.product_name || '').trim() !== ''), 'product_name'))
        .map(([produto, groupedRows]) => ({ produto, total: groupedRows.length }))
        .sort((a, b) => b.total - a.total);

    const granularidadeDiaria = uniqueValues(filterAno).length === 1 && uniqueValues(filterMes).length === 1;
    const rotuloPeriodo = granularidadeDiaria ? 'Dia' : 'Mes';
    const periodMap = {};
    pedidos.forEach(item => {
        const key = granularidadeDiaria ? dayKeyFromDate(item._date) : monthKeyFromDate(item._date);
        if (!periodMap[key]) periodMap[key] = { pedidos: 0, rotulos: 0, arquivos: 0 };
        periodMap[key].pedidos += 1;
        periodMap[key].rotulos += item.totalProducts;
        periodMap[key].arquivos += item.totalAttachments;
    });

    const resumoPeriodo = Object.keys(periodMap).sort().map(key => ({
        key,
        label: granularidadeDiaria ? formatDayKey(key) : formatMonthKey(key),
        pedidos: periodMap[key].pedidos,
        rotulos: periodMap[key].rotulos,
        arquivos: periodMap[key].arquivos
    }));

    const papelLider = papelRank[0] || null;
    const tipoLider = orderTypeRank[0] || null;
    const tipoVice = orderTypeRank[1] || null;

    const filtrosResumo = [];
    if (hasSelection(filterRepresentante)) filtrosResumo.push(selectedLabels(filterRepresentante));
    if (hasSelection(filterTipoPedido)) filtrosResumo.push(selectedLabels(filterTipoPedido));
    if (hasSelection(filterAno)) filtrosResumo.push(selectedLabels(filterAno));
    if (hasSelection(filterMes)) filtrosResumo.push(selectedLabels(filterMes, value => MESES[Number(value) - 1] || value));
    const periodoLabel = filtrosResumo.length ? filtrosResumo.join(' | ') : 'Periodo consolidado';

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="marketing-shell">
            <div class="stats-grid">
                <div class="stat-card red"><div class="stat-label">Pedidos</div><div class="stat-value">${fmt.number(totalPedidos)}</div><div class="stat-sub">${periodoLabel}</div></div>
                <div class="stat-card"><div class="stat-label">Itens Solicitados</div><div class="stat-value">${fmt.number(totalRotulos)}</div><div class="stat-sub">Produtos e amostras cadastrados no formulario</div></div>
                <div class="stat-card purple"><div class="stat-label">Media por Pedido</div><div class="stat-value">${mediaRotulos.toFixed(1)}</div><div class="stat-sub">Itens por envio</div></div>
                <div class="stat-card"><div class="stat-label">Representantes Ativos</div><div class="stat-value">${fmt.number(totalRepresentantes)}</div><div class="stat-sub">Pessoas com solicitacoes no recorte</div></div>
                <div class="stat-card orange"><div class="stat-label">Arquivos Recebidos</div><div class="stat-value">${fmt.number(totalArquivos)}</div><div class="stat-sub">Logo, manuais e referencias</div></div>
                <div class="stat-card green"><div class="stat-label">Papel Mais Usado</div><div class="stat-value">${papelLider ? papelLider.papel : '-'}</div><div class="stat-sub">${papelLider ? fmt.number(papelLider.total) + ' itens' : 'Sem papel informado'}</div></div>
                <div class="stat-card"><div class="stat-label">Tipo Lider</div><div class="stat-value">${tipoLider ? escapeHtml(tipoLider.tipo) : '-'}</div><div class="stat-sub">${tipoLider ? `${fmt.number(tipoLider.total)} pedidos (${fmt.pct(tipoLider.share)})` : 'Sem tipo informado'}</div></div>
                <div class="stat-card purple"><div class="stat-label">2o Tipo</div><div class="stat-value">${tipoVice ? escapeHtml(tipoVice.tipo) : '-'}</div><div class="stat-sub">${tipoVice ? `${fmt.number(tipoVice.total)} pedidos (${fmt.pct(tipoVice.share)})` : 'Nao ha segundo tipo no recorte'}</div></div>
            </div>
            <div class="charts-grid">
                <div class="chart-card wide"><div class="chart-title">Pedidos por ${rotuloPeriodo}</div><div class="chart-subtitle">Comparativo entre volume de pedidos e itens cadastrados</div><div class="chart-wrap"><canvas id="ch-form-pedidos"></canvas></div></div>
                <div class="chart-card compact"><div class="chart-title">Tipo de Solicitacao</div><div class="chart-subtitle">Distribuicao por formulario</div><div class="chart-wrap"><canvas id="ch-form-tipos"></canvas></div></div>
                <div class="chart-card compact"><div class="chart-title">Tipos de Papel</div><div class="chart-subtitle">Quantidade de itens por papel</div><div class="chart-wrap"><canvas id="ch-form-papeis"></canvas></div></div>
                <div class="chart-card compact"><div class="chart-title">Representantes</div><div class="chart-subtitle">Pedidos por representante</div><div class="chart-wrap"><canvas id="ch-form-representantes"></canvas></div></div>
                <div class="chart-card compact"><div class="chart-title">Categorias / Embalagens</div><div class="chart-subtitle">Mais recorrentes no formulario</div><div class="chart-wrap"><canvas id="ch-form-embalagens"></canvas></div></div>
                <div class="chart-card compact"><div class="chart-title">Produtos</div><div class="chart-subtitle">Top nomes de produto no recorte</div><div class="chart-wrap"><canvas id="ch-form-produtos"></canvas></div></div>
            </div>
            <div class="table-card">
                <div class="table-header">Pedidos do formulario</div>
                <table><thead><tr><th>#</th><th>Data</th><th>Representante</th><th>Tipo</th><th>Prescritor</th><th>Produtos</th><th>Categoria / Embalagem</th><th>Itens</th><th>Papeis</th><th>Arquivos</th></tr></thead><tbody>
                    ${pedidos.map((item, index) => `<tr><td><span class="rank-num ${rankClass(index)}">${index + 1}</span></td><td>${item._date.toLocaleDateString('pt-BR')}</td><td><strong>${escapeHtml(item.representative)}</strong></td><td>${escapeHtml(item.orderType)}${item.alterationType ? `<br><small>${escapeHtml(item.alterationType)}</small>` : ''}</td><td>${escapeHtml(item.prescriber)}</td><td>${buildListDropdown(item.productsList, { singularLabel: 'item', pluralLabel: 'itens', shortLimit: 36 })}</td><td>${buildListDropdown(item.packagingList, { singularLabel: 'categoria', pluralLabel: 'categorias', shortLimit: 28 })}</td><td>${fmt.number(item.totalProducts)}</td><td>${buildListDropdown(item.papers, { singularLabel: 'papel', pluralLabel: 'papeis', shortLimit: 28 })}</td><td>${fmt.number(item.totalAttachments)}</td></tr>`).join('')}
                </tbody></table>
            </div>
        </div>`;

    const labelsPeriodo = resumoPeriodo.map(item => item.label);
    const palettePapeis = papelRank.map((_, index) => COLORS.palette[index % COLORS.palette.length]);
    const tiposChartData = orderTypeRank.length
        ? orderTypeRank
        : [{ tipo: 'Sem tipo informado', total: totalPedidos, share: totalPedidos ? 100 : 0 }];
    const paletteTipos = tiposChartData.map((_, index) => COLORS.palette[index % COLORS.palette.length]);
    const paletteRepresentantes = representanteRank.slice(0, 8).map((_, index) => COLORS.palette[index % COLORS.palette.length]);
    const paletteEmbalagens = embalagemRank.slice(0, 8).map((_, index) => COLORS.palette[index % COLORS.palette.length]);
    const paletteProdutos = produtoRank.slice(0, 8).map((_, index) => COLORS.palette[index % COLORS.palette.length]);

    makeChart('ch-form-pedidos', {
        type: 'bar',
        data: {
            labels: labelsPeriodo,
            datasets: [
                { label: 'Pedidos', data: resumoPeriodo.map(item => item.pedidos), backgroundColor: 'rgba(37,99,235,.78)', borderRadius: 8, yAxisID: 'y' },
                { type: 'line', label: 'Itens', data: resumoPeriodo.map(item => item.rotulos), borderColor: COLORS.success, backgroundColor: 'rgba(22,163,74,.08)', tension: .25, pointRadius: 2.5, pointHoverRadius: 4, yAxisID: 'y1' }
            ]
        },
        options: {
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { position: 'left', ticks: { callback: v => fmt.short(v) }, grid: { color: '#e2e8f0' } },
                y1: { position: 'right', ticks: { callback: v => fmt.short(v) }, grid: { display: false } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: granularidadeDiaria ? 12 : 10 } }
            }
        }
    });

    makeChart('ch-form-tipos', {
        type: 'doughnut',
        data: {
            labels: tiposChartData.map(item => item.tipo),
            datasets: [{ data: tiposChartData.map(item => item.total), backgroundColor: paletteTipos, borderColor: '#fff', borderWidth: 2 }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => `${ctx.label}: ${fmt.number(ctx.raw)}` } }
            },
            cutout: '58%'
        }
    });

    makeChart('ch-form-papeis', {
        type: 'doughnut',
        data: {
            labels: papelRank.map(item => item.papel),
            datasets: [{ data: papelRank.map(item => item.total), backgroundColor: palettePapeis, borderColor: '#fff', borderWidth: 2 }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => `${ctx.label}: ${fmt.number(ctx.raw)} itens` } }
            },
            cutout: '58%'
        }
    });

    makeChart('ch-form-representantes', {
        type: 'bar',
        data: {
            labels: representanteRank.slice(0, 8).map(item => item.representante),
            datasets: [{ label: 'Pedidos', data: representanteRank.slice(0, 8).map(item => item.pedidos), backgroundColor: paletteRepresentantes, borderRadius: 8 }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { callback: v => fmt.short(v) }, grid: { color: '#e2e8f0' } },
                y: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-form-embalagens', {
        type: 'bar',
        data: {
            labels: embalagemRank.slice(0, 8).map(item => item.embalagem),
            datasets: [{ label: 'Itens', data: embalagemRank.slice(0, 8).map(item => item.total), backgroundColor: paletteEmbalagens, borderRadius: 8 }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { callback: v => fmt.short(v) }, grid: { color: '#e2e8f0' } },
                y: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-form-produtos', {
        type: 'bar',
        data: {
            labels: produtoRank.slice(0, 8).map(item => item.produto),
            datasets: [{ label: 'Itens', data: produtoRank.slice(0, 8).map(item => item.total), backgroundColor: paletteProdutos, borderRadius: 8 }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { callback: v => fmt.short(v) }, grid: { color: '#e2e8f0' } },
                y: { grid: { display: false } }
            }
        }
    });
}

async function loadMarketingDetalhado() {
    if (filterMarketingFonte === 'formularios_rotulos') {
        return loadMarketingFormularios();
    }

    const raw = await apiFetch('trafego_pago');
    const anos = [...new Set(raw.map(r => parseDateAny(r.data)?.getFullYear()).filter(Boolean))].sort();
    const dataAno = raw.filter(r => {
        const year = parseDateAny(r.data)?.getFullYear();
        return !filterAno || String(year) === String(filterAno);
    });
    const campanhas = [...new Set(dataAno.map(r => String(r.campanha || '').trim()).filter(Boolean))].sort((a,b) => a.localeCompare(b, 'pt-BR'));
    if (filterCampanha && !campanhas.includes(filterCampanha)) filterCampanha = '';
    setFiltersUI(buildAnoSelect('applyFilters()', anos) + buildCampanhaSelect('applyFilters()', campanhas));

    const data = dataAno.filter(r => !filterCampanha || String(r.campanha || '').trim() === filterCampanha);
    if (!data.length) { error('Nenhum dado de Meta Ads encontrado para os filtros selecionados.'); return; }

    const totalGasto = sumBy(data, 'valor_gasto');
    const totalCliques = sumBy(data, 'cliques');
    const totalImpres = sumBy(data, 'impressoes');
    const cpmMedio = totalImpres > 0 ? (totalGasto / totalImpres) * 1000 : 0;
    const cpc = totalCliques > 0 ? totalGasto / totalCliques : 0;
    const ctr = totalImpres > 0 ? (totalCliques / totalImpres) * 100 : 0;

    const datasAtivas = [...new Set(data.map(r => {
        const dt = parseDateAny(r.data);
        return dt ? dt.toISOString().slice(0, 10) : '';
    }).filter(Boolean))];
    const gastoMedioDia = datasAtivas.length > 0 ? totalGasto / datasAtivas.length : 0;

    const porMes = {};
    data.forEach(r => {
        const dt = parseDateAny(r.data);
        if (!dt) return;
        const key = monthKeyFromDate(dt);
        if (!porMes[key]) porMes[key] = { gasto: 0, cliques: 0, impressoes: 0 };
        porMes[key].gasto += Number(r.valor_gasto || 0);
        porMes[key].cliques += Number(r.cliques || 0);
        porMes[key].impressoes += Number(r.impressoes || 0);
    });

    const mesesOrdenados = Object.keys(porMes).sort();
    const resumoMensal = mesesOrdenados.map(key => {
        const row = porMes[key];
        return {
            key,
            label: formatMonthKey(key),
            gasto: row.gasto,
            cliques: row.cliques,
            impressoes: row.impressoes,
            ctr: row.impressoes > 0 ? (row.cliques / row.impressoes) * 100 : 0,
            cpc: row.cliques > 0 ? row.gasto / row.cliques : 0,
            cpm: row.impressoes > 0 ? (row.gasto / row.impressoes) * 1000 : 0
        };
    });

    const porCamp = groupBy(data, 'campanha');
    const rankCamp = Object.entries(porCamp).map(([c, rows]) => {
        const gasto = sumBy(rows, 'valor_gasto');
        const cliques = sumBy(rows, 'cliques');
        const impressoes = sumBy(rows, 'impressoes');
        return {
            campanha: c,
            gasto,
            cliques,
            impressoes,
            share: totalGasto > 0 ? (gasto / totalGasto) * 100 : 0,
            cpc: cliques > 0 ? gasto / cliques : 0,
            cpm: impressoes > 0 ? (gasto / impressoes) * 1000 : 0,
            ctr: impressoes > 0 ? (cliques / impressoes) * 100 : 0
        };
    }).sort((a,b) => b.gasto - a.gasto);

    const topCampanhas = rankCamp.slice(0, 10);
    const shareCampanhas = rankCamp.slice(0, 6).map(r => ({ campanha: r.campanha, gasto: r.gasto }));
    if (rankCamp.length > 6) {
        shareCampanhas.push({ campanha: 'Outras', gasto: sumBy(rankCamp.slice(6), 'gasto') });
    }

    const picoInvest = resumoMensal.reduce((best, row) => !best || row.gasto > best.gasto ? row : best, null);
    const picoCliques = resumoMensal.reduce((best, row) => !best || row.cliques > best.cliques ? row : best, null);
    const melhorCtrMes = resumoMensal.filter(r => r.impressoes > 0).sort((a,b) => b.ctr - a.ctr)[0] || null;
    const melhorCampCtr = [...rankCamp].filter(r => r.impressoes >= 1000).sort((a,b) => b.ctr - a.ctr)[0]
        || [...rankCamp].filter(r => r.impressoes > 0).sort((a,b) => b.ctr - a.ctr)[0]
        || null;
    const melhorCampCpc = [...rankCamp].filter(r => r.cliques > 0).sort((a,b) => a.cpc - b.cpc)[0] || null;
    const campanhaLider = rankCamp[0] || null;
    const periodoLabel = filterCampanha ? `${filterCampanha}${filterAno ? ` • ${filterAno}` : ''}` : (filterAno ? `Ano ${filterAno}` : 'Período consolidado');

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="marketing-shell">
            ${buildMarketingSubnavCard()}
            <div class="stats-grid">
            <div class="stat-card red"><div class="stat-label">💸 Total Investido</div><div class="stat-value">${fmt.currency(totalGasto)}</div><div class="stat-sub">${periodoLabel}</div></div>
            <div class="stat-card"><div class="stat-label">👆 Cliques</div><div class="stat-value">${fmt.short(totalCliques)}</div><div class="stat-sub">${fmt.number(totalCliques)} acumulados</div></div>
            <div class="stat-card purple"><div class="stat-label">👁️ Impressões</div><div class="stat-value">${fmt.short(totalImpres)}</div><div class="stat-sub">${fmt.number(totalImpres)} entregas</div></div>
            <div class="stat-card"><div class="stat-label">📣 Campanhas Ativas</div><div class="stat-value">${fmt.number(rankCamp.length)}</div><div class="stat-sub">${fmt.number(datasAtivas.length)} dias com mídia</div></div>
            <div class="stat-card orange"><div class="stat-label">📊 CPM Médio</div><div class="stat-value">${fmt.currency(cpmMedio)}</div><div class="stat-sub">Custo por mil impressões</div></div>
            <div class="stat-card green"><div class="stat-label">🖱️ CPC Médio</div><div class="stat-value">${fmt.currency(cpc)}</div><div class="stat-sub">Custo por clique</div></div>
            <div class="stat-card"><div class="stat-label">🎯 CTR Médio</div><div class="stat-value">${fmt.pct(ctr)}</div><div class="stat-sub">Taxa de clique consolidada</div></div>
            <div class="stat-card purple"><div class="stat-label">📅 Gasto Médio / Dia</div><div class="stat-value">${fmt.currency(gastoMedioDia)}</div><div class="stat-sub">${fmt.number(datasAtivas.length)} dias ativos</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card wide"><div class="chart-title">📣 Investimento Mensal em Meta Ads</div><div class="chart-subtitle">Leitura consolidada por mês para facilitar comparação entre períodos</div><div class="chart-wrap"><canvas id="ch-mkt-invest"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">👆 Cliques por Mês</div><div class="chart-subtitle">Volume mensal de tráfego gerado</div><div class="chart-wrap"><canvas id="ch-mkt-cliques-detalhe"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🎯 CTR (%) x CPM (R$)</div><div class="chart-subtitle">Eficiência do tráfego ao longo do tempo</div><div class="chart-wrap"><canvas id="ch-mkt-eficiencia"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🖱️ CPC por Mês</div><div class="chart-subtitle">Quanto custou cada clique em média</div><div class="chart-wrap"><canvas id="ch-mkt-cpc"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🏆 Top Campanhas por Investimento</div><div class="chart-subtitle">Ranking das campanhas com maior gasto</div><div class="chart-wrap"><canvas id="ch-mkt-camp"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">📊 Participação do Investimento</div><div class="chart-subtitle">Distribuição do orçamento por campanha</div><div class="chart-wrap"><canvas id="ch-mkt-share"></canvas></div></div>
        </div>
        <div class="chart-card" style="margin-bottom:24px">
            <div class="chart-title">🔍 Leitura Rápida de Meta Ads</div>
            <div class="chart-subtitle">Resumo automático para leitura executiva da mídia paga</div>
            <div class="insight-grid">
                <div class="insight-card green"><div class="insight-title">Pico de investimento</div><div class="insight-text"><span class="insight-highlight">${picoInvest ? picoInvest.label : '—'}</span> concentrou <span class="insight-highlight">${fmt.currency(picoInvest?.gasto || 0)}</span> e gerou <span class="insight-highlight">${fmt.number(picoInvest?.cliques || 0)}</span> cliques.</div></div>
                <div class="insight-card purple"><div class="insight-title">Maior volume de cliques</div><div class="insight-text"><span class="insight-highlight">${picoCliques ? picoCliques.label : '—'}</span> liderou com <span class="insight-highlight">${fmt.number(picoCliques?.cliques || 0)}</span> cliques e CTR de <span class="insight-highlight">${fmt.pct(picoCliques?.ctr || 0)}</span>.</div></div>
                <div class="insight-card orange"><div class="insight-title">Melhor campanha em CTR</div><div class="insight-text">${melhorCampCtr ? `<span class="insight-highlight">${melhorCampCtr.campanha}</span> teve CTR de <span class="insight-highlight">${fmt.pct(melhorCampCtr.ctr)}</span>.` : 'Ainda não há impressões suficientes para medir CTR por campanha.'}</div></div>
                <div class="insight-card red"><div class="insight-title">CPC mais eficiente</div><div class="insight-text">${melhorCampCpc ? `<span class="insight-highlight">${melhorCampCpc.campanha}</span> entregou CPC médio de <span class="insight-highlight">${fmt.currency(melhorCampCpc.cpc)}</span>.` : 'Ainda não há cliques suficientes para medir CPC por campanha.'}</div></div>
            </div>
            <div class="summary-box">
                <div class="summary-title">Análise automática do período</div>
                <div class="summary-list">
                    <div class="summary-item">${campanhaLider ? `${campanhaLider.campanha} concentra ${fmt.pct(campanhaLider.share)} do investimento total e segue como principal frente de mídia.` : 'Nenhuma campanha encontrada para o período.'}</div>
                    <div class="summary-item">${melhorCtrMes ? `${melhorCtrMes.label} teve o melhor CTR mensal, em ${fmt.pct(melhorCtrMes.ctr)}, indicando melhor aderência criativa ou segmentação.` : 'CTR mensal indisponível para o período filtrado.'}</div>
                    <div class="summary-item">${picoInvest ? `${picoInvest.label} foi o mês de maior aporte e serve como referência para comparar escala versus eficiência.` : 'Não foi possível identificar o mês de maior investimento.'}</div>
                    <div class="summary-item">${melhorCampCpc ? `O menor CPC ficou com ${melhorCampCpc.campanha}, útil como base para replicar público, criativo ou objetivo de campanha.` : 'Não foi possível calcular o menor CPC com os dados atuais.'}</div>
                </div>
            </div>
        </div>
        <div class="table-card">
            <div class="table-header">📣 Desempenho por Campanha</div>
            <table><thead><tr><th>#</th><th>Campanha</th><th>Gasto</th><th>% do Total</th><th>Impressões</th><th>Cliques</th><th>CPM</th><th>CPC</th><th>CTR</th></tr></thead><tbody>
                ${rankCamp.map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td><strong>${r.campanha}</strong></td><td>${fmt.currency(r.gasto)}</td><td>${fmt.pct(r.share)}</td><td>${fmt.number(r.impressoes)}</td><td>${fmt.number(r.cliques)}</td><td>${fmt.currency(r.cpm)}</td><td>${fmt.currency(r.cpc)}</td><td><span class="badge ${r.ctr>=3?'badge-green':r.ctr>=1?'badge-orange':'badge-red'}">${fmt.pct(r.ctr)}</span></td></tr>`).join('')}
            </tbody></table>
        </div>`;

    const labelsMes = resumoMensal.map(r => r.label);
    const paletteCamp = topCampanhas.map((_, i) => COLORS.palette[i % COLORS.palette.length]);
    const paletteShare = shareCampanhas.map((_, i) => COLORS.palette[i % COLORS.palette.length]);

    makeChart('ch-mkt-invest', {
        type: 'bar',
        data: { labels: labelsMes, datasets: [{ label: 'Investimento (R$)', data: resumoMensal.map(r => r.gasto), backgroundColor: 'rgba(220,38,38,.78)', borderRadius: 8 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Investimento: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-mkt-cliques-detalhe', {
        type: 'line',
        data: { labels: labelsMes, datasets: [{ label: 'Cliques', data: resumoMensal.map(r => r.cliques), borderColor: COLORS.primary, backgroundColor: 'rgba(37,99,235,.10)', fill: true, tension: .25, pointRadius: 2.5, pointHoverRadius: 4 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Cliques: ${fmt.number(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.short(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-mkt-eficiencia', {
        type: 'line',
        data: {
            labels: labelsMes,
            datasets: [
                { label: 'CTR (%)', data: resumoMensal.map(r => +r.ctr.toFixed(2)), borderColor: COLORS.success, backgroundColor: 'rgba(22,163,74,.08)', tension: .25, yAxisID: 'y', pointRadius: 2.5, pointHoverRadius: 4 },
                { label: 'CPM (R$)', data: resumoMensal.map(r => +r.cpm.toFixed(2)), borderColor: COLORS.warning, backgroundColor: 'rgba(217,119,6,.08)', tension: .25, yAxisID: 'y1', pointRadius: 2.5, pointHoverRadius: 4 }
            ]
        },
        options: {
            scales: {
                y: { position: 'left', ticks: { callback: v => v + '%' }, grid: { color: '#e2e8f0' } },
                y1: { position: 'right', ticks: { callback: v => fmt.currency(v) }, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-mkt-cpc', {
        type: 'line',
        data: { labels: labelsMes, datasets: [{ label: 'CPC (R$)', data: resumoMensal.map(r => +r.cpc.toFixed(2)), borderColor: COLORS.purple, backgroundColor: 'rgba(124,58,237,.10)', fill: true, tension: .25, pointRadius: 2.5, pointHoverRadius: 4 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `CPC: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-mkt-camp', {
        type: 'bar',
        data: { labels: topCampanhas.map(r => r.campanha), datasets: [{ label: 'Investimento', data: topCampanhas.map(r => r.gasto), backgroundColor: paletteCamp, borderRadius: 8 }] },
        options: {
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Investimento: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                y: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-mkt-share', {
        type: 'doughnut',
        data: { labels: shareCampanhas.map(r => r.campanha), datasets: [{ data: shareCampanhas.map(r => r.gasto), backgroundColor: paletteShare, borderWidth: 2, borderColor: '#fff' }] },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => `${ctx.label}: ${fmt.currency(ctx.raw)}` } }
            },
            cutout: '58%'
        }
    });
}

async function loadMarketingDetalhado() {
    const raw = await apiFetch('trafego_pago');
    const base = raw.map(row => {
        const dt = parseDateAny(row.data || row.data_ref);
        if (!dt) return null;
        return {
            ...row,
            _date: dt,
            _ano: String(dt.getFullYear()),
            _mes: String(dt.getMonth() + 1).padStart(2, '0')
        };
    }).filter(Boolean);

    const anos = [...new Set(base.map(r => r._ano))].sort();
    filterAno = uniqueValues(filterAno).filter(value => anos.includes(String(value)));
    const dataAno = base.filter(r => !hasSelection(filterAno) || filterIncludes(filterAno, r._ano));
    const mesesDisponiveis = dataAno.map(r => r._mes);
    filterMes = uniqueValues(filterMes).filter(value => mesesDisponiveis.includes(String(value)));

    const dataPeriodo = dataAno.filter(r => !hasSelection(filterMes) || filterIncludes(filterMes, r._mes));
    const campanhas = [...new Set(dataPeriodo.map(r => String(r.campanha || '').trim()).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    filterCampanha = uniqueValues(filterCampanha).filter(value => campanhas.some(c => normalizeText(c) === normalizeText(value)));

    setFiltersUI(
        buildAnoSelect('applyFilters()', anos) +
        buildMesSelect('applyFilters()', mesesDisponiveis) +
        buildCampanhaSelect('applyFilters()', campanhas)
    );

    const data = dataPeriodo.filter(r => !hasSelection(filterCampanha) || filterIncludes(filterCampanha, String(r.campanha || '').trim(), normalizeText));
    if (!data.length) {
        error('Nenhum dado de Meta Ads encontrado para os filtros selecionados.');
        return;
    }

    const totalGasto = sumBy(data, 'valor_gasto');
    const totalCliques = sumBy(data, 'cliques');
    const totalImpres = sumBy(data, 'impressoes');
    const cpmMedio = totalImpres > 0 ? (totalGasto / totalImpres) * 1000 : 0;
    const cpcMedio = totalCliques > 0 ? totalGasto / totalCliques : 0;
    const ctrMedio = totalImpres > 0 ? (totalCliques / totalImpres) * 100 : 0;

    const granularidadeDiaria = uniqueValues(filterAno).length === 1 && uniqueValues(filterMes).length === 1;
    const rotuloPeriodo = granularidadeDiaria ? 'Dia' : 'Mes';
    const rotuloPeriodoPlural = granularidadeDiaria ? 'dias' : 'meses';
    const datasAtivas = [...new Set(data.map(r => dayKeyFromDate(r._date)))];

    const porPeriodo = {};
    data.forEach(r => {
        const key = granularidadeDiaria ? dayKeyFromDate(r._date) : monthKeyFromDate(r._date);
        if (!porPeriodo[key]) porPeriodo[key] = { gasto: 0, cliques: 0, impressoes: 0 };
        porPeriodo[key].gasto += Number(r.valor_gasto || 0);
        porPeriodo[key].cliques += Number(r.cliques || 0);
        porPeriodo[key].impressoes += Number(r.impressoes || 0);
    });

    const periodosOrdenados = Object.keys(porPeriodo).sort();
    const resumoPeriodo = periodosOrdenados.map(key => {
        const row = porPeriodo[key];
        return {
            key,
            label: granularidadeDiaria ? formatDayKey(key) : formatMonthKey(key),
            gasto: row.gasto,
            cliques: row.cliques,
            impressoes: row.impressoes,
            ctr: row.impressoes > 0 ? (row.cliques / row.impressoes) * 100 : 0,
            cpc: row.cliques > 0 ? row.gasto / row.cliques : 0,
            cpm: row.impressoes > 0 ? (row.gasto / row.impressoes) * 1000 : 0
        };
    });

    const gastoMedioPeriodo = resumoPeriodo.length > 0 ? totalGasto / resumoPeriodo.length : 0;

    const porCamp = groupBy(data, 'campanha');
    const rankCamp = Object.entries(porCamp).map(([campanha, rows]) => {
        const gasto = sumBy(rows, 'valor_gasto');
        const cliques = sumBy(rows, 'cliques');
        const impressoes = sumBy(rows, 'impressoes');
        return {
            campanha,
            gasto,
            cliques,
            impressoes,
            share: totalGasto > 0 ? (gasto / totalGasto) * 100 : 0,
            cpc: cliques > 0 ? gasto / cliques : 0,
            cpm: impressoes > 0 ? (gasto / impressoes) * 1000 : 0,
            ctr: impressoes > 0 ? (cliques / impressoes) * 100 : 0
        };
    }).sort((a, b) => b.gasto - a.gasto);

    const topCampanhas = rankCamp.slice(0, 10);
    const shareCampanhas = rankCamp.slice(0, 6).map(r => ({ campanha: r.campanha, gasto: r.gasto }));
    if (rankCamp.length > 6) {
        shareCampanhas.push({ campanha: 'Outras', gasto: sumBy(rankCamp.slice(6), 'gasto') });
    }

    const picoInvest = resumoPeriodo.reduce((best, row) => !best || row.gasto > best.gasto ? row : best, null);
    const picoCliques = resumoPeriodo.reduce((best, row) => !best || row.cliques > best.cliques ? row : best, null);
    const melhorCtrPeriodo = resumoPeriodo.filter(r => r.impressoes > 0).sort((a, b) => b.ctr - a.ctr)[0] || null;
    const melhorCampCtr = [...rankCamp].filter(r => r.impressoes >= 1000).sort((a, b) => b.ctr - a.ctr)[0]
        || [...rankCamp].filter(r => r.impressoes > 0).sort((a, b) => b.ctr - a.ctr)[0]
        || null;
    const melhorCampCpc = [...rankCamp].filter(r => r.cliques > 0).sort((a, b) => a.cpc - b.cpc)[0] || null;
    const campanhaLider = rankCamp[0] || null;

    const periodoPartes = [];
    if (hasSelection(filterCampanha)) periodoPartes.push(selectedLabels(filterCampanha));
    if (hasSelection(filterAno)) periodoPartes.push(selectedLabels(filterAno));
    if (hasSelection(filterMes)) periodoPartes.push(selectedLabels(filterMes, value => MESES[Number(value) - 1] || value));
    const periodoLabel = periodoPartes.length ? periodoPartes.join(' • ') : 'Periodo consolidado';

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="marketing-shell">
            ${buildMarketingSubnavCard()}
            <div class="stats-grid">
            <div class="stat-card red"><div class="stat-label">Total Investido</div><div class="stat-value">${fmt.currency(totalGasto)}</div><div class="stat-sub">${periodoLabel}</div></div>
            <div class="stat-card"><div class="stat-label">Cliques</div><div class="stat-value">${fmt.short(totalCliques)}</div><div class="stat-sub">${fmt.number(totalCliques)} acumulados</div></div>
            <div class="stat-card purple"><div class="stat-label">Impressoes</div><div class="stat-value">${fmt.short(totalImpres)}</div><div class="stat-sub">${fmt.number(totalImpres)} entregas</div></div>
            <div class="stat-card"><div class="stat-label">Campanhas Ativas</div><div class="stat-value">${fmt.number(rankCamp.length)}</div><div class="stat-sub">${fmt.number(datasAtivas.length)} dias com investimento</div></div>
            <div class="stat-card orange"><div class="stat-label">CPM Medio</div><div class="stat-value">${fmt.currency(cpmMedio)}</div><div class="stat-sub">Custo por mil impressoes</div></div>
            <div class="stat-card green"><div class="stat-label">CPC Medio</div><div class="stat-value">${fmt.currency(cpcMedio)}</div><div class="stat-sub">Custo por clique</div></div>
            <div class="stat-card"><div class="stat-label">CTR Medio</div><div class="stat-value">${fmt.pct(ctrMedio)}</div><div class="stat-sub">Taxa de clique consolidada</div></div>
            <div class="stat-card purple"><div class="stat-label">Gasto Medio / ${rotuloPeriodo}</div><div class="stat-value">${fmt.currency(gastoMedioPeriodo)}</div><div class="stat-sub">${fmt.number(resumoPeriodo.length)} ${rotuloPeriodoPlural} analisados</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card wide"><div class="chart-title">Investimento ${granularidadeDiaria ? 'Diario' : 'Mensal'} em Meta Ads</div><div class="chart-subtitle">${granularidadeDiaria ? 'Leitura por dia dentro do mes filtrado' : 'Leitura por mes para comparar performance'}</div><div class="chart-wrap"><canvas id="ch-mkt-invest"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Cliques por ${rotuloPeriodo}</div><div class="chart-subtitle">Volume de trafego gerado no periodo</div><div class="chart-wrap"><canvas id="ch-mkt-cliques-detalhe"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">CTR (%) x CPM (R$)</div><div class="chart-subtitle">Eficiencia do trafego ao longo do tempo</div><div class="chart-wrap"><canvas id="ch-mkt-eficiencia"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">CPC por ${rotuloPeriodo}</div><div class="chart-subtitle">Quanto custou cada clique em media</div><div class="chart-wrap"><canvas id="ch-mkt-cpc"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Top Campanhas por Investimento</div><div class="chart-subtitle">Ranking das campanhas com maior gasto</div><div class="chart-wrap"><canvas id="ch-mkt-camp"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Participacao do Investimento</div><div class="chart-subtitle">Distribuicao do orcamento por campanha</div><div class="chart-wrap"><canvas id="ch-mkt-share"></canvas></div></div>
        </div>
        <div class="chart-card" style="margin-bottom:24px">
            <div class="chart-title">Leitura Rapida de Meta Ads</div>
            <div class="chart-subtitle">Resumo automatico para leitura executiva da midia paga</div>
            <div class="insight-grid">
                <div class="insight-card green"><div class="insight-title">Pico de investimento</div><div class="insight-text"><span class="insight-highlight">${picoInvest ? picoInvest.label : '-'}</span> concentrou <span class="insight-highlight">${fmt.currency(picoInvest?.gasto || 0)}</span> e gerou <span class="insight-highlight">${fmt.number(picoInvest?.cliques || 0)}</span> cliques.</div></div>
                <div class="insight-card purple"><div class="insight-title">Maior volume de cliques</div><div class="insight-text"><span class="insight-highlight">${picoCliques ? picoCliques.label : '-'}</span> liderou com <span class="insight-highlight">${fmt.number(picoCliques?.cliques || 0)}</span> cliques e CTR de <span class="insight-highlight">${fmt.pct(picoCliques?.ctr || 0)}</span>.</div></div>
                <div class="insight-card orange"><div class="insight-title">Melhor campanha em CTR</div><div class="insight-text">${melhorCampCtr ? `<span class="insight-highlight">${melhorCampCtr.campanha}</span> teve CTR de <span class="insight-highlight">${fmt.pct(melhorCampCtr.ctr)}</span>.` : 'Ainda nao ha impressoes suficientes para medir CTR por campanha.'}</div></div>
                <div class="insight-card red"><div class="insight-title">CPC mais eficiente</div><div class="insight-text">${melhorCampCpc ? `<span class="insight-highlight">${melhorCampCpc.campanha}</span> entregou CPC medio de <span class="insight-highlight">${fmt.currency(melhorCampCpc.cpc)}</span>.` : 'Ainda nao ha cliques suficientes para medir CPC por campanha.'}</div></div>
            </div>
            <div class="summary-box">
                <div class="summary-title">Analise automatica do periodo</div>
                <div class="summary-list">
                    <div class="summary-item">${campanhaLider ? `${campanhaLider.campanha} concentra ${fmt.pct(campanhaLider.share)} do investimento total e segue como principal frente de midia.` : 'Nenhuma campanha encontrada para o periodo.'}</div>
                    <div class="summary-item">${melhorCtrPeriodo ? `${melhorCtrPeriodo.label} teve o melhor CTR do recorte, em ${fmt.pct(melhorCtrPeriodo.ctr)}.` : 'CTR indisponivel para o periodo filtrado.'}</div>
                    <div class="summary-item">${picoInvest ? `${picoInvest.label} foi o recorte de maior aporte e serve como referencia para comparar escala versus eficiencia.` : 'Nao foi possivel identificar o pico de investimento.'}</div>
                    <div class="summary-item">${melhorCampCpc ? `O menor CPC ficou com ${melhorCampCpc.campanha}, util como base para replicar publico, criativo ou objetivo.` : 'Nao foi possivel calcular o menor CPC com os dados atuais.'}</div>
                </div>
            </div>
        </div>
        <div class="table-card">
            <div class="table-header">Desempenho por Campanha</div>
            <table><thead><tr><th>#</th><th>Campanha</th><th>Gasto</th><th>% do Total</th><th>Impressoes</th><th>Cliques</th><th>CPM</th><th>CPC</th><th>CTR</th></tr></thead><tbody>
                ${rankCamp.map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td><strong>${r.campanha}</strong></td><td>${fmt.currency(r.gasto)}</td><td>${fmt.pct(r.share)}</td><td>${fmt.number(r.impressoes)}</td><td>${fmt.number(r.cliques)}</td><td>${fmt.currency(r.cpm)}</td><td>${fmt.currency(r.cpc)}</td><td><span class="badge ${r.ctr>=3?'badge-green':r.ctr>=1?'badge-orange':'badge-red'}">${fmt.pct(r.ctr)}</span></td></tr>`).join('')}
            </tbody></table>
        </div>
        </div>`;

    const labelsPeriodo = resumoPeriodo.map(r => r.label);
    const paletteCamp = topCampanhas.map((_, i) => COLORS.palette[i % COLORS.palette.length]);
    const paletteShare = shareCampanhas.map((_, i) => COLORS.palette[i % COLORS.palette.length]);
    const maxTicksPeriodo = granularidadeDiaria ? 12 : 10;

    makeChart('ch-mkt-invest', {
        type: 'bar',
        data: { labels: labelsPeriodo, datasets: [{ label: 'Investimento (R$)', data: resumoPeriodo.map(r => r.gasto), backgroundColor: 'rgba(220,38,38,.78)', borderRadius: 8 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Investimento: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: maxTicksPeriodo } }
            }
        }
    });

    makeChart('ch-mkt-cliques-detalhe', {
        type: 'line',
        data: { labels: labelsPeriodo, datasets: [{ label: 'Cliques', data: resumoPeriodo.map(r => r.cliques), borderColor: COLORS.primary, backgroundColor: 'rgba(37,99,235,.10)', fill: true, tension: .25, pointRadius: 2.5, pointHoverRadius: 4 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Cliques: ${fmt.number(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.short(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: maxTicksPeriodo } }
            }
        }
    });

    makeChart('ch-mkt-eficiencia', {
        type: 'line',
        data: {
            labels: labelsPeriodo,
            datasets: [
                { label: 'CTR (%)', data: resumoPeriodo.map(r => +r.ctr.toFixed(2)), borderColor: COLORS.success, backgroundColor: 'rgba(22,163,74,.08)', tension: .25, yAxisID: 'y', pointRadius: 2.5, pointHoverRadius: 4 },
                { label: 'CPM (R$)', data: resumoPeriodo.map(r => +r.cpm.toFixed(2)), borderColor: COLORS.warning, backgroundColor: 'rgba(217,119,6,.08)', tension: .25, yAxisID: 'y1', pointRadius: 2.5, pointHoverRadius: 4 }
            ]
        },
        options: {
            scales: {
                y: { position: 'left', ticks: { callback: v => v + '%' }, grid: { color: '#e2e8f0' } },
                y1: { position: 'right', ticks: { callback: v => fmt.currency(v) }, grid: { display: false } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: maxTicksPeriodo } }
            }
        }
    });

    makeChart('ch-mkt-cpc', {
        type: 'line',
        data: { labels: labelsPeriodo, datasets: [{ label: 'CPC (R$)', data: resumoPeriodo.map(r => +r.cpc.toFixed(2)), borderColor: COLORS.purple, backgroundColor: 'rgba(124,58,237,.10)', fill: true, tension: .25, pointRadius: 2.5, pointHoverRadius: 4 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `CPC: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: maxTicksPeriodo } }
            }
        }
    });

    makeChart('ch-mkt-camp', {
        type: 'bar',
        data: { labels: topCampanhas.map(r => r.campanha), datasets: [{ label: 'Investimento', data: topCampanhas.map(r => r.gasto), backgroundColor: paletteCamp, borderRadius: 8 }] },
        options: {
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Investimento: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                y: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-mkt-share', {
        type: 'doughnut',
        data: { labels: shareCampanhas.map(r => r.campanha), datasets: [{ data: shareCampanhas.map(r => r.gasto), backgroundColor: paletteShare, borderWidth: 2, borderColor: '#fff' }] },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => `${ctx.label}: ${fmt.currency(ctx.raw)}` } }
            },
            cutout: '58%'
        }
    });
}


async function loadMarketingDetalhado() {
    if (filterMarketingFonte === 'formularios_rotulos') {
        return loadMarketingFormularios();
    }

    const [raw, rawCupons] = await Promise.all([
        apiFetch('trafego_pago'),
        apiFetch('cupons_usados')
    ]);

    const base = raw.map(row => {
        const dt = parseDateAny(row.data || row.data_ref);
        if (!dt) return null;
        return {
            ...row,
            _date: dt,
            _ano: String(dt.getFullYear()),
            _mes: String(dt.getMonth() + 1).padStart(2, '0')
        };
    }).filter(Boolean);

    const baseCupons = rawCupons.map(row => {
        const dt = parseDateAny(row.data_ref || row.ano_mes || row.mes_ano_uso_cupom);
        if (!dt) return null;
        return {
            ...row,
            cupom: String(row.cupom || row.cupom_aplicado || '').trim(),
            usos: Number(row.usos || 0),
            receita_gerada: Number(row.receita_gerada || 0),
            _date: dt,
            _ano: String(row.ano || dt.getFullYear()),
            _mes: monthNumberFromValue(row.mes_num || row.ano_mes || row.data_ref) || String(dt.getMonth() + 1).padStart(2, '0')
        };
    }).filter(Boolean);

    const anos = [...new Set([
        ...base.map(r => r._ano),
        ...baseCupons.map(r => r._ano)
    ])].sort();
    filterAno = uniqueValues(filterAno).filter(value => anos.includes(String(value)));

    const dataAno = base.filter(r => !hasSelection(filterAno) || filterIncludes(filterAno, r._ano));
    const cuponsAno = baseCupons.filter(r => !hasSelection(filterAno) || filterIncludes(filterAno, r._ano));
    const mesesDisponiveis = [...new Set([
        ...dataAno.map(r => r._mes),
        ...cuponsAno.map(r => r._mes)
    ])].sort();
    filterMes = uniqueValues(filterMes).filter(value => mesesDisponiveis.includes(String(value)));

    const dataPeriodo = dataAno.filter(r => !hasSelection(filterMes) || filterIncludes(filterMes, r._mes));
    const cuponsPeriodo = cuponsAno.filter(r => !hasSelection(filterMes) || filterIncludes(filterMes, r._mes));

    const campanhas = [...new Set(dataPeriodo.map(r => String(r.campanha || '').trim()).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    filterCampanha = uniqueValues(filterCampanha).filter(value => campanhas.some(c => normalizeText(c) === normalizeText(value)));

    const cuponsDisponiveis = [...new Set(cuponsPeriodo.map(r => String(r.cupom || '').trim()).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'pt-BR'));
    filterCupom = uniqueValues(filterCupom).filter(value => cuponsDisponiveis.some(c => normalizeText(c) === normalizeText(value)));

    setFiltersUI(
        buildAnoSelect('applyFilters()', anos) +
        buildMesSelect('applyFilters()', mesesDisponiveis) +
        buildCampanhaSelect('applyFilters()', campanhas) +
        buildCupomSelect('applyFilters()', cuponsDisponiveis)
    );

    const data = dataPeriodo.filter(r => !hasSelection(filterCampanha) || filterIncludes(filterCampanha, String(r.campanha || '').trim(), normalizeText));
    const cuponsData = cuponsPeriodo.filter(r => !hasSelection(filterCupom) || filterIncludes(filterCupom, String(r.cupom || '').trim(), normalizeText));

    if (!data.length && !cuponsData.length) {
        document.getElementById('content').innerHTML = `
            <div class="marketing-shell">
                <div class="error-state">
                    Nenhum dado de Marketing encontrado para os filtros selecionados.
                </div>
            </div>`;
        return;
    }

    const totalGasto = sumBy(data, 'valor_gasto');
    const totalCliques = sumBy(data, 'cliques');
    const totalImpres = sumBy(data, 'impressoes');
    const cpmMedio = totalImpres > 0 ? (totalGasto / totalImpres) * 1000 : 0;
    const cpcMedio = totalCliques > 0 ? totalGasto / totalCliques : 0;
    const ctrMedio = totalImpres > 0 ? (totalCliques / totalImpres) * 100 : 0;

    const granularidadeDiaria = uniqueValues(filterAno).length === 1 && uniqueValues(filterMes).length === 1;
    const rotuloPeriodo = granularidadeDiaria ? 'Dia' : 'Mes';
    const rotuloPeriodoPlural = granularidadeDiaria ? 'dias' : 'meses';
    const datasAtivas = [...new Set(data.map(r => dayKeyFromDate(r._date)))];

    const porPeriodo = {};
    data.forEach(r => {
        const key = granularidadeDiaria ? dayKeyFromDate(r._date) : monthKeyFromDate(r._date);
        if (!porPeriodo[key]) porPeriodo[key] = { gasto: 0, cliques: 0, impressoes: 0 };
        porPeriodo[key].gasto += Number(r.valor_gasto || 0);
        porPeriodo[key].cliques += Number(r.cliques || 0);
        porPeriodo[key].impressoes += Number(r.impressoes || 0);
    });

    const periodosOrdenados = Object.keys(porPeriodo).sort();
    const resumoPeriodo = periodosOrdenados.map(key => {
        const row = porPeriodo[key];
        return {
            key,
            label: granularidadeDiaria ? formatDayKey(key) : formatMonthKey(key),
            gasto: row.gasto,
            cliques: row.cliques,
            impressoes: row.impressoes,
            ctr: row.impressoes > 0 ? (row.cliques / row.impressoes) * 100 : 0,
            cpc: row.cliques > 0 ? row.gasto / row.cliques : 0,
            cpm: row.impressoes > 0 ? (row.gasto / row.impressoes) * 1000 : 0
        };
    });

    const gastoMedioPeriodo = resumoPeriodo.length > 0 ? totalGasto / resumoPeriodo.length : 0;

    const porCamp = groupBy(data, 'campanha');
    const rankCamp = Object.entries(porCamp).map(([campanha, rows]) => {
        const gasto = sumBy(rows, 'valor_gasto');
        const cliques = sumBy(rows, 'cliques');
        const impressoes = sumBy(rows, 'impressoes');
        return {
            campanha,
            gasto,
            cliques,
            impressoes,
            share: totalGasto > 0 ? (gasto / totalGasto) * 100 : 0,
            cpc: cliques > 0 ? gasto / cliques : 0,
            cpm: impressoes > 0 ? (gasto / impressoes) * 1000 : 0,
            ctr: impressoes > 0 ? (cliques / impressoes) * 100 : 0
        };
    }).sort((a, b) => b.gasto - a.gasto);

    const topCampanhas = rankCamp.slice(0, 10);
    const shareCampanhas = rankCamp.slice(0, 6).map(r => ({ campanha: r.campanha, gasto: r.gasto }));
    if (rankCamp.length > 6) {
        shareCampanhas.push({ campanha: 'Outras', gasto: sumBy(rankCamp.slice(6), 'gasto') });
    }

    const picoInvest = resumoPeriodo.reduce((best, row) => !best || row.gasto > best.gasto ? row : best, null);
    const picoCliques = resumoPeriodo.reduce((best, row) => !best || row.cliques > best.cliques ? row : best, null);
    const melhorCtrPeriodo = resumoPeriodo.filter(r => r.impressoes > 0).sort((a, b) => b.ctr - a.ctr)[0] || null;
    const melhorCampCtr = [...rankCamp].filter(r => r.impressoes >= 1000).sort((a, b) => b.ctr - a.ctr)[0]
        || [...rankCamp].filter(r => r.impressoes > 0).sort((a, b) => b.ctr - a.ctr)[0]
        || null;
    const melhorCampCpc = [...rankCamp].filter(r => r.cliques > 0).sort((a, b) => a.cpc - b.cpc)[0] || null;
    const campanhaLider = rankCamp[0] || null;

    const rankCupons = Object.entries(groupBy(cuponsData.filter(r => String(r.cupom || '').trim() !== ''), 'cupom'))
        .map(([cupom, rows]) => ({
            cupom,
            usos: sumBy(rows, 'usos'),
            receita: sumBy(rows, 'receita_gerada')
        }))
        .sort((a, b) => b.usos - a.usos || b.receita - a.receita);
    const topCupons = rankCupons.slice(0, 12);
    const totalUsosCupons = sumBy(rankCupons, 'usos');
    const totalReceitaCupons = sumBy(rankCupons, 'receita');
    const ticketMedioCupom = totalUsosCupons > 0 ? totalReceitaCupons / totalUsosCupons : 0;
    const cupomLider = rankCupons[0] || null;

    const periodoPartes = [];
    if (hasSelection(filterCampanha)) periodoPartes.push(selectedLabels(filterCampanha));
    if (hasSelection(filterAno)) periodoPartes.push(selectedLabels(filterAno));
    if (hasSelection(filterMes)) periodoPartes.push(selectedLabels(filterMes, value => MESES[Number(value) - 1] || value));
    const periodoLabel = periodoPartes.length ? periodoPartes.join(' | ') : 'Periodo consolidado';

    const cupomPartes = [];
    if (hasSelection(filterCupom)) cupomPartes.push(selectedLabels(filterCupom));
    if (hasSelection(filterAno)) cupomPartes.push(selectedLabels(filterAno));
    if (hasSelection(filterMes)) cupomPartes.push(selectedLabels(filterMes, value => MESES[Number(value) - 1] || value));
    const periodoCuponsLabel = cupomPartes.length ? cupomPartes.join(' | ') : 'Todos os cupons';

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="marketing-shell">
            <div class="stats-grid">
            <div class="stat-card red"><div class="stat-label">Total Investido</div><div class="stat-value">${fmt.currency(totalGasto)}</div><div class="stat-sub">${periodoLabel}</div></div>
            <div class="stat-card"><div class="stat-label">Cliques</div><div class="stat-value">${fmt.short(totalCliques)}</div><div class="stat-sub">${fmt.number(totalCliques)} acumulados</div></div>
            <div class="stat-card purple"><div class="stat-label">Impressoes</div><div class="stat-value">${fmt.short(totalImpres)}</div><div class="stat-sub">${fmt.number(totalImpres)} entregas</div></div>
            <div class="stat-card"><div class="stat-label">Campanhas Ativas</div><div class="stat-value">${fmt.number(rankCamp.length)}</div><div class="stat-sub">${fmt.number(datasAtivas.length)} dias com investimento</div></div>
            <div class="stat-card orange"><div class="stat-label">CPM Medio</div><div class="stat-value">${fmt.currency(cpmMedio)}</div><div class="stat-sub">Custo por mil impressoes</div></div>
            <div class="stat-card green"><div class="stat-label">CPC Medio</div><div class="stat-value">${fmt.currency(cpcMedio)}</div><div class="stat-sub">Custo por clique</div></div>
            <div class="stat-card"><div class="stat-label">CTR Medio</div><div class="stat-value">${fmt.pct(ctrMedio)}</div><div class="stat-sub">Taxa de clique consolidada</div></div>
            <div class="stat-card purple"><div class="stat-label">Gasto Medio / ${rotuloPeriodo}</div><div class="stat-value">${fmt.currency(gastoMedioPeriodo)}</div><div class="stat-sub">${fmt.number(resumoPeriodo.length)} ${rotuloPeriodoPlural} analisados</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card wide"><div class="chart-title">Investimento ${granularidadeDiaria ? 'Diario' : 'Mensal'} em Marketing</div><div class="chart-subtitle">${granularidadeDiaria ? 'Leitura por dia dentro do mes filtrado' : 'Leitura por mes para comparar performance'}</div><div class="chart-wrap"><canvas id="ch-mkt-invest"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Cliques por ${rotuloPeriodo}</div><div class="chart-subtitle">Volume de trafego gerado no periodo</div><div class="chart-wrap"><canvas id="ch-mkt-cliques-detalhe"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">CTR (%) x CPM (R$)</div><div class="chart-subtitle">Eficiencia do trafego ao longo do tempo</div><div class="chart-wrap"><canvas id="ch-mkt-eficiencia"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">CPC por ${rotuloPeriodo}</div><div class="chart-subtitle">Quanto custou cada clique em media</div><div class="chart-wrap"><canvas id="ch-mkt-cpc"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Top Campanhas por Investimento</div><div class="chart-subtitle">Ranking das campanhas com maior gasto</div><div class="chart-wrap"><canvas id="ch-mkt-camp"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Participacao do Investimento</div><div class="chart-subtitle">Distribuicao do orcamento por campanha</div><div class="chart-wrap"><canvas id="ch-mkt-share"></canvas></div></div>
        </div>
        <div class="chart-card" style="margin-bottom:24px">
            <div class="chart-title">Cupons Usados</div>
            <div class="chart-subtitle">Leitura dedicada dos cupons aplicados no periodo filtrado</div>
            ${topCupons.length ? `
            <div class="chart-wrap"><canvas id="ch-mkt-cupons"></canvas></div>
            ` : `
            <div class="summary-box"><div class="summary-item">Ajuste ano, mes ou o filtro de cupom para visualizar este grafico.</div></div>
            `}
            <div class="stats-grid" style="margin-top:18px">
                <div class="stat-card"><div class="stat-label">Usos de Cupons</div><div class="stat-value">${fmt.short(totalUsosCupons)}</div><div class="stat-sub">${periodoCuponsLabel}</div></div>
                <div class="stat-card green"><div class="stat-label">Receita por Cupons</div><div class="stat-value">${fmt.currency(totalReceitaCupons)}</div><div class="stat-sub">${fmt.number(totalUsosCupons)} usos acumulados</div></div>
                <div class="stat-card purple"><div class="stat-label">Cupons Ativos</div><div class="stat-value">${fmt.number(rankCupons.length)}</div><div class="stat-sub">${cupomLider ? `${cupomLider.cupom} lidera o recorte` : 'Sem cupons no recorte'}</div></div>
                <div class="stat-card orange"><div class="stat-label">Receita Media / Uso</div><div class="stat-value">${fmt.currency(ticketMedioCupom)}</div><div class="stat-sub">Valor medio gerado por uso</div></div>
            </div>
        </div>
        <div class="chart-card" style="margin-bottom:24px">
            <div class="chart-title">Leitura Rapida de Marketing</div>
            <div class="chart-subtitle">Resumo automatico para leitura executiva da midia paga</div>
            <div class="insight-grid">
                <div class="insight-card green"><div class="insight-title">Pico de investimento</div><div class="insight-text"><span class="insight-highlight">${picoInvest ? picoInvest.label : '-'}</span> concentrou <span class="insight-highlight">${fmt.currency(picoInvest?.gasto || 0)}</span> e gerou <span class="insight-highlight">${fmt.number(picoInvest?.cliques || 0)}</span> cliques.</div></div>
                <div class="insight-card purple"><div class="insight-title">Maior volume de cliques</div><div class="insight-text"><span class="insight-highlight">${picoCliques ? picoCliques.label : '-'}</span> liderou com <span class="insight-highlight">${fmt.number(picoCliques?.cliques || 0)}</span> cliques e CTR de <span class="insight-highlight">${fmt.pct(picoCliques?.ctr || 0)}</span>.</div></div>
                <div class="insight-card orange"><div class="insight-title">Melhor campanha em CTR</div><div class="insight-text">${melhorCampCtr ? `<span class="insight-highlight">${melhorCampCtr.campanha}</span> teve CTR de <span class="insight-highlight">${fmt.pct(melhorCampCtr.ctr)}</span>.` : 'Ainda nao ha impressoes suficientes para medir CTR por campanha.'}</div></div>
                <div class="insight-card red"><div class="insight-title">CPC mais eficiente</div><div class="insight-text">${melhorCampCpc ? `<span class="insight-highlight">${melhorCampCpc.campanha}</span> entregou CPC medio de <span class="insight-highlight">${fmt.currency(melhorCampCpc.cpc)}</span>.` : 'Ainda nao ha cliques suficientes para medir CPC por campanha.'}</div></div>
            </div>
            <div class="summary-box">
                <div class="summary-title">Analise automatica do periodo</div>
                <div class="summary-list">
                    <div class="summary-item">${campanhaLider ? `${campanhaLider.campanha} concentra ${fmt.pct(campanhaLider.share)} do investimento total e segue como principal frente de midia.` : 'Nenhuma campanha encontrada para o periodo.'}</div>
                    <div class="summary-item">${melhorCtrPeriodo ? `${melhorCtrPeriodo.label} teve o melhor CTR do recorte, em ${fmt.pct(melhorCtrPeriodo.ctr)}.` : 'CTR indisponivel para o periodo filtrado.'}</div>
                    <div class="summary-item">${picoInvest ? `${picoInvest.label} foi o recorte de maior aporte e serve como referencia para comparar escala versus eficiencia.` : 'Nao foi possivel identificar o pico de investimento.'}</div>
                    <div class="summary-item">${melhorCampCpc ? `O menor CPC ficou com ${melhorCampCpc.campanha}, util como base para replicar publico, criativo ou objetivo.` : 'Nao foi possivel calcular o menor CPC com os dados atuais.'}</div>
                </div>
            </div>
        </div>
        <div class="table-card">
            <div class="table-header">Desempenho por Campanha</div>
            <table><thead><tr><th>#</th><th>Campanha</th><th>Gasto</th><th>% do Total</th><th>Impressoes</th><th>Cliques</th><th>CPM</th><th>CPC</th><th>CTR</th></tr></thead><tbody>
                ${rankCamp.map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td><strong>${r.campanha}</strong></td><td>${fmt.currency(r.gasto)}</td><td>${fmt.pct(r.share)}</td><td>${fmt.number(r.impressoes)}</td><td>${fmt.number(r.cliques)}</td><td>${fmt.currency(r.cpm)}</td><td>${fmt.currency(r.cpc)}</td><td><span class="badge ${r.ctr>=3?'badge-green':r.ctr>=1?'badge-orange':'badge-red'}">${fmt.pct(r.ctr)}</span></td></tr>`).join('')}
            </tbody></table>
        </div>
        </div>`;

    const labelsPeriodo = resumoPeriodo.map(r => r.label);
    const paletteCamp = topCampanhas.map((_, i) => COLORS.palette[i % COLORS.palette.length]);
    const paletteShare = shareCampanhas.map((_, i) => COLORS.palette[i % COLORS.palette.length]);
    const maxTicksPeriodo = granularidadeDiaria ? 12 : 10;
    const labelsCupons = topCupons.map(r => r.cupom.length > 18 ? `${r.cupom.slice(0, 18)}...` : r.cupom);

    makeChart('ch-mkt-invest', {
        type: 'bar',
        data: { labels: labelsPeriodo, datasets: [{ label: 'Investimento (R$)', data: resumoPeriodo.map(r => r.gasto), backgroundColor: 'rgba(220,38,38,.78)', borderRadius: 8 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Investimento: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: maxTicksPeriodo } }
            }
        }
    });

    makeChart('ch-mkt-cliques-detalhe', {
        type: 'line',
        data: { labels: labelsPeriodo, datasets: [{ label: 'Cliques', data: resumoPeriodo.map(r => r.cliques), borderColor: COLORS.primary, backgroundColor: 'rgba(37,99,235,.10)', fill: true, tension: .25, pointRadius: 2.5, pointHoverRadius: 4 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Cliques: ${fmt.number(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.short(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: maxTicksPeriodo } }
            }
        }
    });

    makeChart('ch-mkt-eficiencia', {
        type: 'line',
        data: {
            labels: labelsPeriodo,
            datasets: [
                { label: 'CTR (%)', data: resumoPeriodo.map(r => +r.ctr.toFixed(2)), borderColor: COLORS.success, backgroundColor: 'rgba(22,163,74,.08)', tension: .25, yAxisID: 'y', pointRadius: 2.5, pointHoverRadius: 4 },
                { label: 'CPM (R$)', data: resumoPeriodo.map(r => +r.cpm.toFixed(2)), borderColor: COLORS.warning, backgroundColor: 'rgba(217,119,6,.08)', tension: .25, yAxisID: 'y1', pointRadius: 2.5, pointHoverRadius: 4 }
            ]
        },
        options: {
            scales: {
                y: { position: 'left', ticks: { callback: v => v + '%' }, grid: { color: '#e2e8f0' } },
                y1: { position: 'right', ticks: { callback: v => fmt.currency(v) }, grid: { display: false } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: maxTicksPeriodo } }
            }
        }
    });

    makeChart('ch-mkt-cpc', {
        type: 'line',
        data: { labels: labelsPeriodo, datasets: [{ label: 'CPC (R$)', data: resumoPeriodo.map(r => +r.cpc.toFixed(2)), borderColor: COLORS.purple, backgroundColor: 'rgba(124,58,237,.10)', fill: true, tension: .25, pointRadius: 2.5, pointHoverRadius: 4 }] },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `CPC: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: maxTicksPeriodo } }
            }
        }
    });

    makeChart('ch-mkt-camp', {
        type: 'bar',
        data: { labels: topCampanhas.map(r => r.campanha), datasets: [{ label: 'Investimento', data: topCampanhas.map(r => r.gasto), backgroundColor: paletteCamp, borderRadius: 8 }] },
        options: {
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `Investimento: ${fmt.currency(ctx.raw)}` } }
            },
            scales: {
                x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                y: { grid: { display: false } }
            }
        }
    });

    makeChart('ch-mkt-share', {
        type: 'doughnut',
        data: { labels: shareCampanhas.map(r => r.campanha), datasets: [{ data: shareCampanhas.map(r => r.gasto), backgroundColor: paletteShare, borderWidth: 2, borderColor: '#fff' }] },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => `${ctx.label}: ${fmt.currency(ctx.raw)}` } }
            },
            cutout: '58%'
        }
    });

    if (topCupons.length) {
        makeChart('ch-mkt-cupons', {
            type: 'bar',
            data: {
                labels: labelsCupons,
                datasets: [
                    { label: 'Usos', data: topCupons.map(r => r.usos), backgroundColor: 'rgba(8,145,178,.78)', borderRadius: 8, yAxisID: 'y' },
                    { type: 'line', label: 'Receita (R$)', data: topCupons.map(r => +r.receita.toFixed(2)), borderColor: COLORS.success, backgroundColor: 'rgba(22,163,74,.08)', tension: .25, yAxisID: 'y1', pointRadius: 2.5, pointHoverRadius: 4 }
                ]
            },
            options: {
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            title: items => topCupons[items?.[0]?.dataIndex]?.cupom || items?.[0]?.label || 'Cupom',
                            label: ctx => ctx.dataset.label === 'Usos'
                                ? `Usos: ${fmt.number(ctx.raw)}`
                                : `Receita: ${fmt.currency(ctx.raw)}`
                        }
                    }
                },
                scales: {
                    y: { position: 'left', ticks: { callback: v => fmt.short(v) }, grid: { color: '#e2e8f0' } },
                    y1: { position: 'right', ticks: { callback: v => fmt.currency(v) }, grid: { display: false } },
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } }
                }
            }
        });
    }
}

// ============================================================
// NAVIGATION
// ============================================================
async function loadProdutosDetalhado() {
    const data = await apiFetch('produtos', { loja: filterLoja, ano: filterAno, mes: filterMes });
    const readTipoProduto = row => {
        const raw = String(row?.tipo_produto || '').trim();
        const normalized = normalizeText(raw);
        return ['', 'n a', 'na', 'nao informado', 'sem tipo', 'nao se aplica'].includes(normalized) ? '' : raw;
    };
    const resolveTipoProduto = rows => rows.map(readTipoProduto).find(Boolean) || 'Sem tipo';
    const anos = [...new Set(data.map(r => Number(r.ano || parseDateAny(r.data_ref || r.data)?.getFullYear())).filter(Boolean))].sort();
    const meses = data.map(r => r.mes_num || r.ano_mes || r.data_ref);
    const tipos = [...new Set(data.map(readTipoProduto).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'pt-BR'));
    setFiltersUI(buildLojaSelect('applyFilters()') + buildAnoSelect('applyFilters()', anos) + buildMesSelect('applyFilters()', meses) + buildTipoProdutoSelect('applyFilters()', tipos) + buildAbcSelect('applyFilters()') + buildProdutoSearch('applyFilters()'));

    if (!data.length) { error('Nenhum dado de produtos encontrado.'); return; }

    const dataTipo = hasSelection(filterTipoProduto)
        ? data.filter(r => filterIncludes(filterTipoProduto, readTipoProduto(r), normalizeText))
        : data;
    if (!dataTipo.length) { error('Nenhum produto encontrado para o tipo selecionado.'); return; }

    const porProduto = groupBy(dataTipo, 'produto');
    let rankProdutos = Object.entries(porProduto).map(([produto, rows]) => ({
        produto,
        tipo: resolveTipoProduto(rows),
        unidades: sumBy(rows, 'unidades_vendidas'),
        faturamento: sumBy(rows, 'valor'),
        periodosAtivos: rows.filter(r => Number(r.unidades_vendidas || 0) > 0 || Number(r.valor || 0) > 0).length,
        ticketMedio: sumBy(rows, 'unidades_vendidas') > 0 ? sumBy(rows, 'valor') / sumBy(rows, 'unidades_vendidas') : 0
    })).sort((a,b) => b.faturamento - a.faturamento);

    const totalFat = sumBy(rankProdutos, 'faturamento');
    let acum = 0;
    rankProdutos = rankProdutos.map(p => {
        acum += p.faturamento;
        const pct = totalFat > 0 ? (acum / totalFat) * 100 : 0;
        return { ...p, abc: pct <= 80 ? 'A' : pct <= 95 ? 'B' : 'C' };
    });

    const contABC = {A:0,B:0,C:0};
    rankProdutos.forEach(p => contABC[p.abc]++);

    let produtosFiltrados = hasSelection(filterAbc) ? rankProdutos.filter(p => filterIncludes(filterAbc, p.abc)) : rankProdutos;
    if (filterProdutoBusca.trim()) {
        const q = filterProdutoBusca.trim().toLowerCase();
        produtosFiltrados = produtosFiltrados.filter(p => String(p.produto).toLowerCase().includes(q));
    }

    const top10Fat = produtosFiltrados.slice(0,10);
    const top10Uni = [...produtosFiltrados].sort((a,b) => b.unidades - a.unidades).slice(0,10);
    const ticketMedioGeral = sumBy(produtosFiltrados, 'unidades') > 0 ? sumBy(produtosFiltrados, 'faturamento') / sumBy(produtosFiltrados, 'unidades') : 0;
    const mediaPeriodosAtivos = produtosFiltrados.length > 0 ? sumBy(produtosFiltrados, 'periodosAtivos') / produtosFiltrados.length : 0;

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">📦 Total de Produtos</div><div class="stat-value">${fmt.number(produtosFiltrados.length)}</div><div class="stat-sub">Após filtros</div></div>
            <div class="stat-card green"><div class="stat-label">⭐ Curva A</div><div class="stat-value">${contABC.A}</div><div class="stat-sub">80% do faturamento</div></div>
            <div class="stat-card"><div class="stat-label">🎯 Curva B</div><div class="stat-value">${contABC.B}</div><div class="stat-sub">95% acumulado</div></div>
            <div class="stat-card orange"><div class="stat-label">📉 Curva C</div><div class="stat-value">${contABC.C}</div><div class="stat-sub">Cauda longa</div></div>
            <div class="stat-card purple"><div class="stat-label">💰 Ticket Médio por Item</div><div class="stat-value">${fmt.currency(ticketMedioGeral)}</div><div class="stat-sub">Média do mix filtrado</div></div>
            <div class="stat-card"><div class="stat-label">📅 Meses com Venda</div><div class="stat-value">${mediaPeriodosAtivos.toFixed(1)}</div><div class="stat-sub">Média por produto no período</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card compact"><div class="chart-title">💰 Top 10 por Faturamento</div><div class="chart-wrap"><canvas id="ch-prod-fat"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">📦 Top 10 por Unidades Vendidas</div><div class="chart-wrap"><canvas id="ch-prod-uni"></canvas></div></div>
        </div>
        <div class="table-card">
            <div class="table-header">📊 Ranking de Produtos com Curva ABC ${hasSelection(filterAbc) ? `— Curvas ${selectedLabels(filterAbc)}` : ''}${filterProdutoBusca ? ` — busca: "${filterProdutoBusca}"` : ''}</div>
            <table><thead><tr><th>#</th><th>Produto</th><th>Tipo</th><th>Unidades</th><th>Faturamento</th><th>Meses com Venda</th><th>Ticket Item</th><th>Curva</th></tr></thead><tbody>
                ${produtosFiltrados.slice(0,100).map((r,i) => `<tr><td>${i+1}</td><td style="max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${r.produto}">${r.produto}</td><td><span class="badge badge-blue" style="font-size:11px">${r.tipo}</span></td><td>${fmt.number(r.unidades)}</td><td>${fmt.currency(r.faturamento)}</td><td>${fmt.number(r.periodosAtivos)}</td><td>${fmt.currency(r.ticketMedio)}</td><td><span class="badge abc-${r.abc.toLowerCase()}">${r.abc}</span></td></tr>`).join('')}
            </tbody></table>
        </div>`;

    const shortLabel = arr => arr.map(r => r.produto.length > 34 ? r.produto.substring(0,34)+'…' : r.produto);
    makeChart('ch-prod-fat', { type: 'bar', data: { labels: shortLabel(top10Fat), datasets: [{ data: top10Fat.map(r => r.faturamento), backgroundColor: COLORS.palette, borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-prod-uni', { type: 'bar', data: { labels: shortLabel(top10Uni), datasets: [{ data: top10Uni.map(r => r.unidades), backgroundColor: 'rgba(124,58,237,.8)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
}

async function loadHoltec() {
    const raw = await apiFetch('holtec');
    const base = raw.map(row => {
        const ano = String(row.ano || '').trim();
        const mes = String(row.mes_num || monthNumberFromValue(row.mes || row.data_ref) || '').padStart(2, '0');
        return {
            ...row,
            _ano: ano,
            _mes: mes,
            _periodKey: ano && mes ? `${ano}-${mes}` : ''
        };
    }).filter(row => row._ano && row._mes);

    const anos = [...new Set(base.map(r => r._ano))].sort();
    filterAno = uniqueValues(filterAno).filter(value => anos.includes(String(value)));

    const dataAno = base.filter(r => !hasSelection(filterAno) || filterIncludes(filterAno, r._ano));
    const mesesDisponiveis = [...new Set(dataAno.map(r => r._mes))].sort();
    filterMes = uniqueValues(filterMes).filter(value => mesesDisponiveis.includes(String(value).padStart(2, '0')));

    const dataPeriodo = dataAno.filter(r => !hasSelection(filterMes) || filterIncludes(filterMes, r._mes));
    const categorias = [...new Set(dataPeriodo.map(r => String(r.categoria || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'pt-BR'));
    const grupos = [...new Set(dataPeriodo.map(r => String(r.grupo || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'pt-BR'));

    filterHoltecCategoria = uniqueValues(filterHoltecCategoria).filter(value => categorias.some(item => normalizeText(item) === normalizeText(value)));
    filterHoltecGrupo = uniqueValues(filterHoltecGrupo).filter(value => grupos.some(item => normalizeText(item) === normalizeText(value)));

    setFiltersUI(
        buildAnoSelect('applyFilters()', anos) +
        buildMesSelect('applyFilters()', mesesDisponiveis) +
        buildHoltecCategoriaSelect('applyFilters()', categorias) +
        buildHoltecGrupoSelect('applyFilters()', grupos)
    );

    const data = dataPeriodo.filter(row =>
        (!hasSelection(filterHoltecCategoria) || filterIncludes(filterHoltecCategoria, String(row.categoria || '').trim(), normalizeText)) &&
        (!hasSelection(filterHoltecGrupo) || filterIncludes(filterHoltecGrupo, String(row.grupo || '').trim(), normalizeText))
    );

    if (!data.length) {
        error('Nenhum dado Holtec encontrado para os filtros selecionados.');
        return;
    }

    const totalVendas = sumBy(data, 'vendas_totais');
    const totalQtde = sumBy(data, 'qtde_vendida');
    const ticketMedio = totalQtde > 0 ? totalVendas / totalQtde : 0;
    const produtosUnicos = new Set(data.map(r => String(r.produto || '').trim()).filter(Boolean)).size;

    const categoriaRank = Object.entries(groupBy(data, 'categoria')).map(([categoria, rows]) => ({
        categoria,
        qtde: sumBy(rows, 'qtde_vendida'),
        vendas: sumBy(rows, 'vendas_totais'),
        share: totalVendas > 0 ? (sumBy(rows, 'vendas_totais') / totalVendas) * 100 : 0
    })).sort((a, b) => b.vendas - a.vendas);

    const grupoRank = Object.entries(groupBy(data, 'grupo')).map(([grupo, rows]) => ({
        grupo,
        qtde: sumBy(rows, 'qtde_vendida'),
        vendas: sumBy(rows, 'vendas_totais'),
        share: totalVendas > 0 ? (sumBy(rows, 'vendas_totais') / totalVendas) * 100 : 0
    })).sort((a, b) => b.vendas - a.vendas);

    const produtoRank = Object.entries(groupBy(data, 'produto')).map(([produto, rows]) => {
        const vendas = sumBy(rows, 'vendas_totais');
        const qtde = sumBy(rows, 'qtde_vendida');
        const categoriasProduto = [...new Set(rows.map(r => String(r.categoria || '').trim()).filter(Boolean))];
        const grupoProduto = Object.entries(groupBy(rows, 'grupo')).sort((a, b) => b[1].length - a[1].length)[0]?.[0] || 'N/A';
        return {
            produto,
            categoria: categoriasProduto.join(', ') || 'N/A',
            grupo: grupoProduto,
            qtde,
            vendas,
            ticket: qtde > 0 ? vendas / qtde : 0,
            share: totalVendas > 0 ? (vendas / totalVendas) * 100 : 0
        };
    }).sort((a, b) => b.vendas - a.vendas);

    const periodMap = {};
    data.forEach(row => {
        const key = row._periodKey;
        if (!key) return;
        if (!periodMap[key]) periodMap[key] = { vendas: 0, qtde: 0 };
        periodMap[key].vendas += Number(row.vendas_totais || 0);
        periodMap[key].qtde += Number(row.qtde_vendida || 0);
    });

    const resumoPeriodo = Object.keys(periodMap).sort().map(key => ({
        key,
        label: formatMonthKey(key),
        vendas: periodMap[key].vendas,
        qtde: periodMap[key].qtde
    }));

    const categoriaLider = categoriaRank[0] || null;
    const grupoLider = grupoRank[0] || null;
    const produtoLider = produtoRank[0] || null;
    const filtrosResumo = [];
    if (hasSelection(filterAno)) filtrosResumo.push(selectedLabels(filterAno));
    if (hasSelection(filterMes)) filtrosResumo.push(selectedLabels(filterMes, value => MESES[Number(value) - 1] || value));
    if (hasSelection(filterHoltecCategoria)) filtrosResumo.push(selectedLabels(filterHoltecCategoria));
    if (hasSelection(filterHoltecGrupo)) filtrosResumo.push(selectedLabels(filterHoltecGrupo));
    const periodoLabel = filtrosResumo.length ? filtrosResumo.join(' | ') : 'Periodo consolidado';
    const shortLabel = (value, limit = 34) => String(value || '').length > limit ? String(value).slice(0, limit) + '...' : String(value || '');

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">Holtec</div><div class="stat-value">${fmt.currency(totalVendas)}</div><div class="stat-sub">${periodoLabel}</div></div>
            <div class="stat-card green"><div class="stat-label">Quantidade Vendida</div><div class="stat-value">${fmt.number(totalQtde)}</div><div class="stat-sub">Unidades no recorte</div></div>
            <div class="stat-card purple"><div class="stat-label">Produtos</div><div class="stat-value">${fmt.number(produtosUnicos)}</div><div class="stat-sub">Itens distintos</div></div>
            <div class="stat-card orange"><div class="stat-label">Ticket Medio Item</div><div class="stat-value">${fmt.currency(ticketMedio)}</div><div class="stat-sub">Vendas / quantidade</div></div>
            <div class="stat-card"><div class="stat-label">Categoria Lider</div><div class="stat-value">${categoriaLider ? escapeHtml(categoriaLider.categoria) : '-'}</div><div class="stat-sub">${categoriaLider ? `${fmt.pct(categoriaLider.share)} das vendas` : 'Sem categoria'}</div></div>
            <div class="stat-card green"><div class="stat-label">Grupo Lider</div><div class="stat-value">${grupoLider ? escapeHtml(grupoLider.grupo) : '-'}</div><div class="stat-sub">${grupoLider ? `${fmt.pct(grupoLider.share)} das vendas` : 'Sem grupo'}</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card wide"><div class="chart-title">Vendas Holtec por Mes</div><div class="chart-wrap"><canvas id="ch-holtec-periodo"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Top Produtos por Venda</div><div class="chart-wrap"><canvas id="ch-holtec-produtos"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Categorias</div><div class="chart-wrap"><canvas id="ch-holtec-categorias"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">Grupos ABC</div><div class="chart-wrap"><canvas id="ch-holtec-grupos"></canvas></div></div>
        </div>
        <div class="chart-card" style="margin-bottom:24px">
            <div class="chart-title">Leitura rapida Holtec</div>
            <div class="summary-box">
                <div class="summary-title">Resumo do periodo</div>
                <div class="summary-list">
                    <div class="summary-item">${produtoLider ? `${escapeHtml(produtoLider.produto)} lidera com ${fmt.currency(produtoLider.vendas)} em vendas e ${fmt.number(produtoLider.qtde)} unidades.` : 'Nenhum produto encontrado.'}</div>
                    <div class="summary-item">${categoriaLider ? `${escapeHtml(categoriaLider.categoria)} concentra ${fmt.pct(categoriaLider.share)} do faturamento Holtec.` : 'Categoria lider indisponivel.'}</div>
                    <div class="summary-item">${grupoLider ? `Grupo ${escapeHtml(grupoLider.grupo)} representa ${fmt.pct(grupoLider.share)} das vendas no recorte.` : 'Grupo lider indisponivel.'}</div>
                </div>
            </div>
        </div>
        <div class="table-card">
            <div class="table-header">Ranking Holtec por Produto</div>
            <table><thead><tr><th>#</th><th>Produto</th><th>Categoria</th><th>Grupo</th><th>Qtde</th><th>Vendas</th><th>Participacao</th><th>Ticket</th></tr></thead><tbody>
                ${produtoRank.slice(0,100).map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td style="max-width:420px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${escapeHtml(r.produto)}"><strong>${escapeHtml(r.produto)}</strong></td><td>${escapeHtml(r.categoria)}</td><td><span class="badge abc-${String(r.grupo || '').toLowerCase()}">${escapeHtml(r.grupo)}</span></td><td>${fmt.number(r.qtde)}</td><td>${fmt.currency(r.vendas)}</td><td>${fmt.pct(r.share)}</td><td>${fmt.currency(r.ticket)}</td></tr>`).join('')}
            </tbody></table>
        </div>`;

    makeChart('ch-holtec-periodo', {
        type: 'bar',
        data: {
            labels: resumoPeriodo.map(r => r.label),
            datasets: [
                { label: 'Vendas', data: resumoPeriodo.map(r => r.vendas), backgroundColor: 'rgba(37,99,235,.78)', borderRadius: 8, yAxisID: 'y' },
                { type: 'line', label: 'Quantidade', data: resumoPeriodo.map(r => r.qtde), borderColor: COLORS.success, backgroundColor: 'rgba(22,163,74,.08)', tension: .25, yAxisID: 'y1', pointRadius: 2.5, pointHoverRadius: 4 }
            ]
        },
        options: {
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { position: 'left', ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } },
                y1: { position: 'right', ticks: { callback: v => fmt.short(v) }, grid: { display: false } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } }
            }
        }
    });

    makeChart('ch-holtec-produtos', { type: 'bar', data: { labels: produtoRank.slice(0, 10).map(r => shortLabel(r.produto)), datasets: [{ data: produtoRank.slice(0, 10).map(r => r.vendas), backgroundColor: COLORS.palette, borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => fmt.currency(ctx.raw) } } }, scales: { x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-holtec-categorias', { type: 'doughnut', data: { labels: categoriaRank.map(r => r.categoria), datasets: [{ data: categoriaRank.map(r => r.vendas), backgroundColor: categoriaRank.map((_, i) => COLORS.palette[i % COLORS.palette.length]), borderWidth: 2, borderColor: '#fff' }] }, options: { plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${fmt.currency(ctx.raw)}` } } }, cutout: '58%' } });
    makeChart('ch-holtec-grupos', { type: 'bar', data: { labels: grupoRank.map(r => r.grupo), datasets: [{ data: grupoRank.map(r => r.vendas), backgroundColor: grupoRank.map((_, i) => COLORS.palette[i % COLORS.palette.length]), borderRadius: 8 }] }, options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => fmt.currency(ctx.raw) } } }, scales: { y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } } } });
}

async function loadOperacao() {
    const [funil, uxGeral, prescritores] = await Promise.all([
        apiFetch('funil'),
        apiFetch('ux_tempo_geral'),
        apiFetch('prescritores')
    ]);
    setFiltersUI('');

    if (!funil.length && !uxGeral.length && !prescritores.length) { error('Nenhum dado operacional encontrado.'); return; }

    const totalFunil = sumBy(funil, 'quantidade');
    const aprovadas = funil.find(r => String(r.status_orcamento || '').toLowerCase().includes('aprov'))?.quantidade || 0;
    const canceladas = sumBy(funil.filter(r => /cancel|expir/i.test(String(r.status_orcamento || ''))), 'quantidade');
    const taxaAprovacao = totalFunil > 0 ? (aprovadas / totalFunil) * 100 : 0;
    const tempoAprov = uxGeral.find(r => /aprov/i.test(String(r.etapa_processo || '')))?.tempo_medio_horas || 0;
    const tempoAtendimento = uxGeral.find(r => /atendimento/i.test(String(r.etapa_processo || '')))?.tempo_medio_horas || 0;
    const topPrescReceita = [...prescritores].sort((a,b) => b.receita_gerada - a.receita_gerada).slice(0,10);
    const topPrescQtd = [...prescritores].sort((a,b) => b.formulas_geradas - a.formulas_geradas).slice(0,10);
    const lider = topPrescReceita[0] || null;
    const etapasOrdenadas = [...uxGeral].map(r => ({
        etapa: r.etapa_processo,
        tempo_texto: r.tempo_medio_texto || r.tempo_texto || '-',
        tempo_horas: Number(r.tempo_medio_horas || r.tempo_horas || 0)
    })).sort((a,b) => b.tempo_horas - a.tempo_horas);

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">📋 Total no Funil</div><div class="stat-value">${fmt.number(totalFunil)}</div><div class="stat-sub">Status consolidados</div></div>
            <div class="stat-card green"><div class="stat-label">✅ Aprovadas</div><div class="stat-value">${fmt.number(aprovadas)}</div><div class="stat-sub">${fmt.pct(taxaAprovacao)} do funil</div></div>
            <div class="stat-card red"><div class="stat-label">⛔ Perdidas</div><div class="stat-value">${fmt.number(canceladas)}</div><div class="stat-sub">Canceladas + expiradas</div></div>
            <div class="stat-card purple"><div class="stat-label">⏱️ Tempo de Aprovação</div><div class="stat-value">${tempoAprov.toFixed(1)}h</div><div class="stat-sub">Média geral</div></div>
            <div class="stat-card orange"><div class="stat-label">🕐 Tempo de Atendimento</div><div class="stat-value">${tempoAtendimento.toFixed(1)}h</div><div class="stat-sub">Média geral</div></div>
            <div class="stat-card"><div class="stat-label">🩺 Principal Prescritor</div><div class="stat-value" style="font-size:18px">${lider ? lider.prescritor : 'N/A'}</div><div class="stat-sub">${fmt.currency(lider?.receita_gerada || 0)}</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card compact"><div class="chart-title">🍩 Funil por Status</div><div class="chart-subtitle">Distribuição consolidada das fórmulas no pipeline</div><div class="chart-wrap"><canvas id="ch-op-funil"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">⏱️ Tempos Médios por Etapa</div><div class="chart-subtitle">Leitura em horas para cada fase operacional</div><div class="chart-wrap"><canvas id="ch-op-tempo"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🧭 Etapas Mais Demoradas</div><div class="chart-subtitle">Ranking das etapas com maior tempo médio</div><div class="chart-wrap"><canvas id="ch-op-status"></canvas></div></div>
            <div class="chart-card wide"><div class="chart-title">🏆 Top Prescritores por Receita</div><div class="chart-subtitle">Receita gerada por prescritor</div><div class="chart-wrap"><canvas id="ch-op-presc-receita"></canvas></div></div>
            <div class="chart-card wide"><div class="chart-title">📝 Top Prescritores por Fórmulas</div><div class="chart-subtitle">Volume gerado por prescritor</div><div class="chart-wrap"><canvas id="ch-op-presc-qtd"></canvas></div></div>
        </div>
        <div class="summary-box" style="margin-bottom:24px">
            <div class="summary-title">Leitura Operacional</div>
            <div class="summary-list">
                <div class="summary-item">${fmt.number(aprovadas)} fórmulas aprovadas representam ${fmt.pct(taxaAprovacao)} do funil consolidado.</div>
                <div class="summary-item">O tempo médio de aprovação está em ${tempoAprov.toFixed(1)}h, enquanto o atendimento inicial leva ${tempoAtendimento.toFixed(1)}h.</div>
                <div class="summary-item">${lider ? `${lider.prescritor} lidera a receita entre os prescritores com ${fmt.currency(lider.receita_gerada)}.` : 'Não foi possível identificar o principal prescritor.'}</div>
            </div>
        </div>
        <div class="table-card">
            <div class="table-header">🩺 Ranking de Prescritores</div>
            <table><thead><tr><th>#</th><th>Prescritor</th><th>Fórmulas</th><th>Receita Gerada</th></tr></thead><tbody>
                ${[...prescritores].sort((a,b) => b.receita_gerada - a.receita_gerada).slice(0,30).map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td>${r.prescritor}</td><td>${fmt.number(r.formulas_geradas)}</td><td>${fmt.currency(r.receita_gerada)}</td></tr>`).join('')}
            </tbody></table>
        </div>
        <div class="table-card">
            <div class="table-header">⏱️ Detalhe das Etapas</div>
            <table><thead><tr><th>#</th><th>Etapa</th><th>Tempo</th><th>Horas</th></tr></thead><tbody>
                ${etapasOrdenadas.map((r,i) => `<tr><td>${i+1}</td><td>${r.etapa}</td><td>${r.tempo_texto || '-'}</td><td>${Number(r.tempo_horas || 0).toFixed(2)}h</td></tr>`).join('')}
            </tbody></table>
        </div>`;

    makeChart('ch-op-funil', { type: 'doughnut', data: { labels: funil.map(r => r.status_orcamento), datasets: [{ data: funil.map(r => r.quantidade), backgroundColor: COLORS.palette, borderWidth: 2, borderColor: '#fff' }] }, options: { plugins: { legend: { position: 'bottom' } }, cutout: '58%' } });
    makeChart('ch-op-tempo', { type: 'bar', data: { labels: uxGeral.map(r => r.etapa_processo), datasets: [{ data: uxGeral.map(r => Number(r.tempo_medio_horas || 0).toFixed(2)), backgroundColor: 'rgba(37,99,235,.82)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => v+'h' }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-op-status', { type: 'bar', data: { labels: etapasOrdenadas.map(r => r.etapa), datasets: [{ data: etapasOrdenadas.map(r => Number(r.tempo_horas.toFixed(2))), backgroundColor: 'rgba(217,119,6,.82)', borderRadius: 8 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => v+'h' }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } } } });
    makeChart('ch-op-presc-receita', { type: 'bar', data: { labels: topPrescReceita.map(r => r.prescritor), datasets: [{ data: topPrescReceita.map(r => r.receita_gerada), backgroundColor: 'rgba(22,163,74,.82)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-op-presc-qtd', { type: 'bar', data: { labels: topPrescQtd.map(r => r.prescritor), datasets: [{ data: topPrescQtd.map(r => r.formulas_geradas), backgroundColor: 'rgba(124,58,237,.82)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
}

async function loadCRM() {
    const [rfm, canais, geografia, recuperacaoRaw] = await Promise.all([
        apiFetch('rfm_clientes'),
        apiFetch('canais_aquisicao'),
        apiFetch('geografia'),
        apiFetch('recuperacao_vendas')
    ]);
    const anosRecuperacao = [...new Set(recuperacaoRaw.map(r => String(r.ano || '').trim()).filter(v => /^\d{4}$/.test(v)))].sort();
    filterAno = uniqueValues(filterAno).filter(value => anosRecuperacao.includes(String(value)));
    const mesesRecuperacao = recuperacaoRaw
        .filter(r => !hasSelection(filterAno) || filterIncludes(filterAno, String(r.ano || '')))
        .map(r => r.ano_mes || '');
    filterMes = uniqueValues(filterMes).filter(value => mesesRecuperacao.map(monthNumberFromValue).includes(String(value)));
    setFiltersUI(buildAnoSelect('applyFilters()', anosRecuperacao) + buildMesSelect('applyFilters()', mesesRecuperacao));

    const recuperacao = recuperacaoRaw.filter(r => {
        if (hasSelection(filterAno) && !filterIncludes(filterAno, String(r.ano || ''))) return false;
        if (hasSelection(filterMes) && !filterIncludes(filterMes, String(r.ano_mes || '').slice(5, 7))) return false;
        return true;
    });

    if (!rfm.length && !canais.length && !geografia.length && !recuperacaoRaw.length) { error('Nenhum dado de CRM encontrado.'); return; }

    const statusRetencao = Object.entries(groupBy(rfm, 'status_retencao')).map(([status, rows]) => ({ status, quantidade: rows.length })).sort((a,b) => b.quantidade - a.quantidade);
    const ativos = statusRetencao.find(r => String(r.status).toLowerCase() === 'ativo')?.quantidade || 0;
    const emRisco = sumBy(statusRetencao.filter(r => String(r.status).toLowerCase() !== 'ativo'), 'quantidade');
    const topClientes = [...rfm].sort((a,b) => b.monetario - a.monetario).slice(0,10);
    const topCanais = [...canais].sort((a,b) => b.receita - a.receita).slice(0,10);
    const topGeo = [...geografia].sort((a,b) => b.faturamento_total - a.faturamento_total).slice(0,10);
    const recuperacaoValida = [...recuperacao].filter(r => Number(r.valor || 0) > 0);
    const potencialRecuperacao = sumBy(recuperacaoValida, 'valor');
    const contatosComEmail = recuperacao.filter(r => r.possui_email).length;
    const recuperacaoMes = Object.entries(groupBy(recuperacaoValida, 'ano_mes')).map(([anoMes, rows]) => ({ anoMes, valor: sumBy(rows, 'valor') })).sort((a,b) => String(a.anoMes).localeCompare(String(b.anoMes)));
    const principalCanal = topCanais[0] || null;
    const principalPraca = topGeo[0] || null;

    destroyCharts();
    document.getElementById('content').innerHTML = `
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">👥 Base de Clientes</div><div class="stat-value">${fmt.number(rfm.length)}</div><div class="stat-sub">Clientes com score RFM</div></div>
            <div class="stat-card green"><div class="stat-label">✅ Clientes Ativos</div><div class="stat-value">${fmt.number(ativos)}</div><div class="stat-sub">${fmt.pct(rfm.length ? (ativos / rfm.length) * 100 : 0)} da base</div></div>
            <div class="stat-card red"><div class="stat-label">⚠️ Em Atenção</div><div class="stat-value">${fmt.number(emRisco)}</div><div class="stat-sub">Demais status de retenção</div></div>
            <div class="stat-card purple"><div class="stat-label">💸 Potencial Recuperável</div><div class="stat-value">${fmt.currency(potencialRecuperacao)}</div><div class="stat-sub">${fmt.number(recuperacaoValida.length)} oportunidades</div></div>
            <div class="stat-card"><div class="stat-label">📧 Leads com Email</div><div class="stat-value">${fmt.number(contatosComEmail)}</div><div class="stat-sub">Na base de recuperação</div></div>
            <div class="stat-card orange"><div class="stat-label">📣 Principal Canal</div><div class="stat-value" style="font-size:18px">${principalCanal ? principalCanal.canal_captacao : 'N/A'}</div><div class="stat-sub">${fmt.currency(principalCanal?.receita || 0)}</div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card compact"><div class="chart-title">🍩 Status de Retenção</div><div class="chart-subtitle">Distribuição da base RFM por situação</div><div class="chart-wrap"><canvas id="ch-crm-retencao"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">📣 Canais de Aquisição</div><div class="chart-subtitle">Receita por canal de entrada</div><div class="chart-wrap"><canvas id="ch-crm-canais"></canvas></div></div>
            <div class="chart-card compact"><div class="chart-title">🗺️ Geografia de Receita</div><div class="chart-subtitle">Praças com maior faturamento</div><div class="chart-wrap"><canvas id="ch-crm-geo"></canvas></div></div>
            <div class="chart-card wide"><div class="chart-title">🏆 Top Clientes por Monetário</div><div class="chart-subtitle">Clientes mais relevantes no score RFM</div><div class="chart-wrap"><canvas id="ch-crm-rfm"></canvas></div></div>
            <div class="chart-card wide"><div class="chart-title">📈 Recuperação por Mês</div><div class="chart-subtitle">Potencial financeiro por mês de criação da oportunidade</div><div class="chart-wrap"><canvas id="ch-crm-recuperacao"></canvas></div></div>
        </div>
        <div class="summary-box" style="margin-bottom:24px">
            <div class="summary-title">Leitura CRM</div>
            <div class="summary-list">
                <div class="summary-item">${principalCanal ? `${principalCanal.canal_captacao} lidera a aquisição com ${fmt.currency(principalCanal.receita)} em receita.` : 'Nenhum canal de aquisição disponível.'}</div>
                <div class="summary-item">${principalPraca ? `${principalPraca.cidade} / ${principalPraca.bairro} é a praça mais relevante com ${fmt.currency(principalPraca.faturamento_total)}.` : 'Nenhuma praça geográfica disponível.'}</div>
                <div class="summary-item">${fmt.number(ativos)} clientes estão marcados como ativos, contra ${fmt.number(emRisco)} em atenção ou risco.</div>
                <div class="summary-item">A base de recuperação soma ${fmt.currency(potencialRecuperacao)} e ${fmt.number(contatosComEmail)} contatos com email para acionamento.</div>
            </div>
        </div>
        <div class="table-card">
            <div class="table-header">💌 Recuperação de Vendas</div>
            <table><thead><tr><th>#</th><th>Cliente</th><th>Telefone</th><th>Email</th><th>Valor</th><th>Data</th></tr></thead><tbody>
                ${recuperacaoValida.sort((a,b) => b.valor - a.valor).slice(0,30).map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td>${r.cliente || 'Nao informado'}</td><td>${r.telefone || '-'}</td><td>${r.email || '<span class="badge badge-red">Sem email</span>'}</td><td>${fmt.currency(r.valor)}</td><td>${r.data_criacao || '-'}</td></tr>`).join('')}
            </tbody></table>
        </div>
        <div class="table-card">
            <div class="table-header">👥 Ranking RFM</div>
            <table><thead><tr><th>#</th><th>Cliente</th><th>Status</th><th>Recência (dias)</th><th>Frequência</th><th>Monetário</th></tr></thead><tbody>
                ${topClientes.map((r,i) => `<tr><td><span class="rank-num ${rankClass(i)}">${i+1}</span></td><td>${r.cliente}</td><td><span class="badge ${String(r.status_retencao).toLowerCase()==='ativo'?'badge-green':'badge-orange'}">${r.status_retencao}</span></td><td>${fmt.number(r.recencia_dias)}</td><td>${fmt.number(r.frequencia)}</td><td>${fmt.currency(r.monetario)}</td></tr>`).join('')}
            </tbody></table>
        </div>`;

    makeChart('ch-crm-retencao', { type: 'doughnut', data: { labels: statusRetencao.map(r => r.status), datasets: [{ data: statusRetencao.map(r => r.quantidade), backgroundColor: COLORS.palette, borderWidth: 2, borderColor: '#fff' }] }, options: { plugins: { legend: { position: 'bottom' } }, cutout: '58%' } });
    makeChart('ch-crm-canais', { type: 'bar', data: { labels: topCanais.map(r => r.canal_captacao), datasets: [{ data: topCanais.map(r => r.receita), backgroundColor: 'rgba(37,99,235,.82)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-crm-geo', { type: 'bar', data: { labels: topGeo.map(r => `${r.cidade} / ${r.bairro}`), datasets: [{ data: topGeo.map(r => r.faturamento_total), backgroundColor: 'rgba(22,163,74,.82)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-crm-rfm', { type: 'bar', data: { labels: topClientes.map(r => r.cliente), datasets: [{ data: topClientes.map(r => r.monetario), backgroundColor: 'rgba(124,58,237,.82)', borderRadius: 8 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } });
    makeChart('ch-crm-recuperacao', { type: 'line', data: { labels: recuperacaoMes.map(r => r.anoMes), datasets: [{ label: 'Recuperacao', data: recuperacaoMes.map(r => r.valor), borderColor: COLORS.warning, backgroundColor: 'rgba(217,119,6,.10)', fill: true, tension: .25, pointRadius: 2.5 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => fmt.currency(v) }, grid: { color: '#e2e8f0' } }, x: { grid: { display: false } } } } });
}
const sectionTitles = {
    faturamento: 'Faturamento',
    orcamentos:  'Orçamentos',
    lojas:       'Lojas',
    vendedores:  'Vendedores',
    produtos:    'Produtos',
    holtec:      'Holtec',
    marketing:   'Marketing',
    impressoes:  'Impressoes',
    operacao:    'Operacao',
    crm:         'CRM'
};

const loaders = {
    faturamento: loadFaturamento,
    orcamentos:  loadOrcamentos,
    lojas:       loadLojas,
    vendedores:  loadVendedores,
    produtos:    loadProdutosDetalhado,
    holtec:      loadHoltec,
    marketing:   loadMarketingDetalhado,
    impressoes:  loadMarketingFormularios,
    operacao:    loadOperacao,
    crm:         loadCRM
};

async function loadSection(section) {
    if (!sectionIsAccessible(section)) {
        section = USER.defaultSection || USER_SECTIONS[0] || 'orcamentos';
    }
    // Reset filtros ao trocar seção
    if (section !== currentSection) {
        filterLoja = USER.loja ? [USER.loja] : [];
        filterAno  = [];
        filterMes  = [];
        filterAbc  = [];
        filterTipoProduto = [];
        filterProdutoBusca = '';
        filterHoltecCategoria = [];
        filterHoltecGrupo = [];
        filterCampanha = [];
        filterCupom = [];
        filterRepresentante = [];
        filterTipoPedido = [];
        filterTipoPapel = [];
    }

    currentSection = section;
    updatePageTitle(section);

    // Sidebar active
    document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.toggle('active', el.dataset.section === section);
    });

    loading();
    destroyCharts();

    try {
        await loaders[section]();
    } catch (err) {
        error('Erro ao carregar dados: ' + (err.message || 'Verifique a conexão com o Google Sheets.'));
        console.error(err);
    }
}

// Click nav
document.querySelectorAll('.nav-item[data-section]').forEach(el => {
    if (!sectionIsAccessible(el.dataset.section)) {
        el.style.display = 'none';
    }
});

document.querySelectorAll('.nav-item[data-section]').forEach(el => {
    el.addEventListener('click', () => {
        loadSection(el.dataset.section);
        // Fecha menu mobile
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('overlay').classList.remove('show');
    });
});

// Mobile menu toggle
document.getElementById('menuToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
});

document.getElementById('overlay').addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
});

// Expõe para filtros inline
window.applyFilters = applyFilters;

// ============================================================
// LIMPAR CACHE (Diretoria apenas)
// ============================================================
async function clearCache() {
    const btn = document.getElementById('cache-btn');
    if (!btn) return;
    btn.disabled = true;
    btn.textContent = '⏳ Limpando...';
    try {
        const res = await fetch(BASE + '/api/clear_cache.php');
        const json = await res.json();
        if (json.success) {
            btn.textContent = '✅ Cache limpo!';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg> Atualizar dados';
                loadSection(currentSection);
            }, 1500);
        }
    } catch(e) {
        btn.disabled = false;
        btn.textContent = '❌ Erro';
        setTimeout(() => {
            btn.innerHTML = '🔄 Atualizar dados';
        }, 2000);
    }
}

// ============================================================
// EXPORTAR CSV
// ============================================================
const exportMap = {
    faturamento: 'vendas',
    orcamentos:  'formulas',
    lojas:       'vendas',
    vendedores:  'vendedores',
    produtos:    'produtos',
    holtec:      'holtec',
    impressoes:  'formularios_rotulos',
    operacao:    'funil',
    crm:         'rfm_clientes'
};

function exportarDados() {
    const tipo = currentSection === 'marketing'
        ? 'trafego_pago'
        : (exportMap[currentSection] || currentSection);
    const params = new URLSearchParams({ tipo });
    if (hasSelection(filterLoja)) params.set('loja', serializeFilterValue(filterLoja));
    if (hasSelection(filterAno))  params.set('ano',  serializeFilterValue(filterAno));
    if (hasSelection(filterMes))  params.set('mes',  serializeFilterValue(filterMes));
    if (hasSelection(filterCampanha)) params.set('campanha', serializeFilterValue(filterCampanha));
    if (hasSelection(filterCupom)) params.set('cupom', serializeFilterValue(filterCupom));
    if (hasSelection(filterHoltecCategoria)) params.set('categoria', serializeFilterValue(filterHoltecCategoria));
    if (hasSelection(filterHoltecGrupo)) params.set('grupo', serializeFilterValue(filterHoltecGrupo));
    if (hasSelection(filterRepresentante)) params.set('representante', serializeFilterValue(filterRepresentante));
    if (hasSelection(filterTipoPedido)) params.set('tipo_pedido', serializeFilterValue(filterTipoPedido));
    if (hasSelection(filterTipoPapel)) params.set('papel', serializeFilterValue(filterTipoPapel));
    window.location.href = BASE + '/api/export.php?' + params.toString();
}

// Init
loadSection(USER.defaultSection || USER_SECTIONS[0] || 'orcamentos');
</script>
</body>
</html>
<script>
// clearCache — injected after main script
async function clearCache() {
    const btn = document.getElementById('cache-btn');
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.textContent = '⏳ Aguarde...';
    btn.disabled = true;
    try {
        const res  = await fetch('<?= BASE_URL ?>/api/clear_cache.php');
        const json = await res.json();
        btn.textContent = '✅ Atualizado!';
        setTimeout(() => {
            btn.innerHTML  = orig;
            btn.disabled   = false;
            loadSection(currentSection);
        }, 1500);
    } catch(e) {
        btn.innerHTML = orig;
        btn.disabled  = false;
    }
}
</script>
