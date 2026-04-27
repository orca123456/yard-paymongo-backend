<?php
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - YardHandicraft</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--pink:#e84393;--red:#e74c3c;--dark:#333;--white:#fff;--shadow:0 .8rem 2rem rgba(0,0,0,.08);}
        *{margin:0;padding:0;box-sizing:border-box;font-family:Verdana,Geneva,Tahoma,sans-serif;}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#fff5f5,#f8f9fb);padding:2rem;}
        .card{width:100%;max-width:560px;background:var(--white);border-radius:1.5rem;box-shadow:var(--shadow);padding:3rem 2.5rem;text-align:center;}
        .icon{width:90px;height:90px;margin:0 auto 1.5rem;border-radius:50%;background:rgba(231,76,60,.12);color:var(--red);display:flex;align-items:center;justify-content:center;font-size:4rem;}
        h1{font-size:2.7rem;color:var(--dark);margin-bottom:1rem;}
        p{font-size:1.5rem;color:#666;line-height:1.7;margin-bottom:1rem;}
        .order-box{margin:2rem 0;background:#fafafa;border:1px solid #eee;border-radius:1rem;padding:1.2rem 1.5rem;font-size:1.4rem;color:#555;}
        .order-box strong{color:var(--pink);}
        .actions{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-top:2rem;}
        .btn{display:inline-block;padding:1rem 2rem;border-radius:5rem;font-size:1.4rem;text-decoration:none;transition:.2s ease;}
        .btn-primary{background:var(--pink);color:#fff;}
        .btn-primary:hover{background:#d63384;}
        .btn-secondary{background:#333;color:#fff;}
        .btn-secondary:hover{background:#111;}
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fas fa-times"></i></div>
        <h1>Payment Cancelled</h1>
        <p>Your payment was not completed. Don't worry — no charges were made to your account.</p>
        <p>You can try again anytime. If you need help, feel free to contact us through our Facebook page or contact form.</p>

        <?php if ($orderId > 0): ?>
            <div class="order-box">
                Order Reference: <strong>#<?= $orderId ?></strong>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-home" style="margin-right:.5rem;"></i>Back to Shop</a>
            <a href="index.php#products" class="btn btn-primary"><i class="fas fa-redo" style="margin-right:.5rem;"></i>Try Again</a>
        </div>
    </div>
</body>
</html>
