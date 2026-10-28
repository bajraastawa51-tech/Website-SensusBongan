<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$setupAvailable = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
?>
<!doctype html>
<html lang="<?= h(current_lang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#143b35">
    <title><?= h(__('auth.login_title')) ?> - <?= h(__('app.name')) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo-bongan.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css?v=<?= asset_version('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-page auth-login-page">
<main class="login-layout">
    <section class="login-identity" aria-label="Identitas Desa Bongan">
        <!-- FOTO SLIDESHOW OVERLAY -->
        <div class="identity-slideshow" aria-hidden="true">
            <div class="identity-slide active" style="background-image: url('assets/images/sawah.jpg');"></div>
            <div class="identity-slide" style="background-image: url('assets/images/mesuryak.jpg');"></div>
            <div class="identity-slide" style="background-image: url('assets/images/kantor-bongan.png');"></div>
            <div class="identity-overlay-mask"></div>
        </div>

        <div class="login-identity-content">
            <img class="login-logo" src="assets/images/logo-bongan.png" alt="Lambang Desa Bongan">
            <span class="login-identity-kicker"><i class="bi bi-shield-check me-1"></i> <?= h(__('app.agency')) ?></span>
            <h1><?= h(__('auth.brand_heading')) ?></h1>
            <p><?= h(__('auth.brand_subheading')) ?></p>
            <span class="login-identity-rule"></span>
            <small><?= h(__('auth.brand_description')) ?></small>
            
            <div class="login-feature-pills mt-4">
                <span class="badge bg-white bg-opacity-10 text-white border border-light border-opacity-25 py-2 px-3 me-2 mb-2"><i class="bi bi-check2-circle text-warning me-1"></i> <?= h(__('auth.pill_realtime')) ?></span>
                <span class="badge bg-white bg-opacity-10 text-white border border-light border-opacity-25 py-2 px-3 me-2 mb-2"><i class="bi bi-shield-lock text-warning me-1"></i> <?= h(__('auth.pill_valid_nik')) ?></span>
                <span class="badge bg-white bg-opacity-10 text-white border border-light border-opacity-25 py-2 px-3 me-2 mb-2"><i class="bi bi-printer text-warning me-1"></i> <?= h(__('auth.pill_monthly')) ?></span>
            </div>

            <!-- KONTROL & INDIKATOR SLIDESHOW FOTO -->
            <div class="identity-slide-controls">
                <div class="identity-slide-caption">
                    <i class="bi bi-camera-fill me-1 text-warning"></i>
                    <span id="slideCaption"><?= current_lang() === 'en' ? 'Bongan Agrarian Landscape' : 'Lanskap Agraris Desa Bongan' ?></span>
                </div>
                <div class="identity-slide-dots">
                    <button type="button" class="slide-dot active" aria-label="Slide 1" onclick="switchIdentitySlide(0)"></button>
                    <button type="button" class="slide-dot" aria-label="Slide 2" onclick="switchIdentitySlide(1)"></button>
                    <button type="button" class="slide-dot" aria-label="Slide 3" onclick="switchIdentitySlide(2)"></button>
                </div>
            </div>
        </div>
    </section>
    
    <section class="auth-card login-form-panel" aria-label="Form masuk">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="auth-kicker"><span class="status-dot"></span> <?= h(__('auth.portal_kicker')) ?></div>
            <div class="d-flex align-items-center gap-2">
                <div class="auth-nav-pill-group">
                    <span class="auth-nav-pill active"><?= h(__('auth.tab_signin')) ?></span>
                    <a href="index.php?page=register" class="auth-nav-pill"><?= h(__('auth.tab_register')) ?></a>
                </div>
                <?= render_lang_switcher() ?>
            </div>
        </div>
        
        <?php if (($message = take_flash()) !== null): ?>
            <div class="alert alert-<?= h($message['type']) ?> alert-dismissible fade show" role="alert">
                <i class="bi <?= $message['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
                <?= h($message['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>
        
        <h2><?= h(__('auth.welcome_title')) ?></h2>
        <p class="text-muted mb-4"><?= h(__('auth.login_subtitle')) ?></p>
        
        <form method="post" action="index.php?page=login" id="loginForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="return_page" value="login">
            
            <div class="mb-3">
                <label class="form-label" for="username"><?= h(__('auth.username')) ?></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input class="form-control border-start-0 ps-1" id="username" name="username" placeholder="<?= h(__('auth.username_placeholder')) ?>" autocomplete="username" required>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label" for="password"><?= h(__('auth.password')) ?></label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input class="form-control border-start-0 border-end-0 ps-1" id="password" name="password" type="password" placeholder="<?= h(__('auth.password_placeholder')) ?>" autocomplete="current-password" required>
                    <button class="btn btn-outline-light text-muted border border-start-0" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()" aria-label="<?= current_lang() === 'en' ? 'Show password' : 'Lihat password' ?>">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>
            
            <button class="btn btn-primary w-100 py-2 auth-submit fw-semibold" type="submit">
                <span><?= h(__('auth.login_btn')) ?></span> <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
        
        <div class="auth-divider"><span><?= h(__('auth.no_account')) ?></span></div>
        <a class="btn btn-outline-secondary w-100 py-2 fw-medium" href="index.php?page=register">
            <i class="bi bi-person-plus me-1"></i> <?= h(__('auth.register_nav')) ?>
        </a>
        
        <?php if ($setupAvailable): ?>
            <div class="text-center mt-3">
                <a class="auth-link text-warning" href="index.php?page=setup">
                    <i class="bi bi-tools me-1"></i> <?= h(__('auth.setup_admin')) ?>
                </a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?= render_senior_assistant() ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=<?= asset_version('assets/js/app.js') ?>"></script>
<script>
function togglePasswordVisibility() {
    const input = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
</body>
</html>
