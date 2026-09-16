-- ============================================================
-- ASIF SNOOKER CLUB - CCTV camera registry
-- Cameras stream through a local media server (go2rtc or
-- mediamtx). rtsp_url is the source camera; stream_name is the
-- media-server stream id shown in the live grid.
-- ============================================================

CREATE TABLE IF NOT EXISTS cameras (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    location    VARCHAR(190) NULL DEFAULT NULL,
    rtsp_url    VARCHAR(500) NULL DEFAULT NULL,
    stream_name VARCHAR(80)  NULL DEFAULT NULL,
    enabled     TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order  INT          NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_stream_name (stream_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (`key`, value, `group`) VALUES
('cctv_server_url', 'http://127.0.0.1:1984', 'cctv')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `group` = VALUES(`group`);

INSERT INTO permissions (name, description) VALUES
('cctv.view',   'View the live CCTV grid'),
('cctv.manage', 'Add, edit, remove CCTV cameras')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO role_permissions (role, permission_id)
SELECT 'owner', id FROM permissions WHERE name IN ('cctv.view','cctv.manage') AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp WHERE rp.role = 'owner' AND rp.permission_id = permissions.id
);

INSERT INTO role_permissions (role, permission_id)
SELECT 'admin', id FROM permissions WHERE name IN ('cctv.view','cctv.manage') AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp WHERE rp.role = 'admin' AND rp.permission_id = permissions.id
);

INSERT INTO role_permissions (role, permission_id)
SELECT 'eco', id FROM permissions WHERE name = 'cctv.view' AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp WHERE rp.role = 'eco' AND rp.permission_id = permissions.id
);

INSERT INTO role_permissions (role, permission_id)
SELECT 'counter', id FROM permissions WHERE name = 'cctv.view' AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp WHERE rp.role = 'counter' AND rp.permission_id = permissions.id
);