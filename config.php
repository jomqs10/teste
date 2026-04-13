<?php
// =============================================================
// CONFIGURACOES DO SISTEMA - Dashboard Holistica
// =============================================================

// Banco de dados MySQL (Hostinger)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u800341527_Dashboard');
define('DB_USER', 'u800341527_admin');
define('DB_PASS', 'WhitzZ@10912');
define('DB_CHARSET', 'utf8mb4');

// URL base do sistema
$baseHost = trim((string) ($_SERVER['HTTP_HOST'] ?? 'grupoholistica.com.br'));
$baseHostName = strtolower(preg_replace('/:\d+$/', '', $baseHost));
$baseScheme = in_array($baseHostName, ['localhost', '127.0.0.1', '::1'], true) ? 'http' : 'https';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $baseScheme = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
} elseif (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
    $baseScheme = 'https';
}
if ($baseHost === '') {
    $baseHost = 'grupoholistica.com.br';
}
$basePath = '/dashboard';
if (in_array($baseHostName, ['localhost', '127.0.0.1', '::1'], true)) {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $parts = array_values(array_filter(explode('/', $scriptName), static fn($part) => $part !== ''));
    $firstSegment = $parts[0] ?? '';
    $basePath = ($firstSegment !== '' && !str_ends_with($firstSegment, '.php')) ? '/' . $firstSegment : '';
}
define('BASE_URL', $baseScheme . '://' . $baseHost . $basePath);

// Dominios permitidos para cadastro
define('ALLOWED_DOMAINS', [
    'farmaciaholistica.com.br',
    'grupoholistica.com.br',
    'holtecpharma.com.br'
]);

// Setores disponiveis
define('SETORES', [
    'Diretoria',
    'Marketing',
    'Coordenador de Loja',
    'Gerente'
]);

// Lojas disponiveis
define('LOJAS', [
    'BOQUEIRAO',
    'CAICARA',
    'GONZAGA',
    'APARECIDA',
    'EPITACIO',
    'COMERCIAL',
    'Farmacia Holistica'
]);

// Secoes do dashboard
define('DASHBOARD_SECTIONS', [
    'faturamento' => 'Faturamento',
    'orcamentos'  => 'Orcamentos',
    'lojas'       => 'Lojas',
    'vendedores'  => 'Vendedores',
    'produtos'    => 'Produtos',
    'holtec'      => 'Holtec',
    'marketing'   => 'Marketing',
    'impressoes'  => 'Impressoes',
    'operacao'    => 'Operacao',
    'crm'         => 'CRM',
]);

// Setores com acesso total
define('ACESSO_TOTAL', ['Diretoria', 'Marketing']);

// Senha inicial padrao para contas criadas pelo sistema
define('DEFAULT_INITIAL_PASSWORD', '1234');

// Notificacao de aprovacao de cadastro
define('APPROVAL_NOTIFICATION_EMAIL', 'marketing@farmaciaholistica.com.br');
define('MAIL_FROM_NAME', 'Dashboard Holistica');
define('MAIL_FROM_EMAIL', APPROVAL_NOTIFICATION_EMAIL);

// Google Sheets - base simplificada
// Mantemos o id editavel para referencia e usamos o id publicado para
// consumir CSV publico com os gids corretos.
define('SHEET_ID', '1PSTVUuHx92ijfXvYghEBVxmV5jlVjwmi');
define('SHEET_PUBLIC_ID', '2PACX-1vS5UxwFawdpHn6jbaTEft3tM1MPtSwijVrC_aSxUx1sxzQScTWRkeX6FXfPmvVDhg');

// GIDs das abas da base simplificada
define('GID_KPIS',         '1892623258'); // 00_Como_Atualizar
define('GID_VENDAS',       '1563800877');
define('GID_PRODUTOS',     '1493372548');
define('GID_FORMULAS',     '1807888819');
define('GID_TRAFEGO',      '1994205272');
define('GID_VENDEDORES',   '517956778');
define('GID_FUNIL',        '1769824015');
define('GID_UX_GERAL',     '805502582');
define('GID_UX_STATUS',    '805502582');
define('GID_RFM',          '446089819');
define('GID_GEOGRAFIA',    '147263748');
define('GID_PRESCRITORES', '1386884001');
define('GID_CANAIS',       '1320229680');
define('GID_RECUPERACAO',  '147745399');
define('GID_CUPONS_USADOS','2143478810');
define('GID_HOLTEC',       '944133993');

// Sessao
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// Versao do sistema
define('SYSTEM_VERSION', '1.0.0');

// Conexao PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Erro de conexao com o banco de dados.']));
        }
    }
    return $pdo;
}

// Verificar dominio do email
function emailDomainAllowed(string $email): bool {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return false;
    return in_array(strtolower($parts[1]), ALLOWED_DOMAINS);
}

// Verificar se usuario tem acesso total
function hasFullAccess(string $setor): bool {
    return in_array($setor, ACESSO_TOTAL);
}

function mailHeaderSafe(string $value): string {
    return trim(str_replace(["\r", "\n"], '', $value));
}

function sendApprovalRequestEmail(array $data): bool {
    if (!function_exists('mail')) {
        return false;
    }

    $nome = trim((string) ($data['nome'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $setor = trim((string) ($data['setor'] ?? ''));
    $loja = trim((string) ($data['loja'] ?? ''));
    $dataHora = date('d/m/Y H:i:s');
    $adminUrl = BASE_URL . '/admin.php';

    $subject = 'Nova solicitacao de acesso - Dashboard Holistica';
    $body = implode("\n", [
        'Uma nova solicitacao de acesso foi criada no Dashboard Holistica.',
        '',
        'Nome: ' . ($nome ?: 'Nao informado'),
        'Email: ' . ($email ?: 'Nao informado'),
        'Setor: ' . ($setor ?: 'Nao informado'),
        'Loja: ' . ($loja ?: 'Nao se aplica'),
        'Data: ' . $dataHora,
        '',
        'Para aprovar o acesso, entre em:',
        $adminUrl,
    ]);

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . mailHeaderSafe(MAIL_FROM_NAME) . ' <' . mailHeaderSafe(MAIL_FROM_EMAIL) . '>',
        'Sender: ' . mailHeaderSafe(MAIL_FROM_EMAIL),
    ];

    if ($email !== '') {
        $headers[] = 'Reply-To: ' . mailHeaderSafe($email);
    }

    $to = mailHeaderSafe(APPROVAL_NOTIFICATION_EMAIL);
    $headersString = implode("\r\n", $headers);
    $envelopeSender = '-f' . mailHeaderSafe(MAIL_FROM_EMAIL);

    if (DIRECTORY_SEPARATOR === '\\') {
        return @mail($to, $subject, $body, $headersString);
    }

    return @mail($to, $subject, $body, $headersString, $envelopeSender);
}

// URL do CSV de cada aba
function sheetUrl(string $gid): string {
    if (defined('SHEET_PUBLIC_ID') && SHEET_PUBLIC_ID !== '') {
        return 'https://docs.google.com/spreadsheets/d/e/' . SHEET_PUBLIC_ID . '/pub?gid=' . urlencode($gid) . '&single=true&output=csv';
    }
    return 'https://docs.google.com/spreadsheets/d/' . SHEET_ID . '/export?format=csv&gid=' . urlencode($gid);
}
