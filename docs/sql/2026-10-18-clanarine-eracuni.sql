-- Faza 4 (Članarine): eRačuni — API log + veze partnera (mock klijent zasad).
-- Pokrenuti jednom u phpMyAdmin. Samo dodavanje tablica (ništa destruktivno).

CREATE TABLE IF NOT EXISTS `eracuni_api_log` (
  `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `method`      VARCHAR(60)      NOT NULL,
  `ok`          TINYINT(1)       NOT NULL DEFAULT 0,
  `http_status` SMALLINT UNSIGNED NULL,
  `request`     JSON             NULL,  -- bez username/secretKey/token
  `response`    TEXT             NULL,  -- skraćeno
  `error`       TEXT             NULL,
  `duration_ms` INT UNSIGNED     NULL,
  `created_at`  TIMESTAMP        NULL,
  PRIMARY KEY (`id`),
  KEY `eracuni_api_log_method_index` (`method`),
  KEY `eracuni_api_log_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `eracuni_partner_links` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payer_email`          VARCHAR(255)    NOT NULL,
  `payer_name`           VARCHAR(255)    NULL,
  `oib`                  VARCHAR(20)     NULL,
  `eracuni_partner_id`   VARCHAR(255)    NOT NULL,
  `eracuni_partner_name` VARCHAR(255)    NULL,
  `linked_by`            VARCHAR(255)    NULL,
  `confirmed_at`         TIMESTAMP       NULL,
  `created_at`           TIMESTAMP       NULL,
  `updated_at`           TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eracuni_partner_links_payer_email_unique` (`payer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
