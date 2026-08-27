<?php
include 'db_init.php';
// Your database connection file include here if needed, e.g., include 'db.php';

if (!empty($cart_items) && is_array($cart_items)) {
    // Database ထဲမှာ table_no (သို့မဟုတ် table_number) ဘယ်ဟာကို သုံးထားလဲ အတည်ပြုပါ
    // အကယ်၍ table_number ကိုသုံးရင် အောက်ပါ query မှာ table_no နေရာမှာ table_number လို့ ပြောင်းပေးပါ
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
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Empty cart']);
}

$conn->close();
exit;
?>