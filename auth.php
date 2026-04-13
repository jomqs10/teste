<?php
// =============================================================
// Session and authentication control
// =============================================================

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => dashboardBasePath(),
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$GLOBALS['auth_bootstrap_error'] = null;
try {
    bootstrapAuthSystem();
} catch (Throwable $e) {
    $GLOBALS['auth_bootstrap_error'] = $e->getMessage();
    error_log('Dashboard auth bootstrap failed: ' . $e->getMessage());
}

function dashboardBasePath(): string
{
    $path = (string) parse_url(BASE_URL, PHP_URL_PATH);
    return $path !== '' ? $path : '/';
}

function authMinPasswordLength(): int
{
    return 4;
}

function authBootstrapReady(): bool
{
    return empty($GLOBALS['auth_bootstrap_error']);
}

function authBootstrapError(): ?string
{
    $error = $GLOBALS['auth_bootstrap_error'] ?? null;
    return is_string($error) && $error !== '' ? $error : null;
}

function authSyntheticEmail(string $login): string
{
    return authNormalizeLogin($login) . '@dashboard.local';
}

function authVisibleEmail(?string $email): ?string
{
    $value = trim((string) $email);
    if ($value === '' || str_ends_with(strtolower($value), '@dashboard.local')) {
        return null;
    }

    return $value;
}

function dashboardSectionKeys(): array
{
    return array_keys(DASHBOARD_SECTIONS);
}

function dashboardSectionLabels(): array
{
    return DASHBOARD_SECTIONS;
}

function dashboardSectionLabel(string $section): string
{
    $labels = dashboardSectionLabels();
    return $labels[$section] ?? ucfirst($section);
}

function dashboardDefaultPermissionsForSetor(string $setor): array
{
    if (hasFullAccess($setor)) {
        return dashboardSectionKeys();
    }

    return ['orcamentos', 'lojas', 'vendedores', 'produtos'];
}

function dashboardSeedUsers(): array
{
    return [
        [
            'nome' => 'Admin',
            'login' => 'admin',
            'setor' => 'Diretoria',
            'loja' => null,
            'is_admin' => true,
            'permissions' => dashboardSectionKeys(),
        ],
        [
            'nome' => 'Alexandre',
            'login' => 'alexandre',
            'setor' => 'Gerente',
            'loja' => null,
            'is_admin' => false,
            'permissions' => dashboardDefaultPermissionsForSetor('Gerente'),
        ],
        [
            'nome' => 'Debora',
            'login' => 'debora',
            'setor' => 'Gerente',
            'loja' => null,
            'is_admin' => false,
            'permissions' => dashboardDefaultPermissionsForSetor('Gerente'),
        ],
        [
            'nome' => 'Marketing',
            'login' => 'marketing',
            'setor' => 'Marketing',
            'loja' => null,
            'is_admin' => false,
            'permissions' => dashboardSectionKeys(),
        ],
        [
            'nome' => 'Katbe',
            'login' => 'katbe',
            'setor' => 'Gerente',
            'loja' => null,
            'is_admin' => false,
            'permissions' => dashboardDefaultPermissionsForSetor('Gerente'),
        ],
    ];
}

function bootstrapAuthSystem(): void
{
    static $booted = false;
    if ($booted) {
        return;
    }

    $booted = true;
    $pdo = getDB();

    authEnsureTables($pdo);
    authEnsureColumn($pdo, 'usuarios', 'login', "ALTER TABLE usuarios ADD COLUMN login VARCHAR(80) NULL AFTER nome");
    authEnsureColumn($pdo, 'usuarios', 'permissoes', "ALTER TABLE usuarios ADD COLUMN permissoes TEXT NULL AFTER loja");
    authEnsureColumn($pdo, 'usuarios', 'is_admin', "ALTER TABLE usuarios ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER permissoes");
    authEnsureColumn($pdo, 'usuarios', 'must_change_password', "ALTER TABLE usuarios ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin");

    try {
        $pdo->exec("ALTER TABLE usuarios MODIFY email VARCHAR(200) NULL");
    } catch (Throwable $e) {
        // Mantem compatibilidade caso o host nao permita alterar a coluna.
    }

    authSyncExistingUsers($pdo);
    authApplyDashboardMigrations($pdo);
    authEnsureUniqueIndex($pdo, 'usuarios', 'unique_login', 'login');
    authSeedDefaultUsers($pdo);
}

