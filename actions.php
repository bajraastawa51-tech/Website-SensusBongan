<?php
declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

function handle_action(PDO $pdo, string $action): void
{
    switch ($action) {
        case 'login':
            $username = required_value('username', 'Username', 80);
            $password = post_value('password', 200);
            $statement = $pdo->prepare('SELECT id, password_hash, approval_status, is_active FROM users WHERE username = ?');
            $statement->execute([$username]);
            $account = $statement->fetch();
            if (!$account || !password_verify($password, $account['password_hash'])) {
                throw new AppException('Username atau password tidak sesuai.');
            }
            if ((int) $account['is_active'] !== 1) {
                throw new AppException('Akun Anda dinonaktifkan oleh administrator desa.');
            }
            if ($account['approval_status'] !== 'approved') {
                throw new AppException('Akun penduduk Anda menunggu persetujuan admin desa sebelum dapat digunakan.');
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $account['id'];
            flash('success', 'Selamat datang kembali.');
            redirect_page('dashboard');

        case 'register_resident':
            $nik = required_value('nik', 'Kode penduduk', 16);
            $householdNumber = required_value('household_number', 'Kode keluarga', 16);
            if (!valid_resident_code($nik) || !valid_household_code($householdNumber)) {
                throw new AppException('Gunakan kode ilustrasi yang terdaftar, misalnya DEMO000000000001 dan KKDEMO0000000001. Jangan gunakan NIK atau nomor KK asli.');
            }

            $username = required_value('username', 'Username', 80);
            if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username)) {
                throw new AppException('Username harus terdiri dari 3–80 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.');
            }

            // Check duplicate username
            $statement = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $statement->execute([$username]);
            if ($statement->fetch()) {
                throw new AppException('Username "' . $username . '" sudah digunakan. Silakan gunakan username lain.');
            }

            $password = required_value('password', 'Password', 200);
            $confirmation = required_value('password_confirmation', 'Konfirmasi password', 200);
            if (strlen($password) < 10) {
                throw new AppException('Password minimal 10 karakter.');
            }
            if (!hash_equals($password, $confirmation)) {
                throw new AppException('Konfirmasi password tidak sama.');
            }

            $statement = $pdo->prepare('SELECT r.id, r.full_name, r.population_status FROM residents r JOIN households h ON h.id=r.household_id WHERE r.nik=? AND h.household_number=?');
            $statement->execute([$nik, $householdNumber]);
            $resident = $statement->fetch();
            if (!$resident) {
                throw new AppException('Kode penduduk dan kode keluarga tidak cocok dengan data contoh yang tersedia. Periksa kembali atau hubungi admin desa.');
            }
            if ($resident['population_status'] !== 'active') {
                throw new AppException('Data kependudukan berstatus tidak aktif sehingga tidak dapat didaftarkan akun.');
            }

            $statement = $pdo->prepare('SELECT id, approval_status FROM users WHERE resident_id=?');
            $statement->execute([(int) $resident['id']]);
            $existingAccount = $statement->fetch();
            if ($existingAccount) {
                if ($existingAccount['approval_status'] === 'pending') {
                    throw new AppException('Data penduduk ini sudah memiliki permohonan akun yang sedang menunggu persetujuan admin desa.');
                }
                throw new AppException('Data penduduk ini sudah terdaftar dan memiliki akun. Hubungi admin desa jika lupa akses.');
            }

            $statement = $pdo->prepare('INSERT INTO users (full_name, username, password_hash, role, resident_id, approval_status) VALUES (?, ?, ?, "penduduk", ?, "pending")');
            $statement->execute([
                $resident['full_name'],
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                (int) $resident['id'],
            ]);
            flash('success', 'Pendaftaran berhasil dikirim. Akun dapat digunakan setelah disetujui admin desa.');
            redirect_page('login');

        case 'setup_admin':
            if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() !== 0) {
                throw new AppException('Akun admin sudah pernah dibuat.');
            }
            $name = required_value('full_name', 'Nama lengkap');
            $username = required_value('username', 'Username', 80);
            if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username)) {
                throw new AppException('Username harus terdiri dari 3–80 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.');
            }
            $password = required_value('password', 'Password', 200);
            if (strlen($password) < 10) {
                throw new AppException('Password admin minimal 10 karakter.');
            }
            $statement = $pdo->prepare('INSERT INTO users (full_name, username, password_hash, role) VALUES (?, ?, ?, "admin")');
            $statement->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Akun admin berhasil dibuat. Silakan masuk.');
            redirect_page('login');

        case 'logout':
            require_role($pdo, 'admin', 'kepala_desa', 'penduduk');
            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'Anda telah keluar dari sistem.');
            redirect_page('login');

        case 'save_resident':
            require_role($pdo, 'admin');
            $id = post_id('id') ?? 0;
            $nik = required_value('nik', 'Kode penduduk', 16);
            if (!valid_resident_code($nik)) {
                throw new AppException('Kode penduduk harus memakai format ilustrasi DEMO diikuti 12 angka, bukan NIK asli.');
            }

            // Check duplicate NIK
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT id FROM residents WHERE nik = ? AND id != ?');
                $stmt->execute([$nik, $id]);
            } else {
                $stmt = $pdo->prepare('SELECT id FROM residents WHERE nik = ?');
                $stmt->execute([$nik]);
            }
            if ($stmt->fetch()) {
                throw new AppException('Kode penduduk (NIK) ' . $nik . ' sudah terdaftar untuk penduduk lain.');
            }

            $birthDate = valid_date(required_value('birth_date', 'Tanggal lahir'), 'Tanggal lahir');
            if ($birthDate > date('Y-m-d')) {
                throw new AppException('Tanggal lahir tidak boleh di masa depan.');
            }
            if ($birthDate < '1900-01-01') {
                throw new AppException('Tanggal lahir tidak realistis (minimal tahun 1900).');
            }

            $householdValue = post_value('household_id');
            $householdId = $householdValue === '' ? null : filter_var($householdValue, FILTER_VALIDATE_INT);
            if ($householdValue !== '' && !$householdId) {
                throw new AppException('Nomor KK yang dipilih tidak valid.');
            }
            if ($householdId !== null) {
                $stmt = $pdo->prepare('SELECT id, household_number FROM households WHERE id = ?');
                $stmt->execute([$householdId]);
                if (!$stmt->fetch()) {
                    throw new AppException('Data keluarga (KK) yang dipilih tidak ditemukan.');
                }
            }

            $fullName = required_value('full_name', 'Nama lengkap', 150);
            $birthPlace = post_value('birth_place', 100);
            $gender = required_value('gender', 'Jenis kelamin', 10);
            if (!in_array($gender, ['Laki-laki', 'Perempuan'], true)) {
                throw new AppException('Jenis kelamin tidak valid.');
            }
            $address = required_value('address', 'Alamat', 500);
            $religion = post_value('religion', 40);
            $maritalStatus = post_value('marital_status', 30);
            $occupation = post_value('occupation', 100);
            $education = post_value('education', 100);
            $familyRole = post_value('family_role', 40);
            $phone = post_value('phone', 30);
            $populationStatus = post_value('population_status', 10);
            if (!in_array($populationStatus, ['active', 'inactive'], true)) {
                $populationStatus = 'active';
            }

            $pdo->beginTransaction();

            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT nik, population_status FROM residents WHERE id = ? FOR UPDATE');
                $stmt->execute([$id]);
                $oldResident = $stmt->fetch();
                if (!$oldResident) {
                    $pdo->rollBack();
                    throw new AppException('Data penduduk tidak ditemukan.');
                }
                $oldNik = $oldResident['nik'];

                $statement = $pdo->prepare('UPDATE residents SET nik=?, full_name=?, birth_place=?, birth_date=?, gender=?, household_id=?, address=?, religion=?, marital_status=?, occupation=?, education=?, family_role=?, phone=?, population_status=? WHERE id=?');
                $statement->execute([
                    $nik, $fullName, $birthPlace, $birthDate, $gender, $householdId, $address,
                    $religion, $maritalStatus, $occupation, $education, $familyRole, $phone,
                    $populationStatus, $id
                ]);

                // Cascade NIK updates if changed
                if ($oldNik !== $nik) {
                    $pdo->prepare('UPDATE households SET head_nik = ? WHERE head_nik = ?')->execute([$nik, $oldNik]);
                    $pdo->prepare('UPDATE population_events SET nik = ? WHERE resident_id = ?')->execute([$nik, $id]);
                }

                // If status changed to inactive, deactivate user and clear head_nik
                if ($populationStatus === 'inactive') {
                    $pdo->prepare('UPDATE users SET is_active = 0 WHERE resident_id = ?')->execute([$id]);
                    $pdo->prepare('UPDATE households SET head_nik = NULL WHERE head_nik = ?')->execute([$nik]);
                }

                // If resident is designated as Kepala Keluarga in a household
                if ($householdId !== null && strcasecmp(trim($familyRole), 'Kepala Keluarga') === 0 && $populationStatus === 'active') {
                    $pdo->prepare('UPDATE households SET head_nik = ? WHERE id = ?')->execute([$nik, $householdId]);
                }

                flash('success', 'Data penduduk berhasil diperbarui.');
            } else {
                $statement = $pdo->prepare('INSERT INTO residents (nik, full_name, birth_place, birth_date, gender, household_id, address, religion, marital_status, occupation, education, family_role, phone, population_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $statement->execute([
                    $nik, $fullName, $birthPlace, $birthDate, $gender, $householdId, $address,
                    $religion, $maritalStatus, $occupation, $education, $familyRole, $phone,
                    $populationStatus
                ]);
                $newId = (int) $pdo->lastInsertId();

                if ($householdId !== null && strcasecmp(trim($familyRole), 'Kepala Keluarga') === 0 && $populationStatus === 'active') {
                    $pdo->prepare('UPDATE households SET head_nik = ? WHERE id = ?')->execute([$nik, $householdId]);
                }

                flash('success', 'Data penduduk berhasil ditambahkan.');
            }

            $pdo->commit();
            redirect_page('residents');

        case 'delete_resident':
            require_role($pdo, 'admin');
            $id = post_id('id');
            if (!$id) {
                throw new AppException('Data penduduk tidak valid.');
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT nik FROM residents WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            if (!$res) {
                $pdo->rollBack();
                throw new AppException('Data penduduk tidak ditemukan.');
            }

            // Clear household head pointer if this resident was the head
            $pdo->prepare('UPDATE households SET head_nik = NULL WHERE head_nik = ?')->execute([$res['nik']]);

            // Deactivate associated user account
            $pdo->prepare('UPDATE users SET is_active = 0 WHERE resident_id = ?')->execute([$id]);

            $statement = $pdo->prepare('DELETE FROM residents WHERE id = ?');
            $statement->execute([$id]);

            $pdo->commit();
            flash('success', 'Data penduduk berhasil dihapus. Akun pengguna terkait telah dinonaktifkan.');
            redirect_page('residents');

        case 'save_household':
            require_role($pdo, 'admin');
            $id = post_id('id') ?? 0;
            $number = required_value('household_number', 'Kode keluarga', 16);
            if (!valid_household_code($number)) {
                throw new AppException('Kode keluarga harus memakai format ilustrasi KKDEMO diikuti 10 angka, bukan nomor KK asli.');
            }

            // Check duplicate household number
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT id FROM households WHERE household_number = ? AND id != ?');
                $stmt->execute([$number, $id]);
            } else {
                $stmt = $pdo->prepare('SELECT id FROM households WHERE household_number = ?');
                $stmt->execute([$number]);
            }
            if ($stmt->fetch()) {
                throw new AppException('Kode keluarga ' . $number . ' sudah terdaftar.');
            }

            $head = post_value('head_nik', 16);
            $headResident = null;
            if ($head !== '') {
                if (!valid_resident_code($head)) {
                    throw new AppException('Kode kepala keluarga harus memakai format ilustrasi DEMO diikuti 12 angka.');
                }
                $stmt = $pdo->prepare('SELECT id, full_name, population_status FROM residents WHERE nik = ?');
                $stmt->execute([$head]);
                $headResident = $stmt->fetch();
                if (!$headResident) {
                    throw new AppException('Kode penduduk ' . $head . ' untuk kepala keluarga tidak ditemukan.');
                }
                if ($headResident['population_status'] !== 'active') {
                    throw new AppException('Penduduk dengan kode ' . $head . ' (' . $headResident['full_name'] . ') berstatus tidak aktif, tidak dapat dijadikan kepala keluarga.');
                }
                $stmt = $pdo->prepare('SELECT id, household_number FROM households WHERE head_nik = ? AND id != ?');
                $stmt->execute([$head, $id]);
                $otherHeadHH = $stmt->fetch();
                if ($otherHeadHH) {
                    throw new AppException('Penduduk ' . $head . ' (' . $headResident['full_name'] . ') sudah menjadi kepala keluarga di KK ' . $otherHeadHH['household_number'] . '.');
                }
            }

            $address = required_value('address', 'Alamat', 500);
            $rt = post_value('rt', 5);
            $rw = post_value('rw', 5);
            $headNikVal = $head !== '' ? $head : null;

            $pdo->beginTransaction();

            if ($id > 0) {
                $pdo->prepare('UPDATE households SET household_number=?, head_nik=?, address=?, rt=?, rw=? WHERE id=?')
                    ->execute([$number, $headNikVal, $address, $rt, $rw, $id]);
                $targetHouseholdId = $id;
                flash('success', 'Data keluarga berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO households (household_number, head_nik, address, rt, rw) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$number, $headNikVal, $address, $rt, $rw]);
                $targetHouseholdId = (int) $pdo->lastInsertId();
                flash('success', 'Data keluarga berhasil ditambahkan.');
            }

            // Sync head resident to this household
            if ($headResident !== null) {
                $pdo->prepare('UPDATE residents SET household_id = ?, family_role = "Kepala Keluarga" WHERE id = ?')
                    ->execute([$targetHouseholdId, (int) $headResident['id']]);
            }

            $pdo->commit();
            redirect_page('households');

        case 'delete_household':
            require_role($pdo, 'admin');
            $id = post_id('id');
            if (!$id) {
                throw new AppException('Data keluarga tidak valid.');
            }
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE residents SET family_role = NULL WHERE household_id = ? AND family_role = "Kepala Keluarga"')
                ->execute([$id]);
            $pdo->prepare('DELETE FROM households WHERE id=?')->execute([$id]);
            $pdo->commit();
            flash('success', 'Data keluarga berhasil dihapus. Data penduduknya tetap tersimpan.');
            redirect_page('households');

        case 'save_event':
            $user = require_role($pdo, 'admin', 'penduduk');
            $type = required_value('event_type', 'Jenis peristiwa', 20);
            $types = ['birth', 'death', 'move_in', 'move_out'];
            if (!in_array($type, $types, true)) {
                throw new AppException('Jenis peristiwa tidak valid.');
            }
            $eventDate = valid_date(required_value('event_date', 'Tanggal peristiwa'), 'Tanggal peristiwa');
            if ($eventDate > date('Y-m-d')) {
                throw new AppException('Tanggal peristiwa tidak boleh di masa depan.');
            }
            $nik = post_value('nik', 16);
            if ($nik !== '' && !valid_resident_code($nik)) {
                throw new AppException('Kode penduduk harus memakai format ilustrasi DEMO diikuti 12 angka.');
            }
            $personName = required_value('person_name', 'Nama terkait peristiwa', 150);
            $residentId = null;

            if ($user['role'] === 'penduduk') {
                if ($user['resident_id'] === null) {
                    throw new AppException('Akun Anda belum terhubung dengan data penduduk. Hubungi admin desa.');
                }
                if (in_array($type, ['death', 'move_out'], true) && $nik === '') {
                    throw new AppException('Kode penduduk yang meninggal atau pindah wajib diisi.');
                }
                if ($nik !== '') {
                    $statement = $pdo->prepare('SELECT target.id, target.full_name, target.population_status FROM residents target JOIN residents reporter ON reporter.id=? WHERE target.nik=? AND (target.id=reporter.id OR (reporter.household_id IS NOT NULL AND target.household_id=reporter.household_id))');
                    $statement->execute([(int) $user['resident_id'], $nik]);
                    $linkedResident = $statement->fetch();
                    if ($linkedResident === false && in_array($type, ['death', 'move_out'], true)) {
                        throw new AppException('Penduduk tersebut tidak ditemukan dalam keluarga akun Anda.');
                    }
                    if ($linkedResident !== false && in_array($type, ['death', 'move_out'], true) && $linkedResident['population_status'] !== 'active') {
                        throw new AppException('Penduduk tersebut sudah tidak aktif.');
                    }
                    $residentId = $linkedResident === false ? null : (int) $linkedResident['id'];
                }
            } else {
                $residentId = post_id('resident_id');
                if ($residentId === null && $nik !== '') {
                    $resCheck = $pdo->prepare('SELECT id, nik, full_name, population_status FROM residents WHERE nik=?');
                    $resCheck->execute([$nik]);
                    $foundRes = $resCheck->fetch();
                    if ($foundRes) {
                        $residentId = (int) $foundRes['id'];
                    }
                }
                if (in_array($type, ['death', 'move_out'], true) && $residentId === null) {
                    throw new AppException('Pilih penduduk terdata atau masukkan kode penduduk valid untuk laporan kematian atau pindah.');
                }
                if ($residentId !== null) {
                    $statement = $pdo->prepare('SELECT id, nik, full_name, population_status FROM residents WHERE id=?');
                    $statement->execute([$residentId]);
                    $selectedResident = $statement->fetch();
                    if (!$selectedResident || (in_array($type, ['death', 'move_out'], true) && $selectedResident['population_status'] !== 'active')) {
                        throw new AppException('Penduduk tidak ditemukan atau sudah tidak aktif.');
                    }
                    if ($nik === '') {
                        $nik = $selectedResident['nik'];
                    } elseif ($nik !== $selectedResident['nik']) {
                        throw new AppException('Kode penduduk tidak sesuai dengan data yang dipilih.');
                    }
                }
            }

            // Cegah duplikasi laporan yang sama persis (Input Data Berulang)
            $dupPending = $pdo->prepare('SELECT id FROM population_events WHERE event_type=? AND event_date=? AND person_name=? AND status="pending" AND submitted_by=?');
            $dupPending->execute([$type, $eventDate, $personName, (int) $user['id']]);
            if ($dupPending->fetch()) {
                throw new AppException('Laporan yang sama persis sedang menunggu verifikasi admin. Mohon tidak mengirim laporan berulang.');
            }

            // Validasi khusus peristiwa kematian: satu orang hanya bisa meninggal sekali
            if ($type === 'death') {
                if ($residentId !== null) {
                    $deathCheck = $pdo->prepare('SELECT id, status FROM population_events WHERE resident_id=? AND event_type="death" AND status IN ("pending", "verified")');
                    $deathCheck->execute([$residentId]);
                    $existingDeath = $deathCheck->fetch();
                    if ($existingDeath) {
                        throw new AppException($existingDeath['status'] === 'verified'
                            ? 'Laporan kematian untuk penduduk ini sudah pernah diverifikasi sebelumnya.'
                            : 'Laporan kematian untuk penduduk ini sudah ada dan sedang menunggu verifikasi.');
                    }
                } elseif ($nik !== '') {
                    $deathCheck = $pdo->prepare('SELECT id, status FROM population_events WHERE nik=? AND event_type="death" AND status IN ("pending", "verified")');
                    $deathCheck->execute([$nik]);
                    $existingDeath = $deathCheck->fetch();
                    if ($existingDeath) {
                        throw new AppException($existingDeath['status'] === 'verified'
                            ? 'Laporan kematian dengan kode penduduk ini sudah pernah diverifikasi.'
                            : 'Laporan kematian dengan kode penduduk ini sedang menunggu verifikasi.');
                    }
                }
            }

            // Validasi khusus peristiwa pindah keluar: cegah laporan ganda pending
            if ($type === 'move_out' && $residentId !== null) {
                $moveCheck = $pdo->prepare('SELECT id FROM population_events WHERE resident_id=? AND event_type="move_out" AND status="pending"');
                $moveCheck->execute([$residentId]);
                if ($moveCheck->fetch()) {
                    throw new AppException('Laporan kepindahan untuk penduduk ini sedang menunggu verifikasi.');
                }
            }

            $gender = post_value('gender', 10);
            if ($gender !== '' && !in_array($gender, ['Laki-laki', 'Perempuan'], true)) {
                throw new AppException('Jenis kelamin tidak valid.');
            }
            $statement = $pdo->prepare('INSERT INTO population_events (event_type, resident_id, person_name, nik, gender, event_date, address_from, address_to, details, status, submitted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", ?)');
            $statement->execute([
                $type,
                $residentId,
                $personName,
                $nik !== '' ? $nik : null,
                $gender !== '' ? $gender : null,
                $eventDate,
                post_value('address_from', 500),
                post_value('address_to', 500),
                required_value('details', 'Keterangan', 2000),
                (int) $user['id'],
            ]);
            flash('success', 'Laporan peristiwa berhasil dikirim dan menunggu verifikasi admin.');
            redirect_page('events');

        case 'review_event':
            require_role($pdo, 'admin');
            $id = post_id('id');
            $status = post_value('status', 20);
            if (!$id || !in_array($status, ['verified', 'rejected'], true)) {
                throw new AppException('Pilihan verifikasi tidak valid.');
            }
            $pdo->beginTransaction();
            $statement = $pdo->prepare('SELECT * FROM population_events WHERE id = ? FOR UPDATE');
            $statement->execute([$id]);
            $event = $statement->fetch();
            if (!$event || $event['status'] !== 'pending') {
                $pdo->rollBack();
                throw new AppException('Laporan tidak ditemukan atau sudah diproses.');
            }

            $targetResidentId = $event['resident_id'] !== null ? (int) $event['resident_id'] : null;
            $targetNik = $event['nik'];

            // Resolusi resident_id jika sebelumnya null tapi NIK tersedia
            if ($targetResidentId === null && $targetNik !== null && $targetNik !== '') {
                $resLookup = $pdo->prepare('SELECT id FROM residents WHERE nik = ?');
                $resLookup->execute([$targetNik]);
                $foundRes = $resLookup->fetch();
                if ($foundRes) {
                    $targetResidentId = (int) $foundRes['id'];
                }
            }

            $pdo->prepare('UPDATE population_events SET status=?, resident_id=COALESCE(?, resident_id), reviewed_by=?, reviewed_at=NOW() WHERE id=?')
                ->execute([$status, $targetResidentId, (int) current_user($pdo)['id'], $id]);

            if ($status === 'verified') {
                if ($targetResidentId !== null) {
                    if ($event['event_type'] === 'death' || $event['event_type'] === 'move_out') {
                        // Nonaktifkan status penduduk
                        $pdo->prepare('UPDATE residents SET population_status="inactive" WHERE id=?')->execute([$targetResidentId]);
                        // Nonaktifkan akun user terkait
                        $pdo->prepare('UPDATE users SET is_active=0 WHERE resident_id=?')->execute([$targetResidentId]);
                        // Hapus dari kepala keluarga jika terdaftar
                        $pdo->prepare('UPDATE households SET head_nik=NULL WHERE head_nik=(SELECT nik FROM residents WHERE id=?)')
                            ->execute([$targetResidentId]);
                    } elseif ($event['event_type'] === 'move_in') {
                        $pdo->prepare("UPDATE residents SET population_status='active', address=COALESCE(NULLIF(?, ''), address) WHERE id=?")
                            ->execute([$event['address_to'], $targetResidentId]);
                    }
                } elseif ($targetNik !== null && $targetNik !== '' && ($event['event_type'] === 'death' || $event['event_type'] === 'move_out')) {
                    $pdo->prepare('UPDATE households SET head_nik=NULL WHERE head_nik=?')->execute([$targetNik]);
                }
            }
            $pdo->commit();
            flash('success', $status === 'verified' ? 'Laporan berhasil diverifikasi.' : 'Laporan ditolak.');
            redirect_page('events');

        case 'save_user':
            require_role($pdo, 'admin');
            $id = post_id('id') ?? 0;
            $name = required_value('full_name', 'Nama lengkap', 150);
            $username = required_value('username', 'Username', 80);
            if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username)) {
                throw new AppException('Username harus terdiri dari 3–80 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.');
            }

            // Cek duplikasi username
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                $stmt->execute([$username, $id]);
            } else {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
                $stmt->execute([$username]);
            }
            if ($stmt->fetch()) {
                throw new AppException('Username "' . $username . '" sudah digunakan oleh akun lain.');
            }

            $role = required_value('role', 'Peran', 30);
            if (!in_array($role, ['admin', 'kepala_desa', 'penduduk'], true)) {
                throw new AppException('Peran pengguna tidak valid.');
            }
            $residentId = post_id('resident_id');
            if ($role === 'penduduk') {
                if ($residentId === null) {
                    throw new AppException('Akun penduduk wajib dihubungkan dengan data penduduk.');
                }
                // Pastikan penduduk aktif dan ada
                $stmt = $pdo->prepare('SELECT id, full_name, population_status FROM residents WHERE id = ?');
                $stmt->execute([$residentId]);
                $residentData = $stmt->fetch();
                if (!$residentData) {
                    throw new AppException('Data penduduk yang dipilih tidak ditemukan.');
                }
                if ($residentData['population_status'] !== 'active') {
                    throw new AppException('Data penduduk ' . $residentData['full_name'] . ' berstatus tidak aktif, tidak dapat dihubungkan ke akun.');
                }

                // Cek apakah penduduk sudah terhubung dengan akun lain
                if ($id > 0) {
                    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE resident_id = ? AND id != ?');
                    $stmt->execute([$residentId, $id]);
                } else {
                    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE resident_id = ?');
                    $stmt->execute([$residentId]);
                }
                $existingResidentUser = $stmt->fetch();
                if ($existingResidentUser) {
                    throw new AppException('Data penduduk ' . $residentData['full_name'] . ' sudah terhubung dengan akun pengguna "' . $existingResidentUser['username'] . '".');
                }
            }

            $password = post_value('password', 200);
            if ($id === 0 && strlen($password) < 10) {
                throw new AppException('Password minimal 10 karakter.');
            }
            if ($password !== '' && strlen($password) < 10) {
                throw new AppException('Password baru minimal 10 karakter.');
            }
            if ($id > 0) {
                if ($password === '') {
                    $pdo->prepare('UPDATE users SET full_name=?, username=?, role=?, resident_id=? WHERE id=?')
                        ->execute([$name, $username, $role, $role === 'penduduk' ? $residentId : null, $id]);
                } else {
                    $pdo->prepare('UPDATE users SET full_name=?, username=?, role=?, resident_id=?, password_hash=? WHERE id=?')
                        ->execute([$name, $username, $role, $role === 'penduduk' ? $residentId : null, password_hash($password, PASSWORD_DEFAULT), $id]);
                }
                flash('success', 'Akun pengguna berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO users (full_name, username, password_hash, role, resident_id) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role, $role === 'penduduk' ? $residentId : null]);
                flash('success', 'Akun pengguna berhasil dibuat.');
            }
            redirect_page('users');

        case 'toggle_user':
            $user = require_role($pdo, 'admin');
            $id = post_id('id');
            if (!$id || $id === (int) $user['id']) {
                throw new AppException('Akun sendiri tidak dapat dinonaktifkan.');
            }
            $target = $pdo->prepare('SELECT role, resident_id, is_active FROM users WHERE id=?');
            $target->execute([$id]);
            $targetUser = $target->fetch();
            if (!$targetUser) {
                throw new AppException('Akun pengguna tidak ditemukan.');
            }
            if ($targetUser['role'] === 'admin' && (int) $targetUser['is_active'] === 1
                && (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role="admin" AND is_active=1')->fetchColumn() <= 1) {
                throw new AppException('Admin aktif terakhir tidak dapat dinonaktifkan.');
            }
            if ($targetUser['role'] === 'penduduk' && (int) $targetUser['is_active'] === 0 && !empty($targetUser['resident_id'])) {
                $resStat = $pdo->prepare('SELECT population_status FROM residents WHERE id=?');
                $resStat->execute([(int) $targetUser['resident_id']]);
                if ($resStat->fetchColumn() !== 'active') {
                    throw new AppException('Akun tidak dapat diaktifkan karena data penduduk terkait berstatus tidak aktif.');
                }
            }
            $statement = $pdo->prepare('UPDATE users SET is_active = IF(is_active=1, 0, 1) WHERE id=?');
            $statement->execute([$id]);
            flash('success', 'Status akun berhasil diperbarui.');
            redirect_page('users');

        case 'approve_registration':
            require_role($pdo, 'admin');
            $id = post_id('id');
            if (!$id) {
                throw new AppException('Permohonan akun tidak valid.');
            }
            $checkReq = $pdo->prepare('SELECT u.id, u.resident_id, r.full_name, r.population_status FROM users u LEFT JOIN residents r ON r.id=u.resident_id WHERE u.id=? AND u.role="penduduk" AND u.approval_status="pending"');
            $checkReq->execute([$id]);
            $pendingReq = $checkReq->fetch();
            if (!$pendingReq) {
                throw new AppException('Permohonan akun tidak ditemukan atau sudah diproses.');
            }
            if ($pendingReq['resident_id'] !== null && $pendingReq['population_status'] !== 'active') {
                throw new AppException('Data penduduk ' . ($pendingReq['full_name'] ?? '') . ' berstatus tidak aktif. Permohonan tidak dapat disetujui.');
            }
            $statement = $pdo->prepare('UPDATE users SET approval_status="approved", is_active=1 WHERE id=? AND role="penduduk" AND approval_status="pending"');
            $statement->execute([$id]);
            if ($statement->rowCount() !== 1) {
                throw new AppException('Permohonan akun tidak ditemukan atau sudah diproses.');
            }
            flash('success', 'Akun penduduk disetujui. Penduduk sekarang dapat login.');
            redirect_page('users');

        case 'reject_registration':
            require_role($pdo, 'admin');
            $id = post_id('id');
            if (!$id) {
                throw new AppException('Permohonan akun tidak valid.');
            }
            $statement = $pdo->prepare('DELETE FROM users WHERE id=? AND role="penduduk" AND approval_status="pending"');
            $statement->execute([$id]);
            if ($statement->rowCount() !== 1) {
                throw new AppException('Permohonan akun tidak ditemukan atau sudah diproses.');
            }
            flash('success', 'Permohonan dihapus. Penduduk dapat mengirim pendaftaran baru.');
            redirect_page('users');

        case 'update_profile':
            $user = require_role($pdo, 'admin', 'kepala_desa', 'penduduk');

            $stUser = $pdo->prepare('SELECT id, full_name, username, password_hash, role, resident_id FROM users WHERE id = ?');
            $stUser->execute([(int) $user['id']]);
            $currentAccount = $stUser->fetch();
            if (!$currentAccount) {
                throw new AppException('Akun pengguna tidak ditemukan.');
            }

            $fullName = required_value('full_name', 'Nama lengkap', 150);
            $username = required_value('username', 'Username', 80);
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) {
                throw new AppException('Username hanya boleh berisi huruf, angka, titik, strip, dan underscore (3-80 karakter).');
            }

            $stDup = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $stDup->execute([$username, (int) $user['id']]);
            if ($stDup->fetch()) {
                throw new AppException('Username sudah digunakan oleh akun lain.');
            }

            $newPassword = post_value('new_password', 200);
            $confirmPassword = post_value('confirm_password', 200);
            $currentPassword = post_value('current_password', 200);
            $updatePasswordHash = null;

            if ($newPassword !== '') {
                if ($currentPassword === '') {
                    throw new AppException('Masukkan password saat ini untuk memverifikasi penggantian password.');
                }
                if (!password_verify($currentPassword, $currentAccount['password_hash'])) {
                    throw new AppException('Password saat ini tidak sesuai.');
                }
                if (strlen($newPassword) < 10) {
                    throw new AppException('Password baru minimal 10 karakter.');
                }
                if (!hash_equals($newPassword, $confirmPassword)) {
                    throw new AppException('Konfirmasi password baru tidak cocok.');
                }
                $updatePasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $updateResident = false;
            $birthDate = null;
            $gender = null;
            $birthPlace = null;
            $address = null;
            $phone = null;
            $religion = null;
            $maritalStatus = null;
            $occupation = null;
            $education = null;

            if ($currentAccount['resident_id'] !== null) {
                $birthDate = valid_date(required_value('birth_date', 'Tanggal lahir'), 'Tanggal lahir');
                if ($birthDate > date('Y-m-d')) {
                    throw new AppException('Tanggal lahir tidak boleh di masa depan.');
                }
                if ($birthDate < '1900-01-01') {
                    throw new AppException('Tanggal lahir tidak realistis (minimal tahun 1900).');
                }
                $gender = required_value('gender', 'Jenis kelamin', 10);
                if (!in_array($gender, ['Laki-laki', 'Perempuan'], true)) {
                    throw new AppException('Jenis kelamin tidak valid.');
                }

                $birthPlace = post_value('birth_place', 100);
                $address = required_value('address', 'Alamat', 500);
                $phone = post_value('phone', 30);
                $religion = post_value('religion', 40);
                $maritalStatus = post_value('marital_status', 30);
                $occupation = post_value('occupation', 100);
                $education = post_value('education', 100);
                $updateResident = true;
            }

            $pdo->beginTransaction();

            if ($updateResident) {
                $pdo->prepare('UPDATE residents SET full_name=?, birth_place=?, birth_date=?, gender=?, address=?, phone=?, religion=?, marital_status=?, occupation=?, education=? WHERE id=?')
                    ->execute([
                        $fullName, $birthPlace, $birthDate, $gender, $address, $phone,
                        $religion, $maritalStatus, $occupation, $education, (int) $currentAccount['resident_id'],
                    ]);
            }

            if ($updatePasswordHash !== null) {
                $pdo->prepare('UPDATE users SET full_name=?, username=?, password_hash=? WHERE id=?')
                    ->execute([$fullName, $username, $updatePasswordHash, (int) $user['id']]);
            } else {
                $pdo->prepare('UPDATE users SET full_name=?, username=? WHERE id=?')
                    ->execute([$fullName, $username, (int) $user['id']]);
            }

            $pdo->commit();
            flash('success', 'Profil dan akun berhasil diperbarui.');
            redirect_page('profile');

        case 'generate_report':
            require_role($pdo, 'admin');
            $month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT);
            $year = filter_var($_POST['year'] ?? null, FILTER_VALIDATE_INT);
            if (!$month || $month < 1 || $month > 12 || !$year || $year < 2000 || $year > 2100) {
                throw new AppException('Bulan atau tahun laporan tidak valid.');
            }
            if ($year > (int) date('Y') || ($year === (int) date('Y') && $month > (int) date('n'))) {
                throw new AppException('Laporan tidak dapat disusun untuk bulan yang akan datang.');
            }
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = (new DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
            $counts = [
                (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE population_status="active"')->fetchColumn(),
                (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE population_status="active" AND gender="Laki-laki"')->fetchColumn(),
                (int) $pdo->query('SELECT COUNT(*) FROM residents WHERE population_status="active" AND gender="Perempuan"')->fetchColumn(),
            ];
            $events = $pdo->prepare('SELECT event_type, COUNT(*) AS total FROM population_events WHERE status="verified" AND event_date >= ? AND event_date < ? GROUP BY event_type');
            $events->execute([$start, $end]);
            $eventCounts = array_column($events->fetchAll(), 'total', 'event_type');
            $values = [...$counts, (int) ($eventCounts['birth'] ?? 0), (int) ($eventCounts['death'] ?? 0), (int) ($eventCounts['move_in'] ?? 0), (int) ($eventCounts['move_out'] ?? 0)];
            $existing = $pdo->prepare('SELECT id, status FROM monthly_reports WHERE report_year=? AND report_month=?');
            $existing->execute([$year, $month]);
            $report = $existing->fetch();
            if ($report && $report['status'] === 'approved') {
                throw new AppException('Laporan yang sudah disetujui tidak dapat dibuat ulang.');
            }
            if ($report) {
                $pdo->prepare('UPDATE monthly_reports SET total_population=?, male_count=?, female_count=?, births=?, deaths=?, move_ins=?, move_outs=?, created_by=?, created_at=NOW() WHERE id=?')
                    ->execute([...$values, (int) current_user($pdo)['id'], $report['id']]);
            } else {
                $pdo->prepare('INSERT INTO monthly_reports (report_year, report_month, total_population, male_count, female_count, births, deaths, move_ins, move_outs, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$year, $month, ...$values, (int) current_user($pdo)['id']]);
            }
            flash('success', 'Laporan bulanan berhasil disusun.');
            header('Location: index.php?page=reports&month=' . $month . '&year=' . $year);
            exit;

        case 'approve_report':
            $user = require_role($pdo, 'kepala_desa');
            $id = post_id('id');
            if (!$id) {
                throw new AppException('Laporan tidak valid.');
            }
            $period = $pdo->prepare('SELECT report_month, report_year FROM monthly_reports WHERE id=?');
            $period->execute([$id]);
            $reportPeriod = $period->fetch();
            if (!$reportPeriod) {
                throw new AppException('Laporan tidak ditemukan.');
            }
            $statement = $pdo->prepare('UPDATE monthly_reports SET status="approved", approved_by=?, approved_at=NOW() WHERE id=? AND status="pending"');
            $statement->execute([(int) $user['id'], $id]);
            if ($statement->rowCount() !== 1) {
                throw new AppException('Laporan tidak ditemukan atau telah diproses.');
            }
            flash('success', 'Laporan bulanan telah disetujui.');
            header('Location: index.php?page=reports&month=' . (int) $reportPeriod['report_month'] . '&year=' . (int) $reportPeriod['report_year']);
            exit;
    }
}
