-- =====================================================================
-- 004_crops — crop type registry + crop plantings (crop_fields).
-- =====================================================================

CREATE TABLE IF NOT EXISTS `crop_types` (
  `id`         VARCHAR(40)  NOT NULL,
  `farm_id`    VARCHAR(40)  NULL,          -- NULL = global canonical type
  `name`       VARCHAR(120) NOT NULL,
  `is_custom`  TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_crop_types_scope_name` (`farm_id`, `name`),
  KEY `idx_crop_types_farm` (`farm_id`),
  CONSTRAINT `fk_crop_types_farm` FOREIGN KEY (`farm_id`)
    REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crop_fields` (
  `id`                    VARCHAR(40)   NOT NULL,
  `farm_id`               VARCHAR(40)   NOT NULL,
  `field_id`              VARCHAR(40)   NOT NULL,
  `crop_type_id`          VARCHAR(40)   NOT NULL,
  `variety`               VARCHAR(120)  NOT NULL DEFAULT '',
  `area_planted`          DECIMAL(12,3) NOT NULL DEFAULT 0,
  `season`                VARCHAR(60)   NOT NULL DEFAULT '',
  `planting_date`         DATE          NOT NULL,
  `expected_harvest_date` DATE          NOT NULL,
  `status`                VARCHAR(20)   NOT NULL DEFAULT 'Active',
  `is_archived`           TINYINT(1)    NOT NULL DEFAULT 0,
  `archived_at`           DATETIME      NULL,
  `archived_reason`       VARCHAR(255)  NULL,
  `created_at`            DATETIME      NOT NULL,
  `updated_at`            DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cf_farm` (`farm_id`),
  KEY `idx_cf_field` (`field_id`),
  KEY `idx_cf_crop_type` (`crop_type_id`),
  KEY `idx_cf_season` (`farm_id`, `season`),
  KEY `idx_cf_active` (`farm_id`, `is_archived`, `status`),
  CONSTRAINT `fk_cf_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cf_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cf_crop_type` FOREIGN KEY (`crop_type_id`) REFERENCES `crop_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
