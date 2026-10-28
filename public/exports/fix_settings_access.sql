-- Fix: enable all Business Settings options after install (phpMyAdmin)
-- Run this on your live database if Modules / Offline / settings toggles won't save or are missing.

-- 1) Ensure visibility row exists and ALL options are ON
DELETE FROM `system` WHERE `key` = 'settings_menu_visibility';
INSERT INTO `system` (`key`, `value`) VALUES (
  'settings_menu_visibility',
  '{"business_settings":1,"business_locations":1,"invoice_settings":1,"barcode_settings":1,"receipt_printers":1,"business":1,"tax":1,"product":1,"contact":1,"sale":1,"pos":1,"display_screen":1,"purchases":1,"payment":1,"dashboard":1,"system":1,"prefixes":1,"email_settings":1,"sms_settings":1,"reward_point_settings":1,"modules":1,"custom_labels":1,"api":1,"bartender":1,"weighing_scale_label":1,"weighing_scale_barcode_scan":1,"costcode":1,"offline_billing":1,"pwa":1,"ebill":1,"mobile_shop":1}'
);

-- 2) Optional: add software owner column (skip if you get "Duplicate column" error)
-- ALTER TABLE `users` ADD `is_software_owner` TINYINT(1) NOT NULL DEFAULT 0 AFTER `allow_login`;

-- 3) Optional: make your admin the software owner (change username if needed)
-- UPDATE `users` SET `is_software_owner` = 1 WHERE `username` = 'admin';
