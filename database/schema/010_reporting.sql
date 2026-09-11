-- =====================================================================
-- 010_reporting — persisted credit-score history and saved report-builder
-- configurations.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `farm_credit_scores` (
  `id`           VARCHAR(40) NOT NULL,
  `farm_id`      VARCHAR(40) NOT NULL,
  `user_id`      VARCHAR(40) NOT NULL,
  `score`        INT         NOT NULL,
  `grade`        VARCHAR(4)  NOT NULL,
  `factors`      JSON        NOT NULL,
  `generated_at` DATETIME    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fcs_farm` (`farm_id`, `generated_at`),
  CONSTRAINT `fk_fcs_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fcs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saved_reports` (
  `id`         VARCHAR(40)  NOT NULL,
  `farm_id`    VARCHAR(40)  NOT NULL,
  `user_id`    VARCHAR(40)  NOT NULL,
  `name`       VARCHAR(120) NOT NULL,
  `config`     JSON         NOT NULL,   -- {sections:[], season, from, to}
  `created_at` DATETIME     NOT NULL,
  `updated_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sr_farm` (`farm_id`),
  CONSTRAINT `fk_sr_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
