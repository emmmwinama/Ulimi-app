-- =====================================================================
-- 018_postharvest — Phase 16: storage/drying/loss tracking per harvest,
-- and collection/transport details on a sale.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `produce_storage` (
  `id`                VARCHAR(40)   NOT NULL,
  `farm_id`           VARCHAR(40)   NOT NULL,
  `harvest_yield_id`  VARCHAR(40)   NOT NULL,
  `storage_location`  VARCHAR(160)  NULL,
  `drying_method`     VARCHAR(120)  NULL,
  `drying_date`       DATE          NULL,
  `quality_grade`     VARCHAR(20)   NULL,   -- A|B|C|reject
  `expected_loss_qty` DECIMAL(14,3) NULL,
  `loss_reason`       VARCHAR(255)  NULL,
  `notes`             VARCHAR(500)  NULL,
  `created_at`        DATETIME      NOT NULL,
  `updated_at`        DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ps_harvest` (`harvest_yield_id`),
  KEY `idx_ps_farm` (`farm_id`),
  CONSTRAINT `fk_ps_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_harvest` FOREIGN KEY (`harvest_yield_id`) REFERENCES `harvest_yields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `inventory_sales`
  ADD COLUMN `collection_point`  VARCHAR(160) NULL AFTER `buyer_name`,
  ADD COLUMN `transport_method`  VARCHAR(80)  NULL AFTER `collection_point`,
  ADD COLUMN `pickup_date`       DATE         NULL AFTER `transport_method`;
