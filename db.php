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
?>
