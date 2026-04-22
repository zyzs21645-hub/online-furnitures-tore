CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS furniture_items (
    item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    category_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (item_id),
    KEY idx_furniture_items_category_id (category_id),
    CONSTRAINT fk_furniture_items_category
        FOREIGN KEY (category_id) REFERENCES categories (category_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (full_name, email, password, role)
VALUES ('Admin User', 'admin@example.com', '$2y$10$m9SIjQpLx27SQWx7/OnfYOuQiUEK5fc8hcDc6CwfYYodCNxser7R2', 'admin')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    role = VALUES(role);

INSERT INTO categories (category_name)
VALUES
    ('Living Room'),
    ('Bedroom'),
    ('Dining Room'),
    ('Office')
ON DUPLICATE KEY UPDATE
    category_name = VALUES(category_name);

INSERT INTO furniture_items (item_name, description, price, stock_quantity, image, category_id)
SELECT 'Modern Sofa', 'A comfortable three-seat sofa for contemporary living rooms.', 1499.00, 8, NULL, c.category_id
FROM categories c
WHERE c.category_name = 'Living Room'
  AND NOT EXISTS (
      SELECT 1
      FROM furniture_items fi
      WHERE fi.item_name = 'Modern Sofa'
  );
