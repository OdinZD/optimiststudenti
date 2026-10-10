-- Optimist — nove tablice za natjecanja i rezultate (2026-10-10)
--
-- Pokreni na produkciji (nema terminala):
--   cPanel → phpMyAdmin → odaberi bazu lijevo → kartica "SQL" → zalijepi → "Pokreni" (Go).
-- Pokreni jednom. (Ako tablice već postoje, prvo ih treba obrisati — ali normalno ne postoje.)

CREATE TABLE competitions (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    held_on    DATE NOT NULL,
    city       VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX competitions_held_on_index (held_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competition_results (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id     BIGINT UNSIGNED NOT NULL,
    competition_id BIGINT UNSIGNED NOT NULL,
    discipline     VARCHAR(10) NOT NULL,   -- kate | kumite
    category       VARCHAR(60) NULL,
    place          TINYINT NULL,           -- plasman; medalja se računa iz plasmana (1/2/3)
    wins           TINYINT NULL,
    losses         TINYINT NULL,
    bouts          TINYINT NULL,
    note           VARCHAR(255) NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    INDEX competition_results_student_id_index (student_id),
    INDEX competition_results_competition_id_index (competition_id),
    CONSTRAINT competition_results_student_id_foreign
        FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT competition_results_competition_id_foreign
        FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
