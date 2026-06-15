SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS mirigrushek;
CREATE DATABASE mirigrushek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mirigrushek;

CREATE TABLE Roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE OrderStatuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE PickupPoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    role_id INT NOT NULL,
    login VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(150) NOT NULL,
    CONSTRAINT fk_users_person FOREIGN KEY (person_id) REFERENCES Persons(id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES Roles(id)
) ENGINE=InnoDB;

CREATE TABLE Products (
    article VARCHAR(20) PRIMARY KEY,
    name VARCHAR(500) NOT NULL,
    unit_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    supplier_id INT NOT NULL,
    manufacturer_id INT NOT NULL,
    category_id INT NOT NULL,
    discount INT NOT NULL DEFAULT 0,
    stock_qty INT NOT NULL DEFAULT 0,
    description TEXT,
    photo VARCHAR(255),
    CONSTRAINT fk_prod_unit FOREIGN KEY (unit_id) REFERENCES Units(id),
    CONSTRAINT fk_prod_sup FOREIGN KEY (supplier_id) REFERENCES Suppliers(id),
    CONSTRAINT fk_prod_man FOREIGN KEY (manufacturer_id) REFERENCES Manufacturers(id),
    CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES Categories(id),
    CONSTRAINT chk_price CHECK (price >= 0),
    CONSTRAINT chk_discount CHECK (discount >= 0),
    CONSTRAINT chk_stock CHECK (stock_qty >= 0)
) ENGINE=InnoDB;

CREATE TABLE Orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_date DATE NULL,
    delivery_date DATE NULL,
    pickup_point_id INT NULL,
    client_user_id INT NULL,
    receive_code VARCHAR(20),
    status_id INT NOT NULL,
    CONSTRAINT fk_ord_point FOREIGN KEY (pickup_point_id) REFERENCES PickupPoints(id),
    CONSTRAINT fk_ord_client FOREIGN KEY (client_user_id) REFERENCES Users(id),
    CONSTRAINT fk_ord_status FOREIGN KEY (status_id) REFERENCES OrderStatuses(id)
) ENGINE=InnoDB;

CREATE TABLE OrderItems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_article VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES Orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_product FOREIGN KEY (product_article) REFERENCES Products(article),
    CONSTRAINT chk_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

INSERT INTO Roles (id, name) VALUES
    (1, 'Администратор'),
    (2, 'Менеджер'),
    (3, 'Авторизированный клиент');

INSERT INTO Categories (id, name) VALUES
    (1, 'Игровой набор'),
    (2, 'Конструктор'),
    (3, 'Детский музыкальный инструмент'),
    (4, 'Машинка');

INSERT INTO Suppliers (id, name) VALUES
    (1, 'Pikeshop'),
    (2, 'Playbig'),
    (3, 'Knauf'),
    (4, 'CHILITOY'),
    (5, 'Vinylon');

INSERT INTO Manufacturers (id, name) VALUES
    (1, 'ABSпластик'),
    (2, 'BambiniFelici'),
    (3, 'Junion');

INSERT INTO Units (id, name) VALUES
    (1, 'шт.');

INSERT INTO OrderStatuses (id, name) VALUES
    (1, 'Завершен'),
    (2, 'Новый');

