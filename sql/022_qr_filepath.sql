-- ── 022 : Chemin du fichier QR (Option 1 du cahier des charges) ───────────────
-- Le QR est identifié de façon unique par qr_event.token_qr (UUID v4, déjà
-- présent) : c'est le "uuid" immuable de l'événement → partage & check-in
-- sécurisés sans énumération. On ajoute en complément le chemin du fichier
-- PNG rendu sur disque (public/qr/event_{id}.png) pour l'affichage/print.

ALTER TABLE evenements
    ADD COLUMN IF NOT EXISTS qr_code_path VARCHAR(255) NULL COMMENT 'Chemin du PNG QR (public/qr/event_{id}.png)';

CREATE INDEX IF NOT EXISTS idx_evenements_qr_code_path ON evenements (qr_code_path);
