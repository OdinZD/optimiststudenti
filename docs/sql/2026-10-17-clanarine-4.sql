-- Članarine — Faza 4 (uplate + alokacije). Pokrenuti JEDNOM u phpMyAdmin na produkciji.
-- Odgovara migracijama 2026_10_17_100006 / 100007 (MariaDB, utf8mb4).

CREATE TABLE `payments` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`          BIGINT UNSIGNED NOT NULL,
    `amount_cents`        INT NOT NULL,                 -- signed: negativan za storno
    `paid_at`             DATE NOT NULL,
    `method`              VARCHAR(10) NOT NULL,         -- bank | cash | other
    `source`              VARCHAR(12) NOT NULL DEFAULT 'manual', -- manual | bank_import | eracuni
    `bank_reference`      VARCHAR(255) NULL,
    `note`                VARCHAR(255) NULL,
    `recorded_by`         BIGINT UNSIGNED NULL,
    `reversed_payment_id` BIGINT UNSIGNED NULL,         -- storno
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `payments_student_id_index` (`student_id`),
    CONSTRAINT `payments_student_id_foreign`          FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`),
    CONSTRAINT `payments_recorded_by_foreign`         FOREIGN KEY (`recorded_by`)         REFERENCES `users` (`id`)     ON DELETE SET NULL,
    CONSTRAINT `payments_reversed_payment_id_foreign` FOREIGN KEY (`reversed_payment_id`) REFERENCES `payments` (`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_allocations` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_id`   BIGINT UNSIGNED NOT NULL,
    `charge_id`    BIGINT UNSIGNED NOT NULL,
    `amount_cents` INT NOT NULL,                        -- signed
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `payment_allocations_charge_id_index` (`charge_id`),
    KEY `payment_allocations_payment_id_index` (`payment_id`),
    CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `payment_allocations_charge_id_foreign`  FOREIGN KEY (`charge_id`)  REFERENCES `charges` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
