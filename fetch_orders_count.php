<?php
// Railway Database ချိတ်ဆက်ခြင်း
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($host, $user, $password, $dbname, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

// လောလောဆယ် Pending ဖြစ်နေတဲ့ အော်ဒါ စုစုပေါင်း အရေအတွက်ကို ရေတွက်ခြင်း
$sql = "SELECT COUNT(id) AS total_orders FROM `orders` WHERE `status` = 'Pending'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

// JavaScript ဆီကို ကိန်းဂဏန်းသီးသန့် လှမ်းပြန်ဖြေပေးခြင်း
echo $row['total_orders'];

$conn->close();
?>