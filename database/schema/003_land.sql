-- =====================================================================
-- 003_land — fields (land parcels).
--
-- Note: every operational table from here on carries `farm_id` directly
-- (denormalised from the field/crop relation the original derives it
-- through). On a constrained shared-host MySQL this removes a join from
-- every list query and makes tenant scoping a single unambiguous
-- `WHERE farm_id = :ctx` on every table.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `fields` (
  `id`                VARCHAR(40)   NOT NULL,
  `farm_id`           VARCHAR(40)   NOT NULL,
  `name`              VARCHAR(160)  NOT NULL,
  `total_area`        DECIMAL(12,3) NOT NULL DEFAULT 0,   -- hectares
  `cultivatable_area` DECIMAL(12,3) NOT NULL DEFAULT 0,   -- hectares
  `soil_type`         VARCHAR(80)   NOT NULL DEFAULT '',
  `location_lat`      DOUBLE        NULL,
  `location_lng`      DOUBLE        NULL,
  `boundary_points`   JSON          NULL,   -- simple point list; full GIS in phase 6
  `notes`             TEXT          NULL,
  `created_at`        DATETIME      NOT NULL,
  `updated_at`        DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fields_farm` (`farm_id`),
  CONSTRAINT `fk_fields_farm` FOREIGN KEY (`farm_id`)
    REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
