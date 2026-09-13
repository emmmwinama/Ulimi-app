-- Auto-fetched market price proposals awaiting admin approval, so live
-- prices only ever change on explicit sign-off — see App\Services\MarketPriceFeed.
CREATE TABLE IF NOT EXISTS `market_price_updates` (
  `id`                VARCHAR(40)   NOT NULL,
  `existing_price_id` VARCHAR(40)   NULL,
  `crop_name`         VARCHAR(120)  NOT NULL,
  `variety`           VARCHAR(120)  NULL,
  `unit`              VARCHAR(20)   NOT NULL DEFAULT 'kg',
  `price_min`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `price_max`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `price_avg`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `market`            VARCHAR(120)  NOT NULL DEFAULT '',
  `region`            VARCHAR(120)  NOT NULL DEFAULT '',
  `currency`          VARCHAR(3)    NOT NULL DEFAULT 'MWK',
  `recorded_at`       DATETIME      NOT NULL,
  `source`            VARCHAR(60)   NOT NULL DEFAULT 'WFP',
  `status`            VARCHAR(20)   NOT NULL DEFAULT 'pending',
  `fetched_at`        DATETIME      NOT NULL,
  `reviewed_by`       VARCHAR(40)   NULL,
  `reviewed_at`       DATETIME      NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mpu_pending_slot` (`crop_name`, `region`, `source`, `status`),
  KEY `idx_mpu_status` (`status`),
  CONSTRAINT `fk_mpu_existing` FOREIGN KEY (`existing_price_id`) REFERENCES `market_prices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mpu_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Single-row table tracking when the feed was last checked, so the lazy
-- refresh (no cron on shared hosting — same pattern as weather_cache) knows
-- whether it's due, independent of whatever the last fetch actually found.
CREATE TABLE IF NOT EXISTS `market_price_feed_state` (
  `id`             VARCHAR(10) NOT NULL,
  `last_checked_at` DATETIME  NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `market_price_feed_state` (`id`, `last_checked_at`) VALUES ('wfp', NULL);
