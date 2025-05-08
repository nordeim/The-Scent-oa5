-- SQL script to populate product_attributes for The Scent Quiz

-- Clear existing attributes if necessary (optional, use with caution)
-- DELETE FROM product_attributes;

-- Link products to mood effects, scent types, and intensity
-- Use plausible values based on product names/descriptions

-- Relaxation / Calming Products
INSERT INTO `product_attributes` (`product_id`, `scent_type`, `mood_effect`, `intensity_level`) VALUES
(1, 'floral', 'calming', 'medium'),  -- Lavender Serenity Oil
(6, 'floral', 'calming', 'medium'),  -- Lavender Dreams Soap
(11, 'floral', 'calming', 'strong'), -- Peaceful Night Blend (assuming strong for sleep)
(14, 'woody', 'calming', 'medium');   -- Stress Relief Blend (assuming woody/earthy notes)

-- Energy / Energizing Products
INSERT INTO `product_attributes` (`product_id`, `scent_type`, `mood_effect`, `intensity_level`) VALUES
(2, 'citrus', 'energizing', 'strong'), -- Citrus Burst Oil
(5, 'fresh', 'energizing', 'strong'),  -- Eucalyptus Fresh Oil
(7, 'citrus', 'energizing', 'medium'), -- Citrus Morning Soap
(10, 'fresh', 'energizing', 'medium'), -- Mountain Air Soap (mint/eucalyptus -> fresh)
(12, 'citrus', 'energizing', 'strong'); -- Morning Energy Blend

-- Focus / Focusing Products
INSERT INTO `product_attributes` (`product_id`, `scent_type`, `mood_effect`, `intensity_level`) VALUES
(3, 'woody', 'focusing', 'medium'),  -- Forest Pine Oil
(8, 'woody', 'focusing', 'medium'),  -- Forest Walk Soap (pine/cedar)
(13, 'fresh', 'focusing', 'strong');  -- Focus Master Blend (often mint/rosemary -> fresh/herbaceous)

-- Balance / Balancing Products
INSERT INTO `product_attributes` (`product_id`, `scent_type`, `mood_effect`, `intensity_level`) VALUES
(4, 'floral', 'balancing', 'medium'), -- Rose Harmony Oil
(9, 'floral', 'balancing', 'light'),   -- Rose Petal Soap
(15, 'oriental', 'balancing', 'medium'); -- Balance & Harmony (assuming warm/complex notes)

-- Verify the insertions (optional)
-- SELECT p.id, p.name, pa.* FROM products p JOIN product_attributes pa ON p.id = pa.product_id ORDER BY pa.mood_effect, p.id;
