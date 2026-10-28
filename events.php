<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$types = [
    'birth' => __('events.type_birth'),
    'death' => __('events.type_death'),
    'move_in' => __('events.type_move_in'),
    'move_out' => __('events.type_move_out'),
];
$filterTypes = $types + ['mutation' => __('events.type_mutation')];
$statusFilter = is_string($_GET['status'] ?? null) && in_array($_GET['status'], ['pending', 'verified', 'rejected'], true) ? $_GET['status'] : '';
$typeFilter = is_string($_GET['type'] ?? null) && array_key_exists($_GET['type'], $filterTypes) ? $_GET['type'] : '';
$sql = 'SELECT e.*, u.full_name AS reporter, r.full_name AS resident_name, reviewer.full_name AS reviewer_name FROM population_events e JOIN users u ON u.id=e.submitted_by LEFT JOIN residents r ON r.id=e.resident_id LEFT JOIN users reviewer ON reviewer.id=e.reviewed_by';
$conditions = [];
$params = [];
if ($user['role'] === 'penduduk') {
    $conditions[] = 'e.submitted_by=?';
    $params[] = (int) $user['id'];
}
if ($statusFilter !== '') {
    $conditions[] = 'e.status=?';
    $params[] = $statusFilter;
}
if ($typeFilter !== '') {
    if ($typeFilter === 'mutation') {
        $conditions[] = 'e.event_type IN ("move_in", "move_out")';
    } else {
        $conditions[] = 'e.event_type=?';
        $params[] = $typeFilter;
    }
}
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY e.created_at DESC LIMIT 300';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$events = $statement->fetchAll();
$residents = $user['role'] === 'admin'
    ? $pdo->query('SELECT id, nik, full_name, population_status FROM residents ORDER BY full_name')->fetchAll()
    : [];
