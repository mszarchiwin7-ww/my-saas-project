<?php
// Database ချိတ်ဆက်ခြင်း
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "my_website_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

// လောလောဆယ် Pending ဖြစ်နေတဲ့ အော်ဒါ စုစုပေါင်း အရေအတွက်ကို ရေတွက်ခြင်း
$sql = "SELECT COUNT(id) AS total_orders FROM `orders` WHERE `status` = 'Pending'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

// JavaScript ဆီကို ကိန်းဂဏန်းသီးသန့် (ဥပမာ- 3) လှမ်းပြန်ဖြေပေးခြင်း
echo $row['total_orders'];

$conn->close();
?>