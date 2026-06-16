<?php
/* admin.php - 星沐手作 Backend Admin Panel & Order Management */

session_start();
require_once 'db.php';

// Helper to respond with JSON for AJAX requests
function ajax_respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// 1. Handle AJAX Status Update and Order Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] !== 'login') {
    // Check authentication for AJAX requests
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Content-Type: application/json');
        ajax_respond(false, '權限不足，請重新登入。');
    }

    $action = $_GET['action'];

    if ($action === 'update_status') {
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = isset($input['order_id']) ? intval($input['order_id']) : 0;
        $status = isset($input['status']) ? trim($input['status']) : '';

        $allowed_statuses = ['pending', 'processing', 'shipped', 'completed'];
        if ($orderId > 0 && in_array($status, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE `orders` SET `status` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $status, $orderId);
            if ($stmt->execute()) {
                ajax_respond(true, '訂單狀態已更新！');
            } else {
                ajax_respond(false, '資料庫更新失敗。');
            }
            $stmt->close();
        }
        ajax_respond(false, '無效的參數或狀態值。');

    } elseif ($action === 'delete_order') {
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = isset($input['order_id']) ? intval($input['order_id']) : 0;

        if ($orderId > 0) {
            $stmt = $conn->prepare("DELETE FROM `orders` WHERE `id` = ?");
            $stmt->bind_param("i", $orderId);
            if ($stmt->execute()) {
                ajax_respond(true, '訂單已成功刪除！');
            } else {
                ajax_respond(false, '刪除資料失敗。');
            }
            $stmt->close();
        }
        ajax_respond(false, '無效的訂單編號。');
    }
}

// 2. Handle Login / Logout Actions
$error = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $admin = $res->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header('Location: admin.php');
                exit;
            }
        }
        $error = '帳號或密碼輸入錯誤 😭';
    } elseif ($action === 'logout') {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_username']);
        header('Location: admin.php');
        exit;
    }
}

