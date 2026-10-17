-- Članarine — Faza 3 (zaduženja). Pokrenuti JEDNOM u phpMyAdmin na produkciji.
-- Odgovara migraciji 2026_10_17_100005 (MariaDB, utf8mb4).

CREATE TABLE `charges` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`          BIGINT UNSIGNED NOT NULL,
    `fee_plan_id`         BIGINT UNSIGNED NOT NULL,
    `student_fee_plan_id` BIGINT UNSIGNED NULL,
    `period`              VARCHAR(7) NOT NULL,          -- 'YYYY-MM'
    `amount_cents`        INT NOT NULL,                 -- već s popustom
    `due_date`            DATE NULL,
    `reference`           VARCHAR(40) NULL,             -- poziv na broj
    `written_off_at`      TIMESTAMP NULL,
    `written_off_by`      BIGINT UNSIGNED NULL,
    `written_off_reason`  VARCHAR(255) NULL,
    `eracuni_invoice_id`  VARCHAR(255) NULL,            -- za kasniju integraciju s eRačunima
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `charges_student_id_fee_plan_id_period_unique` (`student_id`, `fee_plan_id`, `period`),
    KEY `charges_period_index` (`period`),
    CONSTRAINT `charges_student_id_foreign`          FOREIGN KEY (`student_id`)          REFERENCES `students` (`id`),
    CONSTRAINT `charges_fee_plan_id_foreign`         FOREIGN KEY (`fee_plan_id`)         REFERENCES `fee_plans` (`id`),
    CONSTRAINT `charges_student_fee_plan_id_foreign` FOREIGN KEY (`student_fee_plan_id`) REFERENCES `student_fee_plans` (`id`) ON DELETE SET NULL,
    CONSTRAINT `charges_written_off_by_foreign`      FOREIGN KEY (`written_off_by`)      REFERENCES `users` (`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
