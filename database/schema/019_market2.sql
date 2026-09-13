-- =====================================================================
-- 019_market2 — Phase 17: buyer contact book, buyer offers, payment
-- status on transactions, and an optional buyer link on inventory sales.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `buyers` (
  `id`         VARCHAR(40)  NOT NULL,
  `farm_id`    VARCHAR(40)  NOT NULL,
  `name`       VARCHAR(160) NOT NULL,
  `type`       VARCHAR(20)  NOT NULL DEFAULT 'individual', -- individual|company|cooperative|other
  `phone`      VARCHAR(40)  NULL,
  `email`      VARCHAR(160) NULL,
  `location`   VARCHAR(160) NULL,
  `notes`      VARCHAR(500) NULL,
  `created_at` DATETIME     NOT NULL,
  `updated_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_buyers_farm` (`farm_id`),
  CONSTRAINT `fk_buyers_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `buyer_offers` (
  `id`               VARCHAR(40)   NOT NULL,
  `farm_id`          VARCHAR(40)   NOT NULL,
  `buyer_id`         VARCHAR(40)   NULL,
  `crop_name`        VARCHAR(120)  NOT NULL,
  `quantity_wanted`  DECIMAL(14,3) NULL,
  `unit`             VARCHAR(20)   NOT NULL DEFAULT 'kg',
  `price_offered`    DECIMAL(12,2) NULL,
  `status`           VARCHAR(20)   NOT NULL DEFAULT 'open', -- open|accepted|declined|expired
  `expiry_date`      DATE          NULL,
  `notes`            VARCHAR(500)  NULL,
  `created_at`       DATETIME      NOT NULL,
  `updated_at`       DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bo_farm` (`farm_id`, `status`),
  KEY `idx_bo_buyer` (`buyer_id`),
  CONSTRAINT `fk_bo_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bo_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `transactions`
  ADD COLUMN `payment_status` VARCHAR(20) NOT NULL DEFAULT 'paid' AFTER `category`; -- unpaid|partial|paid

ALTER TABLE `inventory_sales`
  ADD COLUMN `buyer_id` VARCHAR(40) NULL AFTER `buyer_name`,
  ADD CONSTRAINT `fk_invs_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE SET NULL;
