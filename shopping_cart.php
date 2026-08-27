<?php
include 'db_init.php'; // Database connection ဖိုင် (သင့်နာမည်အတိုင်းပြင်ပါ)

// Content-Type က JSON လாတာကို လက်ခံဖို့နဲ့ POST method ဟုတ်မဟုတ် စစ်ဆေးရန်
$data = json_decode(file_get_contents('php://input'), true);

$cart_items = isset($data['cart']) ? $data['cart'] : [];
$table_num = isset($data['table_no']) ? $data['table_no'] : '1';
$order_comment = ''; // လိုအပ်ရင် comment ထည့်ရန်
$status = 'pending';

if (!empty($cart_items) && is_array($cart_items)) {
    $stmt = $conn->prepare("INSERT INTO customer_orders (table_no, item_name, price, order_comment, status) VALUES (?, ?, ?, ?, ?)");        
    
    if ($stmt) {
        foreach ($cart_items as $item) {
            $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;
            $name = isset($item['name']) ? $item['name'] : '';
            $price = isset($item['price']) ? floatval($item['price']) : 0;

            for ($i = 0; $i < $qty; $i++) {
                $stmt->bind_param("ssdss", $table_num, $name, $price, $order_comment, $status);
                $stmt->execute();
            }
        }
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Order placed successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Empty cart']);
}

if ($conn) {
    $conn->close();
}
exit;
?>