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

-- Drop the old total_price column if it's no longer needed and data has been migrated to total_amount
-- ALTER TABLE `orders` DROP COLUMN `total_price`;
-- It's safer to keep it for a while or handle its data before dropping.
-- For now, we'll assume total_amount is the primary source.

-- Add foreign key for coupon_id if coupons table exists and has an id column
-- ALTER TABLE `orders` ADD CONSTRAINT `fk_order_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL;

-- Add index for payment_intent_id
ALTER TABLE `orders` ADD INDEX `idx_payment_intent_id` (`payment_intent_id`);
