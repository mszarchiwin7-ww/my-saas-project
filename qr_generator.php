<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$qr_image_url = "";
$table_number = "";
$target_link = "";

if (isset($_POST['generate'])) {
    $table_number = trim($_POST['table_number']);
    
    if (!empty($table_number)) {
        $target_link = "http://192.168.1.107/my-saas-project/index.php?table=" . urlencode($table_number);
        
        // 🌟 QRServer API သို့ ပြောင်းလဲထားခြင်း (၁၀၀% ပုံအမှန်ပေါ်စေရန်)
        $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($target_link);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator - SaaS QR Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Pyidaungsu', sans-serif; }
        .generator-box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 40px; }
        .qr-result { border: 2px dashed #ddd; padding: 20px; border-radius: 10px; background: #fafafa; }
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="mt-4">
        <a href="menu_dashboard.php" class="btn btn-secondary fw-bold">⬅️ Dashboard သို့ ပြန်သွားရန်</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="generator-box">
                <h3 class="fw-bold text-dark text-center mb-4">🔲 စားပွဲခုံ QR Code ထုတ်ပေးရန်</h3>
                
                <form action="qr_generator.php" method="POST" class="mb-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">စားပွဲခုံအမှတ် သတ်မှတ်ရန် (Table Number)</label>
                        <input type="text" name="table_number" class="form-control" placeholder="ဥပမာ - 1 သို့မဟုတ် 2" value="<?php echo htmlspecialchars($table_number); ?>" required>
                    </div>
                    <button type="submit" name="generate" class="btn btn-primary w-100 fw-bold">⚡ QR Code ပြုလုပ်မည်</button>
                </form>

                <?php if (!empty($qr_image_url)) { ?>
                    <div class="qr-result text-center print-area">
                        <h4 class="fw-bold text-dark mb-2">Taste of Myanmar</h4>
                        <p class="badge bg-dark mb-3" style="font-size: 16px;">🪑 Table <?php echo htmlspecialchars($table_number); ?></p>
                        
                        <div class="my-3">
                            <img src="<?php echo $qr_image_url; ?>" alt="QR Code" class="img-fluid" style="max-width: 220px;">
                        </div>
                        
                        <p class="text-muted small mb-1">စမတ်ဖုန်းဖြင့် QR Scan ဖတ်ပြီး အော်ဒါမှာယူပါ</p>
                        <small class="text-primary d-block text-break" style="font-size: 11px;"><?php echo htmlspecialchars($target_link); ?></small>
                    </div>

                    <div class="mt-3 text-center">
                        <button onclick="window.print();" class="btn btn-success fw-bold px-4">🖨️ QR Code ကို Print ထုတ်မည်</button>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>
</div>

</body>
</html>