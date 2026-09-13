-- =====================================================================
-- 020_finance2 — Phase 18: consent-based report sharing and climate-loss
-- evidence for insurance/lender packs.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `report_share_links` (
  `id`             VARCHAR(40)  NOT NULL,
  `farm_id`        VARCHAR(40)  NOT NULL,
  `pack_type`      VARCHAR(20)  NOT NULL,   -- loan|buyer|audit|insurance
  `token_hash`     CHAR(64)     NOT NULL,   -- HMAC of the plaintext token (Support\Token), never the token itself
  `created_by_id`  VARCHAR(40)  NOT NULL,
  `expires_at`     DATETIME     NOT NULL,
  `revoked_at`     DATETIME     NULL,
  `last_viewed_at` DATETIME     NULL,
  `view_count`     INT          NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rsl_hash` (`token_hash`),
  KEY `idx_rsl_farm` (`farm_id`),
  CONSTRAINT `fk_rsl_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rsl_user` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `climate_events` (
  `id`                     VARCHAR(40)   NOT NULL,
  `farm_id`                VARCHAR(40)   NOT NULL,
  `event_type`             VARCHAR(20)   NOT NULL, -- drought|flood|wind|frost|hail|other
  `start_date`             DATE          NOT NULL,
  `end_date`               DATE          NULL,
  `description`            VARCHAR(500)  NULL,
  `estimated_loss_amount`  DECIMAL(14,2) NULL,
  `affected_crop_field_id` VARCHAR(40)   NULL,
  `created_by_id`          VARCHAR(40)   NOT NULL,
  `created_at`             DATETIME      NOT NULL,
  `updated_at`              DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ce_farm` (`farm_id`, `start_date`),
  KEY `idx_ce_crop_field` (`affected_crop_field_id`),
  CONSTRAINT `fk_ce_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ce_crop_field` FOREIGN KEY (`affected_crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ce_user` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
