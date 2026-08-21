<?php
// view_bill.php
$conn = new mysqli("localhost", "root", "", "my_website_db", 3307);
$conn->set_charset("utf8mb4");

if (!isset($_GET['table_number'])) {
    die("ခုံနံပါတ် လွဲမှားနေပါသည်မ။");
}

$table_num = $_GET['table_number'];

// 🌟 ခုံအလိုက် Served ဖြစ်နေတဲ့ ဟင်းပွဲတွေကို ဆွဲထုတ်ခြင်း
$sql = "SELECT item_name, price FROM customer_orders WHERE table_number = ? AND status = 'Served'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $table_num);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$items = [];
while($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total += $row['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill for <?php echo htmlspecialchars($table_num); ?></title>
    <style>
        body { font-family: sans-serif; width: 80mm; margin: 0; padding: 10px; font-size: 14px; }
        .text-center { text-align: center; }
        .line { border-bottom: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        .total { font-weight: bold; font-size: 16px; }
        .qr-area { margin-top: 15px; text-align: center; }
        .qr-area img { width: 150px; height: 150px; }
    </style>
</head>
<body>

    <div class="text-center">
        <h3>🍔 Taste of Myanmar 🍔</h3>
        <p>ဘေလ်ယာယီစာရွက်</p>
        <p><b><?php echo htmlspecialchars($table_num); ?></b></p>
    </div>

    <div class="line"></div>

    <table>
        <?php foreach($items as $item) { ?>
        <tr>
            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
            <td style="text-align: right;"><?php echo number_format($item['price']); ?> ကျပ်</td>
        </tr>
        <?php } ?>
    </table>

    <div class="line"></div>

    <table class="total">
        <tr>
            <td>စုစုပေါင်း ကျသင့်ငွေ:</td>
            <td style="text-align: right;"><?php echo number_format($total); ?> ကျပ်</td>
        </tr>
    </table>

    <div class="qr-area">
        <p style="font-size: 11px;">Scan to Pay (KPay / WavePay)</p>
<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=TasteOfMyanmar" alt="Payment QR">    </div>

    <div class="text-center" style="margin-top: 15px; font-size: 12px;">
        <p>~ ကျေးဇူးတင်ပါသည်၊ နောက်လည်း ကြွခဲ့ပါဦးရှင် ~</p>
    </div>

    <script>
        // စာမျက်နှာပွင့်လာတာနဲ့ Printer စက်ထဲကို တန်းပို့မည့် JavaScript စနစ်
        window.onload = function() {
            window.print();
            // ပရင့်ရိုက်ပြီးရင် ဤ Window ကို အလိုအလျောက် ပြန်ပိတ်ခိုင်းခြင်း
            setTimeout(function() { window.close(); }, 500);
        };
    </script>

</body>
</html>