function authEnsureTables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            login VARCHAR(80) DEFAULT NULL,
            email VARCHAR(200) DEFAULT NULL,
            senha VARCHAR(255) NOT NULL,
            setor VARCHAR(100) NOT NULL DEFAULT 'Gerente',
            loja VARCHAR(100) DEFAULT NULL,
            permissoes TEXT DEFAULT NULL,
            is_admin TINYINT(1) NOT NULL DEFAULT 0,
            must_change_password TINYINT(1) NOT NULL DEFAULT 0,
            ativo TINYINT(1) DEFAULT 1,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            ultimo_login DATETIME DEFAULT NULL,
            UNIQUE KEY unique_login (login),
            UNIQUE KEY unique_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS log_acessos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            ip VARCHAR(45),
            user_agent VARCHAR(300),
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sistema_migracoes (
            chave VARCHAR(100) PRIMARY KEY,
            aplicado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function authEnsureColumn(PDO $pdo, string $table, string $column, string $sql): void
{
    if (authColumnExists($pdo, $table, $column)) {
        return;
    }

    $pdo->exec($sql);
}

function authColumnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

function authIndexExists(PDO $pdo, string $table, string $indexName): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
        LIMIT 1
    ");
    $stmt->execute([$table, $indexName]);
    return (bool) $stmt->fetchColumn();
}

function authEnsureUniqueIndex(PDO $pdo, string $table, string $indexName, string $column): void
{
    if (authIndexExists($pdo, $table, $indexName)) {
        return;
    }

    try {
        $pdo->exec("ALTER TABLE `$table` ADD UNIQUE KEY `$indexName` (`$column`)");
    } catch (Throwable $e) {
        // Evita derrubar o sistema caso o host ja tenha um indice parecido.
    }
}

