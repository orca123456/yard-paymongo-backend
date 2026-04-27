<?php
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - YardHandicraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --rose: #c47a8a;
            --rose-deep: #a8586a;
            --rose-light: #f3dce1;
            --cream: #fdf6f0;
            --warm-white: #fffaf7;
            --dark: #2d2d2d;
            --text: #555;
            --green: #2ecc71;
            --green-light: #e8f8ef;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { font-size:62.5%; }
        body {
            font-family:'Poppins',sans-serif;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            background: linear-gradient(135deg, var(--cream) 0%, var(--warm-white) 50%, var(--rose-light) 100%);
            padding:2rem;
        }
        .card {
            width:100%;
            max-width:56rem;
            background:#fff;
            border-radius:2rem;
            box-shadow: 0 12px 40px rgba(0,0,0,.08);
            padding:4.5rem 3.5rem;
            text-align:center;
            border:1px solid rgba(0,0,0,.05);
            animation: slideUp .6s ease-out both;
        }
        @keyframes slideUp {
            from { opacity:0; transform:translateY(25px); }
            to { opacity:1; transform:translateY(0); }
        }
        .icon-circle {
            width:9rem; height:9rem;
            margin:0 auto 2.5rem;
            border-radius:50%;
            background: var(--green-light);
            display:flex; align-items:center; justify-content:center;
            position:relative;
            animation: popIn .5s ease .3s both;
        }
        @keyframes popIn {
            from { transform:scale(0); }
            50% { transform:scale(1.15); }
            to { transform:scale(1); }
        }
        .icon-circle::after {
            content:'';
            position:absolute;
            inset:-6px;
            border-radius:50%;
            border:2px solid rgba(46,204,113,.2);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { transform:scale(1); opacity:1; }
            50% { transform:scale(1.12); opacity:.5; }
        }
        .icon-circle i {
            font-size:3.8rem;
            color: var(--green);
        }
        h1 {
            font-family:'Playfair Display',serif;
            font-size:3.2rem;
            color:var(--dark);
            margin-bottom:1.2rem;
            font-weight:700;
        }
        .subtitle {
            font-size:1.5rem;
            color:var(--text);
            line-height:1.8;
            max-width:42rem;
            margin:0 auto 1.5rem;
        }
        .order-ref {
            margin:2rem auto;
            background: var(--green-light);
            border:1px solid rgba(46,204,113,.2);
            border-radius:1.2rem;
            padding:1.4rem 2rem;
            font-size:1.4rem;
            color:var(--text);
            display:inline-flex;
            align-items:center;
            gap:.8rem;
        }
        .order-ref i { color:var(--green); font-size:1.3rem; }
        .order-ref strong { color:var(--green); font-size:1.5rem; }
        .divider {
            width:5rem;
            height:3px;
            background: linear-gradient(90deg, var(--rose-light), var(--rose));
            border-radius:2px;
            margin:2.5rem auto;
        }
        .next-steps-title {
            font-family:'Playfair Display',serif;
            font-size:2rem;
            color:var(--dark);
            margin-bottom:2rem;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:.8rem;
        }
        .next-steps-title i { color:var(--rose); font-size:1.6rem; }
        .steps {
            list-style:none;
            text-align:left;
            margin:0 auto 2.5rem;
            max-width:42rem;
        }
        .steps li {
            font-size:1.35rem;
            color:var(--text);
            padding:1.2rem 0 1.2rem 3.5rem;
            position:relative;
            border-bottom:1px solid rgba(0,0,0,.04);
            line-height:1.6;
            transition: all .3s ease;
        }
        .steps li:last-child { border-bottom:none; }
        .steps li:hover { padding-left:4rem; }
        .steps li::before {
            content:'';
            position:absolute;
            left:0; top:1.4rem;
            width:2.2rem; height:2.2rem;
            background:var(--rose-light);
            border-radius:50%;
        }
        .steps li i {
            position:absolute;
            left:.55rem; top:1.8rem;
            color:var(--rose);
            font-size:1.1rem;
            z-index:1;
        }
        .actions {
            display:flex;
            gap:1.2rem;
            justify-content:center;
            flex-wrap:wrap;
        }
        .btn {
            display:inline-flex;
            align-items:center;
            gap:.6rem;
            padding:1.2rem 2.8rem;
            border-radius:5rem;
            font-size:1.4rem;
            font-family:'Poppins',sans-serif;
            font-weight:500;
            text-decoration:none;
            transition: all .3s ease;
            cursor:pointer;
        }
        .btn-dark {
            background:var(--dark);
            color:#fff;
        }
        .btn-dark:hover {
            background:var(--rose);
            transform:translateY(-2px);
            box-shadow: 0 6px 20px rgba(196,122,138,.3);
        }
        .btn-rose {
            background:var(--rose);
            color:#fff;
            box-shadow: 0 4px 15px rgba(196,122,138,.25);
        }
        .btn-rose:hover {
            background:var(--rose-deep);
            transform:translateY(-2px);
            box-shadow: 0 6px 20px rgba(196,122,138,.4);
        }
        .brand {
            margin-top:3rem;
            font-size:1.2rem;
            color:#ccc;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:.5rem;
        }
        .brand i { color:var(--rose-light); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle"><i class="fas fa-check"></i></div>
        <h1>Payment Successful!</h1>
        <p class="subtitle">Thank you for your order! Your payment has been received and recorded successfully.</p>

        <?php if ($orderId > 0): ?>
            <div class="order-ref">
                <i class="fas fa-receipt"></i>
                Order Reference: <strong>#<?= $orderId ?></strong>
            </div>
        <?php endif; ?>

        <div class="divider"></div>

        <h3 class="next-steps-title"><i class="fas fa-clipboard-list"></i> What Happens Next</h3>
        <ul class="steps">
            <li><i class="fas fa-check"></i> Your order has been automatically recorded in our system.</li>
            <li><i class="fas fa-check"></i> Our admin team will verify your payment and order details.</li>
            <li><i class="fas fa-check"></i> You will receive confirmation via email, SMS, or Messenger.</li>
            <li><i class="fas fa-check"></i> Your handmade item will be prepared for pickup or delivery.</li>
            <li><i class="fas fa-check"></i> Please keep your payment receipt or reference number.</li>
        </ul>

        <div class="actions">
            <a href="index.php" class="btn btn-dark"><i class="fas fa-home"></i> Back to Shop</a>
            <a href="index.php#products" class="btn btn-rose"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
        </div>

        <div class="brand"><i class="fas fa-leaf"></i> YardHandicraft</div>
    </div>
</body>
</html>
