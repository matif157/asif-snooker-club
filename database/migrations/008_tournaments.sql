-- ============================================================
-- ASIF SNOOKER CLUB - Tournament manager
-- Single-elimination knockout tournaments with seeded bracket,
-- linked to registered club customers.
-- ============================================================

CREATE TABLE IF NOT EXISTS tournaments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(160) NOT NULL,
    type          ENUM('single_elimination') NOT NULL DEFAULT 'single_elimination',
    entry_fee     DECIMAL(10,2) NOT NULL DEFAULT 0,
    prize_details VARCHAR(500) NULL DEFAULT NULL,
    best_of       TINYINT UNSIGNED NOT NULL DEFAULT 3,
    start_date    DATE NULL DEFAULT NULL,
    end_date      DATE NULL DEFAULT NULL,
    status        ENUM('draft','open','in_progress','completed','cancelled') NOT NULL DEFAULT 'draft',
    champion_id   INT UNSIGNED NULL DEFAULT NULL,
    notes         TEXT NULL DEFAULT NULL,
    created_by    INT UNSIGNED NULL DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tournament_status (status),
    CONSTRAINT fk_tourn_champ FOREIGN KEY (champion_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tournament_players (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tournament_id INT UNSIGNED NOT NULL,
    customer_id   INT UNSIGNED NULL DEFAULT NULL,
    name          VARCHAR(120) NOT NULL,
    phone         VARCHAR(20) NULL DEFAULT NULL,
    seed          INT UNSIGNED NOT NULL DEFAULT 0,
    status        ENUM('registered','withdrew','active','champion') NOT NULL DEFAULT 'registered',
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tournament_customer (tournament_id, customer_id),
    KEY idx_tp_status (status),
    CONSTRAINT fk_tp_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_tp_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tournament_matches (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tournament_id  INT UNSIGNED NOT NULL,
    round_num      INT UNSIGNED NOT NULL,
    match_no       INT UNSIGNED NOT NULL,
    table_id       INT UNSIGNED NULL DEFAULT NULL,
    player_home_id INT UNSIGNED NULL DEFAULT NULL,
    player_away_id INT UNSIGNED NULL DEFAULT NULL,
    score_home     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    score_away     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    winner_id      INT UNSIGNED NULL DEFAULT NULL,
    status         ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    scheduled_at   DATETIME NULL DEFAULT NULL,
    notes          VARCHAR(255) NULL DEFAULT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tm (tournament_id, round_num, match_no),
    KEY idx_tm_round (tournament_id, round_num),
    CONSTRAINT fk_tm_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_tm_table FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, description) VALUES
('tournaments.view',   'View tournaments & brackets'),
('tournaments.manage', 'Create tournaments, register players, record results')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO role_permissions (role, permission_id)
SELECT 'owner', id FROM permissions WHERE name IN ('tournaments.view','tournaments.manage') AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp WHERE rp.role = 'owner' AND rp.permission_id = permissions.id
);

INSERT INTO role_permissions (role, permission_id)
SELECT 'admin', id FROM permissions WHERE name IN ('tournaments.view','tournaments.manage') AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp WHERE rp.role = 'admin' AND rp.permission_id = permissions.id
);

INSERT INTO role_permissions (role, permission_id)
SELECT 'eco', id FROM permissions WHERE name IN ('tournaments.view','tournaments.manage') AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp WHERE rp.role = 'eco' AND rp.permission_id = permissions.id
);

INSERT INTO role_permissions (role, permission_id)
SELECT 'counter', id FROM permissions WHERE name = 'tournaments.view' AND NOT EXISTS (
    SELECT 1 FROM role_permissions rp WHERE rp.role = 'counter' AND rp.permission_id = permissions.id
);