-- =====================================================================
-- 017_inventory2 — Phase 15: inventory hardening (batch/expiry/supplier)
-- and a dedicated equipment + maintenance-log lifecycle, pulled out of
-- inventory_items.category('equipment') since equipment doesn't behave
-- like consumable stock — no quantity depletion, no per-unit sale, but
-- it does need a maintenance history that stock items don't.
--
-- Existing inventory_items rows tagged category='equipment' are left as
-- they are (that category value stays valid on inventory_items for
-- backward compatibility) — this migration only adds the new table for
-- equipment recorded from here on.
-- =====================================================================

ALTER TABLE `inventory_items`
  ADD COLUMN `batch_number`     VARCHAR(60)  NULL AFTER `season`,
  ADD COLUMN `expiry_date`      DATE         NULL AFTER `batch_number`,
  ADD COLUMN `supplier_name`    VARCHAR(160) NULL AFTER `expiry_date`,
  ADD COLUMN `supplier_contact` VARCHAR(160) NULL AFTER `supplier_name`;

CREATE TABLE IF NOT EXISTS `equipment` (
  `id`               VARCHAR(40)   NOT NULL,
  `farm_id`          VARCHAR(40)   NOT NULL,
  `name`             VARCHAR(160)  NOT NULL,
  `category`         VARCHAR(40)   NOT NULL DEFAULT 'other',  -- tractor|irrigation|tool|vehicle|other
  `status`           VARCHAR(20)   NOT NULL DEFAULT 'active', -- active|under_repair|retired
  `acquisition_date` DATE          NULL,
  `acquisition_cost` DECIMAL(12,2) NULL,
  `notes`            VARCHAR(500)  NULL,
  `created_at`       DATETIME      NOT NULL,
  `updated_at`       DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eq_farm` (`farm_id`, `status`),
  CONSTRAINT `fk_eq_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `equipment_maintenance_logs` (
  `id`           VARCHAR(40)   NOT NULL,
  `farm_id`      VARCHAR(40)   NOT NULL,
  `equipment_id` VARCHAR(40)   NOT NULL,
  `date`         DATE          NOT NULL,
  `description`  VARCHAR(255)  NOT NULL,
  `cost`         DECIMAL(12,2) NOT NULL DEFAULT 0,
  `hours_used`   DECIMAL(10,1) NULL,
  `notes`        VARCHAR(500)  NULL,
  `created_at`   DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eml_equipment` (`equipment_id`),
  KEY `idx_eml_farm` (`farm_id`),
  CONSTRAINT `fk_eml_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
