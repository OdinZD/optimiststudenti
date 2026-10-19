-- Optimist — više grupa po djetetu + termini treninga (2026-10-20)
--
-- Kako pokrenuti na produkciji (nema terminala):
--   cPanel → phpMyAdmin → odaberi bazu lijevo → kartica "SQL" → zalijepi ovo → "Pokreni" (Go).
-- Samo dodavanje (ništa se ne briše). Može se pokrenuti više puta: tablice su IF NOT EXISTS, a umetanja INSERT IGNORE.
-- students.location_id i students.training_group_id ostaju nepromijenjeni (više se ne uređuju u aplikaciji).

-- Termin = lokacija + dani + vrijeme.
CREATE TABLE IF NOT EXISTS training_slots (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(255) NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    days        VARCHAR(30) NOT NULL,
    starts_at   TIME NOT NULL,
    ends_at     TIME NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    UNIQUE KEY training_slots_slug_unique (slug),
    CONSTRAINT training_slots_location_id_foreign
        FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Koje grupe treniraju u kojem terminu (dijetetovi termini se izvode iz njegovih grupa).
CREATE TABLE IF NOT EXISTS group_training_slot (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    training_group_id BIGINT UNSIGNED NOT NULL,
    training_slot_id  BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY group_training_slot_training_group_id_training_slot_id_unique (training_group_id, training_slot_id),
    CONSTRAINT group_training_slot_training_group_id_foreign
        FOREIGN KEY (training_group_id) REFERENCES training_groups (id) ON DELETE CASCADE,
    CONSTRAINT group_training_slot_training_slot_id_foreign
        FOREIGN KEY (training_slot_id) REFERENCES training_slots (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dijete može biti u više grupa.
CREATE TABLE IF NOT EXISTS student_training_group (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id        BIGINT UNSIGNED NOT NULL,
    training_group_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY student_training_group_student_id_training_group_id_unique (student_id, training_group_id),
    CONSTRAINT student_training_group_student_id_foreign
        FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT student_training_group_training_group_id_foreign
        FOREIGN KEY (training_group_id) REFERENCES training_groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lokacija Višnjik više ne nosi termin u nazivu (termini su sada zasebni).
UPDATE locations SET name = 'PS Višnjik', updated_at = NOW() WHERE slug = 'visnjik-pon-sri';

-- Termini.
INSERT IGNORE INTO training_slots (slug, location_id, days, starts_at, ends_at, sort_order, created_at, updated_at)
SELECT 'krstica-uto-cet', id, 'Uto/Čet', '17:00:00', '18:00:00', 0, NOW(), NOW() FROM locations WHERE slug = 'os-k-krstica';
INSERT IGNORE INTO training_slots (slug, location_id, days, starts_at, ends_at, sort_order, created_at, updated_at)
SELECT 'stanovi-uto-cet', id, 'Uto/Čet', '20:00:00', '21:00:00', 1, NOW(), NOW() FROM locations WHERE slug = 'os-stanovi';
INSERT IGNORE INTO training_slots (slug, location_id, days, starts_at, ends_at, sort_order, created_at, updated_at)
SELECT 'visnjik-pon', id, 'Pon', '20:00:00', '21:00:00', 2, NOW(), NOW() FROM locations WHERE slug = 'visnjik-pon-sri';
INSERT IGNORE INTO training_slots (slug, location_id, days, starts_at, ends_at, sort_order, created_at, updated_at)
SELECT 'visnjik-sri', id, 'Sri', '20:00:00', '21:00:00', 3, NOW(), NOW() FROM locations WHERE slug = 'visnjik-pon-sri';

-- Grupe po terminu (7 veza).
INSERT IGNORE INTO group_training_slot (training_group_id, training_slot_id)
SELECT g.id, s.id FROM training_groups g JOIN training_slots s ON
       (s.slug = 'krstica-uto-cet' AND g.slug IN ('pocetnici'))
    OR (s.slug = 'stanovi-uto-cet' AND g.slug IN ('kata-napredni', 'kumite-stariji'))
    OR (s.slug = 'visnjik-pon'     AND g.slug IN ('stariji-pocetnici', 'kumite-stariji'))
    OR (s.slug = 'visnjik-sri'     AND g.slug IN ('stariji-pocetnici', 'kumite-mladi'));

-- Dosadašnja jedna grupa po djetetu prelazi u vezu više-na-više.
INSERT IGNORE INTO student_training_group (student_id, training_group_id)
SELECT id, training_group_id FROM students WHERE training_group_id IS NOT NULL;

-- Provjera: očekuje se 4 termina i 7 veza.
-- SELECT (SELECT COUNT(*) FROM training_slots) AS termini, (SELECT COUNT(*) FROM group_training_slot) AS veze;
