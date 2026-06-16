<?php
/* get_products.php - 星沐手作 Fetch Products API */

require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT id, name, price, description, image_path, is_hot FROM products ORDER BY id DESC";
$res = $conn->query($sql);

$products = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $products[] = [
            'id' => 'prod-' . $row['id'],
            'db_id' => intval($row['id']),
            'name' => $row['name'],
            'price' => intval($row['price']),
            'image' => $row['image_path'],
            'desc' => $row['description'],
            'popular' => $row['is_hot'] == 1 ? true : false
        ];
    }
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);
$conn->close();
