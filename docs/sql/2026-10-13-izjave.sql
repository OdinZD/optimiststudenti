-- Optimist — tablica za izjave roditelja (2026-10-13)
--
-- Pokreni na produkciji (nema terminala):
--   cPanel → phpMyAdmin → odaberi bazu → kartica "SQL" → zalijepi → "Pokreni" (Go).
-- Pokreni jednom.
--
-- Datoteke izjava NE idu u bazu — spremaju se enkriptirane u storage/app/private/izjave na serveru.
-- Ovdje su samo metapodaci (koje dijete, izvorni naziv, tip, veličina).

CREATE TABLE declarations (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id    BIGINT UNSIGNED NOT NULL,
    path          VARCHAR(255) NOT NULL,   -- enkriptirana datoteka na privatnom disku (uuid)
    original_name VARCHAR(255) NOT NULL,
    mime          VARCHAR(100) NOT NULL,
    size          INT UNSIGNED NOT NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    UNIQUE KEY declarations_student_id_unique (student_id),
    CONSTRAINT declarations_student_id_foreign
        FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
