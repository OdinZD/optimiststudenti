-- Optimist — dodavanje grupa "Kumite stariji" i "Kumite mlađi" (2026-10-11)
--
-- Kako pokrenuti na produkciji (nema terminala):
--   cPanel → phpMyAdmin → odaberi bazu lijevo → kartica "SQL" → zalijepi ovo → "Pokreni" (Go).
--
-- Dodaje samo dvije nove grupe (bez opisa). NE dira postojeće grupe ni raspored polaznika.
-- Sigurno je i za ponovno pokretanje (ON DUPLICATE KEY UPDATE po jedinstvenom slugu).

INSERT INTO training_groups (slug, name, description, created_at, updated_at) VALUES
  ('kumite-stariji', 'Kumite stariji', NULL, NOW(), NOW()),
  ('kumite-mladi',   'Kumite mlađi',   NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), updated_at = NOW();
