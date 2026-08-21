<?php
// get_live_orders.php
session_start();

$conn = new mysqli("localhost", "root", "", "my_website_db", 3307);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed"]));
}

// ၁။ Kitchen Table အတွက် (Pending နှင့် Cooking) အော်ဒါများ ဆွဲထုတ်ခြင်း
$sql_kitchen = "SELECT * FROM customer_orders WHERE status IN ('Pending', 'Cooking') ORDER BY created_at DESC, id DESC";
$result_kitchen = $conn->query($sql_kitchen);

$kitchen_html = "";
if ($result_kitchen->num_rows > 0) {
    while ($row = $result_kitchen->fetch_assoc()) {
        $t_num = htmlspecialchars(str_ireplace('Table', '', $row['table_number']));
        $item_name = htmlspecialchars($row['item_name']);
        $status = $row['status'];
        $badge_class = ($status == 'Cooking') ? 'bg-info' : 'bg-warning text-dark';
        
        $comment_html = "";
        if (!empty($row['order_comment'])) {
            $comment_html = "<br><span class='badge bg-danger-subtle text-danger fw-normal mt-1' style='font-size: 12px; border: 1px solid #f5c2c2;'>💬 မှတ်ချက်: " . htmlspecialchars($row['order_comment']) . "</span>";
        }

        $kitchen_html .= "<tr>
            <td><span class='badge bg-secondary'>🪑 Table {$t_num}</span></td>
            <td class='text-start ps-3 fw-bold' style='font-size: 15px; color: #2d3748;'>{$item_name}{$comment_html}</td>
            <td><span class='badge {$badge_class}'>{$status}</span></td>
            <td>
                <a href='menu_dashboard.php?id={$row['id']}&status=Cooking' class='btn btn-sm btn-outline-info me-1'>🧑‍🍳 ချက်နေသည်</a>
                <a href='menu_dashboard.php?id={$row['id']}&status=Served' class='btn btn-sm btn-success text-white'>✅ ပွဲပြင်ပြီး</a>
            </td>
        </tr>";
    }
} else {
    $kitchen_html = "<tr><td colspan='4' class='text-muted py-3'>လက်ရှိ ချက်ရန် အော်ဒါမရှိပါ။</td></tr>";
}

// ၂။ Cashier Table အတွက် (Served) အော်ဒါများ ဆွဲထုတ်ခြင်း
$sql_cashier = "SELECT table_number, GROUP_CONCAT(item_name SEPARATOR ', ') AS all_items, SUM(price) AS total_bill FROM customer_orders WHERE status = 'Served' GROUP BY table_number ORDER BY table_number ASC";
$result_cashier = $conn->query($sql_cashier);

$cashier_html = "";
if ($result_cashier->num_rows > 0) {
    while ($row = $result_cashier->fetch_assoc()) {
        $t_num = htmlspecialchars(str_ireplace('Table', '', $row['table_number']));
        $all_items = htmlspecialchars($row['all_items']);
        $total_bill = number_format($row['total_bill']);
        $t_url = urlencode($row['table_number']);

        $cashier_html .= "<tr>
            <td><span class='badge bg-dark'>🪑 Table {$t_num}</span></td>
            <td class='text-start ps-3'>
                <div class='text-wrap text-secondary mb-1' style='font-size: 13px; max-width: 200px;'>📋 {$all_items}</div>
                <span class='text-danger fw-bold' style='font-size: 15px;'>💰 {$total_bill} MMK</span>
            </td>
            <td>
                <a href='view_bill.php?table_number={$t_url}' target='_blank' class='btn btn-sm btn-info fw-bold text-white me-1'>🖨️ ဘေလ်ထုတ်ရန်</a>
                <a href='menu_dashboard.php?clear_table={$t_url}' class='btn btn-sm btn-danger fw-bold'>💵 Сာရင်းပိတ်မည်</a>
            </td>
        </tr>";
    }
} else {
    $cashier_html = "<tr><td colspan='3' class='text-muted py-3'>ငွေရှင်းရန် စောင့်ဆိုင်းနေသောခုံ မရှိပါ။</td></tr>";
}

// ၃။ 🌟 [အရေးကြီးဆုံးအပိုင်း] Pending ဖြစ်နေသော အော်ဒါစုစုပေါင်းနှင့် နောက်ဆုံးဝင်လာသော ခုံကို ရှာဖွေခြင်း
$sql_count = "SELECT id, table_number FROM customer_orders WHERE status = 'Pending' ORDER BY id DESC";
$res_count = $conn->query($sql_count);

$new_order_count = 0;
$latest_pending_table = "";

if ($res_count) {
    $new_order_count = $res_count->num_rows; // Pending အော်ဒါစုစုပေါင်းကို ကွက်တိရေတွက်ခြင်း
    if ($new_order_count > 0) {
        $row_latest = $res_count->fetch_assoc();
        $latest_pending_table = $row_latest['table_number']; // နောက်ဆုံးဝင်လာသည့်ခုံနာမည်
    }
}

// JSON ဖြင့် ဒေတာပြန်ထုတ်ပေးခြင်း
echo json_encode([
    "kitchen_html" => $kitchen_html,
    "cashier_html" => $cashier_html,
    "new_order_count" => $new_order_count, // JavaScript သို့ အရေအတွက်အမှန် ပို့ပေးခြင်း
    "latest_pending_table" => $latest_pending_table
]);

$conn->close();
?>