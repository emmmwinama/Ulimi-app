-- Canonical crop types (global: farm_id NULL). Idempotent.
INSERT INTO `crop_types` (`id`, `farm_id`, `name`, `is_custom`, `created_at`) VALUES
 ('ct_maize',       NULL, 'Maize',        0, UTC_TIMESTAMP()),
 ('ct_soybean',     NULL, 'Soybean',      0, UTC_TIMESTAMP()),
 ('ct_groundnut',   NULL, 'Groundnut',    0, UTC_TIMESTAMP()),
 ('ct_tobacco',     NULL, 'Tobacco',      0, UTC_TIMESTAMP()),
 ('ct_rice',        NULL, 'Rice',         0, UTC_TIMESTAMP()),
 ('ct_sorghum',     NULL, 'Sorghum',      0, UTC_TIMESTAMP()),
 ('ct_millet',      NULL, 'Millet',       0, UTC_TIMESTAMP()),
 ('ct_sunflower',   NULL, 'Sunflower',    0, UTC_TIMESTAMP()),
 ('ct_cassava',     NULL, 'Cassava',      0, UTC_TIMESTAMP()),
 ('ct_sweetpotato', NULL, 'Sweet Potato', 0, UTC_TIMESTAMP()),
 ('ct_irishpotato', NULL, 'Irish Potato', 0, UTC_TIMESTAMP()),
 ('ct_beans',       NULL, 'Beans',        0, UTC_TIMESTAMP()),
 ('ct_cowpea',      NULL, 'Cowpea',       0, UTC_TIMESTAMP()),
 ('ct_pigeonpea',   NULL, 'Pigeon Pea',   0, UTC_TIMESTAMP()),
 ('ct_cotton',      NULL, 'Cotton',       0, UTC_TIMESTAMP()),
 ('ct_sugarcane',   NULL, 'Sugarcane',    0, UTC_TIMESTAMP()),
 ('ct_banana',      NULL, 'Banana',       0, UTC_TIMESTAMP()),
 ('ct_tomato',      NULL, 'Tomato',       0, UTC_TIMESTAMP()),
 ('ct_onion',       NULL, 'Onion',        0, UTC_TIMESTAMP()),
 ('ct_cabbage',     NULL, 'Cabbage',      0, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `is_custom` = 0;
