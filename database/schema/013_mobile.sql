-- =====================================================================
-- 013_mobile — refresh tokens for the mobile JWT API. Access tokens are
-- short-lived signed JWTs (Core\Jwt) and are never persisted; only the
-- long-lived refresh token is stored, and only as a keyed hash.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `api_refresh_tokens` (
  `id`           VARCHAR(40)  NOT NULL,
  `user_id`      VARCHAR(40)  NOT NULL,
  `token_hash`   CHAR(64)     NOT NULL,
  `device_label` VARCHAR(120) NULL,
  `expires_at`   DATETIME     NOT NULL,
  `revoked_at`   DATETIME     NULL,
  `created_at`   DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_api_rt_hash` (`token_hash`),
  KEY `idx_api_rt_user` (`user_id`),
  CONSTRAINT `fk_api_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
