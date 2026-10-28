<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$editing = null;
if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
    $statement = $pdo->prepare('SELECT id, full_name, username, role, resident_id, is_active FROM users WHERE id=?');
    $statement->execute([(int) $_GET['edit']]);
    $editing = $statement->fetch() ?: null;
}
$requests = $pdo->query('SELECT u.id, u.full_name, u.username, u.created_at, r.nik, r.full_name AS resident_name, h.household_number FROM users u JOIN residents r ON r.id=u.resident_id LEFT JOIN households h ON h.id=r.household_id WHERE u.role="penduduk" AND u.approval_status="pending" ORDER BY u.created_at ASC')->fetchAll();
$users = $pdo->query('SELECT u.id, u.full_name, u.username, u.role, u.is_active, r.nik, r.full_name AS resident_name FROM users u LEFT JOIN residents r ON r.id=u.resident_id WHERE u.approval_status="approved" ORDER BY u.created_at DESC')->fetchAll();
$residents = $pdo->query('SELECT r.id, r.nik, r.full_name, r.population_status, u.id AS linked_user_id FROM residents r LEFT JOIN users u ON u.resident_id=r.id ORDER BY r.full_name')->fetchAll();
?>
<div class="panel-card mb-4">
    <div class="panel-heading">
        <div>
            <h3><?= h(__('users.requests_title')) ?></h3>
            <p><?= h(__('users.requests_subtitle', ['count' => count($requests)])) ?></p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle data-table">
            <thead>
                <tr>
                    <th><?= h(__('users.th_resident_sample')) ?></th>
                    <th><?= h(__('users.th_nik')) ?></th>
                    <th><?= h(__('users.th_hh')) ?></th>
                    <th><?= h(__('users.th_username')) ?></th>
                    <th><?= h(__('users.th_request_date')) ?></th>
                    <th><?= h(__('users.th_action')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><strong><?= h($request['resident_name']) ?></strong></td>
                    <td><code><?= h($request['nik']) ?></code></td>
                    <td><?= h($request['household_number'] ?? '-') ?></td>
                    <td><?= h($request['username']) ?></td>
                    <td><?= h((new DateTimeImmutable($request['created_at']))->format(current_lang() === 'en' ? 'M d, Y H:i' : 'd/m/Y H:i')) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <form method="post" action="index.php?page=users" data-confirm="<?= h(__('users.confirm_approve')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve_registration">
                                <input type="hidden" name="return_page" value="users">
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <button class="btn btn-sm btn-success" type="submit"><?= h(__('users.btn_approve')) ?></button>
                            </form>
                            <form method="post" action="index.php?page=users" data-confirm="<?= h(__('users.confirm_reject')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject_registration">
                                <input type="hidden" name="return_page" value="users">
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><?= h(__('users.btn_reject')) ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?>
                <tr><td colspan="6" class="empty-state"><?= h(__('users.no_requests')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel-card mb-4">
    <div class="panel-heading">
        <div>
            <h3><?= $editing ? h(__('users.form_edit')) : h(__('users.form_add')) ?></h3>
            <p><?= h(__('users.form_desc')) ?></p>
        </div>
    </div>
    <form method="post" action="index.php?page=users" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_user">
        <input type="hidden" name="return_page" value="users">
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <div class="col-md-4">
            <label class="form-label"><?= h(__('residents.lbl_name')) ?></label>
            <input class="form-control" name="full_name" value="<?= h($editing['full_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('auth.username')) ?></label>
            <input class="form-control" name="username" maxlength="80" value="<?= h($editing['username'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label"><?= h(__('users.lbl_role')) ?></label>
            <select class="form-select" name="role" id="userRole" required>
                <?php foreach (['admin' => role_label('admin'), 'kepala_desa' => role_label('kepala_desa'), 'penduduk' => role_label('penduduk')] as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= ($editing['role'] ?? '') === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= h(__('users.lbl_link_resident')) ?></label>
            <select class="form-select" name="resident_id">
                <option value=""><?= h(__('users.lbl_select_resident')) ?></option>
                <?php foreach ($residents as $resident): ?>
                    <?php 
                        $isLinked = $resident['linked_user_id'] !== null && (int)$resident['linked_user_id'] !== (int)($editing['id'] ?? 0); 
                        $isInactive = $resident['population_status'] !== 'active'; 
                    ?>
                    <option value="<?= (int) $resident['id'] ?>" <?= (string) ($editing['resident_id'] ?? '') === (string) $resident['id'] ? 'selected' : '' ?> <?= ($isLinked || $isInactive) ? 'disabled' : '' ?>>
                        <?= h($resident['full_name']) ?> (<?= h($resident['nik']) ?>)<?= $isInactive ? h(__('users.lbl_inactive_tag')) : ($isLinked ? h(__('users.lbl_has_account_tag')) : '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= $editing ? h(__('users.lbl_password_edit')) : h(__('users.lbl_password_new')) ?></label>
            <input class="form-control" name="password" type="password" <?= $editing ? '' : 'required minlength="10"' ?> autocomplete="new-password">
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><?= $editing ? h(__('users.btn_save_edit')) : h(__('users.btn_save_new')) ?></button>
            <?php if ($editing): ?>
                <a class="btn btn-light" href="index.php?page=users"><?= h(__('common.cancel')) ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="panel-card">
    <div class="panel-heading">
        <div>
            <h3><?= h(__('users.system_users_title')) ?></h3>
            <p><?= h(__('users.accounts_count', ['count' => count($users)])) ?></p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle data-table">
            <thead>
                <tr>
                    <th><?= h(__('users.th_user')) ?></th>
                    <th><?= h(__('auth.username')) ?></th>
                    <th><?= h(__('users.lbl_role')) ?></th>
                    <th><?= h(__('users.th_nik')) ?></th>
                    <th><?= h(__('common.status')) ?></th>
                    <th><?= h(__('common.action')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $account): ?>
                <tr>
                    <td><strong><?= h($account['full_name']) ?></strong></td>
                    <td><?= h($account['username']) ?></td>
                    <td><?= h(role_label($account['role'])) ?></td>
                    <td><?= $account['resident_name'] ? h($account['resident_name'] . ' · ' . $account['nik']) : '-' ?></td>
                    <td>
                        <span class="badge text-bg-<?= $account['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $account['is_active'] ? h(__('users.active_status')) : h(__('users.inactive_status')) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a class="btn btn-sm btn-light" href="index.php?page=users&edit=<?= (int) $account['id'] ?>" title="<?= h(__('common.edit')) ?>">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="index.php?page=users" data-confirm="<?= h(__('users.confirm_toggle')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_user">
                                <input type="hidden" name="return_page" value="users">
                                <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">
                                    <?= $account['is_active'] ? h(__('users.btn_deactivate')) : h(__('users.btn_activate')) ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
