<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$flashMessage = take_flash();
$navItems = [
    'admin' => [
        ['dashboard', __('nav.dashboard'), 'bi-grid-1x2', ''],
        ['residents', __('nav.residents'), 'bi-people', ''],
        ['households', __('nav.households'), 'bi-houses', ''],
        ['events', __('nav.birth'), 'bi-person-plus', 'birth'],
        ['events', __('nav.death'), 'bi-person-dash', 'death'],
        ['events', __('nav.mutation'), 'bi-arrow-left-right', 'mutation'],
        ['recap', __('nav.recap'), 'bi-pie-chart', ''],
        ['reports', __('nav.reports'), 'bi-file-earmark-bar-graph', ''],
        ['users', __('nav.users'), 'bi-person-gear', ''],
        ['profile', __('nav.profile'), 'bi-person', ''],
    ],
    'kepala_desa' => [
        ['dashboard', __('nav.dashboard'), 'bi-grid-1x2', ''],
        ['reports', __('nav.reports'), 'bi-file-earmark-bar-graph', ''],
        ['profile', __('nav.profile'), 'bi-person', ''],
    ],
    'penduduk' => [
        ['dashboard', __('nav.dashboard'), 'bi-grid-1x2', ''],
        ['family', __('nav.family'), 'bi-houses', ''],
        ['profile', __('nav.profile'), 'bi-person', ''],
        ['events', __('nav.report_birth'), 'bi-person-plus', 'birth'],
        ['events', __('nav.report_death'), 'bi-person-dash', 'death'],
        ['events', __('nav.report_mutation'), 'bi-arrow-left-right', 'mutation'],
        ['events', __('nav.report_status'), 'bi-clipboard-check', ''],
    ],
][$user['role']];
?>
<!doctype html>
<html lang="<?= h(current_lang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#143b35">
    <title><?= h($title) ?> - <?= h(__('app.name')) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo-bongan.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/app.css?v=<?= asset_version('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between mb-2">
            <a class="brand" href="index.php?page=dashboard">
                <span class="brand-mark"><img src="assets/images/logo-bongan.png" alt="Lambang Desa Bongan"></span>
                <div class="brand-text">
                    <strong>DESA BONGAN</strong>
                    <small><?= h(__('app.subname')) ?></small>
                </div>
            </a>
            <button type="button" class="btn p-0 border-0 d-lg-none" id="sidebarCloseBtn" aria-label="<?= h(__('common.close_nav')) ?>">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="nav-caption"><?= h(__('nav.main')) ?></div>
        <nav class="nav flex-column flex-nowrap w-100">
            <?php foreach ($navItems as [$route, $label, $icon, $eventType]): ?>
                <?php $active = $page === $route && ($route !== 'events' || ($_GET['type'] ?? '') === $eventType); ?>
                <a class="nav-link <?= $active ? 'active' : '' ?>" href="index.php?page=<?= h($route) ?><?= $eventType !== '' ? '&amp;type=' . h($eventType) : '' ?>">
                    <i class="bi <?= h($icon) ?>"></i><span><?= h($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <a href="index.php?page=profile" class="user-mini text-decoration-none" title="<?= h(__('common.view_edit_profile')) ?>">
                <span class="avatar"><?= h(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                <span class="text-truncate">
                    <strong><?= h($user['full_name']) ?></strong>
                    <small class="d-flex align-items-center gap-1"><?= h(role_label($user['role'])) ?> &bull; <?= h(__('common.edit_profile')) ?></small>
                </span>
            </a>
            <form method="post" action="index.php?page=dashboard">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="return_page" value="login">
                <button class="btn btn-logout w-100" type="submit"><i class="bi bi-box-arrow-left me-2"></i><?= h(__('nav.logout')) ?></button>
            </form>
        </div>
    </aside>
    <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop"></div>
    <main class="main-area">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button" aria-label="<?= h(__('common.open_nav')) ?>"><i class="bi bi-list"></i></button>
            <div class="page-heading">
                <span class="text-muted small"><?= h(__('app.agency')) ?> <span class="heading-separator">/</span> <?= h(__('app.agency_sub')) ?></span>
                <h1><?= h($title) ?></h1>
            </div>
            <div class="topbar-right">
                <button type="button" class="btn btn-sm btn-outline-success border-2 fw-bold d-none d-md-inline-flex align-items-center gap-1" onclick="toggleSeniorMode()" title="<?= h(__('senior.mode_toggle')) ?>" id="topbarSeniorBtn">
                    <i class="bi bi-eyeglasses"></i> <span id="topbarSeniorText"><?= h(__('senior.mode_toggle')) ?></span>
                </button>
                <?= render_lang_switcher() ?>
                <span class="today"><i class="bi bi-calendar3 me-2"></i><?= h((new DateTimeImmutable())->format(current_lang() === 'en' ? 'M d, Y' : 'd/m/Y')) ?></span>
                <span class="badge rounded-pill bg-light text-dark border px-2 py-1 d-none d-md-inline-block"><?= h(role_label($user['role'])) ?></span>
                <div class="dropdown user-dropdown">
                    <button class="btn p-0 border-0 top-avatar-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= h($user['full_name']) ?> (<?= h(role_label($user['role'])) ?>)">
                        <span class="top-avatar"><?= h(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="dropdown-header">
                            <span class="d-block text-muted" style="font-size: 11px;"><?= h(__('common.signed_in_as')) ?></span>
                            <strong class="d-block text-truncate text-dark" style="max-width: 190px;"><?= h($user['full_name']) ?></strong>
                            <small class="badge bg-light text-success border mt-1"><?= h(role_label($user['role'])) ?></small>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 <?= $page === 'profile' ? 'active' : '' ?>" href="index.php?page=profile">
                                <i class="bi bi-person-gear"></i>
                                <span><?= h(__('nav.profile')) ?></span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="post" action="index.php?page=dashboard" class="m-0 p-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="logout">
                                <input type="hidden" name="return_page" value="login">
                                <button class="dropdown-item d-flex align-items-center gap-2 text-danger" type="submit">
                                    <i class="bi bi-box-arrow-left"></i>
                                    <span><?= h(__('nav.logout')) ?></span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        <section class="content-area">
            <div class="demo-notice"><i class="bi bi-shield-check"></i><span><?= h(__('app.demo_notice')) ?></span></div>
            <?php if ($flashMessage !== null): ?>
                <div class="alert alert-<?= h($flashMessage['type']) ?> alert-dismissible fade show" role="alert">
                    <?= h($flashMessage['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= h(__('common.close')) ?>"></button>
                </div>
            <?php endif; ?>