INSERT INTO PickupPoints (id, address) VALUES
    (1, '420151, г. Лесной, ул. Вишневая, 32'),
    (2, '125061, г. Лесной, ул. Подгорная, 8'),
    (3, '630370, г. Лесной, ул. Шоссейная, 24'),
    (4, '400562, г. Лесной, ул. Зеленая, 32'),
    (5, '614510, г. Лесной, ул. Маяковского, 47'),
    (6, '410542, г. Лесной, ул. Светлая, 46'),
    (7, '620839, г. Лесной, ул. Цветочная, 8'),
    (8, '443890, г. Лесной, ул. Коммунистическая, 1'),
    (9, '603379, г. Лесной, ул. Спортивная, 46'),
    (10, '603721, г. Лесной, ул. Гоголя, 41'),
    (11, '410172, г. Лесной, ул. Северная, 13'),
    (12, '614611, г. Лесной, ул. Молодежная, 50'),
    (13, '454311, г.Лесной, ул. Новая, 19'),
    (14, '660007, г.Лесной, ул. Октябрьская, 19'),
    (15, '603036, г. Лесной, ул. Садовая, 4'),
    (16, '394060, г.Лесной, ул. Фрунзе, 43'),
    (17, '410661, г. Лесной, ул. Школьная, 50'),
    (18, '625590, г. Лесной, ул. Коммунистическая, 20'),
    (19, '625683, г. Лесной, ул. 8 Марта'),
    (20, '450983, г.Лесной, ул. Комсомольская, 26'),
    (21, '394782, г. Лесной, ул. Чехова, 3'),
    (22, '603002, г. Лесной, ул. Дзержинского, 28'),
    (23, '450558, г. Лесной, ул. Набережная, 30'),
    (24, '344288, г. Лесной, ул. Чехова, 1'),
    (25, '614164, г.Лесной,  ул. Степная, 30'),
    (26, '394242, г. Лесной, ул. Коммунистическая, 43'),
    (27, '660540, г. Лесной, ул. Солнечная, 25'),
    (28, '125837, г. Лесной, ул. Шоссейная, 40'),
    (29, '125703, г. Лесной, ул. Партизанская, 49'),
    (30, '625283, г. Лесной, ул. Победы, 46'),
    (31, '614753, г. Лесной, ул. Полевая, 35'),
    (32, '426030, г. Лесной, ул. Маяковского, 44'),
    (33, '450375, г. Лесной ул. Клубная, 44'),
    (34, '625560, г. Лесной, ул. Некрасова, 12'),
    (35, '630201, г. Лесной, ул. Комсомольская, 17'),
    (36, '190949, г. Лесной, ул. Мичурина, 26');

INSERT INTO Persons (id, full_name) VALUES
    (1, 'Ворсин Петр Евгеньевич'),
    (2, 'Старикова Елена Павловна'),
    (3, 'Одинцов Серафим Артёмович'),
    (4, 'Сазонов Руслан Германович'),
    (5, 'Михайлюк Анна Вячеславовна'),
    (6, 'Ситдикова Елена Анатольевна'),
    (7, 'Степанов Михаил Артёмович'),
    (8, 'Никифорова Весения Николаевна');

INSERT INTO Users (id, person_id, role_id, login, password) VALUES
    (1, 1, 1, '94d5ous@gmail.com', 'uzWC67'),
    (2, 2, 1, 'uth4iz@mail.com', '2L6KZG'),
    (3, 3, 1, '4np6se@mail.com', '8ntwUp'),
    (4, 4, 2, 'yzls62@outlook.com', 'YOyhfR'),
    (5, 5, 2, '1diph5e@tutanota.com', 'RSbvHv'),
    (6, 6, 2, 'tjde7c@yahoo.com', 'rwVDh9'),
    (7, 7, 3, '5d4zbu@tutanota.com', 'LdNyos'),
    (8, 1, 3, 'ptec8ym@yahoo.com', 'gynQMT'),
    (9, 2, 3, '1qz4kw@mail.com', 'AtnDjr'),
    (10, 8, 3, 'wpmrc3do@tutanota.com', 'JlFRCZ');

