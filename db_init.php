<?php
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ?: 3307;

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 1. restaurant_menu ဇယားဖန်တီးခြင်း
$sql1 = "CREATE TABLE IF NOT EXISTS restaurant_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    price INT NOT NULL,
    item_image VARCHAR(255) DEFAULT ''
)";
$conn->query($sql1);

// 2. နမူနာ အစားအစာ မီနူးအချို့ ထည့်သွင်းခြင်း (မရှိသေးရင်)
$check = $conn->query("SELECT COUNT(*) as count FROM restaurant_menu");
$row = $check->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO restaurant_menu (item_name, price, item_image) VALUES 
        ('မန္တလေးမုန့်ဟင်းခါး', 2500, ''),
        ('ရှမ်းခေါက်ဆွဲ', 3000, ''),
        ('ကျက်သားဘိန်းမုန့်', 3500, '')");
}

// 3. customer_orders ဇယားဖန်တီးခြင်း
$sql2 = "CREATE TABLE IF NOT EXISTS customer_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_no VARCHAR(50),
    order_details TEXT,
    total_price INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql2);

echo "Database Setup Successfully Completed!";
?>
