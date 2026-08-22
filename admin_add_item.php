<?php
// ၁။ ဒေတာဘေ့စ် ချိတ်ဆက်ခြင်း
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'my_website_db';
$port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($host, $user, $password, $dbname, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

// 🌟 [Insert & Image Upload System] အချက်အလက်များ ဖြည့်သွင်းပြီး သိမ်းဆည်းသည့်အခါ
if (isset($_POST['add_item'])) {
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $price = doubleval($_POST['price']);
    
    // ပုံတင်ခြင်းဆိုင်ရာ လုပ်ငန်းစဉ်
    $image_name = "";
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $target_dir = "uploads/";
        
        // uploads ဖိုဒါ မရှိသေးပါက အလိုအလျောက် ဆောက်ပေးမည့်စနစ်
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // ပုံအမည်များကို တစ်ခုနှင့်တစ်ခု မထပ်စေရန် ဒိတ်အချိန်ဖြင့် အမည်ပြောင်းခြင်း
        $file_ext = pathinfo($_FILES["item_image"]["name"], PATHINFO_EXTENSION);
        $image_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $image_name;
        
        // ပုံကို uploads ဖိုဒါထဲသို့ ရွှေ့ပြောင်းသိမ်းဆည်းခြင်း
        move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_file);
    }

    // ဒေတာဘေ့စ်ထဲသို့ အချက်အလက်များ ထည့်သွင်းခြင်း
    $stmt = $conn->prepare("INSERT INTO restaurant_menu (item_name, price, item_image, category) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $item_name, $price, $image_name, $category);
    
    if ($stmt->execute()) {
        $message = "🎉 ဟင်းလျာအသစ်ကို အောင်မြင်စွာ ထည့်သွင်းပြီးပါပြီဗျာ!";
        $message_type = "success";
        $stmt->close();
    } else {
        $message = "❌ ဒေတာထည့်သွင်းမှု မအောင်မြင်ပါ- " . $conn->error;
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Food - Restaurant Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: 'Pyidaungsu', sans-serif; }
        .form-box { max-width: 600px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); margin: 50px auto; }
        .btn-custom { background: linear-gradient(135deg, #2ed573, #1e90ff); color: white; border: none; font-weight: bold; padding: 12px; border-radius: 10px; }
        .btn-custom:hover { opacity: 0.9; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="form-box">
        <h3 class="fw-bold text-center mb-4 text-success">➕ ဟင်းလျာအသစ် ထည့်သွင်းရန်ပုံစံ</h3>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> fw-bold text-center" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">🍲 ဟင်းလျာအမည်</label>
                <input type="text" name="item_name" class="form-control" placeholder="ဥပမာ - ရှမ်းခေါက်ဆွဲ" required style="border-radius: 8px;">
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">🏷️ အမျိုးအစား (Category)</label>
                <input type="text" name="category" class="form-control" placeholder="ဥပမာ - အကြော်၊ အကင်၊ အမွှမ်း" required style="border-radius: 8px;">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">💰 ဈေးနှုန်း (MMK)</label>
                <input type="number" name="price" class="form-control" placeholder="ဥပမာ - ၃၅၀၀" required style="border-radius: 8px;">
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary">📸 ဟင်းလျာဓာတ်ပုံ တင်ရန်</label>
                <input type="file" name="item_image" class="form-control" accept="image/*" required style="border-radius: 8px;">
            </div>

            <button type="submit" name="add_item" class="btn btn-custom w-100 shadow-sm mb-3">💾 ဒေတာသိမ်းဆည်းမည်</button>
            
            <a href="admin_menu.php" class="btn btn-light text-secondary w-100 fw-bold" style="border-radius: 10px;">⬅️ မီနူးစာရင်းသို့ ပြန်သွားမည်</a>
        </form>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>