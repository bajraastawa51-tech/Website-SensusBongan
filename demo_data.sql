USE sensus_bongan;

INSERT INTO households (household_number, head_nik, address, rt, rw)
VALUES ('KKDEMO0000000001', 'DEMO000000000001', 'Alamat Fiktif 01, Desa Bongan', '001', '001')
ON DUPLICATE KEY UPDATE household_number = VALUES(household_number);

INSERT IGNORE INTO residents
    (nik, full_name, birth_place, birth_date, gender, household_id, address, religion, marital_status, occupation, education, family_role)
SELECT
    'DEMO000000000001',
    'Andi Contoh',
    'Bongan',
    '1990-01-01',
    'Laki-laki',
    id,
    'Alamat Fiktif 01, Desa Bongan',
    'Lainnya',
    'Belum Kawin',
    'Contoh',
    'Contoh',
    'Kepala Keluarga'
FROM households
WHERE household_number = 'KKDEMO0000000001';

INSERT INTO households (household_number, head_nik, address, rt, rw)
VALUES ('KKDEMO0000000002', 'DEMO000000000003', 'Alamat Fiktif 02, Desa Bongan', '002', '001')
ON DUPLICATE KEY UPDATE household_number = VALUES(household_number);

INSERT IGNORE INTO residents
    (nik, full_name, birth_place, birth_date, gender, household_id, address, religion, marital_status, occupation, education, family_role)
SELECT
    'DEMO000000000002',
    'Siti Contoh',
    'Bongan',
    '1992-05-15',
    'Perempuan',
    id,
    'Alamat Fiktif 01, Desa Bongan',
    'Lainnya',
    'Belum Kawin',
    'Contoh',
    'Contoh',
    'Anggota Keluarga'
FROM households
WHERE household_number = 'KKDEMO0000000001';

INSERT IGNORE INTO residents
    (nik, full_name, birth_place, birth_date, gender, household_id, address, religion, marital_status, occupation, education, family_role)
SELECT
    'DEMO000000000003',
    'Budi Contoh',
    'Bongan',
    '1985-09-20',
    'Laki-laki',
    id,
    'Alamat Fiktif 02, Desa Bongan',
    'Lainnya',
    'Kawin',
    'Contoh',
    'Contoh',
    'Kepala Keluarga'
FROM households
WHERE household_number = 'KKDEMO0000000002';

INSERT INTO users (full_name, username, password_hash, role, approval_status, resident_id)
VALUES
    ('Admin Demo', 'demo.admin', '$2y$10$B56vL.9ZHkbE6kbT4at0oeCxXIXJvHVtDBf.qBRCbAD0KZQQsMuW.', 'admin', 'approved', NULL),
    ('Kepala Desa Demo', 'demo.kepala', '$2y$10$Y6In.//FLOqw2XhcxVrjBeBqt6U3BUtZiYyIQLxNQjb0z3HSVxwn2', 'kepala_desa', 'approved', NULL),
    ('Andi Contoh', 'demo.penduduk', '$2y$10$ys6W4aWi03vn5ISc5NDo6unSTHtwf2axy4LSmP5VZUaQE3wu3.uQK', 'penduduk', 'approved', (SELECT id FROM residents WHERE nik = 'DEMO000000000001')),
    ('Budi Contoh', 'demo.penduduk2', '$2y$10$FkUFtjt6jtxvgld0p8cTVunM01p9qVRJR4DG4IVooq7EH6xxZITKW', 'penduduk', 'approved', (SELECT id FROM residents WHERE nik = 'DEMO000000000003'))
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    approval_status = 'approved',
    resident_id = VALUES(resident_id),
    is_active = 1;

INSERT INTO population_events
    (event_type, person_name, gender, event_date, details, status, submitted_by, reviewed_by, reviewed_at)
SELECT
    'birth',
    'Bayi Contoh',
    'Perempuan',
    CURRENT_DATE,
    'Laporan kelahiran untuk demonstrasi.',
    'pending',
    (SELECT id FROM users WHERE username = 'demo.penduduk'),
    NULL,
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM population_events
    WHERE person_name = 'Bayi Contoh' AND details = 'Laporan kelahiran untuk demonstrasi.'
);

INSERT INTO population_events
    (event_type, person_name, nik, gender, event_date, address_to, details, status, submitted_by, reviewed_by, reviewed_at)
SELECT
    'move_in',
    'Siti Contoh',
    'DEMO000000000002',
    'Perempuan',
    CURRENT_DATE,
    'Alamat Fiktif 01, Desa Bongan',
    'Laporan mutasi datang untuk demonstrasi.',
    'verified',
    (SELECT id FROM users WHERE username = 'demo.admin'),
    (SELECT id FROM users WHERE username = 'demo.admin'),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM population_events
    WHERE person_name = 'Siti Contoh' AND details = 'Laporan mutasi datang untuk demonstrasi.'
);
