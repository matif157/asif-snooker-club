-- ============================================================
-- ASIF SNOOKER CLUB - Per-table camera binding
-- A camera can be assigned to a table so the command-center tile
-- shows a live-camera shortcut and CCTV is grouped by table.
-- ============================================================

ALTER TABLE cameras
    ADD COLUMN IF NOT EXISTS table_id INT UNSIGNED NULL DEFAULT NULL AFTER stream_name,
    ADD KEY IF NOT EXISTS idx_cam_table (table_id);

ALTER TABLE cameras
    ADD CONSTRAINT fk_cam_table FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL;