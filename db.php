<?php
/* db.php - 星沐手作 Database Connection & Auto-Initialization */

$host = "localhost";
$username = "root";
$password = "";
$dbname = "starmu_db";

// 1. Connect to MySQL server
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("資料庫連線失敗: " . $conn->connect_error);
}

// 2. Create database if it doesn't exist
$sql_db = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sql_db)) {
    die("建立資料庫失敗: " . $conn->error);
}

// 3. Select the database
if (!$conn->select_db($dbname)) {
    die("選擇資料庫失敗: " . $conn->error);
}

// 4. Create "orders" table if it doesn't exist
$sql_orders = "CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(50) NOT NULL,
  `customer_message` TEXT DEFAULT NULL,
  `total_price` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($sql_orders)) {
    die("建立 orders 資料表失敗: " . $conn->error);
}

// 5. Create "order_items" table if it doesn't exist
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

// Set connection charset to utf8mb4
$conn->set_charset("utf8mb4");
?>
