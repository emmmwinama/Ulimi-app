-- =====================================================================
-- 009_gis — field boundaries, management zones, and point markers.
-- Geometry is stored as GeoJSON in JSON columns (portable, no spatial
-- extension needed on the shared host). Areas/centroids are computed in
-- PHP and cached here.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `field_boundaries` (
  `id`           VARCHAR(40)   NOT NULL,
  `farm_id`      VARCHAR(40)   NOT NULL,
  `field_id`     VARCHAR(40)   NOT NULL,
  `geo_json`     JSON          NOT NULL,
  `area_ha`      DECIMAL(12,4) NULL,
  `centroid_lat` DOUBLE        NULL,
  `centroid_lng` DOUBLE        NULL,
  `created_at`   DATETIME      NOT NULL,
  `updated_at`   DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_boundary_field` (`field_id`),
  KEY `idx_boundary_farm` (`farm_id`),
  CONSTRAINT `fk_boundary_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_boundary_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `field_zones` (
  `id`            VARCHAR(40)   NOT NULL,
  `farm_id`       VARCHAR(40)   NOT NULL,
  `boundary_id`   VARCHAR(40)   NOT NULL,
  `field_id`      VARCHAR(40)   NOT NULL,
  `crop_field_id` VARCHAR(40)   NULL,
  `name`          VARCHAR(120)  NOT NULL,
  `type`          VARCHAR(40)   NOT NULL DEFAULT 'management',
  `geo_json`      JSON          NOT NULL,
  `area_ha`       DECIMAL(12,4) NULL,
  `colour`        VARCHAR(20)   NULL,
  `notes`         VARCHAR(500)  NULL,
  `created_at`    DATETIME      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_zone_boundary` (`boundary_id`),
  KEY `idx_zone_field` (`field_id`),
  KEY `idx_zone_farm` (`farm_id`),
  CONSTRAINT `fk_zone_boundary` FOREIGN KEY (`boundary_id`) REFERENCES `field_boundaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_zone_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_zone_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `farm_markers` (
  `id`         VARCHAR(40)  NOT NULL,
  `farm_id`    VARCHAR(40)  NOT NULL,
  `field_id`   VARCHAR(40)  NULL,
  `type`       VARCHAR(30)  NOT NULL DEFAULT 'other',   -- borehole|irrigation|shed|road|gate|other
  `label`      VARCHAR(120) NOT NULL,
  `lat`        DOUBLE       NOT NULL,
  `lng`        DOUBLE       NOT NULL,
  `notes`      VARCHAR(500) NULL,
  `icon`       VARCHAR(40)  NULL,
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_marker_farm` (`farm_id`),
  CONSTRAINT `fk_marker_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_marker_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
