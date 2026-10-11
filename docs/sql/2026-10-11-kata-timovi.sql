-- Optimist — kata timovi (nova tablica + veza polaznik↔tim) i uklanjanje stare grupe "Kata timovi" (2026-10-11)
--
-- Kako pokrenuti na produkciji (nema terminala):
--   cPanel → phpMyAdmin → odaberi bazu lijevo → kartica "SQL" → zalijepi ovo → "Pokreni" (Go).
-- Pokreni jednom. (Ako tablice već postoje, prvo ih treba obrisati — ali normalno ne postoje.)

CREATE TABLE kata_teams (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(60) NOT NULL,
    color      VARCHAR(7) NOT NULL DEFAULT '#2D3E7B',  -- #RRGGBB, boja oznake na popisu
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kata_team_student (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    kata_team_id BIGINT UNSIGNED NOT NULL,
    student_id   BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY kata_team_student_unique (kata_team_id, student_id),
    CONSTRAINT kata_team_student_team_foreign
        FOREIGN KEY (kata_team_id) REFERENCES kata_teams (id) ON DELETE CASCADE,
    CONSTRAINT kata_team_student_student_foreign
        FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stara obična grupa "Kata timovi" više ne treba (zamijenjena konceptom timova).
-- Polaznicima koji su bili u njoj training_group_id postaje NULL (nullOnDelete) — ostaju u aplikaciji.
DELETE FROM training_groups WHERE slug = 'kata-timovi';
