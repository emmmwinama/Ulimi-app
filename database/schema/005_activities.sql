-- =====================================================================
-- 005_activities — the field activity log and its cost lines, the
-- employee roster, and harvest yield records.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `employees` (
  `id`            VARCHAR(40)   NOT NULL,
  `farm_id`       VARCHAR(40)   NOT NULL,
  `name`          VARCHAR(120)  NOT NULL,
  `role`          VARCHAR(80)   NOT NULL DEFAULT '',
  `pay_rate`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `pay_rate_unit` VARCHAR(20)   NOT NULL DEFAULT 'day',   -- hour|day|month|task
  `phone`         VARCHAR(40)   NULL,
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`    DATETIME      NOT NULL,
  `updated_at`    DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employees_farm` (`farm_id`, `is_active`),
  CONSTRAINT `fk_employees_farm` FOREIGN KEY (`farm_id`)
    REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `farm_activities` (
  `id`                      VARCHAR(40)  NOT NULL,
  `farm_id`                 VARCHAR(40)  NOT NULL,
  `field_id`                VARCHAR(40)  NOT NULL,
  `crop_field_id`           VARCHAR(40)  NULL,
  `activity_type`           VARCHAR(80)  NOT NULL,
  `date`                    DATE         NOT NULL,
  `notes`                   TEXT         NULL,
  `responsible_person_name` VARCHAR(120) NULL,
  `responsible_employee_id` VARCHAR(40)  NULL,
  `created_by_id`           VARCHAR(40)  NOT NULL,
  `created_at`              DATETIME     NOT NULL,
  `updated_at`              DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fa_farm_date` (`farm_id`, `date`),
  KEY `idx_fa_field` (`field_id`),
  KEY `idx_fa_crop_field` (`crop_field_id`),
  KEY `idx_fa_type` (`farm_id`, `activity_type`),
  CONSTRAINT `fk_fa_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fa_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fa_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fa_employee` FOREIGN KEY (`responsible_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fa_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_labour` (
  `id`           VARCHAR(40)   NOT NULL,
  `farm_id`      VARCHAR(40)   NOT NULL,
  `activity_id`  VARCHAR(40)   NOT NULL,
  `employee_id`  VARCHAR(40)   NULL,
  `worker_name`  VARCHAR(120)  NULL,           -- for casual labour not on the roster
  `hours_worked` DECIMAL(8,2)  NOT NULL DEFAULT 0,
  `days_worked`  DECIMAL(8,2)  NOT NULL DEFAULT 0,
  `total_cost`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_al_activity` (`activity_id`),
  KEY `idx_al_farm` (`farm_id`),
  CONSTRAINT `fk_al_activity` FOREIGN KEY (`activity_id`) REFERENCES `farm_activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_al_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_inputs` (
  `id`                    VARCHAR(40)   NOT NULL,
  `farm_id`               VARCHAR(40)   NOT NULL,
  `activity_id`           VARCHAR(40)   NOT NULL,
  `input_name`            VARCHAR(160)  NOT NULL,
  `category`              VARCHAR(60)   NOT NULL DEFAULT 'Other',
  `quantity`              DECIMAL(12,3) NOT NULL DEFAULT 0,
  `unit`                  VARCHAR(20)   NOT NULL DEFAULT '',
  `unit_cost`             DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_cost`            DECIMAL(12,2) NOT NULL DEFAULT 0,
  `acquisition_unit_cost` DECIMAL(12,2) NULL,
  `time_value_cost`       DECIMAL(12,2) NULL,
  `inventory_item_id`     VARCHAR(40)   NULL,   -- FK added in 007_inventory
  PRIMARY KEY (`id`),
  KEY `idx_ai_activity` (`activity_id`),
  KEY `idx_ai_farm` (`farm_id`),
  KEY `idx_ai_inventory` (`inventory_item_id`),
  CONSTRAINT `fk_ai_activity` FOREIGN KEY (`activity_id`) REFERENCES `farm_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_other_costs` (
  `id`          VARCHAR(40)   NOT NULL,
  `farm_id`     VARCHAR(40)   NOT NULL,
  `activity_id` VARCHAR(40)   NOT NULL,
  `description` VARCHAR(200)  NOT NULL,
  `amount`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_aoc_activity` (`activity_id`),
  KEY `idx_aoc_farm` (`farm_id`),
  CONSTRAINT `fk_aoc_activity` FOREIGN KEY (`activity_id`) REFERENCES `farm_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `harvest_yields` (
  `id`            VARCHAR(40)   NOT NULL,
  `farm_id`       VARCHAR(40)   NOT NULL,
  `crop_field_id` VARCHAR(40)   NOT NULL,
  `harvest_date`  DATE          NOT NULL,
  `quantity`      DECIMAL(14,3) NOT NULL DEFAULT 0,
  `unit`          VARCHAR(20)   NOT NULL DEFAULT 'kg',   -- kg|bags|tonnes|crates
  `unit_weight`   DECIMAL(10,3) NULL,                    -- kg per bag/crate
  `quantity_kg`   DECIMAL(14,3) NOT NULL DEFAULT 0,      -- normalised
  `notes`         VARCHAR(500)  NULL,
  `created_at`    DATETIME      NOT NULL,
  `updated_at`    DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hy_farm` (`farm_id`),
  KEY `idx_hy_crop_field` (`crop_field_id`),
  CONSTRAINT `fk_hy_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hy_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