INSERT INTO Products (article, name, unit_id, price, supplier_id, manufacturer_id, category_id, discount, stock_qty, description, photo) VALUES
    ('PMEZMH', 'Детский игровой набор машинок Щенячий патруль / Dogs mini . 9 героев + 9 инерфионных машинок', 1, 1414.00, 1, 1, 1, 22, 50, 'Детский набор машинок с героями мультсериала «Щенячий патруль» подойдет как для мальчиков, так и для девочек. В детский набор входит 9 фигурок щенков спасателей.', '1.jpg'),
    ('BPV4MM', 'Конструктор Гарри Поттер Сова Букля 630 деталей совместим с lego harry potter, лего совместимый)', 1, 771.00, 2, 1, 2, 15, 26, 'Коллекционная модель Букля состоит из множества потрясающих элементов, а также специального механизма внутри. С его помощью можно плавно поднимать-опускать крылья птицы.', '2.jpg'),
    ('JVL42J', 'Музыкальные инструменты для детей, ксилофон, барабаны, развивающие игрушки, игрушки для детей', 1, 2750.00, 2, 2, 3, 15, 0, 'Откройте мир музыки для вашего ребенка с этой уникальной игрушкой! Это многофункциональное музыкальное чудо объединяет в себе всё, что нужно для творческого развития.', '3.jpg'),
    ('F895RB', 'Машинка игрушка диско шар светящаяся музыкальная', 1, 368.00, 3, 1, 4, 6, 7, 'Светящаяся музыкальная машина с диско шаром переливается разными цветами, играет ритмичные мелодии, объезжает препятствия и крутится, поэтому с ней точно не будет скучно.', '4.jpg'),
    ('3XBOTN', 'Игровой набор Hot Wheels Action Loop Cyclone Challenge Track, с машинкой и удобным хранением, HTK16', 1, 3426.00, 3, 2, 1, 10, 21, 'Игровой набор Hot Wheels Action Loop Cyclone Challenge Track - это уникальная игра, которая позволит вам испытать себя и своих друзей в скорости и ловкости.', '5.jpg'),
    ('3L7RCZ', 'Игровой набор с деревянными машинками Стройплощадка Кран-Паркс, Junion', 1, 7400.00, 3, 3, 1, 15, 0, 'Игровой набор «Стройплощадка Кран-Паркс Junion» — это большая игрушечная парковка с деревянными машинками и настоящим подъёмным краном.', '6.jpg'),
    ('S72AM3', 'Синтезатор детский с микрофоном 61 клавиша', 1, 1749.00, 4, 3, 3, 10, 35, 'Откройте для ребенка дверь в мир музыки с детским синтезатором! Этот компактный инструмент с микрофоном станет верным другом для юных музыкантов.', '7.jpg'),
    ('2G3280', 'Деревянный игровой набор JUNION Стройплощадка "Кран-Паркс" с подъёмным, строительным краном и машинками, 18 предметов, подвижные элементы', 1, 1624.00, 5, 3, 1, 9, 20, 'Игровой набор «Стройплощадка Кран-Паркс Junion» — это большая игрушечная парковка с деревянными машинками и настоящим подъёмным краном.', '8.jpg'),
    ('MIO8YV', 'Музыкальная игрушка интерактивная Пульт, детский прорезыватель для малышей', 1, 305.00, 5, 2, 3, 9, 31, 'Музыкальная игрушка интерактивная Пульт, детский прорезыватель для малышей', '9.jpg'),
    ('UER2QD', 'Большой набор опытов и экспериментов для детей 14 в 1', 1, 2506.00, 5, 2, 1, 8, 27, 'Большой набор опытов и экспериментов для детей 14 в 1', '10.jpg');

INSERT INTO Orders (id, order_date, delivery_date, pickup_point_id, client_user_id, receive_code, status_id) VALUES
    (1, '2025-02-27', '2025-04-20', 1, 4, '901', 1),
    (2, '2024-09-28', '2025-04-21', 11, 7, '902', 1),
    (3, '2025-03-21', '2025-04-22', 2, 8, '903', 1),
    (4, '2025-02-20', '2025-04-23', 11, 9, '904', 1),
    (5, '2025-03-17', '2025-04-24', 2, 4, '905', 1),
    (6, '2025-03-01', '2025-04-25', 15, 7, '906', 1),
    (7, NULL, '2025-04-26', 3, 8, '907', 1),
    (8, '2025-03-31', '2025-04-27', 19, 9, '908', 2),
    (9, '2025-04-02', '2025-04-28', 5, 4, '909', 2),
    (10, '2025-04-03', '2025-04-29', 19, 4, '910', 2);

INSERT INTO OrderItems (order_id, product_article, quantity) VALUES
    (1, 'PMEZMH', 2), (1, 'BPV4MM', 2),
    (2, 'JVL42J', 1), (2, 'F895RB', 1),
    (3, '3XBOTN', 10), (3, '3L7RCZ', 10),
    (4, 'S72AM3', 5), (4, '2G3280', 4),
    (5, 'MIO8YV', 2), (5, 'UER2QD', 2),
    (6, 'PMEZMH', 2), (6, 'BPV4MM', 2),
    (7, 'JVL42J', 1), (7, 'F895RB', 1),
    (8, '3XBOTN', 10), (8, '3L7RCZ', 10),
    (9, 'S72AM3', 5), (9, '2G3280', 4),
    (10, 'MIO8YV', 2), (10, 'UER2QD', 2);

SET FOREIGN_KEY_CHECKS = 1;

ALTER USER IF EXISTS 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '123';
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED WITH caching_sha2_password BY '123';
ALTER USER IF EXISTS 'root'@'%' IDENTIFIED WITH caching_sha2_password BY '123';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
