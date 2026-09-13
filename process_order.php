<?php
// JSON response သာ ပြန်ရန် Error များကို ဖုံးကွယ်ထားမည်
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Database Connection (Railway ရော Local မှာပါ အလုပ်လုပ်သည်)
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
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

// JavaScript က ပို့လိုက်သော JSON Data များကို လက်ခံခြင်း
$input_data = json_decode(file_get_contents('php://input'), true);

$table_num = isset($input_data['table_no']) ? $input_data['table_no'] : '1';
$cart_items = isset($input_data['cart']) ? $input_data['cart'] : [];

// customer_orders table မရှိသေးပါက အလိုအလျောက် ဖန်တီးပေးမည်
$conn->query("CREATE TABLE IF NOT EXISTS customer_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(50),
    item_name VARCHAR(255),
    price DECIMAL(10,2),
    order_comment TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// သင့်ရဲ့ မူလကုဒ်ပိုင်းဆိုင်ရာ Logic
if (!empty($cart_items) && is_array($cart_items)) {
    $stmt = $conn->prepare("INSERT INTO customer_orders (table_number, item_name, price, order_comment, status) VALUES (?, ?, ?, ?, 'Pending')");
    
    if ($stmt) {
        $order_comment = '';
        
        foreach ($cart_items as $item) {
            $name = isset($item['name']) ? $item['name'] : '';
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
} else {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty or invalid data']);
}

$conn->close();
?>