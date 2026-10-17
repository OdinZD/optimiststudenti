-- Članarine — Faza 2 (tarife + dodjela). Pokrenuti JEDNOM u phpMyAdmin na produkciji.
-- Odgovara migracijama 2026_10_17_100003 / 100004 (MariaDB, utf8mb4).

-- 1) Tarife
CREATE TABLE `fee_plans` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(255) NOT NULL,
    `amount_cents` INT NOT NULL,                 -- iznos u centima (EUR)
    `type`         VARCHAR(10) NOT NULL,         -- monthly | yearly | one_time
    `active`       TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Dodjela tarife djetetu (s popustom: postotak ILI fiksno, ne oba)
CREATE TABLE `student_fee_plans` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`       BIGINT UNSIGNED NOT NULL,
    `fee_plan_id`      BIGINT UNSIGNED NOT NULL,
    `discount_percent` TINYINT UNSIGNED NULL,
    `discount_cents`   INT NULL,
    `valid_from`       DATE NOT NULL,
    `valid_until`      DATE NULL,                 -- NULL = aktivno
    `payer_name`       VARCHAR(150) NULL,
    `created_by`       BIGINT UNSIGNED NULL,
    `created_at`       TIMESTAMP NULL,
    `updated_at`       TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `student_fee_plans_student_id_valid_from_index` (`student_id`, `valid_from`),
    CONSTRAINT `student_fee_plans_student_id_foreign`  FOREIGN KEY (`student_id`)  REFERENCES `students` (`id`)  ON DELETE CASCADE,
    CONSTRAINT `student_fee_plans_fee_plan_id_foreign` FOREIGN KEY (`fee_plan_id`) REFERENCES `fee_plans` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `student_fee_plans_created_by_foreign`  FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
