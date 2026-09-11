-- =====================================================================
-- 012_cms — marketing-site content management + inbound inquiries.
-- Not farm-scoped: this is the public site's own content, managed only
-- from the admin back office.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `site_content` (
  `key`        VARCHAR(80)  NOT NULL,
  `value`      TEXT         NOT NULL,
  `type`       VARCHAR(20)  NOT NULL DEFAULT 'text',   -- text|image_url|boolean|json
  `group`      VARCHAR(40)  NOT NULL DEFAULT 'general', -- hero|features|contact|social
  `label`      VARCHAR(160) NULL,
  `updated_at` DATETIME     NOT NULL,
  `updated_by` VARCHAR(40)  NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id`         VARCHAR(40)  NOT NULL,
  `slug`       VARCHAR(80)  NOT NULL,
  `title`      VARCHAR(160) NOT NULL,
  `content`    LONGTEXT     NOT NULL,
  `is_public`  TINYINT(1)   NOT NULL DEFAULT 1,
  `updated_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cms_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cms_features` (
  `id`          VARCHAR(40)  NOT NULL,
  `icon`        VARCHAR(40)  NOT NULL DEFAULT 'leaf',
  `title`       VARCHAR(160) NOT NULL,
  `description` TEXT         NOT NULL,
  `sort_order`  INT          NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cms_media` (
  `id`         VARCHAR(40)  NOT NULL,
  `key`        VARCHAR(80)  NOT NULL,
  `url`        VARCHAR(500) NOT NULL,
  `type`       VARCHAR(20)  NOT NULL DEFAULT 'image',
  `label`      VARCHAR(160) NOT NULL DEFAULT '',
  `updated_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cms_media_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`         VARCHAR(40)  NOT NULL,
  `quote`      TEXT         NOT NULL,
  `name`       VARCHAR(120) NOT NULL,
  `role`       VARCHAR(120) NOT NULL DEFAULT '',
  `initials`   VARCHAR(4)   NOT NULL DEFAULT '',
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order` INT          NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id`         VARCHAR(40) NOT NULL,
  `name`       VARCHAR(160) NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `message`    TEXT         NOT NULL,
  `status`     VARCHAR(20)  NOT NULL DEFAULT 'new',  -- new|read|replied|archived
  `notes`      TEXT         NULL,
  `created_at` DATETIME     NOT NULL,
  `replied_at` DATETIME     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contact_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `demo_bookings` (
  `id`         VARCHAR(40)  NOT NULL,
  `name`       VARCHAR(160) NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `farm`       VARCHAR(160) NOT NULL DEFAULT '',
  `message`    TEXT         NULL,
  `status`     VARCHAR(20)  NOT NULL DEFAULT 'pending',  -- pending|confirmed|completed|cancelled
  `notes`      TEXT         NULL,
  `booked_for` DATETIME     NULL,
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_demo_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
