CREATE TABLE `products` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `sale_price` DECIMAL(10,2) NULL,
    `sku` VARCHAR(100) UNIQUE,
    `stock_quantity` INT DEFAULT 0,
    `main_image` VARCHAR(255),
    `images` TEXT,
    `is_active` BOOLEAN DEFAULT 1,
    `is_featured` BOOLEAN DEFAULT 0,
    `views` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;