-- טבלת לפני/אחרי
CREATE TABLE before_after (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title_he VARCHAR(255) NOT NULL COMMENT 'כותרת בעברית',
    before_image VARCHAR(500) NOT NULL COMMENT 'תמונת לפני',
    after_image VARCHAR(500) NOT NULL COMMENT 'תמונת אחרי',
    description TEXT COMMENT 'תיאור',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sort (sort_order),
    INDEX idx_active (is_active)
);

-- טבלת משתמשים
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
    avatar_color VARCHAR(7) DEFAULT '#c8a97e',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--table for reset tokens--
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- טבלת קטגוריות ראשית
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_he VARCHAR(100) NOT NULL COMMENT 'שם בעברית',
    name_en VARCHAR(100) COMMENT 'שם באנגלית',
    icon VARCHAR(50) COMMENT 'אייקון',
    color VARCHAR(20) COMMENT 'צבע לקטגוריה',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- טבלת השירותים
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT 'שם השירות',
    description TEXT COMMENT 'תיאור מפורט',
    short_description VARCHAR(500) COMMENT 'תיאור קצר',
    duration INT NOT NULL COMMENT 'משך בדקות',
    base_price DECIMAL(10, 2) NOT NULL COMMENT 'מחיר בסיס',
    materials_fee DECIMAL(10, 2) DEFAULT 0 COMMENT 'תוספת חומרים',
    popular BOOLEAN DEFAULT FALSE COMMENT 'שירות פופולרי',
    featured BOOLEAN DEFAULT FALSE COMMENT 'שירות מומלץ',
    requires_consultation BOOLEAN DEFAULT FALSE COMMENT 'דורש ייעוץ',
    min_preparation_time INT DEFAULT 0 COMMENT 'זמן הכנה מינימלי בדקות',
    max_clients_per_slot INT DEFAULT 1 COMMENT 'מספר לקוחות מרבי לטיפול',
    image_url VARCHAR(500) COMMENT 'תמונה או אייקון',
    notes TEXT COMMENT 'הערות נוספות',
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    
    INDEX idx_category (category_id),
    INDEX idx_popular (popular),
    INDEX idx_featured (featured),
    INDEX idx_price (base_price)
);

