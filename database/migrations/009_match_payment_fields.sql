-- ============================================================
-- ASIF SNOOKER CLUB - Match / client / loan fields
-- Adds winner+loser capture, primary client, planned end time,
-- fixed-charge support, and an explicit loan (udhaar) marker to
-- sessions. Mirrors winner/loser + amount + method on bookings.
-- ============================================================

SET NAMES utf8mb4;

-- ── Sessions ────────────────────────────────────────────────
ALTER TABLE sessions
    ADD COLUMN IF NOT EXISTS player_winner      VARCHAR(120) NULL DEFAULT NULL AFTER customer_id,
    ADD COLUMN IF NOT EXISTS player_loser       VARCHAR(120) NULL DEFAULT NULL AFTER player_winner,
    ADD COLUMN IF NOT EXISTS client_name        VARCHAR(160) NULL DEFAULT NULL AFTER player_loser,
    ADD COLUMN IF NOT EXISTS expected_end_time  DATETIME     NULL DEFAULT NULL AFTER end_time,
    ADD COLUMN IF NOT EXISTS charge_type        ENUM('timer','fixed') NOT NULL DEFAULT 'timer' AFTER rate_type,
    ADD COLUMN IF NOT EXISTS fixed_amount       DECIMAL(12,2) NULL DEFAULT NULL AFTER charge_type,
    ADD COLUMN IF NOT EXISTS is_loan            TINYINT(1)   NOT NULL DEFAULT 0 AFTER payment_method,
    ADD COLUMN IF NOT EXISTS loan_amount        DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER is_loan;

ALTER TABLE sessions
    ADD KEY IF NOT EXISTS idx_sess_loan (is_loan);

-- ── Bookings ────────────────────────────────────────────────
ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS player_winner  VARCHAR(120) NULL DEFAULT NULL AFTER customer_phone,
    ADD COLUMN IF NOT EXISTS player_loser   VARCHAR(120) NULL DEFAULT NULL AFTER player_winner,
    ADD COLUMN IF NOT EXISTS amount         DECIMAL(12,2) NULL DEFAULT NULL AFTER players_count,
    ADD COLUMN IF NOT EXISTS payment_method VARCHAR(20)  NULL DEFAULT NULL AFTER amount;

-- Backfill: mark pre-existing unpaid/partial sessions with a linked
-- customer as loans so the outstanding ledger stays consistent.
UPDATE sessions
   SET is_loan = 1,
       loan_amount = GREATEST(0, amount - COALESCE((
           SELECT SUM(p.amount) FROM payments p
            WHERE p.session_id = sessions.id AND p.status = 'paid'
       ), 0))
 WHERE customer_id IS NOT NULL
   AND payment_status IN ('unpaid','partial')
   AND status = 'completed'
   AND amount > 0;
