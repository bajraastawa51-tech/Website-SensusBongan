<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$population = $pdo->query('SELECT COUNT(*) AS total, SUM(gender="Laki-laki") AS male, SUM(gender="Perempuan") AS female FROM residents WHERE population_status="active"')->fetch();
$ageGroups = $pdo->query('SELECT
    SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 6) AS early_childhood,
    SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 6 AND 17) AS school_age,
    SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 59) AS productive_age,
    SUM(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 60) AS elderly
    FROM residents WHERE population_status="active"')->fetch();
$eventRows = $pdo->query('SELECT event_type, COUNT(*) AS total FROM population_events WHERE status="verified" GROUP BY event_type')->fetchAll();
$eventCounts = array_fill_keys(['birth', 'death', 'move_in', 'move_out'], 0);
foreach ($eventRows as $row) {
    $eventCounts[$row['event_type']] = (int) $row['total'];
}
$familyStats = $pdo->query('SELECT COUNT(*) AS households, COALESCE(AVG(member_count), 0) AS average_members FROM (SELECT h.id, COUNT(r.id) AS member_count FROM households h LEFT JOIN residents r ON r.household_id=h.id AND r.population_status="active" GROUP BY h.id) household_counts')->fetch();
$labels = [
    'birth' => __('recap.lbl_birth_verified'),
    'death' => __('recap.lbl_death_verified'),
    'move_in' => __('recap.lbl_move_in_verified'),
    'move_out' => __('recap.lbl_move_out_verified'),
];
?>
<div class="welcome-panel">
    <div>
        <span class="eyebrow"><?= h(__('recap.kicker')) ?></span>
        <h2><?= h(__('recap.title')) ?></h2>
        <p><?= h(__('recap.subtitle')) ?></p>
    </div>
    <div class="welcome-icon">
        <i class="bi bi-pie-chart"></i>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        [__('recap.active_residents'), (int) ($population['total'] ?? 0), 'bi-people', 'blue'],
        [__('dashboard.male'), (int) ($population['male'] ?? 0), 'bi-gender-male', 'green'],
        [__('dashboard.female'), (int) ($population['female'] ?? 0), 'bi-gender-female', 'orange'],
        [__('recap.households'), (int) ($familyStats['households'] ?? 0), 'bi-houses', 'violet'],
    ] as [$label, $count, $icon, $color]): ?>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon <?= h($color) ?>"><i class="bi <?= h($icon) ?>"></i></span>
            <div>
                <small><?= h($label) ?></small>
                <strong><?= number_format($count) ?></strong>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel-card h-100">
            <div class="panel-heading">
                <div>
                    <h3><?= h(__('recap.age_groups_title')) ?></h3>
                    <p><?= h(__('recap.age_groups_subtitle')) ?></p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th><?= h(__('recap.th_group')) ?></th>
                            <th><?= h(__('recap.th_range')) ?></th>
                            <th><?= h(__('recap.th_count')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ([
                            [__('recap.group_early_childhood'), __('recap.range_early_childhood'), 'early_childhood'],
                            [__('recap.group_school_age'), __('recap.range_school_age'), 'school_age'],
                            [__('recap.group_productive_age'), __('recap.range_productive_age'), 'productive_age'],
                            [__('recap.group_elderly'), __('recap.range_elderly'), 'elderly'],
                        ] as [$label, $range, $column]): ?>
                            <tr>
                                <td><?= h($label) ?></td>
                                <td><?= h($range) ?></td>
                                <td><strong><?= number_format((int) ($ageGroups[$column] ?? 0)) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="family-summary mt-3">
                <span><?= h(__('recap.avg_family_members')) ?> <strong><?= h(__('recap.persons', ['count' => number_format((float) ($familyStats['average_members'] ?? 0), 1)])) ?></strong></span>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel-card h-100">
            <div class="panel-heading">
                <div>
                    <h3><?= h(__('recap.events_accum_title')) ?></h3>
                    <p><?= h(__('recap.events_accum_subtitle')) ?></p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th><?= h(__('recap.th_event_type')) ?></th>
                            <th><?= h(__('recap.th_count')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($labels as $key => $label): ?>
                            <tr>
                                <td><?= h($label) ?></td>
                                <td><strong><?= number_format($eventCounts[$key]) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="report-note"><?= h(__('recap.events_note')) ?></p>
        </div>
    </div>
</div>
