<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$editing = null;
if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM residents WHERE id=?');
    $statement->execute([(int) $_GET['edit']]);
    $editing = $statement->fetch() ?: null;
}
$households = $pdo->query('SELECT id, household_number FROM households ORDER BY household_number')->fetchAll();
$query = 'SELECT r.*, h.household_number FROM residents r LEFT JOIN households h ON h.id=r.household_id';
$parameters = [];
if ($search !== '') {
    $query .= ' WHERE r.nik LIKE ? OR r.full_name LIKE ? OR h.household_number LIKE ?';
    $term = '%' . $search . '%';
    $parameters = [$term, $term, $term];
}
$query .= ' ORDER BY r.created_at DESC LIMIT 300';
$statement = $pdo->prepare($query);
$statement->execute($parameters);
$residents = $statement->fetchAll();
$value = static fn(string $key, string $fallback = ''): string => (string) ($editing[$key] ?? $fallback);
?>
<?php if ($user['role'] === 'admin'): ?>
<div class="panel-card mb-4" id="resident-form">
    <div class="panel-heading">
        <div>
            <h3><?= $editing ? h(__('residents.form_edit')) : h(__('residents.form_add')) ?></h3>
            <p><?= h(__('residents.form_desc')) ?></p>
        </div>
    </div>
    <form method="post" action="index.php?page=residents" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_resident">
        <input type="hidden" name="return_page" value="residents">
        <input type="hidden" name="id" value="<?= h($value('id', '0')) ?>">
        
        <div class="col-md-4">
            <label class="form-label"><?= h(__('residents.lbl_nik')) ?> <span class="required">*</span></label>
            <input class="form-control" name="nik" pattern="DEMO[0-9]{12}" maxlength="16" placeholder="DEMO000000000001" value="<?= h($value('nik')) ?>" required>
            <small class="form-text"><?= h(__('residents.lbl_nik_hint')) ?></small>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= h(__('residents.lbl_name')) ?> <span class="required">*</span></label>
            <input class="form-control" name="full_name" maxlength="150" value="<?= h($value('full_name')) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= h(__('residents.lbl_kk')) ?></label>
            <select class="form-select" name="household_id">
                <option value=""><?= h(__('residents.lbl_kk_empty')) ?></option>
                <?php foreach ($households as $household): ?>
                    <option value="<?= (int) $household['id'] ?>" <?= (string) ($editing['household_id'] ?? '') === (string) $household['id'] ? 'selected' : '' ?>><?= h($household['household_number']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('residents.lbl_birth_place')) ?></label>
            <input class="form-control" name="birth_place" value="<?= h($value('birth_place')) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('residents.lbl_birth_date')) ?> <span class="required">*</span></label>
            <input class="form-control" type="date" name="birth_date" value="<?= h($value('birth_date')) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('residents.lbl_gender')) ?> <span class="required">*</span></label>
            <select class="form-select" name="gender" required>
                <option value=""><?= current_lang() === 'en' ? 'Select' : 'Pilih' ?></option>
                <?php foreach (['Laki-laki' => __('common.male'), 'Perempuan' => __('common.female')] as $gVal => $gLabel): ?>
                    <option value="<?= h($gVal) ?>" <?= $value('gender') === $gVal ? 'selected' : '' ?>><?= h($gLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('residents.lbl_family_role')) ?></label>
            <input class="form-control" name="family_role" placeholder="<?= h(__('residents.lbl_family_role_placeholder')) ?>" value="<?= h($value('family_role')) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= h(__('residents.lbl_address')) ?> <span class="required">*</span></label>
            <input class="form-control" name="address" value="<?= h($value('address')) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('residents.lbl_religion')) ?></label>
            <input class="form-control" name="religion" value="<?= h($value('religion')) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('residents.lbl_marital')) ?></label>
            <input class="form-control" name="marital_status" value="<?= h($value('marital_status')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= h(__('residents.lbl_occupation')) ?></label>
            <input class="form-control" name="occupation" value="<?= h($value('occupation')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= h(__('residents.lbl_education')) ?></label>
            <input class="form-control" name="education" value="<?= h($value('education')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= h(__('residents.lbl_phone')) ?></label>
            <input class="form-control" name="phone" value="<?= h($value('phone')) ?>">
        </div>
        <?php if ($editing): ?>
            <div class="col-md-4">
                <label class="form-label"><?= h(__('residents.lbl_status')) ?></label>
                <select class="form-select" name="population_status">
                    <option value="active" <?= $value('population_status') === 'active' ? 'selected' : '' ?>><?= h(__('common.active')) ?></option>
                    <option value="inactive" <?= $value('population_status') === 'inactive' ? 'selected' : '' ?>><?= h(__('common.inactive')) ?></option>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2 me-1"></i><?= $editing ? h(__('residents.btn_save_edit')) : h(__('residents.btn_save_new')) ?>
            </button>
            <?php if ($editing): ?>
                <a class="btn btn-light" href="index.php?page=residents"><?= h(__('common.cancel')) ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="panel-card">
    <div class="panel-heading flex-wrap">
        <div>
            <h3><?= h(__('residents.title')) ?></h3>
            <p><?= count($residents) ?> <?= current_lang() === 'en' ? 'records displayed' : 'data ditampilkan' ?><?= $search !== '' ? ' (' . h($search) . ')' : '' ?></p>
        </div>
        <form class="search-form" method="get">
            <input type="hidden" name="page" value="residents">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input class="form-control border-start-0" name="q" placeholder="<?= h(__('residents.search_placeholder')) ?>" value="<?= h($search) ?>">
                <button class="btn btn-outline-primary" type="submit"><?= h(__('common.search')) ?></button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle data-table">
            <thead>
                <tr>
                    <th><?= h(__('residents.th_resident')) ?></th>
                    <th><?= h(__('residents.th_nik')) ?></th>
                    <th><?= h(__('residents.th_kk')) ?></th>
                    <th><?= h(__('residents.th_gender')) ?></th>
                    <th><?= h(__('residents.th_age')) ?></th>
                    <th><?= h(__('residents.th_status')) ?></th>
                    <?php if ($user['role'] === 'admin'): ?><th><?= h(__('residents.th_action')) ?></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($residents as $resident): ?>
                <tr>
                    <td>
                        <strong><?= h($resident['full_name']) ?></strong>
                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= h($resident['address']) ?></small>
                    </td>
                    <td><code><?= h($resident['nik']) ?></code></td>
                    <td><?= $resident['household_number'] ? '<code>' . h($resident['household_number']) . '</code>' : '<span class="text-muted">-</span>' ?></td>
                    <td>
                        <?= $resident['gender'] === 'Laki-laki' 
                            ? '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="bi bi-gender-male me-1"></i>' . h(__('common.male')) . '</span>' 
                            : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-gender-female me-1"></i>' . h(__('common.female')) . '</span>' 
                        ?>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= h(__('residents.years_old', ['years' => (new DateTimeImmutable($resident['birth_date']))->diff(new DateTimeImmutable())->y])) ?></span></td>
                    <td>
                        <?= $resident['population_status'] === 'active' 
                            ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-check-circle-fill me-1"></i>' . h(__('common.active')) . '</span>' 
                            : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><i class="bi bi-dash-circle me-1"></i>' . h(__('common.inactive')) . '</span>' 
                        ?>
                    </td>
                    <?php if ($user['role'] === 'admin'): ?>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="index.php?page=residents&edit=<?= (int) $resident['id'] ?>#resident-form" title="<?= h(__('common.edit')) ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form method="post" action="index.php?page=residents" data-confirm="<?= h(__('residents.confirm_delete')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_resident">
                                    <input type="hidden" name="return_page" value="residents">
                                    <input type="hidden" name="id" value="<?= (int) $resident['id'] ?>">
                                    <button class="btn btn-outline-danger" type="submit" title="<?= h(__('common.delete')) ?>">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$residents): ?>
                <tr>
                    <td colspan="<?= $user['role'] === 'admin' ? '7' : '6' ?>" class="empty-state">
                        <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                        <?= h(__('common.empty')) ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
