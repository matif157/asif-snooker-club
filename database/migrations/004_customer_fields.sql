-- ============================================================
-- ASIF SNOOKER CLUB - Custom customer fields
-- Five optional per-customer fields. Labels are configured in
-- Settings (custom_field_1_label .. custom_field_5_label).
-- ============================================================

ALTER TABLE customers
    ADD COLUMN cf_1 VARCHAR(191) NULL DEFAULT NULL AFTER notes,
    ADD COLUMN cf_2 VARCHAR(191) NULL DEFAULT NULL AFTER cf_1,
    ADD COLUMN cf_3 VARCHAR(191) NULL DEFAULT NULL AFTER cf_2,
    ADD COLUMN cf_4 VARCHAR(191) NULL DEFAULT NULL AFTER cf_3,
    ADD COLUMN cf_5 VARCHAR(191) NULL DEFAULT NULL AFTER cf_4;

INSERT INTO settings (`key`, value, `group`) VALUES
('custom_field_1_label', 'Member Since',   'customers'),
('custom_field_2_label', 'Nickname',       'customers'),
('custom_field_3_label', '',               'customers'),
('custom_field_4_label', '',               'customers'),
('custom_field_5_label', '',               'customers')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `group` = VALUES(`group`);