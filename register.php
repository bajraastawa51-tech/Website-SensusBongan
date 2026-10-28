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
    <title><?= h(__('auth.register_title')) ?> - <?= h(__('app.name')) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo-bongan.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css?v=<?= asset_version('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-page auth-login-page auth-register-page">
<main class="register-layout">
    <!-- OVERLAY SEBELAH KIRI DENGAN SLIDESHOW FOTO DESA -->
    <section class="login-identity" aria-label="Identitas Desa Bongan">
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
            <p><?= h(__('auth.register_identity_sub')) ?></p>
            <span class="login-identity-rule"></span>
            <small><?= h(__('auth.brand_description')) ?></small>
            
            <div class="login-feature-pills mt-4">
                <span class="badge bg-white bg-opacity-10 text-white border border-light border-opacity-25 py-2 px-3 me-2 mb-2"><i class="bi bi-check2-circle text-warning me-1"></i> <?= h(__('auth.pill_realtime')) ?></span>
                <span class="badge bg-white bg-opacity-10 text-white border border-light border-opacity-25 py-2 px-3 me-2 mb-2"><i class="bi bi-shield-lock text-warning me-1"></i> <?= h(__('auth.pill_valid_nik')) ?></span>
                <span class="badge bg-white bg-opacity-10 text-white border border-light border-opacity-25 py-2 px-3 me-2 mb-2"><i class="bi bi-person-check text-warning me-1"></i> <?= h(__('auth.pill_self_service')) ?></span>
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
    
    <!-- FORM REGISTER SEBELAH KANAN -->
    <section class="auth-card register-form-panel" aria-label="Form Pendaftaran">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="auth-kicker"><span class="status-dot"></span> <?= h(__('auth.portal_kicker')) ?></div>
            <div class="d-flex align-items-center gap-2">
                <div class="auth-nav-pill-group">
                    <a href="index.php?page=login" class="auth-nav-pill"><?= h(__('auth.tab_signin')) ?></a>
                    <span class="auth-nav-pill active"><?= h(__('auth.tab_register')) ?></span>
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
        
        <h2><?= h(__('auth.register_title')) ?></h2>
        <p class="text-muted mb-3" style="font-size: 13px;"><?= h(__('auth.register_subtitle')) ?></p>

        <!-- PENGISI KODE CONTOH CEPAT -->
        <div class="p-2 mb-3 rounded-3" style="background:#f0fdf4; border:1px dashed #86efac;">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-success fw-bold" style="font-size: 11px;">
                    <i class="bi bi-info-circle me-1"></i><?= h(__('auth.test_question')) ?>
                </small>
                <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 10.5px;" onclick="fillDemoRegister()">
                    <?= h(__('auth.test_button')) ?>
                </button>
            </div>
        </div>
        
        <form method="post" action="index.php?page=register">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="register_resident">
            <input type="hidden" name="return_page" value="register">
            
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="form-label" for="nik"><?= h(__('auth.nik_label')) ?> <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-person-vcard"></i></span>
                        <input class="form-control" id="nik" name="nik" pattern="DEMO[0-9]{12}" maxlength="16" placeholder="DEMO000000000001" autocomplete="off" required>
                    </div>
                    <small class="form-text text-muted" style="font-size: 10.5px;"><?= h(__('auth.nik_hint')) ?></small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="household_number"><?= h(__('auth.kk_label')) ?> <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-houses"></i></span>
                        <input class="form-control" id="household_number" name="household_number" pattern="KKDEMO[0-9]{10}" maxlength="16" placeholder="KKDEMO0000000001" autocomplete="off" required>
                    </div>
                    <small class="form-text text-muted" style="font-size: 10.5px;"><?= h(__('auth.kk_hint')) ?></small>
                </div>
            </div>
            
            <div class="mb-2">
                <label class="form-label" for="username"><?= h(__('auth.username_new')) ?> <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-at"></i></span>
                    <input class="form-control" id="username" name="username" minlength="3" maxlength="80" pattern="[A-Za-z0-9._-]{3,80}" placeholder="<?= current_lang() === 'en' ? 'e.g. andi_bongan' : 'Contoh: andi_bongan' ?>" autocomplete="username" required>
                </div>
            </div>
            
            <div class="row g-2 mb-3 register-password-row">
                <div class="col-md-6">
                    <label class="form-label" for="password"><?= h(__('auth.password_new')) ?> <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input class="form-control border-start-0 border-end-0 ps-1" id="password" name="password" type="password" minlength="10" placeholder="<?= current_lang() === 'en' ? 'Min. 10 characters...' : 'Min. 10 karakter...' ?>" autocomplete="new-password" required>
                        <button class="btn btn-outline-light text-muted border border-start-0" type="button" onclick="togglePassVisibility('password', this)" aria-label="<?= current_lang() === 'en' ? 'Show password' : 'Lihat password' ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation"><?= h(__('auth.password_confirm')) ?> <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
                        <input class="form-control border-start-0 border-end-0 ps-1" id="password_confirmation" name="password_confirmation" type="password" minlength="10" placeholder="<?= current_lang() === 'en' ? 'Repeat password...' : 'Ulangi sandi...' ?>" autocomplete="new-password" required>
                        <button class="btn btn-outline-light text-muted border border-start-0" type="button" onclick="togglePassVisibility('password_confirmation', this)" aria-label="<?= current_lang() === 'en' ? 'Show password' : 'Lihat password' ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="register-terms">
                    <input type="checkbox" id="termsCheck" required checked>
                    <span><?= h(__('auth.terms_agreement')) ?></span>
                </label>
            </div>
            
            <button class="btn btn-primary w-100 py-2 auth-submit fw-semibold" type="submit">
                <span><?= h(__('auth.submit_register')) ?></span> <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
        
        <div class="text-center mt-3 pt-2 border-top">
            <a class="auth-link text-decoration-none fw-semibold" href="index.php?page=login">
                <i class="bi bi-box-arrow-in-left me-1"></i> <?= h(__('auth.have_account')) ?>
            </a>
        </div>
    </section>
</main>

<?= render_senior_assistant() ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=<?= asset_version('assets/js/app.js') ?>"></script>
<script>
function fillDemoRegister() {
    document.getElementById('nik').value = 'DEMO000000000001';
    document.getElementById('household_number').value = 'KKDEMO0000000001';
    document.getElementById('username').value = 'andi.baru';
    document.getElementById('password').value = 'Password123!';
    document.getElementById('password_confirmation').value = 'Password123!';
}

function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;
    
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
