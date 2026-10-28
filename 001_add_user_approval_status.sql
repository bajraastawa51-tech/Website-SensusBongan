ALTER TABLE users
    ADD COLUMN approval_status ENUM('pending', 'approved') NOT NULL DEFAULT 'approved' AFTER role,
    ADD KEY idx_users_role_active_approval (role, is_active, approval_status);
