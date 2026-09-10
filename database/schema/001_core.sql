-- =====================================================================
-- 001_core — framework tables: migrations ledger, sessions, rate limits,
-- outbound mail queue, audit log.
--
-- Engine/charset: InnoDB + utf8mb4 throughout. ID columns are VARCHAR(40):
-- wide enough for a ULID (26), a cuid/cuid2 (24-32) and a UUIDv4 (36), so
-- IDs imported from the legacy SQLite keep their original value.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `_migrations` (
  `filename`   VARCHAR(191) NOT NULL,
  `applied_at` DATETIME     NOT NULL,
  PRIMARY KEY (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id`            VARCHAR(64)  NOT NULL,
  `user_id`       VARCHAR(40)  NULL,
  `admin_id`      VARCHAR(40)  NULL,
  `ip_address`    VARCHAR(45)  NULL,
  `user_agent`    VARCHAR(255) NULL,
  `payload`       MEDIUMTEXT   NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_last_activity` (`last_activity`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `bucket`     VARCHAR(64)  NOT NULL,   -- sha256 hex of "profile:ip"
  `attempts`   INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`bucket`),
  KEY `idx_rate_limits_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mail_queue` (
  `id`         VARCHAR(40)  NOT NULL,
  `to_email`   VARCHAR(190) NOT NULL,
  `to_name`    VARCHAR(120) NULL,
  `subject`    VARCHAR(255) NOT NULL,
  `html_body`  MEDIUMTEXT   NOT NULL,
  `text_body`  MEDIUMTEXT   NOT NULL,
  `status`     ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` VARCHAR(500) NULL,
  `created_at` DATETIME     NOT NULL,
  `sent_at`    DATETIME     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mail_queue_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          VARCHAR(40) NOT NULL,
  `actor_type`  ENUM('user','admin','system') NOT NULL DEFAULT 'system',
  `actor_id`    VARCHAR(40) NULL,
  `action`      VARCHAR(80) NOT NULL,
  `target_type` VARCHAR(60) NULL,
  `target_id`   VARCHAR(40) NULL,
  `farm_id`     VARCHAR(40) NULL,
  `ip`          VARCHAR(45) NULL,
  `meta`        JSON        NULL,
  `created_at`  DATETIME    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_farm` (`farm_id`),
  KEY `idx_audit_actor` (`actor_id`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
