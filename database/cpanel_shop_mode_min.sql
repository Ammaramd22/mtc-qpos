-- MINIMAL fix if Shop Mode save fails with "Unknown column shop_mode"
-- phpMyAdmin → your QPOS database → SQL → paste → Go

ALTER TABLE `business`
  ADD COLUMN `shop_mode` VARCHAR(50) NULL DEFAULT NULL AFTER `enabled_modules`;
