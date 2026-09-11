-- =====================================================================
-- 014_ai — cache for AI-generated report narratives (App\Services\Ai).
-- One row per farm per insight kind; regenerated lazily on a TTL, same
-- "no cron on shared hosting" pattern as weather_cache.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `ai_insights_cache` (
  `id`         VARCHAR(40)  NOT NULL,
  `farm_id`    VARCHAR(40)  NOT NULL,
  `kind`       VARCHAR(40)  NOT NULL,
  `content`    TEXT         NOT NULL,
  `model`      VARCHAR(80)  NOT NULL,
  `cached_at`  DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_farm_kind` (`farm_id`, `kind`),
  CONSTRAINT `fk_ai_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
