<?php
/* submit_order.php - 星沐手作 AJAX Order Submission Endpoint */

header('Content-Type: application/json; charset=utf-8');
session_start();

// Require DB connection (auto-creates DB/tables if missing)
require_once 'db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '僅支援 POST 請求方式。']);
    exit;
}

// Get JSON input payload
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無效的請求資料格式。']);
    exit;
}

// Extract and sanitize customer info
$customerName = isset($inputData['customer_name']) ? trim($inputData['customer_name']) : '';
$customerEmail = isset($inputData['customer_email']) ? trim($inputData['customer_email']) : '';
$customerPhone = isset($inputData['customer_phone']) ? trim($inputData['customer_phone']) : '';
$customerMessage = isset($inputData['customer_message']) ? trim($inputData['customer_message']) : '';
$totalPrice = isset($inputData['total_price']) ? intval($inputData['total_price']) : 0;
$items = isset($inputData['items']) ? $inputData['items'] : [];

// Validation checks
if (empty($customerName) || empty($customerEmail) || empty($customerPhone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '請填寫必填欄位（姓名、Email、電話）。']);
    exit;
}

if (empty($items) || !is_array($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '購物車內無商品，無法送出訂單。']);
    exit;
}

// Start SQL Transaction to ensure database consistency
$conn->begin_transaction();

try {
    // 1. Insert into orders table
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $stmt = $conn->prepare("INSERT INTO `orders` (`user_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_message`, `total_price`) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("準備訂單 SQL 失敗: " . $conn->error);
    }
    
    $stmt->bind_param("issssi", $userId, $customerName, $customerEmail, $customerPhone, $customerMessage, $totalPrice);
    if (!$stmt->execute()) {
        throw new Exception("執行新增訂單失敗: " . $stmt->error);
    }
    
    $orderId = $conn->insert_id;
    $stmt->close();

    // 2. Insert into order_items table for each cart item
    $stmtItem = $conn->prepare("INSERT INTO `order_items` (`order_id`, `product_name`, `price`, `qty`, `is_custom`, `custom_pattern`, `custom_accessories`) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmtItem) {
        throw new Exception("準備訂單細項 SQL 失敗: " . $conn->error);
    }

    foreach ($items as $item) {
        $prodName = isset($item['name']) ? $item['name'] : '未知商品';
        $price = isset($item['price']) ? intval($item['price']) : 0;
        $qty = isset($item['qty']) ? intval($item['qty']) : 1;
        
        // Customizations flags
        $isCustom = isset($item['isCustom']) && $item['isCustom'] ? 1 : 0;
        $customPattern = isset($item['customPattern']) ? $item['customPattern'] : null;
        
        $customAccStr = null;
        if (isset($item['customAccessories']) && is_array($item['customAccessories'])) {
            $customAccStr = implode(', ', $item['customAccessories']);
        }

        $stmtItem->bind_param("isiiiss", $orderId, $prodName, $price, $qty, $isCustom, $customPattern, $customAccStr);
        if (!$stmtItem->execute()) {
            throw new Exception("執行新增訂單細項失敗: " . $stmtItem->error);
        }
    }
    
    $stmtItem->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => '訂單處理成功！'
    ]);

} catch (Exception $e) {
    // Rollback transaction on failure
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '送出訂單失敗，系統錯誤：' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>
