-- =====================================================================
-- 006_finance — income/expense transactions and overhead expenses.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `transactions` (
  `id`               VARCHAR(40)   NOT NULL,
  `farm_id`          VARCHAR(40)   NOT NULL,
  `type`             VARCHAR(10)   NOT NULL,           -- Income | Expense
  `category`         VARCHAR(80)   NOT NULL DEFAULT 'Other',
  `amount`           DECIMAL(14,2) NOT NULL DEFAULT 0,
  `date`             DATE          NOT NULL,
  `description`      VARCHAR(255)  NOT NULL DEFAULT '',
  `season`           VARCHAR(60)   NULL,
  `field_id`         VARCHAR(40)   NULL,
  `crop_field_id`    VARCHAR(40)   NULL,
  `harvest_yield_id` VARCHAR(40)   NULL,
  `inventory_item_id` VARCHAR(40)  NULL,
  `source`           VARCHAR(20)   NOT NULL DEFAULT 'manual',  -- manual | inventory_sale
  `created_by_id`    VARCHAR(40)   NOT NULL,
  `created_at`       DATETIME      NOT NULL,
  `updated_at`       DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tx_farm_date` (`farm_id`, `date`),
  KEY `idx_tx_farm_type` (`farm_id`, `type`),
  KEY `idx_tx_season` (`farm_id`, `season`),
  KEY `idx_tx_crop_field` (`crop_field_id`),
  CONSTRAINT `fk_tx_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tx_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tx_yield` FOREIGN KEY (`harvest_yield_id`) REFERENCES `harvest_yields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tx_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `overhead_expenses` (
  `id`          VARCHAR(40)   NOT NULL,
  `farm_id`     VARCHAR(40)   NOT NULL,
  `description` VARCHAR(200)  NOT NULL,
  `category`    VARCHAR(40)   NOT NULL DEFAULT 'Other',  -- Salary|Rent|Utilities|Insurance|Loan|Other
  `amount`      DECIMAL(14,2) NOT NULL DEFAULT 0,
  `date`        DATE          NOT NULL,
  `recurring`   TINYINT(1)    NOT NULL DEFAULT 0,
  `notes`       VARCHAR(500)  NULL,
  `created_at`  DATETIME      NOT NULL,
  `updated_at`  DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oh_farm_date` (`farm_id`, `date`),
  CONSTRAINT `fk_oh_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
