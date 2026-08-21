<?php
// ၁။ ကွက်တိမှန်ကန်သော ဒေတာဘေ့စ်ချိတ်ဆက်မှု
$conn = new mysqli("localhost", "root", "", "my_website_db", 3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// ၂။ အချက်အလက်များ လက်ခံခြင်း
if (isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = intval($_GET['id']);
    $status_input = trim($_GET['status']);

    // စာလုံးအကြီး/အသေး စနစ်တကျ ညှိခြင်း
    if (strtolower($status_input) == 'cooking') {
        $new_status = 'Cooking';
    } elseif (strtolower($status_input) == 'served') {
        $new_status = 'Served';
    } else {
        $new_status = 'Pending';
    }

    // ၃။ SQL Update ပြုလုပ်ခြင်း
    $sql = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";
    
    if ($conn->query($sql) === TRUE) {
        // 🌟 အောင်မြင်ပါက အဖြူရောင် Debug စာမျက်နှာ မပြတော့ဘဲ Dashboard ဆီသို့ တိုက်ရိုက် ပြန်ခေါ်သွားမည်
        header("Location: menu_dashboard.php");
        exit();
    } else {
        echo "SQL Error: " . $conn->error;
    }
} else {
    echo "Invalid Request!";
}
?>