-- Apply this to your 'the_scent' database
-- This script assumes your current 'orders' table matches the one from 'the_scent_schema.sql.txt'

-- Add new columns and modify existing ones
ALTER TABLE `orders`
    ADD COLUMN `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `user_id`,
    ADD COLUMN `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`,
    ADD COLUMN `coupon_code` VARCHAR(50) DEFAULT NULL AFTER `discount_amount`,
    ADD COLUMN `coupon_id` INT DEFAULT NULL AFTER `coupon_code`,
    ADD COLUMN `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `coupon_id`,
    ADD COLUMN `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `shipping_cost`,
    ADD COLUMN `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `tax_amount`,
    ADD COLUMN `shipping_name` VARCHAR(255) DEFAULT NULL AFTER `total_amount`,
    ADD COLUMN `shipping_email` VARCHAR(255) DEFAULT NULL AFTER `shipping_name`,
    ADD COLUMN `shipping_address` TEXT DEFAULT NULL AFTER `shipping_email`,
    ADD COLUMN `shipping_address_line2` VARCHAR(255) DEFAULT NULL AFTER `shipping_address`,
    ADD COLUMN `shipping_city` VARCHAR(100) DEFAULT NULL AFTER `shipping_address_line2`,
    ADD COLUMN `shipping_state` VARCHAR(100) DEFAULT NULL AFTER `shipping_city`,
    ADD COLUMN `shipping_zip` VARCHAR(20) DEFAULT NULL AFTER `shipping_state`,
    ADD COLUMN `shipping_country` VARCHAR(50) DEFAULT NULL AFTER `shipping_zip`,
    MODIFY COLUMN `status` ENUM('pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'disputed', 'payment_failed', 'completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending_payment',
    ADD COLUMN `payment_status` VARCHAR(50) DEFAULT 'pending' AFTER `status`,
    ADD COLUMN `payment_intent_id` VARCHAR(255) DEFAULT NULL AFTER `payment_status`,
    ADD COLUMN `order_notes` TEXT DEFAULT NULL AFTER `payment_intent_id`,
    ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
    ADD COLUMN `paid_at` DATETIME DEFAULT NULL AFTER `updated_at`,
    ADD COLUMN `dispute_id` VARCHAR(255) DEFAULT NULL AFTER `paid_at`,
    ADD COLUMN `disputed_at` DATETIME DEFAULT NULL AFTER `dispute_id`,
    ADD COLUMN `refund_id` VARCHAR(255) DEFAULT NULL AFTER `disputed_at`,
    ADD COLUMN `refunded_at` DATETIME DEFAULT NULL AFTER `refund_id`,
    ADD COLUMN `tracking_number` VARCHAR(100) DEFAULT NULL AFTER `refunded_at`,
    ADD COLUMN `carrier` VARCHAR(100) DEFAULT NULL AFTER `tracking_number`;

-- If you are sure 'total_price' is fully replaced by 'total_amount' and its data is not needed:
-- ALTER TABLE `orders` DROP COLUMN `total_price`;
-- It's recommended to verify this or migrate data before dropping.

-- Optional: If you have a 'coupons' table and want to enforce foreign key constraint:
-- Assuming 'coupons' table has an 'id' primary key.
-- ALTER TABLE `orders` ADD CONSTRAINT `fk_order_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL;

-- Add an index for faster lookups on payment_intent_id
ALTER TABLE `orders` ADD INDEX `idx_payment_intent_id` (`payment_intent_id`);

-- Verify the changes
DESCRIBE `orders`;

-- Database Schema Patch for Tax Tables (v16.2) - Included for completeness
-- This patch adds the `tax_rates` and `tax_rate_history` tables
-- if they do not already exist, as indicated by the error logs.

CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `country_code` VARCHAR(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISO 3166-1 alpha-2 country code',
  `state_code` VARCHAR(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO 3166-2 state/province code (if applicable)',
  `rate` DECIMAL(10,4) NOT NULL COMMENT 'Tax rate (e.g., 0.05 for 5%)',
  `is_active` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Whether this tax rate is currently active',
  `start_date` DATE DEFAULT NULL COMMENT 'Date when this tax rate becomes effective',
  `end_date` DATE DEFAULT NULL COMMENT 'Date when this tax rate expires (NULL if no expiry)',
  `created_by` INT DEFAULT NULL COMMENT 'User ID of the admin who created/last modified this rate',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_country_state` (`country_code`, `state_code`),
  KEY `idx_country_code` (`country_code`),
  KEY `idx_is_active` (`is_active`),
  KEY `fk_tax_rates_user` (`created_by`),
  CONSTRAINT `fk_tax_rates_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores tax rates for different regions';

CREATE TABLE IF NOT EXISTS `tax_rate_history` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tax_rate_id` INT NOT NULL,
  `old_rate` DECIMAL(10,4) DEFAULT NULL COMMENT 'Previous tax rate',
  `new_rate` DECIMAL(10,4) NOT NULL COMMENT 'New tax rate after change',
  `changed_by` INT DEFAULT NULL COMMENT 'User ID of the admin who made the change',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of when the change was made',
  PRIMARY KEY (`id`),
  KEY `idx_tax_rate_id` (`tax_rate_id`),
  KEY `fk_tax_history_user` (`changed_by`),
  CONSTRAINT `fk_tax_rate_history_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tax_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks changes to tax rates';

-- Optional: Insert some default tax rates if desired
-- Example: No tax for all countries by default (rate 0 for wildcard country '*')
INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('*', NULL, 0.0000, 1, CURDATE(), 1); -- Assuming user ID 1 is an admin

-- Example: 10% tax for US, California (CA)
INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('US', 'CA', 0.1000, 1, CURDATE(), 1);

-- Example: 5% tax for all of Canada (CA) - state_code is NULL for country-wide rate
INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('CA', NULL, 0.0500, 1, CURDATE(), 1);

