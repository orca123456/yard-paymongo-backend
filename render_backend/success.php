<?php
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - YardHandicraft</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--pink:#e84393;--green:#27ae60;--dark:#333;--white:#fff;--shadow:0 .8rem 2rem rgba(0,0,0,.08);}
        *{margin:0;padding:0;box-sizing:border-box;font-family:Verdana,Geneva,Tahoma,sans-serif;}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#fff0f7,#f8f9fb);padding:2rem;}
        .card{width:100%;max-width:600px;background:var(--white);border-radius:1.5rem;box-shadow:var(--shadow);padding:3rem 2.5rem;text-align:center;}
        .icon{width:90px;height:90px;margin:0 auto 1.5rem;border-radius:50%;background:rgba(39,174,96,.12);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:4rem;}
        h1{font-size:2.7rem;color:var(--dark);margin-bottom:1rem;}
        p{font-size:1.5rem;color:#666;line-height:1.7;margin-bottom:1rem;}
        .order-box{margin:2rem 0;background:#fafafa;border:1px solid #eee;border-radius:1rem;padding:1.2rem 1.5rem;font-size:1.4rem;color:#555;}
        .order-box strong{color:var(--pink);}
        .steps{text-align:left;margin:2rem 0;padding:0;list-style:none;}
        .steps li{font-size:1.3rem;color:#555;padding:.8rem 0;padding-left:3rem;position:relative;border-bottom:1px solid #f5f5f5;}
        .steps li:last-child{border-bottom:none;}
        .steps li i{position:absolute;left:0;top:.8rem;color:var(--green);font-size:1.4rem;}
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
        <div class="icon"><i class="fas fa-check"></i></div>
        <h1>Payment Successful!</h1>
        <p>Thank you for your order! Your payment has been received and recorded successfully.</p>

        <?php if ($orderId > 0): ?>
            <div class="order-box">
                Order Reference: <strong>#<?= $orderId ?></strong>
            </div>
        <?php endif; ?>

        <h3 style="font-size:1.8rem;color:#333;margin:1.5rem 0 1rem;">What Happens Next</h3>
        <ul class="steps">
            <li><i class="fas fa-check-circle"></i> Your order has been automatically recorded in our system.</li>
            <li><i class="fas fa-user-check"></i> Our admin team will verify your payment and order details.</li>
            <li><i class="fas fa-bell"></i> You will receive confirmation via email, SMS, or Messenger.</li>
            <li><i class="fas fa-gift"></i> Your handmade item will be prepared for pickup or delivery.</li>
            <li><i class="fas fa-file-invoice"></i> Please keep your payment receipt or reference number for verification.</li>
        </ul>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-home" style="margin-right:.5rem;"></i>Back to Shop</a>
        </div>
    </div>
</body>
</html>
