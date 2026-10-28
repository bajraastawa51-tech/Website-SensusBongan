<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$month = filter_var($_GET['month'] ?? date('n'), FILTER_VALIDATE_INT);
$year = filter_var($_GET['year'] ?? date('Y'), FILTER_VALIDATE_INT);
if (!$month || $month < 1 || $month > 12) {
    $month = (int) date('n');
}
if (!$year || $year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}
$statement = $pdo->prepare('SELECT mr.*, creator.full_name AS creator_name, approver.full_name AS approver_name FROM monthly_reports mr JOIN users creator ON creator.id=mr.created_by LEFT JOIN users approver ON approver.id=mr.approved_by ORDER BY mr.report_year DESC, mr.report_month DESC LIMIT 100');
$statement->execute();
$reports = $statement->fetchAll();
$selected = null;
foreach ($reports as $report) {
    if ((int) $report['report_month'] === $month && (int) $report['report_year'] === $year) {
        $selected = $report;
        break;
    }
}
$monthName = month_name($month) . ' ' . $year;
?>
<div class="panel-card mb-4 no-print">
    <div class="panel-heading">
        <div>
            <h3><?= h(__('reports.title')) ?></h3>
            <p><?= h(__('reports.subtitle')) ?></p>
        </div>
    </div>
    <form method="get" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="reports">
        <div class="col-sm-4 col-md-3">
            <label class="form-label"><?= h(__('common.month')) ?></label>
            <select class="form-select" name="month">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= h(month_name($m)) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label"><?= h(__('common.year')) ?></label>
            <input class="form-control" type="number" min="2000" max="2100" name="year" value="<?= $year ?>">
        </div>
        <div class="col-sm-4 col-md-6 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-search me-1"></i><?= h(__('common.show')) ?>
            </button>
            <?php if ($selected && ($user['role'] !== 'kepala_desa' || $selected['status'] === 'approved')): ?>
                <a class="btn btn-outline-primary" href="index.php?page=reports&month=<?= $month ?>&year=<?= $year ?>&export=csv">
                    <i class="bi bi-download me-1"></i><?= h(__('reports.download_csv')) ?>
                </a>
                <button class="btn btn-outline-secondary" type="button" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i><?= h(__('reports.print')) ?>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($user['role'] === 'admin'): ?>
    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
        <span><i class="bi bi-info-circle me-2"></i><?= h(__('reports.generate_banner', ['period' => $monthName])) ?></span>
        <form method="post" action="index.php?page=reports" data-confirm="<?= h(__('reports.generate_confirm')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="generate_report">
            <input type="hidden" name="return_page" value="reports">
            <input type="hidden" name="month" value="<?= $month ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <button class="btn btn-info" type="submit"><?= h(__('reports.btn_generate')) ?></button>
        </form>
    </div>
<?php endif; ?>

<?php if ($selected): ?>
    <div class="report-sheet">
        <div class="report-header">
            <span class="report-seal"><img src="assets/images/logo-bongan.png" alt="Lambang Desa Bongan"></span>
            <div>
                <h2><?= h(__('app.agency')) ?></h2>
                <p><?= h(__('reports.sheet_title', ['period' => $monthName])) ?></p>
            </div>
        </div>
        <hr>
        <div class="report-status">
            <?= status_badge($selected['status']) ?>
            <small><?= h(__('reports.prepared_by', ['name' => $selected['creator_name']])) ?> · <?= h((new DateTimeImmutable($selected['created_at']))->format(current_lang() === 'en' ? 'M d, Y H:i' : 'd/m/Y H:i')) ?></small>
            <?php if ($selected['approver_name']): ?>
                <small><?= h(__('reports.approved_by', ['name' => $selected['approver_name']])) ?> · <?= h((new DateTimeImmutable($selected['approved_at']))->format(current_lang() === 'en' ? 'M d, Y H:i' : 'd/m/Y H:i')) ?></small>
            <?php endif; ?>
        </div>
        <div class="row g-3 report-stats">
            <?php foreach ([
                [__('reports.lbl_active_pop'), 'total_population', 'bi-people'],
                [__('dashboard.male'), 'male_count', 'bi-gender-male'],
                [__('dashboard.female'), 'female_count', 'bi-gender-female'],
                [__('event.birth'), 'births', 'bi-person-plus'],
                [__('event.death'), 'deaths', 'bi-person-dash'],
                [__('reports.lbl_move_ins'), 'move_ins', 'bi-box-arrow-in-down-left'],
                [__('reports.lbl_move_outs'), 'move_outs', 'bi-box-arrow-up-right'],
            ] as [$label, $column, $icon]): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="report-metric">
                        <i class="bi <?= h($icon) ?>"></i>
                        <span><?= h($label) ?></span>
                        <strong><?= number_format((int) $selected[$column]) ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="report-note"><?= h(__('reports.note')) ?></p>
        <?php if ($user['role'] === 'kepala_desa' && $selected['status'] === 'pending'): ?>
            <form method="post" action="index.php?page=reports" class="no-print mt-4" data-confirm="<?= h(__('reports.approve_confirm')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="approve_report">
                <input type="hidden" name="return_page" value="reports">
                <input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
                <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i><?= h(__('reports.btn_approve')) ?></button>
            </form>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="panel-card empty-state p-5">
        <?= h(__('reports.empty_period', ['period' => $monthName])) ?><?php if ($user['role'] === 'admin'): ?><?= h(__('reports.empty_period_admin')) ?><?php endif; ?>
    </div>
<?php endif; ?>

<div class="panel-card mt-4 no-print">
    <div class="panel-heading">
        <div>
            <h3><?= h(__('reports.history_title')) ?></h3>
            <p><?= h(__('reports.history_subtitle')) ?></p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle data-table">
            <thead>
                <tr>
                    <th><?= h(__('reports.th_period')) ?></th>
                    <th><?= h(__('reports.th_population')) ?></th>
                    <th><?= h(__('reports.th_births')) ?></th>
                    <th><?= h(__('reports.th_deaths')) ?></th>
                    <th><?= h(__('reports.th_status')) ?></th>
                    <th><?= h(__('reports.th_prepared_by')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>
                            <a href="index.php?page=reports&month=<?= (int) $report['report_month'] ?>&year=<?= (int) $report['report_year'] ?>">
                                <?= h(month_name((int) $report['report_month']) . ' ' . $report['report_year']) ?>
                            </a>
                        </td>
                        <td><?= number_format((int) $report['total_population']) ?></td>
                        <td><?= (int) $report['births'] ?></td>
                        <td><?= (int) $report['deaths'] ?></td>
                        <td><?= status_badge($report['status']) ?></td>
                        <td><?= h($report['creator_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reports): ?>
                    <tr><td colspan="6" class="empty-state"><?= h(__('reports.history_empty')) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
