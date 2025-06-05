-- Создание таблицы users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
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
    user_id INT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    service_id INT NOT NULL,
    description TEXT,
    status ENUM('new', 'in_progress', 'completed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Создание таблицы request_files
CREATE TABLE request_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Создание таблицы request_comments
CREATE TABLE request_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Создание таблицы reviews
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    author VARCHAR(255) NOT NULL,
    text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Вставка тестовых данных

-- Тестовые пользователи
INSERT INTO users (name, phone, email, password, role) VALUES
('Администратор', '1234567890', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Клиент', '0987654321', 'client@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client');

-- Тестовые категории
INSERT INTO categories (name) VALUES
('Крупная бытовая техника'),
('Мелкая бытовая техника'),
('Портативная техника'),
('Компьютерная техника'),
('Фото и видеотехника');

-- Тестовые услуги
INSERT INTO services (category_id, name, description, price, image_path) VALUES
-- Крупная бытовая техника (category_id = 1)
(1, 'Ремонт стиральной машины', 'Диагностика и ремонт любых неисправностей стиральных машин.', 5000.00, 'images/services/washing_machine_repair.jpg'),
(1, 'Ремонт холодильника', 'Устранение утечек хладагента, замена компрессора.', 4500.00, 'images/services/refrigerator_repair.jpg'),
(1, 'Ремонт посудомоечной машины', 'Ремонт насоса, устранение засоров.', 5500.00, 'images/services/dishwasher_repair.jpg'),
(1, 'Ремонт духового шкафа', 'Замена нагревательных элементов, ремонт электроники.', 4000.00, 'images/services/oven_repair.jpg'),
(1, 'Ремонт варочной панели', 'Ремонт индукционных и электрических панелей.', 3500.00, 'images/services/cooktop_repair.jpg'),
(1, 'Ремонт сушильной машины', 'Замена ремня, ремонт барабана.', 4800.00, 'images/services/dryer_repair.jpg'),
(1, 'Ремонт морозильной камеры', 'Ремонт системы охлаждения, замена термостата.', 4200.00, 'images/services/freezer_repair.jpg'),
(1, 'Ремонт винного шкафа', 'Настройка системы охлаждения, ремонт электроники.', 6000.00, 'images/services/wine_cooler_repair.jpg'),
(1, 'Ремонт вытяжки', 'Замена мотора, ремонт фильтров.', 3000.00, 'images/services/range_hood_repair.jpg'),
(1, 'Ремонт микроволновой печи', 'Замена магнетрона, ремонт платы управления.', 3200.00, 'images/services/microwave_repair.jpg'),
-- Мелкая бытовая техника (category_id = 2)
(2, 'Ремонт кофемашины', 'Чистка системы, замена насоса.', 4000.00, 'images/services/coffee_machine_repair.jpg'),
(2, 'Ремонт блендера', 'Замена ножей, ремонт двигателя.', 2000.00, 'images/services/blender_repair.jpg'),
(2, 'Ремонт миксера', 'Ремонт мотора, замена шестерёнок.', 1800.00, 'images/services/mixer_repair.jpg'),
(2, 'Ремонт тостера', 'Замена нагревательных элементов, ремонт таймера.', 2200.00, 'images/services/toaster_repair.jpg'),
(2, 'Ремонт электрочайника', 'Замена нагревателя, ремонт кнопки включения.', 2000.00, 'images/services/kettle_repair.jpg'),
(2, 'Ремонт мультиварки', 'Ремонт платы управления, замена датчиков.', 3500.00, 'images/services/multicooker_repair.jpg'),
(2, 'Ремонт пароварки', 'Замена нагревательного элемента, ремонт корпуса.', 2800.00, 'images/services/steamer_repair.jpg'),
(2, 'Ремонт соковыжималки', 'Ремонт двигателя, замена фильтров.', 3000.00, 'images/services/juicer_repair.jpg'),
(2, 'Ремонт мясорубки', 'Замена шестерён, ремонт мотора.', 2500.00, 'images/services/meat_grinder_repair.jpg'),
(2, 'Ремонт утюга', 'Ремонт парогенератора, замена шнура.', 2300.00, 'images/services/iron_repair.jpg'),
-- Портативная техника (category_id = 3)
(3, 'Ремонт смартфона', 'Замена экрана, батареи, разъёмов.', 3000.00, 'images/services/smartphone_repair.jpg'),
(3, 'Ремонт планшета', 'Ремонт экрана, замена аккумулятора.', 3500.00, 'images/services/tablet_repair.jpg'),
(3, 'Ремонт смарт-часов', 'Замена дисплея, ремонт датчиков.', 4000.00, 'images/services/smartwatch_repair.jpg'),
(3, 'Ремонт беспроводных наушников', 'Ремонт динамиков, замена батареи.', 2500.00, 'images/services/wireless_headphones_repair.jpg'),
(3, 'Ремонт электронной книги', 'Замена экрана, ремонт кнопок.', 2800.00, 'images/services/ebook_reader_repair.jpg'),
(3, 'Ремонт портативной колонки', 'Ремонт динамика, замена аккумулятора.', 2700.00, 'images/services/portable_speaker_repair.jpg'),
(3, 'Ремонт MP3-плеера', 'Замена разъёма, ремонт платы.', 2000.00, 'images/services/mp3_player_repair.jpg'),
(3, 'Ремонт фитнес-браслета', 'Замена экрана, ремонт датчиков.', 2300.00, 'images/services/fitness_tracker_repair.jpg'),
(3, 'Ремонт портативного проектора', 'Ремонт лампы, настройка оптики.', 5000.00, 'images/services/portable_projector_repair.jpg'),
(3, 'Ремонт GPS-навигатора', 'Обновление ПО, ремонт экрана.', 2600.00, 'images/services/gps_navigator_repair.jpg'),
-- Компьютерная техника (category_id = 4)
(4, 'Ремонт ноутбука', 'Замена клавиатуры, ремонт материнской платы.', 4000.00, 'images/services/laptop_repair.jpg'),
(4, 'Ремонт настольного ПК', 'Замена блока питания, ремонт видеокарты.', 4500.00, 'images/services/desktop_pc_repair.jpg'),
(4, 'Ремонт монитора', 'Ремонт подсветки, замена матрицы.', 3500.00, 'images/services/monitor_repair.jpg'),
(4, 'Ремонт принтера', 'Ремонт подачи бумаги, замена картриджа.', 3000.00, 'images/services/printer_repair.jpg'),
(4, 'Ремонт сканера', 'Ремонт оптики, замена лампы.', 2800.00, 'images/services/scanner_repair.jpg'),
(4, 'Ремонт роутера', 'Обновление прошивки, ремонт антенн.', 2500.00, 'images/services/router_repair.jpg'),
(4, 'Ремонт внешнего жесткого диска', 'Восстановление данных, ремонт платы.', 4000.00, 'images/services/external_hdd_repair.jpg'),
(4, 'Ремонт материнской платы', 'Замена чипов, ремонт цепей питания.', 6000.00, 'images/services/motherboard_repair.jpg'),
(4, 'Ремонт видеокарты', 'Замена кулера, ремонт GPU.', 5500.00, 'images/services/graphics_card_repair.jpg'),
(4, 'Ремонт блока питания ПК', 'Замена конденсаторов, ремонт схемы.', 3000.00, 'images/services/power_supply_repair.jpg'),
-- Фото и видеотехника (category_id = 5)
(5, 'Ремонт цифрового фотоаппарата', 'Ремонт объектива, замена матрицы.', 4500.00, 'images/services/digital_camera_repair.jpg'),
(5, 'Ремонт видеокамеры', 'Ремонт оптики, замена микрофона.', 5000.00, 'images/services/video_camera_repair.jpg'),
(5, 'Ремонт экшн-камеры', 'Замена корпуса, ремонт объектива.', 3500.00, 'images/services/action_camera_repair.jpg'),
(5, 'Ремонт зеркального фотоаппарата', 'Ремонт затвора, калибровка автофокуса.', 6000.00, 'images/services/dslr_camera_repair.jpg'),
(5, 'Ремонт дрона', 'Ремонт моторов, замена камеры.', 5500.00, 'images/services/drone_repair.jpg'),
(5, 'Ремонт штатива', 'Ремонт креплений, замена шарниров.', 2000.00, 'images/services/tripod_repair.jpg'),
(5, 'Ремонт вспышки', 'Замена лампы, ремонт платы.', 3000.00, 'images/services/flash_repair.jpg'),
(5, 'Ремонт проектора', 'Замена лампы, чистка оптики.', 6500.00, 'images/services/projector_repair.jpg'),
(5, 'Ремонт бинокля', 'Настройка оптики, ремонт корпуса.', 2500.00, 'images/services/binoculars_repair.jpg'),
(5, 'Ремонт телескопа', 'Калибровка оптики, ремонт монтировки.', 4000.00, 'images/services/telescope_repair.jpg');

-- Тестовые отзывы
INSERT INTO reviews (request_id, user_id, author, text) VALUES
(1, 1, 'Администратор', 'Отличный сервис! Быстро и качественно отремонтировали мой холодильник.'),
(2, 2, 'Клиент', 'Очень довольна ремонтом смартфона. Спасибо!'),
(3, 2, 'Клиент', 'Профессиональный подход и доступные цены.');