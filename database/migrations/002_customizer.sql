-- ============================================================
-- ASIF SNOOKER CLUB - Customizer (roles + themes)
-- Adds per-user theme preference and the club accent color.
-- ============================================================

ALTER TABLE users
    ADD COLUMN theme ENUM('dark','light','auto') NOT NULL DEFAULT 'dark'
    AFTER status;

INSERT INTO settings (`key`, value, `group`)
VALUES ('accent_color', '#10b981', 'appearance')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);