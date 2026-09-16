-- ------------------------------------------------------------
-- Reminders & WhatsApp notifications (engine v1)
-- Installs: dedup columns + reminder settings
-- ------------------------------------------------------------

ALTER TABLE customers ADD COLUMN last_reminded_at DATETIME DEFAULT NULL AFTER notes;
ALTER TABLE bookings  ADD COLUMN reminder_sent_at DATETIME DEFAULT NULL AFTER status;

INSERT INTO settings (`key`, value, `group`) VALUES
('reminder_enabled',              '1', 'notifications'),
('reminder_horizon_min',          '120', 'notifications'),
('booking_reminder_template',     'Hi {name}! Just a friendly reminder: your snooker booking at {club} is today at {time} on Table {table}. See you there!', 'notifications'),
('outstanding_reminder_template', 'Hi {name}! A gentle reminder from {club} that you have an outstanding balance of {currency} {amount}. Please settle at your convenience. Thank you!', 'notifications')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);