function authSyncExistingUsers(PDO $pdo): void
{
    $rows = $pdo->query("
        SELECT id, nome, email, login, setor, permissoes, is_admin
        FROM usuarios
        ORDER BY id ASC
    ")->fetchAll();

    $usedLogins = [];
    foreach ($rows as $row) {
        $current = authNormalizeLogin((string) ($row['login'] ?? ''));
        if ($current !== '') {
            $usedLogins[$current] = (int) $row['id'];
        }
    }

    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $updates = [];
        $params = [];

        $desiredLogin = authNormalizeLogin((string) ($row['login'] ?? ''));
        if ($desiredLogin === '') {
            $base = (string) ($row['nome'] ?: $row['email'] ?: ('usuario' . $id));
            $desiredLogin = authUniqueLoginFromValue($base, $usedLogins, $id);
        } elseif (isset($usedLogins[$desiredLogin]) && $usedLogins[$desiredLogin] !== $id) {
            $desiredLogin = authUniqueLoginFromValue($desiredLogin, $usedLogins, $id);
        }

        if (strtolower((string) ($row['login'] ?? '')) !== $desiredLogin) {
            $updates[] = 'login = ?';
            $params[] = $desiredLogin;
        }
        $usedLogins[$desiredLogin] = $id;

        if (trim((string) ($row['permissoes'] ?? '')) === '') {
            $updates[] = 'permissoes = ?';
            $params[] = json_encode(
                dashboardDefaultPermissionsForSetor((string) ($row['setor'] ?? '')),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        if (($row['setor'] ?? '') === 'Diretoria' && empty($row['is_admin'])) {
            $updates[] = 'is_admin = 1';
        }

        if ($updates !== []) {
            $params[] = $id;
            $stmt = $pdo->prepare('UPDATE usuarios SET ' . implode(', ', $updates) . ' WHERE id = ?');
            $stmt->execute($params);
        }
    }
}

function authApplyDashboardMigrations(PDO $pdo): void
{
    $migrationKey = '20260410_add_holtec_permission';

    try {
        $stmt = $pdo->prepare('SELECT 1 FROM sistema_migracoes WHERE chave = ? LIMIT 1');
        $stmt->execute([$migrationKey]);
        if ($stmt->fetchColumn()) {
            return;
        }

        $legacyFullAccess = [
            'faturamento',
            'orcamentos',
            'lojas',
            'vendedores',
            'produtos',
            'marketing',
            'impressoes',
            'operacao',
            'crm',
        ];

        $rows = $pdo->query('SELECT id, setor, permissoes FROM usuarios ORDER BY id ASC')->fetchAll();
        $update = $pdo->prepare('UPDATE usuarios SET permissoes = ? WHERE id = ?');

        foreach ($rows as $row) {
            $raw = trim((string) ($row['permissoes'] ?? ''));
            $decoded = $raw !== '' ? json_decode($raw, true) : null;
            $permissions = is_array($decoded)
                ? $decoded
                : dashboardDefaultPermissionsForSetor((string) ($row['setor'] ?? ''));
            $normalized = dashboardNormalizePermissions($permissions);

            if (!in_array('holtec', $normalized, true) && array_diff($legacyFullAccess, $normalized) === []) {
                $normalized[] = 'holtec';
                $update->execute([
                    json_encode(dashboardNormalizePermissions($normalized), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    (int) $row['id'],
                ]);
            }
        }

        $mark = $pdo->prepare('INSERT INTO sistema_migracoes (chave) VALUES (?)');
        $mark->execute([$migrationKey]);
    } catch (Throwable $e) {
        error_log('Dashboard migration failed: ' . $migrationKey . ' - ' . $e->getMessage());
    }
}

function authSeedDefaultUsers(PDO $pdo): void
{
    $select = $pdo->prepare("SELECT id FROM usuarios WHERE login = ?");
    $insert = $pdo->prepare("
        INSERT INTO usuarios (nome, login, email, senha, setor, loja, permissoes, is_admin, must_change_password, ativo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)
    ");

    foreach (dashboardSeedUsers() as $seed) {
        $login = authNormalizeLogin((string) $seed['login']);
        $select->execute([$login]);
        if ($select->fetch()) {
            continue;
        }

        $insert->execute([
            $seed['nome'],
            $login,
            authSyntheticEmail($login),
            password_hash(DEFAULT_INITIAL_PASSWORD, PASSWORD_DEFAULT),
            $seed['setor'],
            $seed['loja'],
            json_encode($seed['permissions'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            !empty($seed['is_admin']) ? 1 : 0,
        ]);
    }
}

function authNormalizeLogin(string $value): string
{
    $value = function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && $ascii !== '') {
            $value = $ascii;
        }
    }

    $value = preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    return substr($value, 0, 50);
}

function authUniqueLoginFromValue(string $value, array $usedLogins, int $ignoreId = 0): string
{
    $base = authNormalizeLogin($value);
    if ($base === '') {
        $base = 'usuario';
    }

    $candidate = $base;
    $counter = 2;
    while (isset($usedLogins[$candidate]) && $usedLogins[$candidate] !== $ignoreId) {
        $candidate = $base . $counter;
        $counter++;
    }

    return $candidate;
}

function authFindUserById(int $id): ?array
{
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function authFindUserByLogin(string $login): ?array
{
    $key = authNormalizeLogin($login);
    if ($key === '') {
        return null;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ?");
    $stmt->execute([$key]);
    $user = $stmt->fetch();

    if ($user) {
        return $user;
    }

    if (filter_var(trim($login), FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) = ?");
        $stmt->execute([strtolower(trim($login))]);
        $user = $stmt->fetch();
        if ($user) {
            return $user;
        }
    }

    return null;
}

function authPermissionsFromRow(array $row): array
{
    $raw = trim((string) ($row['permissoes'] ?? ''));
    $decoded = $raw !== '' ? json_decode($raw, true) : null;
    $permissions = is_array($decoded)
        ? $decoded
        : dashboardDefaultPermissionsForSetor((string) ($row['setor'] ?? ''));

    return dashboardNormalizePermissions($permissions);
}

function dashboardNormalizePermissions(array $permissions): array
{
    $allowed = array_flip(dashboardSectionKeys());
    $normalized = [];

    foreach ($permissions as $permission) {
        $key = strtolower(trim((string) $permission));
        if ($key !== '' && isset($allowed[$key])) {
            $normalized[$key] = true;
        }
    }

    return array_keys($normalized);
}

function authSessionUserFromRow(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'nome' => (string) ($row['nome'] ?? ''),
        'login' => (string) ($row['login'] ?? ''),
        'email' => authVisibleEmail($row['email'] ?? null),
        'setor' => (string) ($row['setor'] ?? ''),
        'loja' => $row['loja'] !== null ? (string) $row['loja'] : null,
        'permissions' => authPermissionsFromRow($row),
        'is_admin' => !empty($row['is_admin']) || (($row['setor'] ?? '') === 'Diretoria'),
        'must_change_password' => !empty($row['must_change_password']),
        'ativo' => !empty($row['ativo']),
    ];
}

function authStoreSessionUser(array $row): void
{
    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['user'] = authSessionUserFromRow($row);
}

function authDestroySession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function requireLogin(): void
{
    if (!authBootstrapReady()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    if (!empty($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        authDestroySession();
        header('Location: ' . BASE_URL . '/login.php?timeout=1');
        exit;
    }

    $user = currentUser();
    if ($user === null) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $allowedDuringPasswordChange = ['login.php', 'primeiro-acesso.php', 'logout.php'];
    if (!empty($user['must_change_password']) && !in_array($script, $allowedDuringPasswordChange, true)) {
        header('Location: ' . BASE_URL . '/primeiro-acesso.php');
        exit;
    }
}

function currentUser(): ?array
{
    if (!authBootstrapReady()) {
        return null;
    }

    $sessionUser = $_SESSION['user'] ?? null;
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0 || !is_array($sessionUser)) {
        return null;
    }

    if (!empty($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        authDestroySession();
        return null;
    }

    $fresh = authFindUserById($userId);
    if ($fresh === null || empty($fresh['ativo'])) {
        authDestroySession();
        return null;
    }

    authStoreSessionUser($fresh);
    return $_SESSION['user'];
}

function refreshCurrentUser(): ?array
{
    return currentUser();
}

function userAllowedSections(?array $user = null): array
{
    $user = $user ?? currentUser();
    if (!$user) {
        return [];
    }

    return dashboardNormalizePermissions((array) ($user['permissions'] ?? []));
}

function userCanAccessSection(string $section, ?array $user = null): bool
{
    return in_array($section, userAllowedSections($user), true);
}

function userCanAccessAnySection(array $sections, ?array $user = null): bool
{
    foreach ($sections as $section) {
        if (userCanAccessSection((string) $section, $user)) {
            return true;
        }
    }

    return false;
}

function userDefaultSection(?array $user = null): string
{
    $allowed = userAllowedSections($user);
    $priority = ['faturamento', 'holtec', 'marketing', 'impressoes', 'operacao', 'crm', 'orcamentos', 'lojas', 'vendedores', 'produtos'];

    foreach ($priority as $section) {
        if (in_array($section, $allowed, true)) {
            return $section;
        }
    }

    return 'orcamentos';
}

function userCanManageUsers(?array $user = null): bool
{
    $user = $user ?? currentUser();
    return $user ? !empty($user['is_admin']) : false;
}

function userCanSeeAllLojas(?array $user = null): bool
{
    $user = $user ?? currentUser();
    if (!$user) {
        return false;
    }

    return trim((string) ($user['loja'] ?? '')) === '';
}

function userHasFullAccess(): bool
{
    return userCanAccessAnySection(['faturamento', 'marketing', 'operacao', 'crm']);
}

function userLoja(): ?string
{
    $user = currentUser();
    $loja = $user['loja'] ?? null;
    return $loja !== null && $loja !== '' ? $loja : null;
}

function doLogin(string $login, string $senha): array
{
    if (!authBootstrapReady()) {
        return ['success' => false, 'message' => 'O sistema nao conseguiu preparar o login. Atualize os arquivos do dashboard e tente novamente.'];
    }

    $user = authFindUserByLogin($login);
    if (!$user || !password_verify($senha, (string) ($user['senha'] ?? ''))) {
        return ['success' => false, 'message' => 'Login ou senha invalidos.'];
    }

    if (empty($user['ativo'])) {
        return ['success' => false, 'message' => 'Esta conta esta desativada. Fale com o Admin.'];
    }

    $pdo = getDB();
    $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([(int) $user['id']]);
    $pdo->prepare("INSERT INTO log_acessos (usuario_id, ip, user_agent) VALUES (?,?,?)")
        ->execute([(int) $user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

    $fresh = authFindUserById((int) $user['id']) ?? $user;
    session_regenerate_id(true);
    authStoreSessionUser($fresh);

    return ['success' => true, 'user' => $_SESSION['user']];
}

function doLogout(): void
{
    authDestroySession();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

function updateUserPassword(int $userId, string $newPassword, bool $mustChangePassword = false): void
{
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo = getDB();
    $pdo->prepare("UPDATE usuarios SET senha = ?, must_change_password = ? WHERE id = ?")
        ->execute([$hash, $mustChangePassword ? 1 : 0, $userId]);

    if ((int) ($_SESSION['user_id'] ?? 0) === $userId) {
        refreshCurrentUser();
    }
}

function completeCurrentUserFirstAccess(string $newPassword): void
{
    $user = currentUser();
    if (!$user) {
        return;
    }

    updateUserPassword((int) $user['id'], $newPassword, false);
}
