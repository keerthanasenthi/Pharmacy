-- Add BARCODE column to meds for camera/scan lookup
-- Run this once in phpMyAdmin (select pharmacy database) or: mysql -u root pharmacy < db_add_barcode.sql

ALTER TABLE `meds` ADD COLUMN `BARCODE` VARCHAR(32) DEFAULT NULL AFTER `LOCATION_RACK`;
