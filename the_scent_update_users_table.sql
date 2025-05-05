-- Content of the_scent_update_users_table.sql (example)
ALTER TABLE `users`
    ADD COLUMN `status` enum('active','inactive','locked') NOT NULL DEFAULT 'active' COMMENT 'User account status (active, inactive, locked)' AFTER `role`,
    ADD COLUMN `newsletter_subscribed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Flag indicating newsletter subscription (0=No, 1=Yes)' AFTER `status`,
    ADD COLUMN `reset_token` varchar(255) DEFAULT NULL COMMENT 'Secure token for password reset requests' AFTER `newsletter_subscribed`,
    ADD COLUMN `reset_token_expires_at` datetime DEFAULT NULL COMMENT 'Expiry timestamp for the password reset token' AFTER `reset_token`,
    ADD COLUMN `address_line1` varchar(255) DEFAULT NULL COMMENT 'Primary address line' AFTER `reset_token_expires_at`,
    ADD COLUMN `address_line2` varchar(255) DEFAULT NULL COMMENT 'Secondary address line (optional)' AFTER `address_line1`,
    ADD COLUMN `city` varchar(100) DEFAULT NULL COMMENT 'City name' AFTER `address_line2`,
    ADD COLUMN `state` varchar(100) DEFAULT NULL COMMENT 'State / Province / Region' AFTER `city`,
    ADD COLUMN `postal_code` varchar(20) DEFAULT NULL COMMENT 'Postal or ZIP code' AFTER `state`,
    ADD COLUMN `country` varchar(50) DEFAULT NULL COMMENT 'Country name or code' AFTER `postal_code`,
    ADD COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of the last record update' AFTER `created_at`,
    ADD INDEX `idx_reset_token` (`reset_token`),
    ADD INDEX `idx_status` (`status`);

-- It's crucial to ensure the 'created_at' column also exists and is properly defined,
-- potentially with a default CURRENT_TIMESTAMP if not already set.
-- ALTER TABLE `users` MODIFY COLUMN `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP;
