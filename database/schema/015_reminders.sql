-- =====================================================================
-- 015_reminders — Phase 13: proactive alerts.
--
-- Only one schema change needed: a reorder threshold on inventory_items
-- for low-stock alerts. Livestock due-date reminders reuse the existing,
-- previously-unused animal_health.next_due_date column; price alerts
-- compare against the existing market_prices table; weather alerts are
-- derived from the existing weather_cache payload. No new tables.
-- =====================================================================

ALTER TABLE `inventory_items`
  ADD COLUMN `reorder_threshold` DECIMAL(14,3) NULL AFTER `quantity`;
