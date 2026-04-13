<?php
require_once 'auth.php';

$pdo = getDB();
$usuarios = $pdo->query("SELECT nome, login, setor, is_admin, must_change_password FROM usuarios ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instalacao - Dashboard Holistica</title>
<style>
body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 40px 20px; color: #111827; }
.box { max-width: 820px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 12px 30px rgba(15, 23, 42, .08); }
h1 { margin-bottom: 8px; }
p { color: #6b7280; line-height: 1.6; }
ul { margin: 20px 0; padding-left: 18px; }
li { margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
th { font-size: 12px; text-transform: uppercase; color: #6b7280; letter-spacing: .08em; }
.btn { display: inline-block; margin-top: 22px; padding: 11px 18px; border-radius: 10px; background: #892042; color: #fff; text-decoration: none; font-weight: 700; }
</style>
</head>
<body>
<div class="box">
    <h1>Instalacao concluida</h1>
    <p>O esquema atual de usuarios, logins e permissoes ja foi garantido automaticamente. As contas iniciais foram criadas com senha padrao <strong><?= htmlspecialchars(DEFAULT_INITIAL_PASSWORD) ?></strong> e troca obrigatoria no primeiro acesso.</p>

    <ul>
        <li>Login simplificado por usuario.</li>
        <li>Cadastro publico desativado.</li>
        <li>Primeiro acesso obrigando nova senha.</li>
        <li>Painel de admin para criar contas e definir direitos.</li>
    </ul>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Login</th>
                <th>Setor</th>
                <th>Admin</th>
                <th>Primeiro acesso</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $usuario['nome']) ?></td>
                    <td><?= htmlspecialchars((string) $usuario['login']) ?></td>
                    <td><?= htmlspecialchars((string) $usuario['setor']) ?></td>
                    <td><?= !empty($usuario['is_admin']) ? 'Sim' : 'Nao' ?></td>
                    <td><?= !empty($usuario['must_change_password']) ? 'Pendente' : 'Concluido' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a class="btn" href="login.php">Ir para o login</a>
</div>
</body>
</html>