?>
<?php if (in_array($user['role'], ['admin', 'penduduk'], true)): ?>
<div class="panel-card mb-4">
    <div class="panel-heading">
        <div>
            <h3><?= h(__('events.submit_title')) ?></h3>
            <p><?= h(__('events.submit_desc')) ?></p>
        </div>
    </div>
    <form method="post" action="index.php?page=events" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_event">
        <input type="hidden" name="return_page" value="events">
        
        <div class="col-md-4">
            <label class="form-label"><?= h(__('events.type_label')) ?></label>
            <select class="form-select" name="event_type" id="eventType" required>
                <option value=""><?= h(__('events.type_select')) ?></option>
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= ($_GET['type'] ?? '') === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-4">
            <label class="form-label"><?= h(__('events.name_label')) ?></label>
            <input class="form-control" name="person_name" maxlength="150" required>
        </div>
        
        <div class="col-md-4">
            <label class="form-label"><?= h(__('events.date_label')) ?></label>
            <input class="form-control" name="event_date" type="date" max="<?= h(date('Y-m-d')) ?>" required>
        </div>
        
        <?php if ($user['role'] === 'admin'): ?>
            <div class="col-md-6">
                <label class="form-label"><?= h(__('events.link_resident')) ?></label>
                <select class="form-select" name="resident_id" id="residentSelect">
                    <option value=""><?= h(__('events.link_none')) ?></option>
                    <?php foreach ($residents as $resident): ?>
                        <option value="<?= (int) $resident['id'] ?>" data-nik="<?= h($resident['nik']) ?>" data-name="<?= h($resident['full_name']) ?>">
                            <?= h($resident['full_name']) ?> — <?= h($resident['nik']) ?><?= $resident['population_status'] === 'inactive' ? ' (' . h(__('common.inactive')) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <div class="col-md-3">
            <label class="form-label"><?= h(__('events.nik_label')) ?></label>
            <input class="form-control" name="nik" pattern="DEMO[0-9]{12}" maxlength="16" placeholder="DEMO000000000001">
        </div>
        
        <div class="col-md-3">
            <label class="form-label"><?= h(__('events.gender_label')) ?></label>
            <select class="form-select" name="gender">
                <option value=""><?= h(__('events.gender_unknown')) ?></option>
                <option value="Laki-laki"><?= h(__('common.male')) ?></option>
                <option value="Perempuan"><?= h(__('common.female')) ?></option>
            </select>
        </div>
        
        <div class="col-md-6">
            <label class="form-label"><?= h(__('events.address_from')) ?></label>
            <input class="form-control" name="address_from" maxlength="500" placeholder="<?= h(__('events.address_from_placeholder')) ?>">
        </div>
        
        <div class="col-md-6">
            <label class="form-label"><?= h(__('events.address_to')) ?></label>
            <input class="form-control" name="address_to" maxlength="500" placeholder="<?= h(__('events.address_to_placeholder')) ?>">
        </div>
        
        <div class="col-12">
            <label class="form-label"><?= h(__('events.details_label')) ?></label>
            <textarea class="form-control" name="details" rows="2" maxlength="2000" required></textarea>
        </div>
        
        <div class="col-12">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-send me-1"></i><?= h(__('events.btn_submit')) ?>
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="panel-card">
    <div class="panel-heading flex-wrap">
        <div>
            <h3><?= $user['role'] === 'penduduk' ? h(__('events.my_reports_title')) : h(__('events.admin_reports_title')) ?></h3>
            <p><?= h(__('events.reports_desc')) ?></p>
        </div>
        <form method="get" class="search-form">
            <input type="hidden" name="page" value="events">
            <select class="form-select" name="type" aria-label="<?= h(__('common.all_events')) ?>">
                <option value=""><?= h(__('common.all_events')) ?></option>
                <?php foreach ($filterTypes as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $typeFilter === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="status" aria-label="<?= h(__('common.all_status')) ?>">
                <option value=""><?= h(__('common.all_status')) ?></option>
                <?php foreach (['pending' => __('status.pending'), 'verified' => __('status.verified'), 'rejected' => __('status.rejected')] as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary" type="submit" aria-label="<?= h(__('common.apply_filter')) ?>" title="<?= h(__('common.apply_filter')) ?>">
                <i class="bi bi-funnel"></i>
            </button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table align-middle data-table">
            <thead>
                <tr>
                    <th><?= h(__('events.th_event')) ?></th>
                    <th><?= h(__('events.th_date')) ?></th>
                    <?php if ($user['role'] !== 'penduduk'): ?>
                        <th><?= h(__('events.th_reporter')) ?></th>
                    <?php endif; ?>
                    <th><?= h(__('events.th_person')) ?></th>
                    <th><?= h(__('events.th_details')) ?></th>
                    <th><?= h(__('events.th_status')) ?></th>
                    <?php if ($user['role'] === 'admin'): ?>
                        <th><?= h(__('events.th_verify')) ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><span class="event-type"><?= h($types[$event['event_type']] ?? $event['event_type']) ?></span></td>
                    <td><?= h((new DateTimeImmutable($event['event_date']))->format(current_lang() === 'en' ? 'M d, Y' : 'd/m/Y')) ?></td>
                    <?php if ($user['role'] !== 'penduduk'): ?>
                        <td><?= h($event['reporter']) ?></td>
                    <?php endif; ?>
                    <td>
                        <strong><?= h($event['person_name']) ?></strong>
                        <small>
                            <?= h($event['nik'] ?? __('events.no_code')) ?>
                            <?= $event['resident_name'] ? ' · ' . h($event['resident_name']) : '' ?>
                        </small>
                    </td>
                    <td>
                        <?= h($event['details']) ?>
                        <small>
                            <?= $event['address_from'] ? h(__('events.origin_prefix')) . h($event['address_from']) . ' ' : '' ?>
                            <?= $event['address_to'] ? h(__('events.dest_prefix')) . h($event['address_to']) : '' ?>
                        </small>
                    </td>
                    <td>
                        <?= status_badge($event['status']) ?>
                        <?php if ($event['reviewer_name']): ?>
                            <small><?= h(__('events.by_reviewer', ['name' => $event['reviewer_name']])) ?></small>
                        <?php endif; ?>
                    </td>
                    <?php if ($user['role'] === 'admin'): ?>
                        <td>
                            <?php if ($event['status'] === 'pending'): ?>
                                <div class="d-flex gap-1">
                                    <form method="post" action="index.php?page=events" data-confirm="<?= h(__('events.confirm_verify')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="review_event">
                                        <input type="hidden" name="return_page" value="events">
                                        <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                                        <input type="hidden" name="status" value="verified">
                                        <button class="btn btn-sm btn-success" type="submit"><?= h(__('events.btn_verify')) ?></button>
                                    </form>
                                    <form method="post" action="index.php?page=events" data-confirm="<?= h(__('events.confirm_reject')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="review_event">
                                        <input type="hidden" name="return_page" value="events">
                                        <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><?= h(__('events.btn_reject')) ?></button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-muted"><?= h(__('common.done')) ?></span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$events): ?>
                <tr>
                    <td colspan="<?= $user['role'] === 'admin' ? '7' : ($user['role'] === 'penduduk' ? '5' : '6') ?>" class="empty-state">
                        <?= h(__('events.no_events')) ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
