if (!empty($cart_items) && is_array($cart_items)) {
    $stmt = $conn->prepare("INSERT INTO customer_orders (table_number, item_name, price, order_comment, status) VALUES (?, ?, ?, ?, 'Pending')");
    
    if ($stmt) {
        $order_comment = '';
        
        foreach ($cart_items as $item) {
            $name = $item['name'];
            $price = floatval($item['price']);
            $qty = intval($item['quantity']);

            for ($i = 0; $i < $qty; $i++) {
                // status ကို တိုက်ရိုက်ထည့်လိုက်ပြီဖြစ်므로 ssds ၄ ခုသာ bind လုပ်ပါ
                $stmt->bind_param("ssds", $table_num, $name, $price, $order_comment);
                $stmt->execute();
            }
        }
        $stmt->close();
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}