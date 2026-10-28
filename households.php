<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$editing = null;
if ($user['role'] === 'admin' && isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM households WHERE id=?');
    $statement->execute([(int) $_GET['edit']]);
    $editing = $statement->fetch() ?: null;
}
$households = $pdo->query('SELECT h.*, COUNT(r.id) AS member_count FROM households h LEFT JOIN residents r ON r.household_id=h.id GROUP BY h.id ORDER BY h.household_number')->fetchAll();
$residentsByHousehold = [];
if ($user['role'] === 'penduduk' && $user['resident_id'] !== null) {
    $statement = $pdo->prepare('SELECT r.*, h.household_number, h.head_nik FROM residents current JOIN residents r ON r.household_id=current.household_id LEFT JOIN households h ON h.id=current.household_id WHERE current.id=? ORDER BY r.family_role, r.full_name');
    $statement->execute([(int) $user['resident_id']]);
    $residentsByHousehold = $statement->fetchAll();
}
?>
<?php if ($user['role'] === 'admin'): ?>
<div class="panel-card mb-4">
    <div class="panel-heading">
        <div>
            <h3><?= $editing ? h(__('hh.form_edit')) : h(__('hh.form_add')) ?></h3>
            <p><?= h(__('hh.form_desc')) ?></p>
        </div>
    </div>
    <form method="post" action="index.php?page=households" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_household">
        <input type="hidden" name="return_page" value="households">
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div class="col-md-3">
            <label class="form-label"><?= h(__('hh.lbl_code')) ?> <span class="required">*</span></label>
            <input class="form-control" name="household_number" pattern="KKDEMO[0-9]{10}" maxlength="16" placeholder="KKDEMO0000000001" value="<?= h($editing['household_number'] ?? '') ?>" required>
            <small class="form-text"><?= h(__('hh.lbl_code_hint')) ?></small>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('hh.lbl_head_nik')) ?></label>
            <input class="form-control" name="head_nik" pattern="DEMO[0-9]{12}" maxlength="16" placeholder="DEMO000000000001" value="<?= h($editing['head_nik'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= h(__('hh.lbl_address')) ?> <span class="required">*</span></label>
            <input class="form-control" name="address" value="<?= h($editing['address'] ?? '') ?>" required>
        </div>
        <div class="col-md-1">
            <label class="form-label"><?= h(__('hh.lbl_rt')) ?></label>
            <input class="form-control" name="rt" value="<?= h($editing['rt'] ?? '') ?>">
        </div>
        <div class="col-md-1">
            <label class="form-label"><?= h(__('hh.lbl_rw')) ?></label>
            <input class="form-control" name="rw" value="<?= h($editing['rw'] ?? '') ?>">
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><?= h(__('hh.btn_save')) ?></button>
            <?php if ($editing): ?>
                <a class="btn btn-light" href="index.php?page=households"><?= h(__('common.cancel')) ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="panel-card">
    <div class="panel-heading">
        <div>
            <h3><?= h(__('hh.title')) ?></h3>
            <p><?= count($households) ?> <?= current_lang() === 'en' ? 'households recorded in database' : 'keluarga terdata dalam basis data' ?></p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle data-table">
            <thead>
                <tr>
                    <th><?= h(__('hh.th_number')) ?></th>
                    <th><?= h(__('hh.th_head')) ?></th>
                    <th><?= h(__('hh.th_address')) ?></th>
                    <th><?= h(__('hh.th_members')) ?></th>
                    <th><?= h(__('hh.th_action')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($households as $household): ?>
                <tr>
                    <td><code><?= h($household['household_number']) ?></code></td>
                    <td><?= $household['head_nik'] ? '<code>' . h($household['head_nik']) . '</code>' : '<span class="text-muted">-</span>' ?></td>
                    <td>
                        <strong><?= h($household['address']) ?></strong>
                        <small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i>RT <?= h($household['rt'] ?? '-') ?> / RW <?= h($household['rw'] ?? '-') ?></small>
                    </td>
                    <td>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                            <i class="bi bi-people me-1"></i><?= h(__('hh.members_count', ['count' => (int) $household['member_count']])) ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a class="btn btn-outline-secondary" href="index.php?page=households&edit=<?= (int) $household['id'] ?>" title="<?= h(__('common.edit')) ?>">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form method="post" action="index.php?page=households" data-confirm="<?= h(__('hh.confirm_delete')) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_household">
                                <input type="hidden" name="return_page" value="households">
                                <input type="hidden" name="id" value="<?= (int) $household['id'] ?>">
                                <button class="btn btn-outline-danger" title="<?= h(__('common.delete')) ?>">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$households): ?>
                <tr><td colspan="5" class="empty-state"><?= h(__('common.empty')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="panel-card">
    <div class="panel-heading">
        <div>
            <h3><?= h(__('hh.my_family_title')) ?></h3>
            <p><?= h(__('hh.my_family_desc')) ?></p>
        </div>
    </div>
    <?php if ($residentsByHousehold): ?>
        <div class="family-summary">
            <span><?= h(__('hh.th_number')) ?>: <strong><?= h($residentsByHousehold[0]['household_number'] ?? __('residents.lbl_kk_empty')) ?></strong></span>
            <span><?= h(__('residents.lbl_address')) ?>: <strong><?= h($residentsByHousehold[0]['address']) ?></strong></span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle data-table">
                <thead>
                    <tr>
                        <th><?= h(__('residents.th_nik')) ?></th>
                        <th><?= h(__('residents.lbl_name')) ?></th>
                        <th><?= h(__('residents.lbl_family_role')) ?></th>
                        <th><?= h(__('residents.th_gender')) ?></th>
                        <th><?= h(__('residents.lbl_birth_date')) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($residentsByHousehold as $member): ?>
                    <tr>
                        <td><code><?= h($member['nik']) ?></code></td>
                        <td><strong><?= h($member['full_name']) ?></strong></td>
                        <td><?= h(family_role_label($member['family_role'])) ?></td>
                        <td><?= $member['gender'] === 'Laki-laki' ? h(__('common.male')) : h(__('common.female')) ?></td>
                        <td><?= h((new DateTimeImmutable($member['birth_date']))->format(current_lang() === 'en' ? 'M d, Y' : 'd/m/Y')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state p-5"><?= h(__('hh.not_linked')) ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>
