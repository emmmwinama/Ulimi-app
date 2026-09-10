-- =====================================================================
-- 007_inventory — produce/input stock and sales, plus the deferred FK
-- from activity_inputs back to inventory_items.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id`                    VARCHAR(40)   NOT NULL,
  `farm_id`               VARCHAR(40)   NOT NULL,
  `name`                  VARCHAR(160)  NOT NULL,
  `category`              VARCHAR(40)   NOT NULL DEFAULT 'other',  -- crop_harvest|seed|fertiliser|chemical|equipment|other
  `unit`                  VARCHAR(20)   NOT NULL DEFAULT 'kg',
  `quantity`              DECIMAL(14,3) NOT NULL DEFAULT 0,
  `acquisition_unit_cost` DECIMAL(12,2) NULL,
  `acquired_at`           DATE          NULL,
  `unit_weight`           DECIMAL(10,3) NULL,
  `season`                VARCHAR(60)   NULL,
  `crop_field_id`         VARCHAR(40)   NULL,
  `harvest_yield_id`      VARCHAR(40)   NULL,
  `notes`                 VARCHAR(500)  NULL,
  `created_at`            DATETIME      NOT NULL,
  `updated_at`            DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inv_farm` (`farm_id`, `category`),
  KEY `idx_inv_crop_field` (`crop_field_id`),
  CONSTRAINT `fk_inv_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inv_yield` FOREIGN KEY (`harvest_yield_id`) REFERENCES `harvest_yields` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_sales` (
  `id`                VARCHAR(40)   NOT NULL,
  `farm_id`           VARCHAR(40)   NOT NULL,
  `inventory_item_id` VARCHAR(40)   NOT NULL,
  `transaction_id`    VARCHAR(40)   NULL,
  `quantity_sold`     DECIMAL(14,3) NOT NULL DEFAULT 0,
  `unit`              VARCHAR(20)   NOT NULL DEFAULT '',
  `price_per_unit`    DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_amount`      DECIMAL(14,2) NOT NULL DEFAULT 0,
  `buyer_name`        VARCHAR(160)  NULL,
  `sale_date`         DATE          NOT NULL,
  `notes`             VARCHAR(500)  NULL,
  `created_at`        DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invs_farm` (`farm_id`),
  KEY `idx_invs_item` (`inventory_item_id`),
  CONSTRAINT `fk_invs_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invs_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invs_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `activity_inputs`
  ADD CONSTRAINT `fk_ai_inventory` FOREIGN KEY (`inventory_item_id`)
  REFERENCES `inventory_items` (`id`) ON DELETE SET NULL;

ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_inventory` FOREIGN KEY (`inventory_item_id`)
  REFERENCES `inventory_items` (`id`) ON DELETE SET NULL;
