-- =====================================================================
-- 021_cooperative — Phase 19 (MVP): cooperative/group layer above farms.
--
-- Scoped deliberately: member registry, a shared join code (no async
-- approval workflow — simplest thing that lets several farms actually
-- form a group), a contribution ledger, and collective sales with
-- per-member quantity/amount splits. Group inventory/production is a
-- read-only rollup over each member farm's *existing* tables — no new
-- storage for that, see Services\CooperativeStats.
--
-- Deferred to a later phase: shared-equipment booking calendars,
-- automated NGO/lender report generation, bulk input ordering.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `cooperatives` (
  `id`            VARCHAR(40)  NOT NULL,
  `name`          VARCHAR(160) NOT NULL,
  `region`        VARCHAR(120) NULL,
  `join_code`     VARCHAR(12)  NOT NULL,
  `created_by_id` VARCHAR(40)  NOT NULL,
  `created_at`    DATETIME     NOT NULL,
  `updated_at`    DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_coop_join_code` (`join_code`),
  CONSTRAINT `fk_coop_user` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cooperative_members` (
  `id`             VARCHAR(40)  NOT NULL,
  `cooperative_id` VARCHAR(40)  NOT NULL,
  `farm_id`        VARCHAR(40)  NOT NULL,
  `role`           VARCHAR(20)  NOT NULL DEFAULT 'member', -- chair|secretary|treasurer|member
  `joined_at`      DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cm_coop_farm` (`cooperative_id`, `farm_id`),
  KEY `idx_cm_farm` (`farm_id`),
  CONSTRAINT `fk_cm_coop` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cm_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cooperative_contributions` (
  `id`             VARCHAR(40)   NOT NULL,
  `cooperative_id` VARCHAR(40)   NOT NULL,
  `farm_id`        VARCHAR(40)   NOT NULL,
  `amount`         DECIMAL(14,2) NOT NULL DEFAULT 0,
  `date`           DATE          NOT NULL,
  `type`           VARCHAR(40)   NOT NULL DEFAULT 'membership', -- membership|input_fund|equipment_fund|other
  `notes`          VARCHAR(500)  NULL,
  `created_by_id`  VARCHAR(40)   NOT NULL,
  `created_at`     DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cc_coop` (`cooperative_id`),
  KEY `idx_cc_farm` (`farm_id`),
  CONSTRAINT `fk_cc_coop` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cc_user` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cooperative_sales` (
  `id`             VARCHAR(40)   NOT NULL,
  `cooperative_id` VARCHAR(40)   NOT NULL,
  `crop_name`      VARCHAR(120)  NOT NULL,
  `total_quantity` DECIMAL(14,3) NOT NULL DEFAULT 0,
  `unit`           VARCHAR(20)   NOT NULL DEFAULT 'kg',
  `price_per_unit` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_amount`   DECIMAL(14,2) NOT NULL DEFAULT 0,
  `buyer_name`     VARCHAR(160)  NULL,
  `sale_date`      DATE          NOT NULL,
  `notes`          VARCHAR(500)  NULL,
  `created_by_id`  VARCHAR(40)   NOT NULL,
  `created_at`     DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cs_coop` (`cooperative_id`),
  CONSTRAINT `fk_cs_coop` FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_user` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cooperative_sale_splits` (
  `id`                   VARCHAR(40)   NOT NULL,
  `cooperative_sale_id`  VARCHAR(40)   NOT NULL,
  `farm_id`              VARCHAR(40)   NOT NULL,
  `quantity`             DECIMAL(14,3) NOT NULL DEFAULT 0,
  `amount`               DECIMAL(14,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_css_sale` (`cooperative_sale_id`),
  KEY `idx_css_farm` (`farm_id`),
  CONSTRAINT `fk_css_sale` FOREIGN KEY (`cooperative_sale_id`) REFERENCES `cooperative_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_css_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
