<?php
declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/lang.php';

final class AppException extends RuntimeException
{
}

function current_lang(): string
{
    $lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'id';
    return in_array($lang, ['id', 'en'], true) ? $lang : 'id';
}

function __(string $key, array $replace = []): string
{
    global $i18n;
    $lang = current_lang();
    $text = $i18n[$lang][$key] ?? $i18n['id'][$key] ?? $key;
    if (!empty($replace)) {
        foreach ($replace as $placeholder => $value) {
            $text = str_replace(':' . $placeholder, (string) $value, $text);
        }
    }
    return $text;
}

function render_lang_switcher(string $extraClass = ''): string
{
    $current = current_lang();
    $query = $_GET;
    unset($query['lang']);
    
    $queryId = $query;
    $queryId['lang'] = 'id';
    $linkId = 'index.php?' . http_build_query($queryId);
    
    $queryEn = $query;
    $queryEn['lang'] = 'en';
    $linkEn = 'index.php?' . http_build_query($queryEn);
    
    $btnId = $current === 'id' ? 'btn-success fw-bold' : 'btn-outline-secondary';
    $btnEn = $current === 'en' ? 'btn-success fw-bold' : 'btn-outline-secondary';
    
    return '<div class="btn-group btn-group-sm lang-switcher ' . h($extraClass) . '" role="group" aria-label="Language">
        <a href="' . h($linkId) . '" class="btn ' . $btnId . '" title="Bahasa Indonesia">ID</a>
        <a href="' . h($linkEn) . '" class="btn ' . $btnEn . '" title="English">EN</a>
    </div>';
}

