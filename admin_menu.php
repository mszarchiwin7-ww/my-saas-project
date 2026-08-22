<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);// Railway Environment Variables တွေကို အသုံးပြု၍ ချိတ်ဆက်ခြင်း
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ?: '3306'; // Railway မှာ 3306 ဖြစ်လေ့ရှိပါတယ်

$conn = new mysqli($host, $user, $password, $dbname, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 🌟 [Delete Feature] ဖျက်ရန်ခလုတ် နှိပ်လိုက်ပါက ဒေတာဘေ့စ်ထဲက ဖျက်ပေးမည့်စနစ်
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // ပုံပါရှိပါက ပုံကိုပါ server ထဲက တစ်ခါတည်း ဖျက်ပစ်မည်
    $img_query = $conn->query("SELECT item_image FROM restaurant_menu WHERE id = $delete_id");
    if($img_row = $img_query->fetch_assoc()) {
        if(!empty($img_row['item_image']) && file_exists("uploads/" . $img_row['item_image'])) {
            unlink("uploads/" . $img_row['item_image']);
        }
    }
    
    $conn->query("DELETE FROM restaurant_menu WHERE id = $delete_id");
    header("Location: admin_menu.php");
    exit();
}

// ၂။ ရှိသမျှ မီနူးအားလုံးကို ဆွဲထုတ်ခြင်း
$sql = "SELECT * FROM restaurant_menu ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Pyidaungsu', sans-serif; }
        .admin-box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 40px; }
        .table-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container">
    <div class="admin-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark">⚙️ ဟင်းလျာမီနူးများ စီမံခန့်ခွဲခြင်း (Menu Panel)</h3>
            <a href="admin_add_item.php" class="btn btn-success fw-bold">+ ဟင်းလျာအသစ်ထည့်မည်</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ပုံရိပ်</th>
                        <th>ဟင်းလျာအမည်</th>
                        <th>အမျိုးအစား</th>
                        <th>ဈေးနှုန်း</th>
                        <th>လုပ်ဆောင်ချက်</th>
                    </tr>
                </thead>
                <tbody>
<?php
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // file_exists ကို ဖြုတ်ပြီး Database ထဲမှာ ပုံနာမည်ရှိရင် ယူသုံးခိုင်းခြင်း
        $img_src = !empty($row['item_image']) 
            ? "uploads/" . $row['item_image'] 
            : "https://via.placeholder.com/60";
?>                            <tr>
                                <td><img src="<?php echo $img_src; ?>" class="table-img" alt="food"></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td class="text-danger fw-bold"><?php echo number_format($row['price']); ?> MMK</td>
                                <td>
                                    <a href="admin_edit_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning fw-bold text-dark">✏️ ပြင်မည်</a>
                                    <a href="admin_menu.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger fw-bold ms-1" onclick="return confirm('သေချာပေါက် ဖျက်မှာပါလားဗျာ?')">🗑️ ဖျက်မည်</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-muted py-4'>လောလောဆယ် မီနူးထဲမှာ ဘာဟင်းပွဲမှ မရှိသေးပါဗျာ။</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>