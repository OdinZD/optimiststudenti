-- Optimist — popisi za natjecanje: tablice competition_rosters i competition_roster_entries (2026-10-19)
--
-- Kako pokrenuti na produkciji (nema terminala):
--   cPanel → phpMyAdmin → odaberi bazu lijevo → kartica "SQL" → zalijepi ovo → "Pokreni" (Go).
-- Sigurno je pokrenuti više puta (CREATE TABLE IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS competition_rosters (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    event_date DATE NULL,                      -- datum natjecanja, neobavezan
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS competition_roster_entries (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    competition_roster_id BIGINT UNSIGNED NOT NULL,
    student_id            BIGINT UNSIGNED NOT NULL,
    discipline            VARCHAR(10) NULL,    -- kate | kumite | kata_team; NULL = još nije odabrano
    category              VARCHAR(60) NULL,
    note                  VARCHAR(255) NULL,
    confirmed             TINYINT(1) NOT NULL DEFAULT 0,
    created_at            TIMESTAMP NULL,
    updated_at            TIMESTAMP NULL,
    -- Jedinstvenost (popis, dijete, disciplina) provjerava aplikacija (NULL-ovi su u MariaDB-u međusobno različiti).
    KEY roster_entries_roster_student_index (competition_roster_id, student_id),
    CONSTRAINT competition_roster_entries_competition_roster_id_foreign
        FOREIGN KEY (competition_roster_id) REFERENCES competition_rosters (id) ON DELETE CASCADE,
    CONSTRAINT competition_roster_entries_student_id_foreign
        FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
