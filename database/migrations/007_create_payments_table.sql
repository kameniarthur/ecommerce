CREATE TABLE `payments` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `payment_method` VARCHAR(50),
    `amount` DECIMAL(10,2) NOT NULL,
    `transaction_id` VARCHAR(100),
    `status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;