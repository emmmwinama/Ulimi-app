-- Seed the public site's editable content so Phase 10 renders real copy
-- from the database instead of hardcoded HTML. Idempotent via fixed ids
-- / primary keys.

INSERT INTO `site_content` (`key`, `value`, `type`, `group`, `label`, `updated_at`, `updated_by`) VALUES
 ('hero_eyebrow',   'Farm records & evidence',                                   'text', 'hero', 'Hero eyebrow',    UTC_TIMESTAMP(), NULL),
 ('hero_title',     'The record vault your farm can prove.',                     'text', 'hero', 'Hero title',      UTC_TIMESTAMP(), NULL),
 ('hero_subtitle',  'Capture fields, crops, activities, labour, finance and livestock in one place — then hand a buyer, lender, auditor or insurer exactly the evidence they ask for.', 'text', 'hero', 'Hero subtitle', UTC_TIMESTAMP(), NULL),
 ('contact_email',  'support@agrivault.local',                                   'text', 'contact', 'Support email', UTC_TIMESTAMP(), NULL)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO `cms_features` (`id`, `icon`, `title`, `description`, `sort_order`, `is_active`) VALUES
 ('feat_records',  'leaf',      'Every record in one vault',   'Fields, crops, activities, inputs, labour, finance, inventory and livestock — structured, searchable, and linked.', 1, 1),
 ('feat_proof',    'shield',    'Built to be proven',          'Buyer packs, loan-readiness files, audit and insurance evidence generated from your real records.', 2, 1),
 ('feat_team',     'users',     'Team access, done right',     'Owner, manager, agronomist, accountant and field-worker roles with per-resource permissions.', 3, 1),
 ('feat_economics','bar-chart', 'Season economics',            'Cashflow, crop and field profitability, cost per hectare and per kilogram.', 4, 1),
 ('feat_credit',   'gauge',     'Credit readiness',            'A transparent score from your own data — cashflow, activity cost and revenue factors.', 5, 1),
 ('feat_map',      'map',       'Field mapping',                'Draw field boundaries and management zones; mark boreholes, sheds, gates and roads.', 6, 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `testimonials` (`id`, `quote`, `name`, `role`, `initials`, `is_active`, `sort_order`, `created_at`) VALUES
 ('test_1', 'For the first time I can hand a bank officer a season''s worth of records instead of a shoebox of receipts.', 'Grace Banda', 'Smallholder farmer, Mchinji', 'GB', 1, 1, UTC_TIMESTAMP()),
 ('test_2', 'Our field team logs activities from their phones; I see the season''s cost picture without chasing anyone.', 'Kondwani Phiri', 'Farm manager, Kasungu', 'KP', 1, 2, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE `quote` = VALUES(`quote`);

INSERT INTO `cms_pages` (`id`, `slug`, `title`, `content`, `is_public`, `updated_at`) VALUES
 ('page_privacy', 'privacy', 'Privacy Policy', '<p>AgriVault stores your farm records to provide the service you sign up for. We do not sell your data. Contact support to request an export or deletion of your account.</p>', 1, UTC_TIMESTAMP()),
 ('page_terms',   'terms',   'Terms of Service', '<p>By using AgriVault you agree to use the service for lawful farm record-keeping. Accounts may be suspended for abuse or non-payment. See your subscription plan for feature and storage limits.</p>', 1, UTC_TIMESTAMP()),
 ('page_security','security','Security', '<p>Passwords are hashed with Argon2id/bcrypt. All state-changing requests are CSRF-protected. Every farm-scoped query is restricted to your account''s farm membership. Report a security concern to the support email on the Contact page.</p>', 1, UTC_TIMESTAMP()),
 ('page_about',   'about',   'About AgriVault', '<p>AgriVault is a farm-record vault built for field teams, managers, accountants, lenders, buyers, auditors and insurers who need real, provable farm data.</p>', 1, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `content` = VALUES(`content`);
