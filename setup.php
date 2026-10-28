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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#143b35">
    <title><?= current_lang() === 'en' ? 'Create Admin' : 'Buat Admin' ?> - <?= h(__('app.name')) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo-bongan.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css?v=<?= asset_version('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-page">
<main class="auth-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="auth-brand m-0">
            <span class="brand-mark"><img src="assets/images/logo-bongan.png" alt="Lambang Desa Bongan"></span>
            <div>
                <strong><?= h(__('app.agency')) ?></strong>
                <small><?= current_lang() === 'en' ? 'Initial administrator setup' : 'Penyiapan administrator pertama' ?></small>
            </div>
        </div>
        <?= render_lang_switcher() ?>
    </div>
    <?php if (($message = take_flash()) !== null): ?>
        <div class="alert alert-<?= h($message['type']) ?>"><?= h($message['message']) ?></div>
    <?php endif; ?>
    <h1><?= current_lang() === 'en' ? 'Create Admin Account' : 'Buat akun admin' ?></h1>
    <p class="text-muted mb-4"><?= current_lang() === 'en' ? 'This account is granted full privileges. Please use a strong password.' : 'Akun ini memperoleh akses penuh. Gunakan password yang kuat.' ?></p>
    <form method="post" action="index.php?page=setup">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="setup_admin">
        <input type="hidden" name="return_page" value="setup">
        <label class="form-label" for="full_name"><?= h(__('residents.lbl_name')) ?></label>
        <input class="form-control mb-3" id="full_name" name="full_name" required>
        <label class="form-label" for="username"><?= h(__('auth.username')) ?></label>
        <input class="form-control mb-3" id="username" name="username" required>
        <label class="form-label" for="password"><?= current_lang() === 'en' ? 'Password (minimum 10 characters)' : 'Password (minimal 10 karakter)' ?></label>
        <input class="form-control mb-4" id="password" name="password" type="password" minlength="10" required>
        <button class="btn btn-primary w-100 py-2" type="submit"><?= current_lang() === 'en' ? 'Create Admin Account' : 'Buat akun admin' ?></button>
    </form>
</main>
</body>
</html>