-- Appointments table
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_duration` int(11) NOT NULL,
  `service_price` decimal(10,2) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('confirmed','cancelled','completed','no_show') DEFAULT 'confirmed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_appointment_date` (`appointment_date`),
  KEY `idx_status` (`status`),
  KEY `idx_customer_phone` (`customer_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- הכנסת קטגוריות ראשיות
INSERT INTO categories (name_he, name_en, icon, color, sort_order) VALUES
('תספורות ועיצוב שיער', 'Haircuts & Styling', '✂️', '#FF6B6B', 1),
('צבע שיער', 'Hair Coloring', '🎨', '#4ECDC4', 2),
('החלקות וטיפולים', 'Straightening & Treatments', '🔆', '#45B7D1', 3),
('תוספות שיער', 'Hair Extensions', '💇‍♀️', '#96CEB4', 4),
('חבילות ואירועים', 'Packages & Events', '🎁', '#82E0AA', 5);


-- 1. תספורות ועיצוב שיער (קטגוריה 1)
INSERT INTO services (category_id, title, description, short_description, duration, base_price, materials_fee, popular, featured) VALUES
(1, 'תספורת נשים', 'תספורת מקצועית כולל ייעוץ, גזירה, שטיפה, ייבוש ועיצוב סופי', 'תספורת מלאה עם עיצוב', 60, 120.00, 0, TRUE, TRUE),
(1, 'תספורת ילדות', 'תספורת לילדות עד גיל 12 עם גישה מיוחדת לילדים וכיסא מיוחד', 'תספורת ילדות עד גיל 12', 45, 90.00, 0, TRUE, FALSE),
(1, 'תספורת פן בלבד', 'עיצוב ושיוף פנים בלבד ללא שטיפה', 'עיצוב פנים בלבד', 30, 70.00, 0, FALSE, FALSE),
(1, 'תספורת שיער קצר', 'תספורת ועיצוב מקצועי לשיער קצר מאוד עם תשומת לב לפרטים', 'תספורת לשיער קצר', 45, 100.00, 0, TRUE, FALSE),
(1, 'תסרוקת אירוע', 'עיצוב תסרוקת מיוחדת לאירועים, חתונות ואירועים מיוחדים', 'תסרוקת לאירועים מיוחדים', 90, 250.00, 50.00, FALSE, TRUE),
(1, 'עיצוב תלתולים', 'טיפול ועיצוב לשיער מתולתל - סלסול או יישור', 'עיצוב לשיער מתולתל', 60, 150.00, 20.00, TRUE, FALSE),
(1, 'תספורת גברית', 'תספורת גברים כולל מכונה, מספריים וגילוח קצוות', 'תספורת גברים', 45, 80.00, 0, TRUE, FALSE);

-- 2. צבע שיער (קטגוריה 2)
INSERT INTO services (category_id, title, description, short_description, duration, base_price, materials_fee, popular, featured, requires_consultation) VALUES
(2, 'צבע שורשים', 'צביעת שורשים בלבד עם צבע איכותי נטול אמוניה', 'צבע שורשים בלבד', 60, 150.00, 80.00, TRUE, TRUE, TRUE),
(2, 'צבע מלא', 'צביעת שיער מלאה כולל שורשים ואורך עם צבע איכותי', 'צבע מלא לשיער', 90, 250.00, 150.00, TRUE, TRUE, TRUE),
(2, 'הילייט מלא', 'הבהרה מקצועית מלאה בשיטת foil עם הבהרה מקצועית', 'הבהרה מלאה', 120, 350.00, 200.00, TRUE, TRUE, TRUE),
(2, 'הילייט חלקי', 'הבהרה בחלק העליון של הראש בלבד', 'הבהרה חלקית', 90, 220.00, 120.00, TRUE, FALSE, TRUE);

-- 3. החלקות וטיפולים (קטגוריה 3)
INSERT INTO services (category_id, title, description, short_description, duration, base_price, materials_fee, popular, featured, requires_consultation) VALUES
(3, 'החלקה ברזילאית', 'החלקה מלאה עם מרכיבים טבעיים מברזיל', 'החלקה מלאה ברזילאית', 180, 600.00, 300.00, TRUE, TRUE, TRUE),
(3, 'החלקה יפנית', 'החלקה אורגנית ועדינה במיוחד בשיטה היפנית', 'החלקה יפנית אורגנית', 150, 700.00, 350.00, FALSE, TRUE, TRUE),
(3, 'החלקת קראטין', 'טיפול קראטין לחיזוק והחלקת השיער', 'טיפול קראטין להחלקה', 120, 500.00, 250.00, TRUE, FALSE, TRUE),
(3, 'טיפול החלקה חודשי', 'תחזוקה והחלקה להחלקה קיימת', 'תחזוקה להחלקה', 60, 150.00, 80.00, TRUE, FALSE, FALSE);

-- 4. תוספות שיער (קטגוריה 4)
INSERT INTO services (category_id, title, description, short_description, duration, base_price, materials_fee, popular, featured, requires_consultation, notes) VALUES
(4, 'הרחבת שיער בקליפים', 'הרחבה בשיטת קליפים (ניתן להסיר לבד) - לא קבועה', 'הרחבה בקליפים', 120, 400.00, 600.00, TRUE, TRUE, TRUE, 'לא קבוע - להסרה עצמית'),
(4, 'הרחבת שיער במיקרו-רינג', 'הרחבה בשיטת מיקרו-רינג קבועה ואיכותית', 'הרחבה במיקרו-רינג', 180, 800.00, 1200.00, TRUE, TRUE, TRUE, 'מחזיקה 3-4 חודשים'),
(4, 'הרחבת שיער בקרניבן', 'הרחבה בשיטת הקרניבן האיטלקית המתקדמת', 'הרחבה בקרניבן', 200, 900.00, 1400.00, FALSE, TRUE, TRUE, 'מתאים לשיער עבה'),
(4, 'הרחבת שיער בקשר', 'הרחבה בשיטת הקשר הקוריאני העדינה', 'הרחבה בקשר', 160, 750.00, 1100.00, TRUE, FALSE, TRUE, 'שיטה עדינה לשיער דק');

-- 5. חבילות ואירועים (קטגוריה 5)
INSERT INTO services (category_id, title, description, short_description, duration, base_price, materials_fee, popular, featured, requires_consultation, notes) VALUES
(5, 'חבילת כלה מלאה', 'תספורת, איפור כלה, מניקור, פדיקור והכנה מלאה לחתונה', 'חבילת כלה שלמה', 240, 900.00, 200.00, TRUE, TRUE, TRUE, 'כולל ניסוי מקדים חינם'),
(5, 'חבילת פינוק מלאה', 'תספורת, צבע מלא וטיפול פנים מפנק', 'חבילת פינוק', 210, 750.00, 150.00, TRUE, FALSE, FALSE, 'מתנה מושלמת ליום הולדת'),
(5, 'חבילת כלה בסיסית', 'תספורת ואיפור כלה בלבד ללא ציפורניים', 'חבילת כלה בסיסית', 150, 600.00, 150.00, TRUE, FALSE, TRUE, 'ללא טיפולי ציפורניים'),
(5, 'חבילת יום הולדת', 'חבילת פינוק ליום ההולדת עם פריסקו וכיבוד', 'חבילת יום הולדת', 180, 500.00, 100.00, FALSE, FALSE, FALSE, 'כולל פריסקו וכיבוד קל');


-- ניקוי הטבלאות בסדר הנכון (קודם שירותים, אחר כך קטגוריות)
--TRUNCATE TABLE services;
--TRUNCATE TABLE categories;

-- בדוק שהכל תקין
-- SELECT c.id AS category_id, c.name_he, COUNT(s.id) AS service_count FROM categories c 
--LEFT JOIN services s ON c.id = s.category_id GROUP BY c.id, c.name_he ORDER BY c.id;
