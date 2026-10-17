-- Članarine — Faza U (usklađivanje s eRačuni smjerom). phpMyAdmin, JEDNOM. Bez destruktivnog (samo ADD COLUMN).
-- Odgovara migracijama 2026_10_18_1000xx (MariaDB, utf8mb4).

-- 1) club_billing_settings — predložak/podsjetnik + eRačuni vjerodajnice (šifrirane u aplikaciji)
ALTER TABLE `club_billing_settings`
    ADD COLUMN `invoice_line_template`   TEXT NULL,
    ADD COLUMN `reminder_days_after_due` SMALLINT UNSIGNED NULL,
    ADD COLUMN `eracuni_username`        TEXT NULL,
    ADD COLUMN `eracuni_secret_key`      TEXT NULL,
    ADD COLUMN `eracuni_token`           TEXT NULL,
    ADD COLUMN `eracuni_endpoint`        TEXT NULL,
    ADD COLUMN `eracuni_environment`     VARCHAR(12) NULL;

-- 2) student_fee_plans — način plaćanja + e-mail platitelja (postojeće dodjele ostaju NULL)
ALTER TABLE `student_fee_plans`
    ADD COLUMN `nacin_placanja` VARCHAR(12)  NULL AFTER `payer_name`,
    ADD COLUMN `payer_email`    VARCHAR(255) NULL AFTER `nacin_placanja`;

-- 3) charges — kopija načina plaćanja + eRačuni stanje računa
ALTER TABLE `charges`
    ADD COLUMN `nacin_placanja`         VARCHAR(12)  NULL AFTER `student_fee_plan_id`,
    ADD COLUMN `eracuni_invoice_number` VARCHAR(255) NULL AFTER `eracuni_invoice_id`,
    ADD COLUMN `invoice_status`         VARCHAR(10)  NOT NULL DEFAULT 'none' AFTER `eracuni_invoice_number`,
    ADD COLUMN `last_sent_at`           TIMESTAMP    NULL AFTER `invoice_status`;

-- Backfill nacin_placanja iz dodjele (JOIN UPDATE — MariaDB)
UPDATE `charges` c
    JOIN `student_fee_plans` s ON s.id = c.student_fee_plan_id
    SET c.nacin_placanja = s.nacin_placanja
    WHERE c.student_fee_plan_id IS NOT NULL;

-- 4) payments — tko je primio gotovinu (obavezno kad je method='cash')
ALTER TABLE `payments`
    ADD COLUMN `received_by` VARCHAR(255) NULL AFTER `recorded_by`;
