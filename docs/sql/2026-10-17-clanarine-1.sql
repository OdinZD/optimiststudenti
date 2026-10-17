-- Članarine — Faza 1 (temelji). Pokrenuti JEDNOM u phpMyAdmin na produkciji.
-- Odgovara migracijama 2026_10_17_1000* (MariaDB, utf8mb4).

-- 1) Uloga na korisnicima. Default 'trener' → postojeći računi i ponašanje nepromijenjeni.
ALTER TABLE `users`
    ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'trener' AFTER `password`;

-- 2) Postavke naplate kluba (podaci primatelja). Prave vrijednosti (IBAN, model,
--    šifra namjene, rok, mjeseci naplate, pravilo poziva na broj) popuniti prije pokretanja modula.
CREATE TABLE `club_billing_settings` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `club_name`      VARCHAR(255) NULL,
    `address`        VARCHAR(255) NULL,
    `iban`           VARCHAR(34)  NULL,
    `model`          VARCHAR(7)   NULL,
    `reference_rule` VARCHAR(100) NULL,
    `purpose_code`   VARCHAR(4)   NULL,
    `due_day`        TINYINT UNSIGNED NULL,
    `billing_months` JSON NULL,
    `currency`       CHAR(3) NOT NULL DEFAULT 'EUR',
    `note`           VARCHAR(255) NULL,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Audit log (dopisni; tko, kada, staro → novo). Bez updated_at.
CREATE TABLE `audit_logs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NULL,
    `action`         VARCHAR(255) NOT NULL,
    `auditable_type` VARCHAR(255) NULL,
    `auditable_id`   BIGINT UNSIGNED NULL,
    `old_values`     JSON NULL,
    `new_values`     JSON NULL,
    `ip`             VARCHAR(45) NULL,
    `created_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`, `auditable_id`),
    KEY `audit_logs_user_id_index` (`user_id`),
    KEY `audit_logs_created_at_index` (`created_at`),
    CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
