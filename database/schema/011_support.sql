-- =====================================================================
-- 011_support — weather cache, market price reference data, farm
-- documents, and in-app notifications.
--
-- Note: seasonal activity/payroll templates are NOT a separate CRUD
-- system here — Services\CropTimeline (Phase 3) already provides
-- per-crop stage guidance and activity-type suggestions, which covers
-- the same need without a second admin-maintained data set.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `weather_cache` (
  `id`        VARCHAR(40) NOT NULL,
  `farm_id`   VARCHAR(40) NOT NULL,
  `data`      JSON        NOT NULL,
  `cached_at` DATETIME    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_weather_farm` (`farm_id`),
  CONSTRAINT `fk_weather_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `market_prices` (
  `id`          VARCHAR(40)   NOT NULL,
  `crop_name`   VARCHAR(120)  NOT NULL,
  `variety`     VARCHAR(120)  NULL,
  `unit`        VARCHAR(20)   NOT NULL DEFAULT 'kg',
  `price_min`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `price_max`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `price_avg`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `market`      VARCHAR(120)  NOT NULL DEFAULT '',
  `region`      VARCHAR(120)  NOT NULL DEFAULT '',
  `currency`    VARCHAR(3)    NOT NULL DEFAULT 'MWK',
  `season`      VARCHAR(60)   NULL,
  `recorded_at` DATETIME      NOT NULL,
  `source`      VARCHAR(60)   NOT NULL DEFAULT 'ADMARC',
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_mp_crop` (`crop_name`, `is_active`),
  KEY `idx_mp_recorded` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `farm_documents` (
  `id`          VARCHAR(40)  NOT NULL,
  `farm_id`     VARCHAR(40)  NOT NULL,
  `name`        VARCHAR(200) NOT NULL,
  `type`        VARCHAR(40)  NOT NULL DEFAULT 'other',  -- deed|certificate|receipt|contract|photo|other
  `asset_id`    VARCHAR(64)  NOT NULL,                  -- key into storage/uploads (see Services\Upload)
  `mime_type`   VARCHAR(120) NOT NULL,
  `size`        INT          NULL,
  `linked_to`   VARCHAR(40)  NULL,   -- e.g. an activity/transaction/crop_field id
  `linked_type` VARCHAR(40)  NULL,   -- activity|transaction|crop
  `notes`       VARCHAR(500) NULL,
  `uploaded_by` VARCHAR(40)  NOT NULL,
  `uploaded_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_farm` (`farm_id`),
  CONSTRAINT `fk_doc_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         VARCHAR(40)  NOT NULL,
  `user_id`    VARCHAR(40)  NOT NULL,
  `farm_id`    VARCHAR(40)  NOT NULL,
  `type`       VARCHAR(40)  NOT NULL,   -- harvest_due|no_activity|low_inventory|price_alert
  `dedupe_key` VARCHAR(160) NOT NULL,   -- stable key so lazy generation doesn't duplicate
  `title`      VARCHAR(160) NOT NULL,
  `message`    VARCHAR(500) NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `link`       VARCHAR(255) NULL,
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notif_dedupe` (`user_id`, `dedupe_key`),
  KEY `idx_notif_user` (`user_id`, `is_read`, `created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
