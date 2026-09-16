-- ============================================================
-- ASIF SNOOKER CLUB - Member portal PIN
-- A 4-digit PIN (stored as a bcrypt hash) lets a customer log
-- into the public member portal by phone + PIN instead of the
-- unauthenticated phone lookup.
-- ============================================================

ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS portal_pin VARCHAR(255) NULL DEFAULT NULL AFTER whatsapp;