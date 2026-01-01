CREATE TABLE `reviews` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `rating` INT CHECK (`rating` >= 1 AND `rating` <= 5),
    `comment` TEXT,
    `is_approved` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;