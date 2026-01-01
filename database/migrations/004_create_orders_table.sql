CREATE TABLE `orders` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT UNSIGNED,
    `order_number` VARCHAR(50) UNIQUE,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    `shipping_address` TEXT,
    `billing_address` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;