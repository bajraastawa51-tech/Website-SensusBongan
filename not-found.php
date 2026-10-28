<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}
?>
<!doctype html>
<html lang="<?= h(current_lang()) ?>">
<head>
    <meta charset="utf-8">
    <title><?= current_lang() === 'en' ? 'Page Not Found' : 'Halaman tidak ditemukan' ?> - <?= h(__('app.name')) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo-bongan.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="card p-5 text-center shadow-sm" style="max-width: 480px; margin: 0 auto; border-radius: 16px;">
            <h1 class="display-4 fw-bold text-success mb-2">404</h1>
            <p class="text-muted mb-4"><?= current_lang() === 'en' ? 'The requested page was not found.' : 'Halaman yang diminta tidak ditemukan.' ?></p>
            <div>
                <a href="index.php" class="btn btn-success px-4"><?= h(__('common.back')) ?></a>
            </div>
        </div>
    </main>
</body>
</html>