function render_senior_assistant(): string
{
    ob_start();
    ?>
    <!-- WIDGET BANTUAN LANSIA & RAMAH WARGA (ACCESSIBILITY ASSISTANT) -->
    <aside class="senior-assist-dock" id="seniorAssistDock" aria-label="<?= h(__('senior.widget_title')) ?>">
        <!-- Floating FAB Trigger saat bar diminimize -->
        <button type="button" class="senior-fab-trigger shadow-lg" id="seniorFabTrigger" onclick="toggleSeniorDock(false)" title="<?= h(__('senior.widget_title')) ?>" aria-label="<?= h(__('senior.open_fab_aria')) ?>">
            <i class="bi bi-eyeglasses"></i>
            <span class="senior-fab-badge"><i class="bi bi-volume-up-fill"></i></span>
        </button>

        <!-- Dock Kontrol Rapi & Ergonomis -->
        <div class="senior-assist-bar shadow-lg" id="seniorAssistBar">
            <button type="button" class="senior-btn senior-btn-mode" id="toggleSeniorModeBtn" onclick="toggleSeniorMode()" title="<?= h(__('senior.mode_toggle')) ?>">
                <i class="bi bi-eyeglasses"></i>
                <span class="senior-btn-text" id="seniorModeText"><?= h(__('senior.btn_mode')) ?></span>
            </button>
            <button type="button" class="senior-btn senior-btn-speech" id="speakGuideBtn" onclick="speakPageGuide()" title="<?= h(__('senior.listen_page')) ?>">
                <i class="bi bi-volume-up-fill" id="speakIcon"></i>
                <span class="senior-btn-text" id="speakText"><?= h(__('senior.btn_listen')) ?></span>
            </button>
            <button type="button" class="senior-btn senior-btn-help" data-bs-toggle="modal" data-bs-target="#seniorHelpModal" title="<?= h(__('senior.help_btn')) ?>">
                <i class="bi bi-telephone-fill"></i>
                <span class="senior-btn-text"><?= h(__('senior.btn_help')) ?></span>
            </button>
            <button type="button" class="senior-btn-close" onclick="toggleSeniorDock(true)" title="<?= h(__('senior.btn_minimize')) ?>" aria-label="<?= h(__('senior.close_bar_aria')) ?>">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </aside>

    <!-- MODAL PANDUAN & BANTUAN PETUGAS DESA -->
    <div class="modal fade" id="seniorHelpModal" tabindex="-1" aria-labelledby="seniorHelpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content senior-modal-content border-0">
                <div class="modal-header senior-modal-header border-0">
                    <div class="d-flex align-items-center gap-3">
                        <span class="senior-modal-icon"><img src="assets/images/icon-bantuan.png?v=<?= asset_version('assets/images/icon-bantuan.png') ?>" alt="Ikon Pusat Bantuan Desa Bongan"></span>
                        <div>
                            <h5 class="modal-title senior-modal-title mb-0" id="seniorHelpModalLabel"><?= h(__('senior.modal_title')) ?></h5>
                            <small class="senior-modal-subtitle"><?= h(__('senior.modal_subtitle')) ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= h(__('common.close')) ?>"></button>
                </div>
                <div class="modal-body senior-modal-body">
                    <!-- Hubungi Petugas Desa -->
                    <div class="senior-help-box senior-help-box-call mb-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div style="max-width: 440px;">
                                <h6 class="fw-bold mb-1 fs-6" style="color: #0f2e1a;"><i class="bi bi-telephone-fill me-2" style="color: #16a34a;"></i><?= h(__('senior.officer_title')) ?></h6>
                                <p class="mb-0 small" style="color: #475569;"><?= h(__('senior.officer_desc')) ?></p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="tel:03617993495" class="btn senior-btn-call px-3 py-2">
                                    <i class="bi bi-telephone-fill me-2" style="color: #fde047;"></i> <?= h(__('senior.call_btn')) ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Datang ke Kantor Desa -->
                    <div class="senior-help-box senior-help-box-office mb-4">
                        <h6 class="fw-bold mb-1 fs-6" style="color: #0f2e1a;"><i class="bi bi-geo-alt-fill me-2" style="color: #d97706;"></i><?= h(__('senior.office_address')) ?></h6>
                        <p class="mb-2 small" style="color: #475569;"><?= h(__('senior.office_hours')) ?></p>
                        <span class="badge py-1 px-3 fw-semibold shadow-sm" style="background: #184a29; color: #ffffff; border: 1.5px solid var(--gold); border-radius: 999px;">
                            <i class="bi bi-check-circle-fill me-1" style="color: #fde047;"></i> <?= h(__('senior.free_service')) ?>
                        </span>
                    </div>

                    <!-- Panduan 3 Langkah -->
                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: #0f2e1a;">
                        <i class="bi bi-list-stars fs-5" style="color: #d97706;"></i><?= h(__('senior.steps_title')) ?>
                    </h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="senior-step-card senior-step-card-1 shadow-sm">
                                <span class="badge senior-step-badge-1 mb-2"><?= h(__('senior.step1')) ?></span>
                                <strong class="d-block mb-1 fs-6" style="color: #0f2e1a;"><?= h(__('senior.step1_title')) ?></strong>
                                <small class="text-muted"><?= h(__('senior.step1_desc')) ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="senior-step-card senior-step-card-active shadow-sm">
                                <span class="badge senior-step-badge-2 mb-2"><?= h(__('senior.step2')) ?></span>
                                <strong class="d-block mb-1 fs-6" style="color: #78350f;"><?= h(__('senior.step2_title')) ?></strong>
                                <small style="color: #57534e;"><?= h(__('senior.step2_desc')) ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="senior-step-card senior-step-card-3 shadow-sm">
                                <span class="badge senior-step-badge-3 mb-2"><?= h(__('senior.step3')) ?></span>
                                <strong class="d-block mb-1 fs-6" style="color: #0f2e1a;"><?= h(__('senior.step3_title')) ?></strong>
                                <small class="text-muted"><?= h(__('senior.step3_desc')) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer senior-modal-footer justify-content-between">
                    <button type="button" class="btn senior-btn-listen-modal px-3 py-2" onclick="speakPageGuide()">
                        <i class="bi bi-volume-up-fill me-1" style="color: #d97706;"></i> <?= h(__('senior.listen_page')) ?>
                    </button>
                    <button type="button" class="btn senior-btn-close-modal px-4 py-2" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1" style="color: #fde047;"></i> <?= h(__('senior.close_modal')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function current_user(PDO $pdo): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId)) {
        return null;
    }

    $statement = $pdo->prepare('SELECT id, full_name, username, role, resident_id FROM users WHERE id = ? AND is_active = 1 AND approval_status = "approved"');
    $statement->execute([$userId]);
    $user = $statement->fetch() ?: null;
    if ($user === null) {
        unset($_SESSION['user_id']);
    }

    return $user;
}

