<?php
/* user_auth.php - 星沐手作 User (Customer) Authentication Endpoint */

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// helper to respond with JSON
function json_respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON payload for login and register actions
    $inputData = json_decode(file_get_contents('php://input'), true);
    if (!$inputData && ($action === 'login' || $action === 'register')) {
        json_respond(false, '無效的請求資料格式。');
    }

    if ($action === 'login') {
        $username = isset($inputData['username']) ? trim($inputData['username']) : '';
        $password = isset($inputData['password']) ? $inputData['password'] : '';

        if (empty($username) || empty($password)) {
            json_respond(false, '請輸入帳號與密碼。');
        }

        $stmt = $conn->prepare("SELECT id, username, password, email, phone FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                json_respond(true, '登入成功！', [
                    'user' => [
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'phone' => $user['phone']
                    ]
                ]);
            }
        }
        json_respond(false, '帳號或密碼錯誤。');

    } elseif ($action === 'register') {
        $username = isset($inputData['username']) ? trim($inputData['username']) : '';
        $password = isset($inputData['password']) ? $inputData['password'] : '';
        $email = isset($inputData['email']) ? trim($inputData['email']) : '';
        $phone = isset($inputData['phone']) ? trim($inputData['phone']) : '';

        if (empty($username) || empty($password) || empty($email) || empty($phone)) {
            json_respond(false, '請填寫所有欄位。');
        }

        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            json_respond(false, '帳號已被使用。');
        }
        $stmt->close();

        // Hash password and insert
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $phone);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            json_respond(true, '註冊成功！', [
                'user' => [
                    'username' => $username,
                    'email' => $email,
                    'phone' => $phone
                ]
            ]);
        } else {
            json_respond(false, '註冊失敗，系統錯誤，請稍後再試。');
        }
    } else {
        json_respond(false, '未知的操作請求。');
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'check') {
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            
            // 1. Get user profile details
            $stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userRes = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$userRes) {
                // Session points to a user that was deleted
                session_destroy();
                json_respond(false, '會員不存在。', ['logged_in' => false]);
            }

            // 2. Fetch order history
            $orders = [];
            $stmt = $conn->prepare("SELECT id, customer_name, customer_email, customer_phone, customer_message, total_price, created_at, status FROM orders WHERE user_id = ? ORDER BY id DESC");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $ordersRes = $stmt->get_result();

            while ($order = $ordersRes->fetch_assoc()) {
                $orderId = $order['id'];
                
                // 3. Fetch items for this order
                $items = [];
                $stmtItems = $conn->prepare("SELECT product_name, price, qty, is_custom, custom_pattern, custom_accessories FROM order_items WHERE order_id = ?");
                $stmtItems->bind_param("i", $orderId);
                $stmtItems->execute();
                $itemsRes = $stmtItems->get_result();
                while ($item = $itemsRes->fetch_assoc()) {
                    $items[] = $item;
                }
                $stmtItems->close();

                $order['items'] = $items;
                $orders[] = $order;
            }
            $stmt->close();

            json_respond(true, '已登入', [
                'logged_in' => true,
                'user' => $userRes,
                'orders' => $orders
            ]);
        } else {
            json_respond(true, '未登入', ['logged_in' => false]);
        }

    } elseif ($action === 'logout') {
        session_unset();
        session_destroy();
        json_respond(true, '已登出。');
    } else {
        json_respond(false, '未知的操作請求。');
    }
} else {
    json_respond(false, '不支援的請求方法。');
}
?>
