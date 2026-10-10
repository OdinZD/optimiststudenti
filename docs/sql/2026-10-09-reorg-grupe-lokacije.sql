-- Optimist — reorganizacija grupa i lokacija (2026-10-09)
--
-- Kako pokrenuti na produkciji (nema terminala):
--   cPanel → phpMyAdmin → odaberi bazu lijevo → kartica "SQL" → zalijepi ovo → "Pokreni" (Go).
--
-- !! POKRENI SAMO JEDNOM. !!  Drugo pokretanje bi ponovno obrisalo grupe i poništilo
-- raspoređivanje polaznika koje si u međuvremenu napravio.

START TRANSACTION;

-- 1) Svim polaznicima makni grupu (sam ćeš ih rasporediti u aplikaciji).
UPDATE students SET training_group_id = NULL;

-- 2) Obriši sve stare grupe i dodaj dvije nove.
DELETE FROM training_groups;
INSERT INTO training_groups (slug, name, description, created_at, updated_at) VALUES
  ('pocetnici',         'Početnici',         '6–8 godina',      NOW(), NOW()),
  ('stariji-pocetnici', 'Stariji početnici', '8 i više godina', NOW(), NOW());

-- 3) Lokacije: makni "Višnjik · četvrtak" (polaznici ondje ostaju bez lokacije), dodaj "OŠ K. Krstića".
DELETE FROM locations WHERE slug = 'visnjik-cetvrtak';
INSERT INTO locations (slug, name, created_at, updated_at) VALUES
  ('os-k-krstica', 'OŠ K. Krstića', NOW(), NOW());

COMMIT;
