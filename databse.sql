-- Создание таблицы users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'worker', 'editor', 'client') NOT NULL DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Создание таблицы categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Создание таблицы services
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Создание таблицы requests
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    name VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    service_id INT NOT NULL,
    description TEXT NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    status ENUM('new', 'in_progress', 'completed') DEFAULT 'new',
    comment TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Создание таблицы reviews
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author VARCHAR(255) NOT NULL,
    text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Вставка тестовых данных

-- Тестовые пользователи
INSERT INTO users (name, phone, email, address, password, role) VALUES
('Администратор', '1234567890', 'admin@example.com', 'Адрес администратора', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Клиент', '0987654321', 'client@example.com', 'Адрес клиента', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client');

-- Тестовые категории
INSERT INTO categories (name) VALUES
('Бытовая техника'),
('Электроника');

-- Тестовые услуги
INSERT INTO services (category_id, name, description, price, image_path) VALUES
(1, 'Ремонт стиральной машины', 'Полный ремонт стиральных машин любых марок', 5000.00, 'images/services/washing_machine.jpg'),
(1, 'Ремонт холодильника', 'Диагностика и ремонт холодильников', 4500.00, 'images/services/refrigerator.jpg'),
(2, 'Ремонт смартфона', 'Замена экрана, батареи и других компонентов', 3000.00, 'images/services/smartphone.jpg'),
(2, 'Ремонт ноутбука', 'Ремонт и обслуживание ноутбуков', 4000.00, 'images/services/laptop.jpg'),
(2, 'Ремонт планшета', 'Ремонт планшетов любых марок', 3500.00, 'images/services/tablet.jpg');

-- Тестовые отзывы
INSERT INTO reviews (author, text) VALUES
('Иван Иванов', 'Отличный сервис! Быстро и качественно отремонтировали мой холодильник.'),
('Мария Петрова', 'Очень довольна ремонтом смартфона. Спасибо!'),
('Алексей Сидоров', 'Профессиональный подход и доступные цены.');