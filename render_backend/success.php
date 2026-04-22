<?php
$orderId = $_GET['order_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Yard Handicraft</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{
            --pink:#e84393;
            --green:#27ae60;
            --dark:#333;
            --light:#f8f9fb;
            --white:#fff;
            --shadow:0 .8rem 2rem rgba(0,0,0,.08);
        }

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Verdana, Geneva, Tahoma, sans-serif;
        }

        body{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg, #fff0f7, #f8f9fb);
            padding:2rem;
        }

        .card{
            width:100%;
            max-width:560px;
            background:var(--white);
            border-radius:1.5rem;
            box-shadow:var(--shadow);
            padding:3rem 2.5rem;
            text-align:center;
        }

        .icon{
            width:90px;
            height:90px;
            margin:0 auto 1.5rem;
            border-radius:50%;
            background:rgba(39,174,96,.12);
            color:var(--green);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:4rem;
        }

        h1{
            font-size:2.7rem;
            color:var(--dark);
            margin-bottom:1rem;
        }

        p{
            font-size:1.5rem;
            color:#666;
            line-height:1.7;
            margin-bottom:1rem;
        }

        .order-box{
            margin:2rem 0;
            background:#fafafa;
            border:1px solid #eee;
            border-radius:1rem;
            padding:1.2rem 1.5rem;
            font-size:1.4rem;
            color:#555;
        }

        .order-box strong{
            color:var(--pink);
        }

        .actions{
            display:flex;
            gap:1rem;
            justify-content:center;
            flex-wrap:wrap;
            margin-top:2rem;
        }

        .btn{
            display:inline-block;
            padding:1rem 2rem;
            border-radius:5rem;
            font-size:1.4rem;
            text-decoration:none;
            transition:.2s ease;
        }

        .btn-primary{
            background:var(--pink);
            color:#fff;
        }

        .btn-primary:hover{
            background:#d63384;
        }

        .btn-secondary{
            background:#333;
            color:#fff;
        }

        .btn-secondary:hover{
            background:#111;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <i class="fas fa-check"></i>
        </div>

        <h1>Payment Successful</h1>
        <p>Your payment has been received successfully.</p>
        <p>Our team will review and process your order as soon as possible.</p>

        <?php if ($orderId): ?>
            <div class="order-box">
                Order Reference: <strong><?= htmlspecialchars($orderId) ?></strong>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="https://yardhandicraft.onrender.com/" class="btn btn-secondary">Back to Home</a>
            <a href="javascript:window.close();" class="btn btn-primary">Close Page</a>
        </div>
    </div>
</body>
</html>