// 3. Render HTML Content based on Authentication State
$is_authenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>星沐手作 | 後台管理系統 🦖</title>
  <!-- Google Fonts -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@400;600;700&family=Fredoka:wght@400;600;700&display=swap">
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    /* Admin custom CSS matching childish Korean pastel style */
    :root {
      --bg-cream: #FDFBF7;
      --text-dark: #5C4E4B;
      --text-light: #8E7C77;
      --border-color: #D6C7C2;
      --border-dark: #5C4E4B;
      
      --pastel-pink: #FFECEC;
      --pastel-pink-dark: #FFA3A3;
      --pastel-green: #EAF2E8;
      --pastel-green-dark: #97C09E;
      --pastel-yellow: #FFF5E1;
      --pastel-yellow-dark: #F7C887;
      --pastel-blue: #E8F4F8;
      --pastel-blue-dark: #8DBDCF;
      
      --shadow-bubble: 0 6px 0px rgba(92, 78, 75, 1);
      --shadow-bubble-hover: 0 2px 0px rgba(92, 78, 75, 1);
      --transition-bubble: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      --transition-smooth: all 0.3s ease;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Comfortaa', 'Fredoka', "Microsoft JhengHei", "PingFang TC", sans-serif;
      background-color: var(--bg-cream);
      color: var(--text-dark);
      background-image: radial-gradient(#F0E9DF 1.5px, transparent 1.5px);
      background-size: 24px 24px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    header {
      background-color: rgba(253, 251, 247, 0.95);
      border-bottom: 3.5px solid var(--border-dark);
      padding: 18px 24px;
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(10px);
    }

    .header-container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo-link {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: var(--text-dark);
    }

    .logo-img {
      width: 45px;
      height: 45px;
      object-fit: contain;
      animation: float 3s ease-in-out infinite;
    }

    .logo-text {
      font-family: 'Fredoka', sans-serif;
      font-weight: 700;
      font-size: 1.4rem;
      letter-spacing: 1px;
    }

    .admin-title-badge {
      background-color: var(--pastel-yellow);
      border: 2px solid var(--border-dark);
      border-radius: 12px;
      padding: 4px 10px;
      font-size: 0.8rem;
      font-weight: 700;
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .welcome-text {
      font-size: 0.9rem;
      font-weight: 700;
    }

    /* Bubbly Button Style */
    .btn-cute {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background-color: var(--pastel-pink);
      border: 2.5px solid var(--border-dark);
      border-radius: 18px;
      padding: 10px 18px;
      font-family: inherit;
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text-dark);
      text-decoration: none;
      cursor: pointer;
      box-shadow: var(--shadow-bubble-hover);
      transform: translateY(2px);
      transition: var(--transition-bubble);
    }

    .btn-cute:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-bubble);
      background-color: var(--pastel-pink-dark);
    }

    .btn-cute.btn-secondary {
      background-color: var(--pastel-blue);
    }
    .btn-cute.btn-secondary:hover {
      background-color: var(--pastel-blue-dark);
    }
    
    .btn-cute.btn-yellow {
      background-color: var(--pastel-yellow);
    }
    .btn-cute.btn-yellow:hover {
      background-color: var(--pastel-yellow-dark);
    }

    .btn-cute.btn-danger {
      background-color: #FFC0C0;
    }
    .btn-cute.btn-danger:hover {
      background-color: #FF7B7B;
      color: white;
    }

    /* Login Screen Container */
    .login-container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    .login-card {
      background-color: white;
      border: 3.5px solid var(--border-dark);
      border-radius: 35px;
      padding: 40px;
      width: 100%;
      max-width: 420px;
      box-shadow: var(--shadow-bubble);
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .login-card h2 {
      font-size: 1.8rem;
      margin-bottom: 24px;
      font-weight: 700;
    }

    .login-card h2 i {
      color: var(--pastel-pink-dark);
    }

    .form-group {
      text-align: left;
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 0.9rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      background-color: var(--bg-cream);
      border: 2.5px solid var(--border-color);
      border-radius: 18px;
      padding: 12px 16px;
      font-family: inherit;
      font-size: 0.9rem;
      color: var(--text-dark);
      transition: var(--transition-smooth);
    }

    .form-control:focus {
      outline: none;
      border-color: var(--border-dark);
      box-shadow: 2px 2px 0 var(--border-dark);
    }

    .error-msg {
      background-color: #FFF2F2;
      border: 2px solid #FF8D8D;
      color: #D32F2F;
      border-radius: 15px;
      padding: 10px;
      margin-bottom: 20px;
      font-size: 0.88rem;
      font-weight: 700;
    }

    .btn-login {
      width: 100%;
      margin-top: 10px;
      transform: translateY(0);
      box-shadow: 0 4px 0 var(--border-dark);
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 0 var(--border-dark);
    }

    /* Main Dashboard Layout */
    main {
      flex: 1;
      max-width: 1200px;
      width: 100%;
      margin: 0 auto;
      padding: 40px 24px;
    }

    .dashboard-title-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .dashboard-title-row h2 {
      font-size: 2rem;
      font-weight: 700;
    }

    /* Filters & Summary Row */
    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 30px;
      align-items: center;
    }

    .filter-btn {
      background-color: white;
      border: 2px solid var(--border-color);
      border-radius: 16px;
      padding: 8px 16px;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text-dark);
      cursor: pointer;
      transition: var(--transition-bubble);
    }

    .filter-btn:hover, .filter-btn.active {
      border-color: var(--border-dark);
      box-shadow: 2px 2px 0 var(--border-dark);
    }

    .filter-btn.active.pending { background-color: var(--pastel-pink); }
    .filter-btn.active.processing { background-color: var(--pastel-yellow); }
    .filter-btn.active.shipped { background-color: var(--pastel-blue); }
    .filter-btn.active.completed { background-color: var(--pastel-green); }
    .filter-btn.active.all { background-color: #E6E6E6; }

    /* Orders Grid */
    .orders-container {
      display: grid;
      grid-template-columns: 1fr;
      gap: 24px;
    }

    .order-card {
      background-color: white;
      border: 3px solid var(--border-dark);
      border-radius: 30px;
      box-shadow: var(--shadow-bubble);
      padding: 28px;
      display: flex;
      flex-direction: column;
      gap: 20px;
      transition: var(--transition-smooth);
      position: relative;
    }

    .order-card-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px dashed var(--border-color);
      padding-bottom: 16px;
    }

    .order-id-date h3 {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .order-date {
      font-size: 0.8rem;
      color: var(--text-light);
      font-weight: 700;
    }

    .order-status-badge {
      border: 2px solid var(--border-dark);
      border-radius: 12px;
      padding: 4px 12px;
      font-size: 0.85rem;
      font-weight: 700;
      text-transform: capitalize;
    }

    .status-pending { background-color: var(--pastel-pink); }
    .status-processing { background-color: var(--pastel-yellow); }
    .status-shipped { background-color: var(--pastel-blue); }
    .status-completed { background-color: var(--pastel-green); }

    /* Order details layout */
    .order-card-body {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 28px;
    }

    @media (max-width: 900px) {
      .order-card-body {
        grid-template-columns: 1fr;
      }
    }

    .customer-info-section h4, .order-items-section h4 {
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 12px;
      border-left: 4px solid var(--pastel-pink-dark);
      padding-left: 8px;
    }

    .info-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
      font-size: 0.9rem;
    }

    .info-list li strong {
      color: var(--text-light);
      font-weight: 700;
      margin-right: 4px;
    }

    .order-message-block {
      background-color: var(--bg-cream);
      border: 1.5px dashed var(--border-color);
      border-radius: 14px;
      padding: 10px 12px;
      margin-top: 10px;
      font-size: 0.85rem;
    }

    /* Items table list */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }

    .items-table th {
      text-align: left;
      font-weight: 700;
      color: var(--text-light);
      padding-bottom: 8px;
      border-bottom: 2px solid var(--border-color);
    }

    .items-table td {
      padding: 10px 0;
      border-bottom: 1px dashed var(--border-color);
      vertical-align: top;
    }

    .item-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .item-name {
      font-weight: 700;
    }

    .item-custom-badge {
      display: inline-block;
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--text-dark);
      background-color: var(--pastel-blue);
      border: 1.5px solid var(--border-dark);
      border-radius: 8px;
      padding: 1px 6px;
      align-self: flex-start;
      margin-top: 2px;
    }

    .item-custom-details {
      font-size: 0.75rem;
      color: var(--text-light);
      padding-left: 6px;
      border-left: 2px solid var(--border-color);
      margin-top: 2px;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .order-card-footer {
      border-top: 2px dashed var(--border-color);
      padding-top: 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }

    .order-total-price {
      font-size: 1.2rem;
      font-weight: 700;
    }

    .order-total-price span {
      color: #FF7B7B;
      font-size: 1.4rem;
    }

    .order-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .status-select-wrapper {
      position: relative;
    }

    .status-select {
      background-color: white;
      border: 2.5px solid var(--border-dark);
      border-radius: 16px;
      padding: 8px 30px 8px 12px;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.85rem;
      color: var(--text-dark);
      cursor: pointer;
      appearance: none;
      transition: var(--transition-smooth);
    }

    .status-select:focus {
      outline: none;
      box-shadow: 2px 2px 0 var(--border-dark);
    }

    .status-select-wrapper::after {
      content: "\f0d7";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
    }

    /* Empty state */
    .empty-state {
      background-color: white;
      border: 3.5px dashed var(--border-color);
      border-radius: 35px;
      padding: 60px 40px;
      text-align: center;
      color: var(--text-light);
    }

    .empty-state i {
      font-size: 4rem;
      color: var(--border-color);
      margin-bottom: 20px;
    }

    .empty-state h3 {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--text-dark);
    }

    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-8px); }
      100% { transform: translateY(0px); }
    }
  </style>
