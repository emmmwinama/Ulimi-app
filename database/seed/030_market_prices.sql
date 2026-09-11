-- Reference market prices (MWK) so the farmer-facing comparison page has
-- real-looking data before the admin market module (Phase 9) is in use.
-- Idempotent via fixed ids.
INSERT INTO `market_prices` (`id`,`crop_name`,`variety`,`unit`,`price_min`,`price_max`,`price_avg`,`market`,`region`,`currency`,`season`,`recorded_at`,`source`,`is_active`) VALUES
 ('mp_maize_lil',     'Maize',     NULL, 'kg', 550, 700, 620, 'Lilongwe ADMARC',  'Central', 'MWK', NULL, UTC_TIMESTAMP(), 'ADMARC', 1),
 ('mp_maize_mch',     'Maize',     NULL, 'kg', 500, 650, 580, 'Mchinji Market',   'Central', 'MWK', NULL, UTC_TIMESTAMP(), 'ADMARC', 1),
 ('mp_soybean_lil',   'Soybean',   NULL, 'kg', 950, 1150, 1050, 'Lilongwe ADMARC', 'Central', 'MWK', NULL, UTC_TIMESTAMP(), 'ADMARC', 1),
 ('mp_groundnut_lil', 'Groundnut', NULL, 'kg', 1100, 1400, 1250, 'Lilongwe ADMARC', 'Central', 'MWK', NULL, UTC_TIMESTAMP(), 'ADMARC', 1),
 ('mp_tobacco_auc',   'Tobacco',   NULL, 'kg', 1800, 2600, 2200, 'Auction Floors',  'National','MWK', NULL, UTC_TIMESTAMP(), 'TCC', 1),
 ('mp_rice_kar',      'Rice',      NULL, 'kg', 700, 900, 800, 'Karonga Market',   'Northern','MWK', NULL, UTC_TIMESTAMP(), 'ADMARC', 1),
 ('mp_sunflower_lil', 'Sunflower', NULL, 'kg', 750, 950, 850, 'Lilongwe ADMARC',  'Central', 'MWK', NULL, UTC_TIMESTAMP(), 'ADMARC', 1),
 ('mp_beans_mzu',     'Beans',     NULL, 'kg', 900, 1200, 1050, 'Mzuzu Market',    'Northern','MWK', NULL, UTC_TIMESTAMP(), 'ADMARC', 1)
ON DUPLICATE KEY UPDATE
  `price_min` = VALUES(`price_min`), `price_max` = VALUES(`price_max`), `price_avg` = VALUES(`price_avg`),
  `recorded_at` = VALUES(`recorded_at`);
