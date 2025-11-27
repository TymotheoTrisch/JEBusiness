<?php
// Migration MySQL: cria tabela 'users'
$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `qty` INT NOT NULL,
    `type` ENUM('in','out') NOT NULL,
    `reason` VARCHAR(255) NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_movements_product
      FOREIGN KEY (product_id) REFERENCES products(id)
      ON DELETE CASCADE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

return $sql;