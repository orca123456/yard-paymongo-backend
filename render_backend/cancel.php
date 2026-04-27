<?php
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - YardHandicraft</title>
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
            --red: #d94f5c;
            --red-light: #fce8ea;
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
            max-width:52rem;
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
            background: var(--red-light);
            display:flex; align-items:center; justify-content:center;
            animation: popIn .5s ease .3s both;
        }
        @keyframes popIn {
            from { transform:scale(0); }
            50% { transform:scale(1.15); }
            to { transform:scale(1); }
        }
        .icon-circle i {
            font-size:3.8rem;
            color: var(--red);
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
            margin-bottom:1rem;
            max-width:40rem;
            margin-left:auto;
            margin-right:auto;
        }
        .order-ref {
            margin:2.5rem auto;
            background: var(--cream);
            border:1px solid rgba(196,122,138,.15);
            border-radius:1.2rem;
            padding:1.4rem 2rem;
            font-size:1.4rem;
            color:var(--text);
            display:inline-flex;
            align-items:center;
            gap:.8rem;
        }
        .order-ref i { color:var(--rose); font-size:1.3rem; }
        .order-ref strong { color:var(--rose-deep); font-size:1.5rem; }
        .divider {
            width:5rem;
            height:3px;
            background: linear-gradient(90deg, var(--rose-light), var(--rose));
            border-radius:2px;
            margin:2rem auto;
        }
        .help-text {
            font-size:1.35rem;
            color:#999;
            line-height:1.7;
            margin-bottom:2.5rem;
        }
        .help-text a {
            color:var(--rose);
            text-decoration:none;
            font-weight:500;
        }
        .help-text a:hover { text-decoration:underline; }
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
        <div class="icon-circle"><i class="fas fa-times"></i></div>
        <h1>Payment Cancelled</h1>
        <p class="subtitle">Your payment was not completed. Don't worry — no charges were made to your account.</p>

        <?php if ($orderId > 0): ?>
            <div class="order-ref">
                <i class="fas fa-receipt"></i>
                Order Reference: <strong>#<?= $orderId ?></strong>
            </div>
        <?php endif; ?>

        <div class="divider"></div>
        <p class="help-text">You can try again anytime. Need help? Reach us on <a href="https://www.facebook.com/profile.php?id=61571860299181" target="_blank">Facebook</a> or our <a href="index.php#contact">contact form</a>.</p>

        <div class="actions">
            <a href="index.php" class="btn btn-dark"><i class="fas fa-home"></i> Back to Shop</a>
            <a href="index.php#products" class="btn btn-rose"><i class="fas fa-redo"></i> Try Again</a>
        </div>

        <div class="brand"><i class="fas fa-leaf"></i> YardHandicraft</div>
    </div>
</body>
</html>
