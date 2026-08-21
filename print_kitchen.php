<?php
// print_kitchen.php
$conn = new mysqli("localhost", "root", "", "my_website_db", 3307);
$conn->set_charset("utf8mb4");

if (!isset($_GET['table_number'])) {
    die("ခုံနံပါတ် လွဲမှားနေပါသည်မ။");
}

$table_num = $_GET['table_number'];

// မီးဖိုဆောင်အတွက် Pending ဖြစ်နေဆဲ အော်ဒါများကိုသာ ဆွဲထုတ်ခြင်း (စျေးနှုန်းမပါပါ)
$sql = "SELECT item_name, order_comment FROM customer_orders WHERE table_number = ? AND status = 'Pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $table_num);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while($row = $result->fetch_assoc()) {
    $items[] = $row;
}

if(count($items) == 0) {
    die("ယခုခုံအတွက် ချက်ရန်အော်ဒါအသစ် မရှိပါဗျာ။");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kitchen Ticket - <?php echo htmlspecialchars($table_num); ?></title>
    <style>
        body { 
            font-family: sans-serif; 
            width: 80mm; 
            margin: 0; 
            padding: 5px; 
            font-size: 16px; 
            font-weight: bold; 
        }
        .text-center { text-align: center; }
        .line { border-bottom: 2px dashed #000; margin: 8px 0; }
        table { width: 100%; }
        .comment { font-size: 13px; font-weight: normal; color: red; }
    </style>
</head>
<body>

    <div class="text-center">
        <h2 style="margin:0;">🔥 KITCHEN ORDER 🔥</h2>
        <h1 style="margin: 5px 0; font-size: 32px;"><?php echo htmlspecialchars($table_num); ?></h1>
        <p style="font-size: 12px; font-weight: normal; margin:0;">Time: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="line"></div>

    <table>
        <?php $count = 1; foreach($items as $item) { ?>
        <tr>
            <td style="vertical-align: top; width: 25px;"><?php echo $count++; ?>.</td>
            <td>
                <span><?php echo htmlspecialchars($item['item_name']); ?></span>
                <?php if (!empty($item['order_comment'])) { ?>
                    <br><span class="comment">💬 (မှတ်ချက်: <?php echo htmlspecialchars($item['order_comment']); ?>)</span>
                <?php } ?>
            </td>
        </tr>
        <tr><td colspan="2" style="height: 10px;"></td></tr>
        <?php } ?>
    </table>

    <div class="line"></div>

    <script>
        // စာမျက်နှာပွင့်လာတာနဲ့ Print Box တန်းခေါ်ပြီး Print ပြီးရင် Window ပြန်ပိတ်ခိုင်းခြင်း
        window.onload = function() {
            window.print();
            setTimeout(function() { window.close(); }, 500);
        };
    </script>

</body>
</html>