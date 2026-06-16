<?php
/* db.php - 星沐手作 Database Connection & Auto-Initialization */

$host = "localhost";
$username = "root";
$password = "";
$dbname = "starmu_db";

// 1. Connect to MySQL server (smart connection)
$conn = @new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    // If connection fails, try to create database (only if running on localhost / XAMPP)
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $temp_conn = @new mysqli($host, $username, $password);
        if (!$temp_conn->connect_error) {
            $temp_conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $temp_conn->close();
            
            // Try connecting to database again
            $conn = @new mysqli($host, $username, $password, $dbname);
        }
    }
}

if ($conn->connect_error) {
    die("資料庫連線失敗: " . $conn->connect_error . "。請確認 db.php 中的資料庫主機、帳號、密碼與資料庫名稱設定是否正確！");
}

// Set connection charset to utf8mb4
$conn->set_charset("utf8mb4");

// 4. Create "users" table if it doesn't exist
$sql_users = "CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($sql_users)) {
    die("建立 users 資料表失敗: " . $conn->error);
}

// 5. Create "admins" table if it doesn't exist
$sql_admins = "CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($sql_admins)) {
    die("建立 admins 資料表失敗: " . $conn->error);
}

// 6. Create "orders" table if it doesn't exist
$sql_orders = "CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL DEFAULT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(50) NOT NULL,
  `customer_message` TEXT DEFAULT NULL,
  `total_price` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(20) DEFAULT 'pending',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($sql_orders)) {
    die("建立 orders 資料表失敗: " . $conn->error);
}

// Check if user_id column exists in orders table (just in case the table already existed without user_id)
$check_column = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'user_id'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE `orders` ADD COLUMN `user_id` INT NULL DEFAULT NULL AFTER `id`");
    $conn->query("ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL");
}

// 7. Create "order_items" table if it doesn't exist
$sql_items = "CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `price` INT NOT NULL,
  `qty` INT NOT NULL,
  `is_custom` TINYINT(1) DEFAULT 0,
  `custom_pattern` VARCHAR(100) DEFAULT NULL,
  `custom_accessories` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($sql_items)) {
    die("建立 order_items 資料表失敗: " . $conn->error);
}

// 8. Seed Default Admin Account if table is empty
$check_admin = $conn->query("SELECT * FROM `admins` LIMIT 1");
if ($check_admin->num_rows == 0) {
    $default_admin_user = 'admin';
    $default_admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO `admins` (`username`, `password`) VALUES (?, ?)");
    $stmt->bind_param("ss", $default_admin_user, $default_admin_pass);
    $stmt->execute();
    $stmt->close();
}

// 9. Seed Default Customer Account if table is empty
$check_user = $conn->query("SELECT * FROM `users` LIMIT 1");
if ($check_user->num_rows == 0) {
    $default_cust_user = 'user';
    $default_cust_pass = password_hash('user123', PASSWORD_DEFAULT);
    $default_cust_email = 'user@example.com';
    $default_cust_phone = '0912345678';
    $stmt = $conn->prepare("INSERT INTO `users` (`username`, `password`, `email`, `phone`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $default_cust_user, $default_cust_pass, $default_cust_email, $default_cust_phone);
    $stmt->execute();
    $stmt->close();
}

// 10. Create "products" table if it doesn't exist
$sql_products = "CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `price` INT NOT NULL,
  `description` TEXT,
  `image_path` VARCHAR(255) NOT NULL,
  `is_hot` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($sql_products)) {
    die("建立 products 資料表失敗: " . $conn->error);
}

// 11. Seed Default Products if table is empty
$check_products = $conn->query("SELECT * FROM `products` LIMIT 1");
if ($check_products->num_rows == 0) {
    $default_products = [
        [
            'name' => '恐龍樂園雙層拉鍊包',
            'price' => 390,
            'desc' => '經典黃色印花布搭配軟萌恐龍，雙層收納大空間。正面貼心透明膠片格層，附帶撞色手拎帶，實用又童趣。',
            'image' => 'assets/product1.jpg',
            'is_hot' => 1
        ],
        [
            'name' => '萌萌熊貓棉花糖彈片包',
            'price' => 280,
            'desc' => '粉綠色調配上香甜棉花糖熊貓，搭配柔軟珊瑚粉口金翻蓋與撞色金屬按扣，輕壓即開，最適合收納零錢與耳機。',
            'image' => 'assets/product2.jpg',
            'is_hot' => 1
        ],
        [
            'name' => '莫內花園手感捲軸杯套',
            'price' => 220,
            'desc' => '藍色夢幻水彩玫瑰如同印象派畫作，質地硬挺。收納時可如捲軸般輕巧捲起，側邊車縫精緻品牌布標。',
            'image' => 'assets/product3.jpg',
            'is_hot' => 1
        ],
        [
            'name' => '莫內花園隨行提帶杯套',
            'price' => 250,
            'desc' => '莫內花園系列杯套完整展開版，附有專屬同花色加長調整型手提帶，提著走超方便，環保也能美美的！',
            'image' => 'assets/product4.jpg',
            'is_hot' => 0
        ]
    ];
    
    $stmt = $conn->prepare("INSERT INTO `products` (`name`, `price`, `description`, `image_path`, `is_hot`) VALUES (?, ?, ?, ?, ?)");
    foreach ($default_products as $p) {
        $stmt->bind_param("sissi", $p['name'], $p['price'], $p['desc'], $p['image'], $p['is_hot']);
        $stmt->execute();
    }
    $stmt->close();
}
?>
