<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$residentId = $user['resident_id'] === null ? null : (int) $user['resident_id'];
$family = [];
$householdNumber = null;
$address = null;

if ($residentId !== null) {
    $statement = $pdo->prepare(
        'SELECT member.id, member.nik, member.full_name, member.gender, member.birth_date, member.family_role,
                household.household_number, household.address
         FROM residents AS account_resident
         LEFT JOIN households AS household ON household.id = account_resident.household_id
         LEFT JOIN residents AS member
             ON member.household_id = account_resident.household_id
             AND member.population_status = "active"
         WHERE account_resident.id = ?
             AND account_resident.population_status = "active"
         ORDER BY CASE WHEN member.id = account_resident.id THEN 0 ELSE 1 END, member.full_name'
    );
    $statement->execute([$residentId]);
    $family = $statement->fetchAll();

    if ($family !== []) {
        $householdNumber = $family[0]['household_number'];
        $address = $family[0]['address'];
    }
}
?>
<div class="panel-card">
    <div class="panel-heading">
        <div>
            <h3><?= h(__('family.title')) ?></h3>
            <p><?= h(__('family.desc')) ?></p>
        </div>
    </div>
    <?php if ($residentId === null): ?>
        <div class="alert alert-warning mb-0"><?= h(__('family.not_linked_alert')) ?></div>
    <?php elseif ($family === []): ?>
        <div class="empty-state p-5"><?= h(__('family.empty_alert')) ?></div>
    <?php else: ?>
        <div class="family-summary">
            <span><?= h(__('family.code')) ?><strong><?= h($householdNumber ?? __('residents.lbl_kk_empty')) ?></strong></span>
            <span><?= h(__('family.address')) ?><strong><?= h($address ?? __('common.not_available')) ?></strong></span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle data-table">
                <thead>
                    <tr>
                        <th><?= h(__('family.th_nik')) ?></th>
                        <th><?= h(__('family.th_name')) ?></th>
                        <th><?= h(__('family.th_role')) ?></th>
                        <th><?= h(__('family.th_gender')) ?></th>
                        <th><?= h(__('family.th_birth_date')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($family as $member): ?>
                        <tr>
                            <td><code><?= h($member['nik']) ?></code></td>
                            <td>
                                <strong><?= h($member['full_name']) ?></strong>
                                <?php if ((int) $member['id'] === $residentId): ?><small><?= h(__('family.my_account')) ?></small><?php endif; ?>
                            </td>
                            <td><?= h(family_role_label($member['family_role'])) ?></td>
                            <td><?= $member['gender'] === 'Laki-laki' ? h(__('common.male')) : h(__('common.female')) ?></td>
                            <td><?= h((new DateTimeImmutable($member['birth_date']))->format(current_lang() === 'en' ? 'M d, Y' : 'd/m/Y')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
