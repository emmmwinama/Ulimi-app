-- =====================================================================
-- 016_incidents — Phase 14: crop pest/disease incident log.
--
-- Media (photo/voice) attaches through the existing farm_documents
-- linked_to/linked_type columns (already present since 011_support,
-- never wired up until now) rather than a parallel photo column —
-- see App\Controllers\Farm\CropIncidentsController.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `crop_incidents` (
  `id`              VARCHAR(40)  NOT NULL,
  `farm_id`         VARCHAR(40)  NOT NULL,
  `crop_field_id`   VARCHAR(40)  NOT NULL,
  `type`            VARCHAR(20)  NOT NULL DEFAULT 'pest',    -- pest|disease|other
  `description`     VARCHAR(500) NOT NULL,
  `severity`        VARCHAR(20)  NOT NULL DEFAULT 'moderate', -- mild|moderate|severe
  `status`          VARCHAR(20)  NOT NULL DEFAULT 'open',     -- open|treated|resolved
  `reported_date`   DATE         NOT NULL,
  `treatment_notes` VARCHAR(1000) NULL,
  `referred_to`     VARCHAR(160) NULL,   -- vet / extension officer name or contact
  `created_by_id`   VARCHAR(40)  NOT NULL,
  `created_at`      DATETIME     NOT NULL,
  `updated_at`      DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ci_farm` (`farm_id`, `status`),
  KEY `idx_ci_crop_field` (`crop_field_id`),
  KEY `idx_ci_farm_type_date` (`farm_id`, `type`, `reported_date`),
  CONSTRAINT `fk_ci_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_user` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
