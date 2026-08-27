<?php
header('Content-Type: application/json');

// Railway ရော Local (XAMPP) မှာပါ အမှားအယွင်းမရှိ ချိတ်ဆက်ရန်
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ? intval(getenv('MYSQLPORT')) : 3307;

$database_url = getenv('MYSQL_URL');
if ($database_url && strpos($database_url, '${') === false) {
    $db = parse_url($database_url);
    if (isset($db["host"])) $host = $db["host"];
    if (isset($db["user"])) $user = $db["user"];
    if (isset($db["pass"])) $pass = $db["pass"];
    if (isset($db["path"])) $dbname = ltrim($db["path"], "/");
    if (isset($db["port"])) $port = $db["port"];
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB Connection Failed']);
    exit;
}
$conn->set_charset("utf8mb4");

$data = json_decode(file_get_contents('php://input'), true);
$cart_items = isset($data['cart']) ? $data['cart'] : [];
$table_num = isset($data['table_no']) ? $data['table_no'] : '1';

if (!empty($cart_items) && is_array($cart_items)) {
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
exit;
?>