function require_role(PDO $pdo, string ...$roles): array
{
    $user = current_user($pdo);
    if ($user === null) {
        header('Location: index.php?page=login');
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Anda tidak memiliki akses ke halaman ini.');
    }

    return $user;
}

function csrf_field(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf_token" value="' . h($_SESSION['csrf_token']) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';
    if (!is_string($submitted) || !is_string($known) || $known === '' || !hash_equals($known, $submitted)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid atau telah berakhir. Silakan muat ulang halaman.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($message) ? $message : null;
}

function post_value(string $name, int $maxLength = 255): string
{
    $value = $_POST[$name] ?? '';
    if (!is_string($value)) {
        throw new AppException('Format data formulir tidak valid.');
    }
    $value = trim($value);
    if (mb_strlen($value) > $maxLength) {
        throw new AppException('Salah satu isian terlalu panjang.');
    }

    return $value;
}

function required_value(string $name, string $label, int $maxLength = 255): string
{
    $value = post_value($name, $maxLength);
    if ($value === '') {
        throw new AppException($label . ' wajib diisi.');
    }

    return $value;
}

function valid_resident_code(string $code): bool
{
    return preg_match('/^DEMO\d{12}$/', $code) === 1;
}

function valid_household_code(string $code): bool
{
    return preg_match('/^KKDEMO\d{10}$/', $code) === 1;
}

function post_id(string $name): ?int
{
    $value = post_value($name, 20);
    if ($value === '') {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        throw new AppException('ID data tidak valid.');
    }

    return $id;
}

function valid_date(string $value, string $label): string
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        throw new AppException($label . ' tidak valid.');
    }

    return $value;
}

function redirect_page(string $page): void
{
    header('Location: index.php?page=' . rawurlencode($page));
    exit;
}

function role_label(string $role): string
{
    return __('role.' . $role);
}

function family_role_label(?string $role): string
{
    if ($role === null || trim($role) === '') {
        return '-';
    }
    $roleTrim = trim($role);
    $map = [
        'Kepala Keluarga' => 'family_role.head',
        'Head of Household' => 'family_role.head',
        'Istri' => 'family_role.wife',
        'Wife' => 'family_role.wife',
        'Suami' => 'family_role.husband',
        'Husband' => 'family_role.husband',
        'Anak' => 'family_role.child',
        'Child' => 'family_role.child',
        'Famili Lain' => 'family_role.relative',
        'Other Relative' => 'family_role.relative',
        'Penduduk' => 'family_role.resident',
        'Resident' => 'family_role.resident',
    ];
    $key = $map[$roleTrim] ?? null;
    return $key ? __($key) : $roleTrim;
}

function month_name(int $month): string
{
    return __('month.' . $month);
}

function status_badge(string $status): string
{
    $classes = [
        'pending' => 'warning',
        'verified' => 'success',
        'rejected' => 'danger',
        'approved' => 'success',
    ];
    $class = $classes[$status] ?? 'secondary';
    $label = __('status.' . $status);

    $iconMap = [
        'warning' => 'bi-hourglass-split',
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-x-circle-fill',
        'secondary' => 'bi-record-fill',
    ];
    $icon = $iconMap[$class] ?? 'bi-dot';

    return '<span class="badge bg-' . h($class) . ' bg-opacity-10 text-' . h($class) . ' border border-' . h($class) . ' border-opacity-25 py-1 px-2"><i class="bi ' . h($icon) . ' me-1"></i>' . h($label) . '</span>';
}

function asset_version(string $path): string
{
    $file = __DIR__ . '/../' . ltrim($path, '/\\');
    return file_exists($file) ? (string) filemtime($file) : '1.0';
}
