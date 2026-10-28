CREATE DATABASE IF NOT EXISTS sensus_bongan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sensus_bongan;

CREATE TABLE IF NOT EXISTS households (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    household_number CHAR(16) NOT NULL COMMENT 'Kode ilustrasi KKDEMO + 10 angka; bukan nomor KK asli',
    head_nik CHAR(16) NULL COMMENT 'Kode ilustrasi DEMO + 12 angka; bukan NIK asli',
    address VARCHAR(500) NOT NULL,
    rt VARCHAR(5) NULL,
    rw VARCHAR(5) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_households_number (household_number),
    KEY idx_households_head_nik (head_nik),
    CONSTRAINT chk_households_demo_number CHECK (household_number REGEXP '^KKDEMO[0-9]{10}$'),
    CONSTRAINT chk_households_demo_head CHECK (head_nik IS NULL OR head_nik REGEXP '^DEMO[0-9]{12}$')
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS residents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nik CHAR(16) NOT NULL COMMENT 'Kode ilustrasi DEMO + 12 angka; bukan NIK asli',
    full_name VARCHAR(150) NOT NULL,
    birth_place VARCHAR(100) NULL,
    birth_date DATE NOT NULL,
    gender ENUM('Laki-laki', 'Perempuan') NOT NULL,
    household_id BIGINT UNSIGNED NULL,
    address VARCHAR(500) NOT NULL,
    religion VARCHAR(40) NULL,
    marital_status VARCHAR(30) NULL,
    occupation VARCHAR(100) NULL,
    education VARCHAR(100) NULL,
    family_role VARCHAR(40) NULL,
    phone VARCHAR(30) NULL,
    population_status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_residents_nik (nik),
    KEY idx_residents_name (full_name),
    KEY idx_residents_household (household_id),
    KEY idx_residents_status_gender (population_status, gender),
    CONSTRAINT chk_residents_demo_code CHECK (nik REGEXP '^DEMO[0-9]{12}$'),
    CONSTRAINT fk_residents_household FOREIGN KEY (household_id)
        REFERENCES households (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kepala_desa', 'penduduk') NOT NULL,
    approval_status ENUM('pending', 'approved') NOT NULL DEFAULT 'approved',
    resident_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_resident (resident_id),
    KEY idx_users_role_active_approval (role, is_active, approval_status),
    CONSTRAINT fk_users_resident FOREIGN KEY (resident_id)
        REFERENCES residents (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS population_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type ENUM('birth', 'death', 'move_in', 'move_out') NOT NULL,
    resident_id BIGINT UNSIGNED NULL,
    person_name VARCHAR(150) NOT NULL,
    nik CHAR(16) NULL COMMENT 'Kode ilustrasi DEMO + 12 angka; bukan NIK asli',
    gender ENUM('Laki-laki', 'Perempuan') NULL,
    event_date DATE NOT NULL,
    address_from VARCHAR(500) NULL,
    address_to VARCHAR(500) NULL,
    details TEXT NOT NULL,
    status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    submitted_by BIGINT UNSIGNED NOT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_events_type_status_date (event_type, status, event_date),
    KEY idx_events_resident (resident_id),
    KEY idx_events_submitter (submitted_by, created_at),
    CONSTRAINT chk_events_demo_code CHECK (nik IS NULL OR nik REGEXP '^DEMO[0-9]{12}$'),
    CONSTRAINT fk_events_resident FOREIGN KEY (resident_id)
        REFERENCES residents (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_events_submitter FOREIGN KEY (submitted_by)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_events_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS monthly_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    report_year SMALLINT UNSIGNED NOT NULL,
    report_month TINYINT UNSIGNED NOT NULL,
    total_population INT UNSIGNED NOT NULL DEFAULT 0,
    male_count INT UNSIGNED NOT NULL DEFAULT 0,
    female_count INT UNSIGNED NOT NULL DEFAULT 0,
    births INT UNSIGNED NOT NULL DEFAULT 0,
    deaths INT UNSIGNED NOT NULL DEFAULT 0,
    move_ins INT UNSIGNED NOT NULL DEFAULT 0,
    move_outs INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending',
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reports_period (report_year, report_month),
    KEY idx_reports_status_period (status, report_year, report_month),
    CONSTRAINT fk_reports_creator FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_reports_approver FOREIGN KEY (approved_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_reports_month CHECK (report_month BETWEEN 1 AND 12)
) ENGINE=InnoDB;
