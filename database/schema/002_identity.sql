-- =====================================================================
-- 002_identity — users, admins, credential tokens, billing (tiers /
-- subscriptions / payments), farms and farm membership (roles + perms).
-- =====================================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id`            VARCHAR(40)  NOT NULL,
  `name`          VARCHAR(120) NULL,
  `email`         VARCHAR(190) NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 0,
  `last_login_at` DATETIME     NULL,
  `created_at`    DATETIME     NOT NULL,
  `updated_at`    DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`             VARCHAR(40)  NOT NULL,
  `email`          VARCHAR(190) NOT NULL,
  `password`       VARCHAR(255) NOT NULL,
  `name`           VARCHAR(120) NOT NULL,
  `is_super_admin` TINYINT(1)   NOT NULL DEFAULT 0,
  `last_login_at`  DATETIME     NULL,
  `created_at`     DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id`         VARCHAR(40) NOT NULL,
  `user_id`    VARCHAR(40) NOT NULL,
  `type`       ENUM('activation','reset') NOT NULL,
  `token_hash` CHAR(64)    NOT NULL,          -- HMAC-SHA256 of the emailed token
  `expires_at` DATETIME    NOT NULL,
  `used_at`    DATETIME    NULL,
  `created_at` DATETIME    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_auth_tokens_hash` (`token_hash`),
  KEY `idx_auth_tokens_user_type` (`user_id`, `type`),
  CONSTRAINT `fk_auth_tokens_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subscription_tiers` (
  `id`            VARCHAR(40)  NOT NULL,
  `name`          VARCHAR(80)  NOT NULL,
  `description`   VARCHAR(255) NOT NULL DEFAULT '',
  `currency`      VARCHAR(3)   NOT NULL DEFAULT 'MWK',
  `price_monthly` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `price_annual`  DECIMAL(12,2) NULL,
  `price_lifetime` DECIMAL(12,2) NULL,
  `audience`      VARCHAR(120) NULL,
  `cta_label`     VARCHAR(80)  NULL,
  `cta_href`      VARCHAR(255) NULL,
  `offer_items`   JSON         NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `is_public`     TINYINT(1)   NOT NULL DEFAULT 1,
  `is_featured`   TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`    INT          NOT NULL DEFAULT 0,
  `max_fields`        INT NOT NULL DEFAULT 1,
  `max_crops`         INT NOT NULL DEFAULT 1,
  `max_activities`    INT NOT NULL DEFAULT 10,
  `max_transactions`  INT NOT NULL DEFAULT 5,
  `max_employees`     INT NOT NULL DEFAULT 1,
  `max_farms`         INT NOT NULL DEFAULT 1,
  `max_team_members`  INT NOT NULL DEFAULT 1,
  `season_analytics`  TINYINT(1) NOT NULL DEFAULT 0,
  `yield_suggestions` TINYINT(1) NOT NULL DEFAULT 0,
  `cost_per_hectare`  TINYINT(1) NOT NULL DEFAULT 0,
  `payroll_tracking`  TINYINT(1) NOT NULL DEFAULT 0,
  `multiple_farms`    TINYINT(1) NOT NULL DEFAULT 0,
  `team_accounts`     TINYINT(1) NOT NULL DEFAULT 0,
  `custom_reports`    TINYINT(1) NOT NULL DEFAULT 0,
  `api_access`        TINYINT(1) NOT NULL DEFAULT 0,
  `data_retention_lifetime` TINYINT(1) NOT NULL DEFAULT 1,
  `sync_enabled`      TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`        DATETIME   NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tiers_visible` (`is_active`, `is_public`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id`            VARCHAR(40) NOT NULL,
  `user_id`       VARCHAR(40) NOT NULL,
  `tier_id`       VARCHAR(40) NOT NULL,
  `status`        VARCHAR(20) NOT NULL DEFAULT 'active',   -- active|trial|past_due|expired|suspended
  `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly',  -- monthly|annual|lifetime
  `start_date`    DATETIME    NOT NULL,
  `end_date`      DATETIME    NULL,
  `trial_ends_at` DATETIME    NULL,
  `provider`      VARCHAR(20) NULL,
  `provider_subscription_id` VARCHAR(120) NULL,
  `activation_token` VARCHAR(80) NULL,
  `activated_at`  DATETIME    NULL,
  `notes`         VARCHAR(500) NULL,
  `created_at`    DATETIME    NOT NULL,
  `updated_at`    DATETIME    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_subscriptions_user` (`user_id`),
  UNIQUE KEY `uniq_subscriptions_provider` (`provider_subscription_id`),
  UNIQUE KEY `uniq_subscriptions_acttoken` (`activation_token`),
  KEY `idx_subscriptions_tier` (`tier_id`),
  CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscriptions_tier` FOREIGN KEY (`tier_id`)
    REFERENCES `subscription_tiers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id`             VARCHAR(40)  NOT NULL,
  `subscription_id` VARCHAR(40) NOT NULL,
  `amount`         DECIMAL(12,2) NOT NULL,
  `currency`       VARCHAR(3)   NOT NULL DEFAULT 'MWK',
  `status`         VARCHAR(20)  NOT NULL,   -- paid|pending|failed|refunded
  `method`         VARCHAR(30)  NOT NULL,   -- cash|mobile_money|bank_transfer|card|paypal
  `reference`      VARCHAR(120) NULL,
  `provider_transaction_id` VARCHAR(120) NULL,
  `notes`          VARCHAR(500) NULL,
  `paid_at`        DATETIME     NULL,
  `created_at`     DATETIME     NOT NULL,
  `created_by_admin_id` VARCHAR(40) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_payments_provider_txn` (`provider_transaction_id`),
  KEY `idx_payments_subscription` (`subscription_id`),
  CONSTRAINT `fk_payments_subscription` FOREIGN KEY (`subscription_id`)
    REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `farms` (
  `id`           VARCHAR(40)  NOT NULL,
  `name`         VARCHAR(160) NOT NULL,
  `location`     VARCHAR(160) NOT NULL DEFAULT '',
  `location_lat` DOUBLE       NULL,
  `location_lng` DOUBLE       NULL,
  `owner_name`   VARCHAR(120) NULL,
  `user_id`      VARCHAR(40)  NOT NULL,   -- the owner account
  `created_at`   DATETIME     NOT NULL,
  `updated_at`   DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_farms_user` (`user_id`),
  CONSTRAINT `fk_farms_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `farm_members` (
  `id`                VARCHAR(40)  NOT NULL,
  `farm_id`           VARCHAR(40)  NOT NULL,
  `user_id`           VARCHAR(40)  NULL,           -- NULL while an invite is pending
  `role`              VARCHAR(20)  NOT NULL,       -- owner|manager|agronomist|accountant|field_worker|viewer
  `permissions`       JSON         NOT NULL,
  `invite_email`      VARCHAR(190) NULL,
  `invite_token`      CHAR(64)     NULL,           -- HMAC-SHA256 of the emailed invite token
  `invite_expires_at` DATETIME     NULL,
  `status`            VARCHAR(20)  NOT NULL DEFAULT 'active',   -- active|invited|suspended
  `invited_by`        VARCHAR(40)  NULL,
  `created_at`        DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_member_farm_user` (`farm_id`, `user_id`),
  UNIQUE KEY `uniq_member_invite_token` (`invite_token`),
  KEY `idx_member_user` (`user_id`),
  KEY `idx_member_farm` (`farm_id`),
  CONSTRAINT `fk_member_farm` FOREIGN KEY (`farm_id`)
    REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
