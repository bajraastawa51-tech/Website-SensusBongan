<?php
declare(strict_types=1);
define('APP_BOOTSTRAPPED', true);

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/includes/functions.php';

// Handle Language Switch Request
if (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + (3600 * 24 * 30), '/');
    $redirectParams = $_GET;
    unset($redirectParams['lang']);
    $redirectUrl = 'index.php' . (!empty($redirectParams) ? '?' . http_build_query($redirectParams) : '');
    header('Location: ' . $redirectUrl);
    exit;
}

$pdo = require __DIR__ . '/config/database.php';
$user = current_user($pdo);

$page = $_GET['page'] ?? ($user === null ? 'login' : 'dashboard');
if (!is_string($page)) {
    $page = 'not-found';
}

$publicPages = ['login', 'setup', 'register'];
$pageRoles = [
    'dashboard' => ['admin', 'kepala_desa', 'penduduk'],
    'residents' => ['admin'],
    'households' => ['admin'],
    'events' => ['admin', 'penduduk'],
    'recap' => ['admin'],
    'reports' => ['admin', 'kepala_desa'],
    'users' => ['admin'],
    'profile' => ['admin', 'kepala_desa', 'penduduk'],
    'family' => ['penduduk'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if (!is_string($action)) {
            throw new AppException('Aksi tidak valid.');
        }
        require __DIR__ . '/includes/actions.php';
        handle_action($pdo, $action);
        throw new AppException('Aksi tidak dikenal.');
    } catch (AppException $exception) {
        flash('danger', $exception->getMessage());
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        flash('danger', 'Data tidak dapat diproses. Periksa kembali isian dan pastikan data terkait tersedia.');
    }

    redirect_page(is_string($_POST['return_page'] ?? null) ? $_POST['return_page'] : $page);
}

if (!in_array($page, $publicPages, true)) {
    if (!isset($pageRoles[$page])) {
        http_response_code(404);
        $page = 'not-found';
    } else {
        $user = require_role($pdo, ...$pageRoles[$page]);
    }
} elseif (in_array($page, ['login', 'register'], true) && $user !== null) {
    redirect_page('dashboard');
}

if ($page === 'setup') {
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount > 0) {
        redirect_page('login');
    }
}

$titles = [
    'dashboard' => __('nav.dashboard'),
    'residents' => __('nav.residents'),
    'households' => __('nav.households'),
    'events' => __('nav.events'),
    'recap' => __('nav.recap'),
    'reports' => __('nav.reports'),
    'users' => __('nav.users'),
    'profile' => __('nav.profile'),
    'family' => __('nav.family'),
];
$title = $titles[$page] ?? (in_array($page, ['login', 'register'], true) ? __('auth.login_title') : ($page === 'setup' ? 'Setup Admin' : 'Page Not Found'));
if ($page === 'events') {
    $title = match ($_GET['type'] ?? '') {
        'birth' => __('nav.birth'),
        'death' => __('nav.death'),
        'mutation' => __('nav.mutation'),
        default => $user['role'] === 'penduduk' ? __('nav.report_status') : __('nav.events'),
    };
}

if (in_array($page, $publicPages, true) || $page === 'not-found') {
    require __DIR__ . '/pages/' . $page . '.php';
    exit;
}

if ($page === 'reports' && ($_GET['export'] ?? '') === 'csv') {
    require __DIR__ . '/includes/report_export.php';
    exit;
}

require __DIR__ . '/partials/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/partials/footer.php';
