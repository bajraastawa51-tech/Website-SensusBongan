<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$stUser = $pdo->prepare('SELECT id, full_name, username, role, approval_status, resident_id, created_at FROM users WHERE id = ?');
$stUser->execute([(int) $user['id']]);
$account = $stUser->fetch() ?: $user;

$resident = null;
if ($account['resident_id'] !== null) {
    $stRes = $pdo->prepare('SELECT r.*, h.household_number FROM residents r LEFT JOIN households h ON h.id = r.household_id WHERE r.id = ?');
    $stRes->execute([(int) $account['resident_id']]);
    $resident = $stRes->fetch() ?: null;
}
?>

<div class="row g-3">
    <!-- Left Column: User Profile Card -->
    <div class="col-lg-4">
        <div class="profile-card">
            <span class="profile-avatar"><?= h(mb_strtoupper(mb_substr($account['full_name'], 0, 1))) ?></span>
            <h2><?= h($account['full_name']) ?></h2>
            <p class="text-muted mb-2">@<?= h($account['username']) ?></p>
            <span class="badge text-bg-success mb-3"><?= h(role_label($account['role'])) ?></span>
            
            <hr>
            
            <?php if ($resident !== null): ?>
                <div class="profile-detail">
                    <small><?= h(__('profile.nik_code')) ?></small>
                    <strong><?= h($resident['nik']) ?></strong>
                </div>
                <div class="profile-detail">
                    <small><?= h(__('profile.hh_code')) ?></small>
                    <strong><?= h($resident['household_number'] ?? __('residents.lbl_kk_empty')) ?></strong>
                </div>
                <div class="profile-detail">
                    <small><?= h(__('profile.family_role')) ?></small>
                    <strong><?= h(family_role_label($resident['family_role'] ?? 'Penduduk')) ?></strong>
                </div>
                <div class="profile-detail">
                    <small><?= h(__('profile.birth_date')) ?></small>
                    <strong><?= h((new DateTimeImmutable($resident['birth_date']))->format(current_lang() === 'en' ? 'M d, Y' : 'd/m/Y')) ?></strong>
                </div>
                <div class="profile-detail">
                    <small><?= h(__('profile.gender')) ?></small>
                    <strong><?= $resident['gender'] === 'Laki-laki' ? h(__('common.male')) : h(__('common.female')) ?></strong>
                </div>
            <?php else: ?>
                <div class="profile-detail">
                    <small><?= h(__('profile.access_rights')) ?></small>
                    <strong><?= h(role_label($account['role'])) ?></strong>
                </div>
                <div class="profile-detail">
                    <small><?= h(__('profile.account_status')) ?></small>
                    <strong class="text-success"><i class="bi bi-check-circle-fill me-1"></i><?= h(__('common.active')) ?></strong>
                </div>
                <div class="profile-detail">
                    <small><?= h(__('profile.registered_since')) ?></small>
                    <strong><?= h((new DateTimeImmutable($account['created_at']))->format(current_lang() === 'en' ? 'M d, Y' : 'd/m/Y')) ?></strong>
                </div>
                <div class="profile-detail">
                    <small><?= h(__('profile.notes')) ?></small>
                    <strong><?= $account['role'] === 'admin' ? h(__('profile.admin_desc')) : ($account['role'] === 'kepala_desa' ? h(__('profile.head_desc')) : h(__('profile.user_desc'))) ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Edit Profile Form -->
    <div class="col-lg-8">
        <div class="panel-card">
            <div class="panel-heading">
                <div>
                    <h3><?= h(__('profile.title')) ?></h3>
                    <p><?= h(__('profile.subtitle')) ?></p>
                </div>
            </div>

            <form method="post" action="index.php?page=profile" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="return_page" value="profile">

                <!-- Section 1: Account Information -->
                <div class="col-12">
                    <h6 class="fw-bold text-success mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge"></i> <?= h(__('profile.sec_account')) ?>
                    </h6>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= h(__('residents.lbl_name')) ?> <span class="required">*</span></label>
                    <input class="form-control" name="full_name" maxlength="150" value="<?= h($account['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= h(__('auth.username')) ?> <span class="required">*</span></label>
                    <input class="form-control" name="username" maxlength="80" value="<?= h($account['username']) ?>" required>
                </div>

                <!-- Section 2: Population / Resident Data -->
                <?php if ($resident !== null): ?>
                    <div class="col-12 mt-4">
                        <h6 class="fw-bold text-success mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-person-vcard"></i> <?= h(__('profile.sec_resident')) ?>
                        </h6>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= h(__('residents.lbl_birth_place')) ?></label>
                        <input class="form-control" name="birth_place" maxlength="100" value="<?= h($resident['birth_place'] ?? '') ?>" placeholder="<?= h(__('profile.birth_place_placeholder')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= h(__('residents.lbl_birth_date')) ?> <span class="required">*</span></label>
                        <input class="form-control" type="date" name="birth_date" value="<?= h($resident['birth_date']) ?>" max="<?= h(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= h(__('residents.lbl_gender')) ?> <span class="required">*</span></label>
                        <select class="form-select" name="gender" required>
                            <option value="Laki-laki" <?= $resident['gender'] === 'Laki-laki' ? 'selected' : '' ?>><?= h(__('common.male')) ?></option>
                            <option value="Perempuan" <?= $resident['gender'] === 'Perempuan' ? 'selected' : '' ?>><?= h(__('common.female')) ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= h(__('residents.lbl_phone')) ?></label>
                        <input class="form-control" name="phone" maxlength="30" value="<?= h($resident['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= h(__('residents.lbl_address')) ?> <span class="required">*</span></label>
                        <textarea class="form-control" name="address" rows="2" maxlength="500" required><?= h($resident['address']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= h(__('residents.lbl_religion')) ?></label>
                        <input class="form-control" name="religion" maxlength="40" value="<?= h($resident['religion'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= h(__('residents.lbl_marital')) ?></label>
                        <input class="form-control" name="marital_status" maxlength="30" value="<?= h($resident['marital_status'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= h(__('residents.lbl_occupation')) ?></label>
                        <input class="form-control" name="occupation" maxlength="100" value="<?= h($resident['occupation'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= h(__('residents.lbl_education')) ?></label>
                        <input class="form-control" name="education" maxlength="100" value="<?= h($resident['education'] ?? '') ?>">
                    </div>
                <?php else: ?>
                    <div class="col-12 mt-2">
                        <div class="alert alert-light border d-flex align-items-center gap-2 small mb-0 py-2 px-3">
                            <i class="bi bi-info-circle text-success fs-5"></i>
                            <span><?= h(__('profile.unlinked_notice', ['role' => role_label($account['role'])])) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section 3: Password Change (Optional) -->
                <div class="col-12 mt-4">
                    <h6 class="fw-bold text-success mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock"></i> <?= h(__('profile.sec_password')) ?>
                    </h6>
                    <p class="text-muted small mb-3"><?= h(__('profile.password_hint')) ?></p>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= h(__('profile.current_password')) ?></label>
                    <input class="form-control" type="password" name="current_password" autocomplete="current-password" placeholder="<?= h(__('profile.current_password_placeholder')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= h(__('profile.new_password')) ?></label>
                    <input class="form-control" type="password" name="new_password" autocomplete="new-password" minlength="10" placeholder="<?= h(__('profile.new_password_placeholder')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= h(__('profile.confirm_password')) ?></label>
                    <input class="form-control" type="password" name="confirm_password" autocomplete="new-password" minlength="10" placeholder="<?= h(__('profile.confirm_password_placeholder')) ?>">
                </div>

                <!-- Submit Button -->
                <div class="col-12 mt-4 pt-3 border-top d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check2-circle me-1"></i><?= h(__('profile.btn_save')) ?>
                    </button>
                    <a href="index.php?page=dashboard" class="btn btn-outline-secondary">
                        <?= h(__('common.cancel')) ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
