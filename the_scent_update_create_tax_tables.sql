-- Database Schema Patch for Tax Tables (v16.2)
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
-- INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
-- VALUES ('*', NULL, 0.0000, 1, CURDATE(), 1); -- Assuming user ID 1 is an admin

-- Example: 10% tax for US, California (CA)
-- INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
-- VALUES ('US', 'CA', 0.1000, 1, CURDATE(), 1);

-- Example: 5% tax for all of Canada (CA) - state_code is NULL for country-wide rate
-- INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
-- VALUES ('CA', NULL, 0.0500, 1, CURDATE(), 1);