</head>
<body>

  <!-- --- Header / Navigation --- -->
  <header>
    <div class="header-container">
      <a href="admin.php" class="logo-link">
        <img src="assets/logo.png" alt="星沐手作 Logo" class="logo-img">
        <span class="logo-text">星沐手作</span>
        <span class="admin-title-badge">管理後台 🦖</span>
      </a>
      
      <?php if ($is_authenticated): ?>
        <div class="nav-actions">
          <span class="welcome-text"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
          <a href="admin.php?action=logout" class="btn-cute btn-danger"><i class="fa-solid fa-right-from-bracket"></i> 登出</a>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <!-- --- Main Area --- -->
  <?php if (!$is_authenticated): ?>
    <!-- --- Login Screen --- -->
    <div class="login-container">
      <div class="login-card">
        <h2>管理者登入 <i class="fa-solid fa-cookie-bite"></i></h2>
        
        <?php if (!empty($error)): ?>
          <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="admin.php?action=login" method="POST">
          <div class="form-group">
            <label for="username">管理帳號</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="請輸入管理帳號" required autocomplete="off">
          </div>
          
          <div class="form-group">
            <label for="password">管理密碼</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="請輸入密碼" required>
          </div>

          <button type="submit" class="btn-cute btn-login">進入管理後台 <i class="fa-solid fa-arrow-right-to-bracket"></i></button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <!-- --- Admin Dashboard --- -->
    <main>
      <div class="dashboard-title-row">
        <h2>訂單管理面板 📋</h2>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <span style="font-weight: 700; margin-right: 8px;">狀態篩選：</span>
        <button class="filter-btn active all" onclick="filterOrders('all')">全部</button>
        <button class="filter-btn pending" onclick="filterOrders('pending')">待處理</button>
        <button class="filter-btn processing" onclick="filterOrders('processing')">製作中</button>
        <button class="filter-btn shipped" onclick="filterOrders('shipped')">已出貨</button>
        <button class="filter-btn completed" onclick="filterOrders('completed')">已完成</button>
      </div>

      <!-- Orders Listings -->
      <div class="orders-container" id="orders-list">
        <?php
        // Fetch all orders
        $sql = "SELECT id, user_id, customer_name, customer_email, customer_phone, customer_message, total_price, created_at, status FROM orders ORDER BY id DESC";
        $res = $conn->query($sql);

        if ($res && $res->num_rows > 0):
          while ($order = $res->fetch_assoc()):
            $orderId = $order['id'];
            
            // Fetch items
            $stmtItems = $conn->prepare("SELECT product_name, price, qty, is_custom, custom_pattern, custom_accessories FROM order_items WHERE order_id = ?");
            $stmtItems->bind_param("i", $orderId);
            $stmtItems->execute();
            $itemsRes = $stmtItems->get_result();
        ?>
            <div class="order-card" data-status="<?php echo htmlspecialchars($order['status']); ?>" id="order-card-<?php echo $orderId; ?>">
              <div class="order-card-header">
                <div class="order-id-date">
                  <h3>訂單編號 #<?php echo $orderId; ?></h3>
                  <div class="order-date"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($order['created_at']); ?></div>
                </div>
                <span class="order-status-badge status-<?php echo htmlspecialchars($order['status']); ?>" id="badge-<?php echo $orderId; ?>">
                  <?php
                  $status_zh = ['pending' => '待處理 ⏳', 'processing' => '製作中 ✂️', 'shipped' => '已出貨 🚚', 'completed' => '已完成 🎉'];
                  echo isset($status_zh[$order['status']]) ? $status_zh[$order['status']] : htmlspecialchars($order['status']);
                  ?>
                </span>
              </div>

              <div class="order-card-body">
                <!-- Customer Info -->
                <div class="customer-info-section">
                  <h4><i class="fa-solid fa-address-card"></i> 訂購人資訊</h4>
                  <ul class="info-list">
                    <li><strong>收件姓名：</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                      <?php if ($order['user_id']): ?>
                        <span style="font-size:0.75rem; background:#EAF2E8; border:1px solid #97C09E; border-radius:6px; padding:1px 4px; font-weight:700; color:#5C4E4B; margin-left:4px;">註冊會員</span>
                      <?php endif; ?>
                    </li>
                    <li><strong>聯絡電話：</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></li>
                    <li><strong>電子信箱：</strong> <?php echo htmlspecialchars($order['customer_email']); ?></li>
                  </ul>
                  <?php if (!empty($order['customer_message'])): ?>
                    <div class="order-message-block">
                      <strong>備註留言：</strong><br>
                      <?php echo nl2br(htmlspecialchars($order['customer_message'])); ?>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Order Items -->
                <div class="order-items-section">
                  <h4><i class="fa-solid fa-box-open"></i> 購買品項</h4>
                  <table class="items-table">
                    <thead>
                      <tr>
                        <th>品項描述</th>
                        <th style="width: 80px; text-align: right;">單價</th>
                        <th style="width: 60px; text-align: center;">數量</th>
                        <th style="width: 80px; text-align: right;">小計</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      while ($item = $itemsRes->fetch_assoc()):
                        $subtotal = $item['price'] * $item['qty'];
                      ?>
                        <tr>
                          <td>
                            <div class="item-info">
                              <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                              <?php if ($item['is_custom']): ?>
                                <span class="item-custom-badge"><i class="fa-solid fa-compass-drafting"></i> 客製商品</span>
                                <div class="item-custom-details">
                                  <span><strong>布料花色：</strong>
                                    <?php
                                    $pattern_zh = [
                                      'pat-dino' => '綠色小恐龍 🦖',
                                      'pat-panda' => '棉花糖熊貓 🐼',
                                      'pat-garden' => '莫內花園 🌹',
                                      'pat-strawberry' => '香甜草莓 🍓',
                                      'pat-balloon' => '氣球雲朵 🎈'
                                    ];
                                    echo isset($pattern_zh[$item['custom_pattern']]) ? $pattern_zh[$item['custom_pattern']] : htmlspecialchars($item['custom_pattern']);
                                    ?>
                                  </span>
                                  <?php if (!empty($item['custom_accessories'])): ?>
                                    <span><strong>加購配件：</strong>
                                      <?php
                                      $acc_zh = [
                                        'tag' => '手作布標 🏷️',
                                        'strap' => '同款加長手提帶 🎗️'
                                      ];
                                      $acc_parts = explode(', ', $item['custom_accessories']);
                                      $acc_zh_parts = [];
                                      foreach ($acc_parts as $part) {
                                          $acc_zh_parts[] = isset($acc_zh[$part]) ? $acc_zh[$part] : $part;
                                      }
                                      echo implode(', ', $acc_zh_parts);
                                      ?>
                                    </span>
                                  <?php endif; ?>
                                </div>
                              <?php endif; ?>
                            </div>
                          </td>
                          <td style="text-align: right;">NT$ <?php echo $item['price']; ?></td>
                          <td style="text-align: center;"><?php echo $item['qty']; ?></td>
                          <td style="text-align: right; font-weight: 700;">NT$ <?php echo $subtotal; ?></td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Footer Actions -->
              <div class="order-card-footer">
                <div class="order-total-price">
                  訂單總金額： <span>NT$ <?php echo $order['total_price']; ?></span>
                </div>
                
                <div class="order-actions">
                  <span style="font-size: 0.85rem; font-weight: 700;">變更狀態：</span>
                  <div class="status-select-wrapper">
                    <select class="status-select" onchange="updateStatus(<?php echo $orderId; ?>, this.value)">
                      <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>待處理 ⏳</option>
                      <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>製作中 ✂️</option>
                      <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>已出貨 🚚</option>
                      <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>已完成 🎉</option>
                    </select>
                  </div>
                  
                  <button class="btn-cute btn-danger" onclick="deleteOrder(<?php echo $orderId; ?>)">
                    <i class="fa-regular fa-trash-can"></i> 刪除訂單
                  </button>
                </div>
              </div>
            </div>
        <?php
            $stmtItems->close();
          endwhile;
        else:
        ?>
          <div class="empty-state" id="no-orders-msg">
            <i class="fa-solid fa-receipt"></i>
            <h3>目前尚未有任何訂單</h3>
            <p>小恐龍正在耐心等候顧客下單喔～</p>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <script>
      // Admin dashboard interactions via AJAX
      function filterOrders(status) {
        // Update filter button active states
        document.querySelectorAll('.filter-btn').forEach(btn => {
          btn.classList.remove('active');
        });
        
        // Find click trigger button
        const clickedBtn = document.querySelector('.filter-btn.' + status);
        if (clickedBtn) clickedBtn.classList.add('active');

        // Toggle card visibility
        const cards = document.querySelectorAll('.order-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
          if (status === 'all' || card.getAttribute('data-status') === status) {
            card.style.display = 'flex';
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });

        // Toggle empty state message
        let emptyMsg = document.getElementById('no-orders-msg');
        if (visibleCount === 0) {
          if (!emptyMsg) {
            const container = document.getElementById('orders-list');
            container.innerHTML += `
              <div class="empty-state" id="no-orders-msg">
                <i class="fa-solid fa-receipt"></i>
                <h3>沒有符合此狀態的訂單</h3>
                <p>小恐龍找了半天都沒看見～</p>
              </div>
            `;
          } else {
            emptyMsg.style.display = 'block';
          }
        } else {
          if (emptyMsg) {
            emptyMsg.style.display = 'none';
          }
        }
      }

      function updateStatus(orderId, newStatus) {
        fetch('admin.php?action=update_status', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            order_id: orderId,
            status: newStatus
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // Update UI badge
            const badge = document.getElementById('badge-' + orderId);
            const card = document.getElementById('order-card-' + orderId);
            
            // Map text
            const status_zh = {
              'pending': '待處理 ⏳',
              'processing': '製作中 ✂️',
              'shipped': '已出貨 🚚',
              'completed': '已完成 🎉'
            };

            // Reset classes
            badge.className = 'order-status-badge status-' + newStatus;
            badge.textContent = status_zh[newStatus] || newStatus;
            
            // Update dataset status for filtering
            card.setAttribute('data-status', newStatus);
            
            alert('🎉 ' + data.message);
          } else {
            alert('❌ 更新失敗：' + data.message);
          }
        })
        .catch(err => {
          console.error(err);
          alert('❌ 網路連線異常，無法更新狀態。');
        });
      }

      function deleteOrder(orderId) {
        if (!confirm('⚠️ 確定要刪除這筆訂單嗎？此動作無法復原！')) return;

        fetch('admin.php?action=delete_order', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            order_id: orderId
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const card = document.getElementById('order-card-' + orderId);
            card.style.opacity = '0';
            setTimeout(() => {
              card.remove();
              // Check if all cards deleted
              const remainingCards = document.querySelectorAll('.order-card');
              if (remainingCards.length === 0) {
                location.reload();
              }
            }, 400);
            alert('🗑️ ' + data.message);
          } else {
            alert('❌ 刪除失敗：' + data.message);
          }
        })
        .catch(err => {
          console.error(err);
          alert('❌ 網路連線異常，無法刪除訂單。');
        });
      }
    </script>
  <?php endif; ?>

</body>
</html>
<?php
$conn->close();
?>
