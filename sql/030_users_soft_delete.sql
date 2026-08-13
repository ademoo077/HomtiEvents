-- ============================================================
-- 030 — Suppression logique des comptes (soft delete)
--
-- Ajoute users.deleted_at pour archiver un compte sans détruire
-- son historique (participations, évaluations, notifications).
-- Le compte archivé ne peut plus se connecter (garde dans
-- AuthController::login()) et est exclu des listes admin.
-- Idempotent (ADD COLUMN IF NOT EXISTS, MariaDB 10.6+).
-- ============================================================

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER is_active;

CREATE INDEX IF NOT EXISTS idx_users_deleted_at ON users (deleted_at);
