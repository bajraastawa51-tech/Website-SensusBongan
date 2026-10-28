<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$isCitizen = $user['role'] === 'penduduk';
$total = (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE population_status="active"')->fetchColumn();
$male = (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE population_status="active" AND gender="Laki-laki"')->fetchColumn();
$female = (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE population_status="active" AND gender="Perempuan"')->fetchColumn();
$householdsCount = (int) $pdo->query('SELECT COUNT(*) FROM households')->fetchColumn();
$pendingEvents = (int) $pdo->query('SELECT COUNT(*) FROM population_events WHERE status="pending"')->fetchColumn();
$pendingReports = (int) $pdo->query('SELECT COUNT(*) FROM monthly_reports WHERE status="pending"')->fetchColumn();
$eventTotals = array_fill_keys(['birth', 'death', 'move_in', 'move_out'], 0);
$eventStatement = $pdo->prepare('SELECT event_type, COUNT(*) AS total FROM population_events WHERE status="verified" AND event_date >= ? AND event_date < ? GROUP BY event_type');
$eventStatement->execute([(new DateTimeImmutable('first day of this month'))->format('Y-m-d'), (new DateTimeImmutable('first day of next month'))->format('Y-m-d')]);
foreach ($eventStatement->fetchAll() as $row) {
    $eventTotals[$row['event_type']] = (int) $row['total'];
}

$chartLabels = [];
$birthChart = [];
$deathChart = [];
$monthly = $pdo->prepare('SELECT event_type, COUNT(*) AS total FROM population_events WHERE status="verified" AND event_type IN ("birth", "death") AND event_date >= ? AND event_date < ? GROUP BY event_type');
for ($offset = 5; $offset >= 0; $offset--) {
    $month = (new DateTimeImmutable('first day of this month'))->modify("-{$offset} months");
    $start = $month->format('Y-m-d');
    $end = $month->modify('+1 month')->format('Y-m-d');
    $monthly->execute([$start, $end]);
    $counts = array_column($monthly->fetchAll(), 'total', 'event_type');
    $chartLabels[] = month_name((int) $month->format('n')) . ' ' . $month->format('Y');
    $birthChart[] = (int) ($counts['birth'] ?? 0);
    $deathChart[] = (int) ($counts['death'] ?? 0);
}

$citizenPending = 0;
if ($isCitizen) {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM population_events WHERE submitted_by=? AND status="pending"');
    $statement->execute([(int) $user['id']]);
    $citizenPending = (int) $statement->fetchColumn();
}
?>
<!-- WELCOME HERO PANEL -->
<div class="welcome-panel">
    <div class="welcome-copy">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="eyebrow"><i class="bi bi-geo-alt-fill text-warning me-1"></i>DESA BONGAN, KEC. TABANAN</span>
            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 py-1 px-2" style="font-size: 10px;">
                <?= h(role_label($user['role'])) ?>
            </span>
        </div>
        <h2><?= h(__('dashboard.welcome')) ?>, <?= h($user['full_name']) ?>!</h2>
        <p><?= h(__('dashboard.subtitle')) ?></p>
        
        <div class="welcome-actions mt-3 d-flex flex-wrap gap-2">
            <?php if ($user['role'] === 'admin'): ?>
                <a href="index.php?page=residents#resident-form" class="btn btn-sm btn-success shadow-sm">
                    <i class="bi bi-person-plus-fill me-1"></i> <?= h(__('dashboard.add_resident')) ?>
                </a>
                <a href="index.php?page=events&type=birth" class="btn btn-sm btn-outline-dark bg-white shadow-sm">
                    <i class="bi bi-clipboard-plus me-1"></i> <?= h(__('dashboard.record_event')) ?>
                </a>
                <a href="index.php?page=recap" class="btn btn-sm btn-outline-dark bg-white shadow-sm">
                    <i class="bi bi-pie-chart me-1"></i> <?= h(__('dashboard.recap')) ?>
                </a>
                <a href="index.php?page=profile" class="btn btn-sm btn-outline-dark bg-white shadow-sm">
                    <i class="bi bi-person-gear me-1"></i> <?= h(__('dashboard.my_profile')) ?>
                </a>
            <?php elseif ($user['role'] === 'kepala_desa'): ?>
                <a href="index.php?page=reports" class="btn btn-sm btn-success shadow-sm">
                    <i class="bi bi-file-earmark-check-fill me-1"></i> <?= h(__('dashboard.approve_report')) ?>
                </a>
                <a href="index.php?page=profile" class="btn btn-sm btn-outline-dark bg-white shadow-sm">
                    <i class="bi bi-person-gear me-1"></i> <?= h(__('dashboard.my_profile')) ?>
                </a>
            <?php else: ?>
                <a href="index.php?page=events&type=birth" class="btn btn-sm btn-success shadow-sm">
                    <i class="bi bi-send-plus me-1"></i> <?= h(__('dashboard.submit_event')) ?>
                </a>
                <a href="index.php?page=family" class="btn btn-sm btn-outline-dark bg-white shadow-sm">
                    <i class="bi bi-houses me-1"></i> <?= h(__('dashboard.my_family')) ?>
                </a>
                <a href="index.php?page=profile" class="btn btn-sm btn-outline-dark bg-white shadow-sm">
                    <i class="bi bi-person-gear me-1"></i> <?= h(__('dashboard.my_profile')) ?>
                </a>
            <?php endif; ?>
        </div>

        <span class="welcome-date"><i class="bi bi-calendar3 me-1"></i><?= h((new DateTimeImmutable())->format(current_lang() === 'en' ? 'M d, Y' : 'd F Y')) ?></span>
    </div>
    
    <div class="welcome-emblem">
        <img src="assets/images/logo-bongan.png" alt="Lambang Desa Bongan">
        <span>DESA BONGAN</span>
    </div>
</div>

<!-- KEY STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon blue"><i class="bi bi-people-fill"></i></span>
            <div>
                <small><?= h(__('dashboard.total_residents')) ?></small>
                <strong><?= number_format($total) ?></strong>
                <span class="stat-hint"><i class="bi bi-check-circle-fill text-success me-1"></i><?= h(__('dashboard.active_registered')) ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon green"><i class="bi bi-gender-male"></i></span>
            <div>
                <small><?= h(__('dashboard.male')) ?></small>
                <strong><?= number_format($male) ?></strong>
                <span class="stat-hint"><?= $total > 0 ? number_format(($male / $total) * 100, 1) : '0' ?>% <?= h(__('dashboard.of_total')) ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon orange"><i class="bi bi-gender-female"></i></span>
            <div>
                <small><?= h(__('dashboard.female')) ?></small>
                <strong><?= number_format($female) ?></strong>
                <span class="stat-hint"><?= $total > 0 ? number_format(($female / $total) * 100, 1) : '0' ?>% <?= h(__('dashboard.of_total')) ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon violet"><i class="bi bi-houses-fill"></i></span>
            <div>
                <small><?= h(__('dashboard.households')) ?></small>
                <strong><?= number_format($householdsCount) ?></strong>
                <span class="stat-hint"><i class="bi bi-house-door me-1"></i><?= h(__('dashboard.official_families')) ?></span>
            </div>
        </div>
    </div>
</div>

<?php if ($isCitizen): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="event-tile">
                <span class="stat-icon green"><i class="bi bi-person-plus-fill"></i></span>
                <div>
                    <small><?= h(__('event.birth')) ?> (<?= h(__('dashboard.this_month')) ?>)</small>
                    <strong><?= $eventTotals['birth'] ?></strong>
                    <span class="stat-hint"><?= h(__('status.verified')) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="event-tile">
                <span class="stat-icon red"><i class="bi bi-person-dash-fill"></i></span>
                <div>
                    <small><?= h(__('event.death')) ?> (<?= h(__('dashboard.this_month')) ?>)</small>
                    <strong><?= $eventTotals['death'] ?></strong>
                    <span class="stat-hint"><?= h(__('status.verified')) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="event-tile">
                <span class="stat-icon orange"><i class="bi bi-hourglass-split"></i></span>
                <div>
                    <small><?= h(__('dashboard.pending_events')) ?></small>
                    <strong><?= $citizenPending ?></strong>
                    <span class="stat-hint"><?= h(__('dashboard.awaiting_admin')) ?></span>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- MONTHLY EVENTS QUICK RECAP -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['birth', __('event.birth') . ' (' . __('dashboard.this_month') . ')', 'bi-person-plus-fill', 'green'],
            ['death', __('event.death') . ' (' . __('dashboard.this_month') . ')', 'bi-person-dash-fill', 'red'],
            ['move_out', __('event.move_out') . ' (' . __('dashboard.this_month') . ')', 'bi-box-arrow-up-right', 'orange'],
            ['move_in', __('event.move_in') . ' (' . __('dashboard.this_month') . ')', 'bi-box-arrow-in-down-left', 'blue'],
        ] as [$event, $label, $icon, $color]): ?>
            <div class="col-6 col-xl-3">
                <div class="event-tile">
                    <span class="stat-icon <?= h($color) ?>"><i class="bi <?= h($icon) ?>"></i></span>
                    <div>
                        <small><?= h($label) ?></small>
                        <strong><?= $eventTotals[$event] ?></strong>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($user['role'] === 'admin'): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <a class="action-banner text-decoration-none shadow-sm" href="index.php?page=events">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-warning bg-opacity-25 text-warning-emphasis p-2 fs-5 rounded-3">
                            <i class="bi bi-hourglass-split"></i>
                        </span>
                        <span>
                            <strong><?= h(__('dashboard.pending_events_banner', ['count' => $pendingEvents])) ?></strong>
                            <small class="text-muted"><?= h(__('dashboard.pending_events_sub')) ?></small>
                        </span>
                    </div>
                    <i class="bi bi-arrow-right-circle-fill fs-4 text-warning"></i>
                </a>
            </div>
            <div class="col-md-6">
                <a class="action-banner text-decoration-none shadow-sm" href="index.php?page=reports">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-primary bg-opacity-25 text-primary p-2 fs-5 rounded-3">
                            <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        </span>
                        <span>
                            <strong><?= h(__('dashboard.pending_reports_banner', ['count' => $pendingReports])) ?></strong>
                            <small class="text-muted"><?= h(__('dashboard.pending_reports_sub')) ?></small>
                        </span>
                    </div>
                    <i class="bi bi-arrow-right-circle-fill fs-4 text-primary"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- CHARTS ROW -->
<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card h-100">
            <div class="panel-heading">
                <div>
                    <h3><?= h(__('dashboard.chart_trend')) ?></h3>
                    <p><?= h(__('dashboard.chart_trend_sub')) ?></p>
                </div>
                <span class="heading-icon"><i class="bi bi-graph-up"></i></span>
            </div>
            <div class="chart-wrap">
                <canvas id="eventsChart" aria-label="Events Chart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card h-100">
            <div class="panel-heading">
                <div>
                    <h3><?= h(__('dashboard.chart_gender')) ?></h3>
                    <p><?= h(__('dashboard.chart_gender_sub')) ?></p>
                </div>
                <span class="heading-icon"><i class="bi bi-pie-chart-fill"></i></span>
            </div>
            <div class="chart-wrap doughnut-wrap">
                <canvas id="genderChart" aria-label="Gender Chart"></canvas>
            </div>
            <div class="chart-legend">
                <span><i class="legend-dot male"></i> <?= h(__('common.male')) ?> <strong><?= number_format($male) ?></strong> (<?= $total > 0 ? number_format(($male / $total) * 100, 1) : 0 ?>%)</span>
                <span><i class="legend-dot female"></i> <?= h(__('common.female')) ?> <strong><?= number_format($female) ?></strong> (<?= $total > 0 ? number_format(($female / $total) * 100, 1) : 0 ?>%)</span>
            </div>
        </div>
    </div>
</div>

<script>
window.sensusCharts = <?= json_encode([
    'labels' => $chartLabels,
    'births' => $birthChart,
    'deaths' => $deathChart,
    'male' => $male,
    'female' => $female,
    'labelBirth' => __('event.birth'),
    'labelDeath' => __('event.death'),
    'labelMale' => __('common.male'),
    'labelFemale' => __('common.female'),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
