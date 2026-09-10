-- =====================================================================
-- 008_livestock — livestock types, animals, and per-animal event logs
-- (health, production, weight, expenses, sales).
-- =====================================================================

CREATE TABLE IF NOT EXISTS `livestock_types` (
  `id`         VARCHAR(40)  NOT NULL,
  `farm_id`    VARCHAR(40)  NOT NULL,
  `name`       VARCHAR(80)  NOT NULL,
  `category`   VARCHAR(60)  NOT NULL DEFAULT '',
  `icon`       VARCHAR(40)  NOT NULL DEFAULT 'cow',
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lt_farm_name` (`farm_id`, `name`),
  CONSTRAINT `fk_lt_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animals` (
  `id`               VARCHAR(40)   NOT NULL,
  `farm_id`          VARCHAR(40)   NOT NULL,
  `livestock_type_id` VARCHAR(40)  NOT NULL,
  `tag`              VARCHAR(60)   NULL,
  `name`             VARCHAR(80)   NULL,
  `animal_group`     VARCHAR(80)   NULL,
  `sex`              VARCHAR(10)   NOT NULL DEFAULT 'Unknown',
  `birth_date`       DATE          NULL,
  `acquisition_date` DATE          NOT NULL,
  `acquisition_type` VARCHAR(40)   NOT NULL DEFAULT 'Born on farm',
  `acquisition_cost` DECIMAL(12,2) NULL,
  `status`           VARCHAR(20)   NOT NULL DEFAULT 'Active',   -- Active|Sold|Dead|Culled|Lost
  `breed`            VARCHAR(80)   NULL,
  `colour`           VARCHAR(60)   NULL,
  `weight`           DECIMAL(10,2) NULL,
  `notes`            VARCHAR(500)  NULL,
  `parent_id`        VARCHAR(40)   NULL,
  `created_at`       DATETIME      NOT NULL,
  `updated_at`       DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_animals_farm` (`farm_id`, `status`),
  KEY `idx_animals_type` (`livestock_type_id`),
  KEY `idx_animals_parent` (`parent_id`),
  CONSTRAINT `fk_animals_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_animals_type` FOREIGN KEY (`livestock_type_id`) REFERENCES `livestock_types` (`id`),
  CONSTRAINT `fk_animals_parent` FOREIGN KEY (`parent_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animal_health` (
  `id`            VARCHAR(40)   NOT NULL,
  `farm_id`       VARCHAR(40)   NOT NULL,
  `animal_id`     VARCHAR(40)   NOT NULL,
  `type`          VARCHAR(60)   NOT NULL,        -- Vaccination|Treatment|Deworming|Check-up|Other
  `description`   VARCHAR(255)  NOT NULL,
  `veterinarian`  VARCHAR(120)  NULL,
  `cost`          DECIMAL(12,2) NOT NULL DEFAULT 0,
  `date`          DATE          NOT NULL,
  `next_due_date` DATE          NULL,
  `notes`         VARCHAR(500)  NULL,
  `created_at`    DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ah_animal` (`animal_id`),
  KEY `idx_ah_farm` (`farm_id`),
  CONSTRAINT `fk_ah_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animal_production` (
  `id`           VARCHAR(40)   NOT NULL,
  `farm_id`      VARCHAR(40)   NOT NULL,
  `animal_id`    VARCHAR(40)   NULL,          -- NULL = herd/flock-level record
  `type`         VARCHAR(60)   NOT NULL,      -- Milk|Eggs|Wool|Manure|Other
  `quantity`     DECIMAL(14,3) NOT NULL DEFAULT 0,
  `unit`         VARCHAR(20)   NOT NULL DEFAULT '',
  `date`         DATE          NOT NULL,
  `price_per_unit` DECIMAL(12,2) NULL,
  `total_value`  DECIMAL(14,2) NULL,
  `notes`        VARCHAR(500)  NULL,
  `created_at`   DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ap_animal` (`animal_id`),
  KEY `idx_ap_farm_date` (`farm_id`, `date`),
  CONSTRAINT `fk_ap_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ap_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animal_weight` (
  `id`        VARCHAR(40)   NOT NULL,
  `farm_id`   VARCHAR(40)   NOT NULL,
  `animal_id` VARCHAR(40)   NOT NULL,
  `weight`    DECIMAL(10,2) NOT NULL DEFAULT 0,
  `unit`      VARCHAR(10)   NOT NULL DEFAULT 'kg',
  `date`      DATE          NOT NULL,
  `notes`     VARCHAR(300)  NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aw_animal` (`animal_id`, `date`),
  CONSTRAINT `fk_aw_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animal_expenses` (
  `id`          VARCHAR(40)   NOT NULL,
  `farm_id`     VARCHAR(40)   NOT NULL,
  `animal_id`   VARCHAR(40)   NULL,
  `category`    VARCHAR(60)   NOT NULL DEFAULT 'Other',  -- Feed|Medicine|Housing|Transport|Other
  `description` VARCHAR(200)  NOT NULL,
  `amount`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `date`        DATE          NOT NULL,
  `notes`       VARCHAR(500)  NULL,
  `created_at`  DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ae_farm_date` (`farm_id`, `date`),
  KEY `idx_ae_animal` (`animal_id`),
  CONSTRAINT `fk_ae_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ae_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animal_sales` (
  `id`            VARCHAR(40)   NOT NULL,
  `farm_id`       VARCHAR(40)   NOT NULL,
  `animal_id`     VARCHAR(40)   NOT NULL,
  `transaction_id` VARCHAR(40)  NULL,
  `sale_date`     DATE          NOT NULL,
  `quantity`      INT           NOT NULL DEFAULT 1,
  `weight_at_sale` DECIMAL(10,2) NULL,
  `price_per_kg`  DECIMAL(12,2) NULL,
  `total_amount`  DECIMAL(14,2) NOT NULL DEFAULT 0,
  `buyer`         VARCHAR(160)  NULL,
  `notes`         VARCHAR(500)  NULL,
  `created_at`    DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_as_farm` (`farm_id`),
  KEY `idx_as_animal` (`animal_id`),
  CONSTRAINT `fk_as_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
