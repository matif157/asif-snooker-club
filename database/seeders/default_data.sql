-- ============================================================
-- ASIF SNOOKER CLUB - Default Seed Data
-- NOTE: Admin password is set via install script (argon2id hash)
-- Placeholder password below is replaced at install time.
-- ============================================================

-- Default permissions (RBAC core)
INSERT INTO permissions (name, description) VALUES
('dashboard.view',          'View the dashboard'),
('tables.manage',           'Create, edit, delete snooker tables'),
('tables.view',             'View the table command center'),
('sessions.manage',         'Start, pause, end sessions'),
('sessions.view',           'View sessions'),
('customers.manage',        'Create, edit customers'),
('customers.view',          'View customers'),
('bookings.manage',         'Create, update, cancel bookings'),
('bookings.view',           'View bookings'),
('payments.manage',         'Record payments'),
('payments.view',           'View payments'),
('expenses.manage',         'Add, edit, approve expenses'),
('expenses.view',           'View expenses'),
('reports.view',            'View reports'),
('settings.manage',         'Manage system settings'),
('staff.manage',            'Manage staff users'),
('audit.view',              'View audit log'),
('finance.view',            'View financial data');

-- owner gets all permissions
INSERT INTO role_permissions (role, permission_id)
SELECT 'owner', id FROM permissions;

INSERT INTO role_permissions (role, permission_id)
SELECT 'admin', id FROM permissions;

-- eco: operations & finance
INSERT INTO role_permissions (role, permission_id)
SELECT 'eco', id FROM permissions
WHERE name IN ('dashboard.view','tables.view','sessions.manage','sessions.view','customers.manage','customers.view','bookings.manage','bookings.view','payments.manage','payments.view','expenses.manage','expenses.view','reports.view','finance.view');

-- counter: day-to-day operations
INSERT INTO role_permissions (role, permission_id)
SELECT 'counter', id FROM permissions
WHERE name IN ('dashboard.view','tables.view','sessions.manage','sessions.view','customers.manage','customers.view','bookings.manage','bookings.view','payments.manage','payments.view');

-- staff: view + limited
INSERT INTO role_permissions (role, permission_id)
SELECT 'staff', id FROM permissions
WHERE name IN ('dashboard.view','tables.view','sessions.view');

-- auditor: read-only
INSERT INTO role_permissions (role, permission_id)
SELECT 'auditor', id FROM permissions
WHERE name IN ('reports.view','finance.view','audit.view','payments.view','expenses.view');

-- Default settings
INSERT INTO settings (`key`, value, `group`) VALUES
('club_name',            'Asif Snooker Club',   'club'),
('club_phone',           '+923001234567',       'club'),
('club_address',         'D Ground, Faisalabad, Punjab, Pakistan', 'club'),
('currency',             'Rs',                  'general'),
('timezone',             'Asia/Karachi',        'general'),
('default_hourly_rate',  '300',                 'pricing'),
('default_min_charge',   '100',                 'pricing'),
('business_hours_open',  '12:00',               'general'),
('business_hours_close', '02:00',               'general'),
('peak_enabled',         '1',                   'pricing'),
('peak_start',           '19:00',               'pricing'),
('peak_end',             '00:00',               'pricing'),
('peak_rate_multiplier', '1.0',                 'pricing'),
('night_start',          '00:00',               'pricing'),
('night_end',            '06:00',               'pricing'),
('whatsapp_template',    'Assalam o Alaikum {name}! Thank you for visiting Asif Snooker Club.', 'notifications'),
('close_table_bookings', '0',                   'bookings');

-- Default tables (club starts clean; comment out if you prefer to add via UI)
INSERT INTO tables (number, name, type, hourly_rate, min_charge, status, sort_order) VALUES
('01', 'Table 01', 'Standard', 300, 100, 'available', 1),
('02', 'Table 02', 'Standard', 300, 100, 'available', 2),
('03', 'Table 03', 'Standard', 300, 100, 'available', 3),
('04', 'Table 04', 'Standard', 300, 100, 'available', 4),
('05', 'Table 05', 'VIP',      400, 150, 'available', 5),
('06', 'Table 06', 'VIP',      400, 150, 'available', 6);