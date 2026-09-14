<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$paths = app_paths();
$erro = null;
$usuarioInformado = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $usuarioInformado = trim((string) ($_POST['usuario'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($usuarioInformado === '' || $senha === '') {
        $erro = 'Informe usuário e senha.';
    } elseif ($usuarioInformado === 'admin' && $senha === 'admin1234') {
        header('Location: ' . $paths['admin_url'] . 'index.php');
        exit;
    } else {
        $erro = 'Usuário ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($paths['assets_url']) ?>css/shared.css?v=2" rel="stylesheet">
    <link href="<?= htmlspecialchars($paths['assets_url']) ?>css/admin.css?v=3" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="card login-card border-0 shadow-lg w-100">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="bi bi-gear-wide-connected fs-1 text-primary"></i>
                    <h1 class="h4 fw-bold mt-2 mb-1">Área administrativa</h1>
                    <p class="text-muted mb-0">Entre para acessar o painel</p>
                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="post" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuário</label>
                        <input type="text" class="form-control" id="usuario" name="usuario"
                               value="<?= htmlspecialchars($usuarioInformado) ?>"
                               autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha"
                               autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        Entrar
                    </button>
                </form>

                <p class="text-center mt-3 mb-0">
                    <a href="<?= htmlspecialchars($paths['url']) ?>" class="small text-decoration-none">
                        Voltar ao site público
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
