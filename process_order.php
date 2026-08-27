<?php
header('Content-Type: application/json');
include 'db_init.php'; // သင့် database connection ဖိုင် (အပေါ်က connection ကုဒ်အတိုင်း ဒီထဲမှာလည်း ထည့်နိုင်ပါတယ်)

// Database Connection သေချာအောင် တည်ဆောက်ရန်
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ? intval(getenv('MYSQLPORT')) : 3307;

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB Connection Failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cart_items = isset($data['cart']) ? $data['cart'] : [];
$table_num = isset($data['table_no']) ? $data['table_no'] : '1';

if (!empty($cart_items) && is_array($cart_items)) {
    // Railway database ထဲက customer_orders table သို့ ထည့်သွင်းမည်
    $stmt = $conn->prepare("INSERT INTO customer_orders (table_no, item_name, price, order_comment, status) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $order_comment = '';
        $status = 'pending';
        
        foreach ($cart_items as $item) {
            $name = $item['name'];
            $price = floatval($item['price']);
            $qty = intval($item['quantity']);

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
?>