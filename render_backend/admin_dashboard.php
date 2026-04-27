<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

require_once 'db.php';

$statusMsg = '';

$allowedStatuses = ['pending', 'paid', 'processing', 'ready_for_pickup', 'shipped', 'completed', 'cancelled'];
$allowedFilters = ['all', 'pending', 'paid', 'processing', 'ready_for_pickup', 'shipped', 'completed', 'cancelled'];

function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function js($value)
{
    return htmlspecialchars(json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
}

function orderValue($order, $key, $default = '')
{
    return $order[$key] ?? $default;
}

// Make sure preorders table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS preorders (
            id SERIAL PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150),
            contact VARCHAR(50),
            fb_link TEXT,
            address TEXT,
            product VARCHAR(150),
            price NUMERIC(10, 2) DEFAULT 0,
            quantity INTEGER DEFAULT 1,
            notes TEXT,
            payment_method VARCHAR(100),
            order_status VARCHAR(30) DEFAULT 'pending',
            payment_status VARCHAR(30) DEFAULT 'pending',
            paymongo_checkout_id VARCHAR(150),
            paymongo_payment_id VARCHAR(150),
            paid_at TIMESTAMPTZ,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");
} catch (PDOException $e) {
    error_log("Table setup failed: " . $e->getMessage());
    die("Service temporarily unavailable.");
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $newStatus = $_POST['order_status'] ?? '';

    if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("
            UPDATE preorders
            SET order_status = :order_status,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':order_status' => $newStatus,
            ':id' => $orderId
        ]);

        $statusMsg = "Order #{$orderId} status updated to <strong>" . e(ucfirst($newStatus)) . "</strong>.";
    }
}

// Handle cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = (int) ($_POST['order_id'] ?? 0);

    if ($orderId > 0) {
        $stmt = $pdo->prepare("
            UPDATE preorders
            SET order_status = 'cancelled',
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $orderId
        ]);

        $statusMsg = "Order #{$orderId} has been marked as <strong>Cancelled</strong>.";
    }
}

// Fetch all orders
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$where = [];
$params = [];

if ($filter !== 'all') {
    $where[] = "order_status = :filter";
    $params[':filter'] = $filter;
}

if ($search !== '') {
    $where[] = "(name ILIKE :search_name OR product ILIKE :search_product OR contact ILIKE :search_contact)";
    $params[':search_name'] = "%{$search}%";
    $params[':search_product'] = "%{$search}%";
    $params[':search_contact'] = "%{$search}%";
}

$sql = "SELECT * FROM preorders";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Stats
$totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM preorders")->fetchColumn();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM preorders WHERE order_status = :status");

$countStmt->execute([':status' => 'pending']);
$pendingCount = (int) $countStmt->fetchColumn();

$countStmt->execute([':status' => 'paid']);
$paidCount = (int) $countStmt->fetchColumn();

$countStmt->execute([':status' => 'processing']);
$processingCount = (int) $countStmt->fetchColumn();

$countStmt->execute([':status' => 'completed']);
$completedCount = (int) $countStmt->fetchColumn();

$countStmt->execute([':status' => 'cancelled']);
$cancelledCount = (int) $countStmt->fetchColumn();

// Revenue: sum price*quantity for paid + completed orders
$revenueStmt = $pdo->query("SELECT COALESCE(SUM(price * COALESCE(quantity, 1)), 0) FROM preorders WHERE order_status IN ('paid', 'completed')");
$totalRevenue = (float) $revenueStmt->fetchColumn();

// Contacts / customer messages
try {
    $contactsStmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 50");
    $contactMessages = $contactsStmt->fetchAll();
} catch (PDOException $e) {
    $contactMessages = [];
}
$contactCount = count($contactMessages);

$statusColors = [
    'pending' => '#f39c12',
    'paid' => '#3498db',
    'processing' => '#9b59b6',
    'ready_for_pickup' => '#1abc9c',
    'shipped' => '#2980b9',
    'completed' => '#27ae60',
    'cancelled' => '#e74c3c',
];

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="15">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Yard Handicraft</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --pink: #e84393;
            --dark: #333;
            --light-bg: #f7f7f7;
            --card-shadow: 0 .4rem 1.5rem rgba(0, 0, 0, .09);
        }

        * {
            margin: 0;
            box-sizing: border-box;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            outline: none;
            border: none;
            text-decoration: none;
            transition: .2s linear;
        }

        html {
            font-size: 62.5%;
        }

        body {
            background: var(--light-bg);
            color: var(--dark);
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: #fff;
            box-shadow: 0 .3rem 1rem rgba(0, 0, 0, .08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.4rem 3rem;
            height: 6rem;
        }

        .topbar .brand {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--dark);
        }

        .topbar .brand span {
            color: var(--pink);
        }

        .topbar .right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .topbar .admin-badge {
            font-size: 1.4rem;
            color: #666;
            background: #fafafa;
            border: .1rem solid #e0e0e0;
            padding: .5rem 1.4rem;
            border-radius: 5rem;
        }

        .topbar .admin-badge i {
            color: var(--pink);
            margin-right: .4rem;
        }

        .btn-logout {
            font-size: 1.4rem;
            background: #333;
            color: #fff;
            padding: .7rem 2rem;
            border-radius: 5rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .btn-logout:hover {
            background: var(--pink);
        }

        .page-wrap {
            padding: 8rem 3rem 3rem;
            max-width: 1300px;
            margin: 0 auto;
        }

        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            flex: 1 1 20rem;
            background: #fff;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            padding: 2rem 2.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border-left: 4px solid var(--pink);
        }

        .stat-card .icon {
            width: 5rem;
            height: 5rem;
            border-radius: 50%;
            background: rgba(232, 67, 147, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: var(--pink);
            flex-shrink: 0;
        }

        .stat-card .icon.orange {
            background: rgba(243, 156, 18, .1);
            color: #f39c12;
        }

        .stat-card .icon.green {
            background: rgba(39, 174, 96, .1);
            color: #27ae60;
        }

        .stat-card .icon.red {
            background: rgba(231, 76, 60, .1);
            color: #e74c3c;
        }

        .stat-card .icon.purple {
            background: rgba(155, 89, 182, .1);
            color: #9b59b6;
        }

        .stat-card h4 {
            font-size: 1.3rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: .05rem;
        }

        .stat-card h2 {
            font-size: 2.8rem;
            color: var(--dark);
            margin-top: .3rem;
        }

        .toolbar {
            background: #fff;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            padding: 1.8rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
        }

        .toolbar form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            width: 100%;
            align-items: center;
        }

        .toolbar .search-wrap {
            display: flex;
            align-items: center;
            border: .1rem solid #e0e0e0;
            border-radius: .5rem;
            overflow: hidden;
            flex: 1 1 22rem;
        }

        .toolbar .search-wrap i {
            padding: 0 1.2rem;
            color: #bbb;
            font-size: 1.6rem;
        }

        .toolbar .search-wrap input {
            flex: 1;
            padding: 1rem;
            font-size: 1.5rem;
            color: #333;
            background: #fff;
        }

        .btn-search {
            padding: 1rem 2.5rem;
            font-size: 1.5rem;
            background: var(--dark);
            color: #fff;
            border-radius: .5rem;
            cursor: pointer;
        }

        .btn-search:hover {
            background: var(--pink);
        }

        .success-msg {
            background: rgba(39, 174, 96, .1);
            color: #27ae60;
            border: .1rem solid rgba(39, 174, 96, .3);
            border-radius: .5rem;
            padding: 1.2rem 1.8rem;
            font-size: 1.5rem;
            margin-bottom: 1.8rem;
        }

        .table-wrap {
            background: #fff;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.4rem;
            min-width: 950px;
        }

        thead tr {
            background: rgba(232, 67, 147, .06);
            border-bottom: .2rem solid rgba(232, 67, 147, .15);
        }

        thead th {
            padding: 1.5rem 1.8rem;
            text-align: left;
            color: var(--pink);
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: .05rem;
        }

        tbody tr {
            border-bottom: .1rem solid #f0f0f0;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        tbody td {
            padding: 1.4rem 1.8rem;
            color: #444;
            vertical-align: top;
        }

        tbody td small {
            display: block;
            color: #aaa;
            font-size: 1.1rem;
            margin-top: .3rem;
        }

        .no-orders {
            text-align: center;
            padding: 4rem;
            color: #bbb;
            font-size: 1.6rem;
        }

        .badge {
            display: inline-block;
            padding: .4rem 1.2rem;
            border-radius: 5rem;
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: capitalize;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(51, 51, 51, .55);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 1rem;
            padding: 3rem;
            width: 95%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, .2);
            position: relative;
        }

        .modal-box h3 {
            font-size: 2rem;
            color: var(--pink);
            margin-bottom: 2rem;
        }

        .modal-close {
            position: absolute;
            top: 1.2rem;
            right: 1.8rem;
            font-size: 2.4rem;
            color: #aaa;
            cursor: pointer;
        }

        .modal-close:hover {
            color: var(--pink);
        }

        .detail-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.2rem;
            font-size: 1.5rem;
        }

        .detail-row .lbl {
            color: #999;
            min-width: 14rem;
            flex-shrink: 0;
        }

        .detail-row .val {
            color: #333;
            font-weight: bold;
            word-break: break-word;
        }

        .detail-row .val a {
            color: var(--pink);
        }

        .detail-row .val a:hover {
            text-decoration: underline;
        }

        .status-form {
            margin-top: 2rem;
            border-top: .1rem solid #f0f0f0;
            padding-top: 2rem;
        }

        .status-form label {
            font-size: 1.4rem;
            color: #555;
            display: block;
            margin-bottom: .7rem;
        }

        .status-form select {
            width: 100%;
            padding: 1rem;
            font-size: 1.5rem;
            border: .1rem solid #e0e0e0;
            border-radius: .5rem;
            color: #333;
            background: #fff;
        }

        .btn-update {
            display: block;
            width: 100%;
            margin-top: 1.2rem;
            padding: 1.1rem;
            font-size: 1.5rem;
            background: #333;
            color: #fff;
            border-radius: 5rem;
            cursor: pointer;
            text-align: center;
        }

        .btn-update:hover {
            background: var(--pink);
        }

        .btn-cancel-order {
            display: block;
            width: 100%;
            margin-top: 1.2rem;
            padding: 1.1rem;
            font-size: 1.5rem;
            background: #e74c3c;
            color: #fff;
            border-radius: 5rem;
            cursor: pointer;
            text-align: center;
        }

        .btn-cancel-order:hover {
            background: #c0392b;
        }

        .pill-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: .8rem;
            margin-bottom: 2rem;
        }

        .pill-tab {
            padding: .7rem 1.8rem;
            font-size: 1.4rem;
            border-radius: 5rem;
            cursor: pointer;
            border: .15rem solid #e0e0e0;
            color: #666;
            background: #fff;
        }

        .pill-tab.active,
        .pill-tab:hover {
            background: var(--pink);
            color: #fff;
            border-color: var(--pink);
        }

        @media (max-width: 600px) {
            .topbar {
                padding: 1.4rem 1.5rem;
            }

            .page-wrap {
                padding: 8rem 1rem 2rem;
            }

            .topbar .brand {
                font-size: 1.7rem;
            }

            .topbar .admin-badge {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="brand">
            Yard Handicraft<span>.</span>
            <small style="font-size:1.4rem;color:#999;font-weight:normal;margin-left:.5rem;">Admin</small>
        </div>

        <div class="right">
            <span class="admin-badge">
                <i class="fas fa-user-shield"></i><?= e($adminUsername) ?>
            </span>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="page-wrap">

        <div class="stats">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                <div>
                    <h4>Total Orders</h4>
                    <h2><?= $totalOrders ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon" style="background:rgba(39,174,96,.1);color:#27ae60;"><i class="fas fa-peso-sign"></i></div>
                <div>
                    <h4>Paid Revenue</h4>
                    <h2>₱<?= number_format($totalRevenue, 2) ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon orange"><i class="fas fa-clock"></i></div>
                <div>
                    <h4>Pending</h4>
                    <h2><?= $pendingCount ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon" style="background:rgba(52,152,219,.1);color:#3498db;"><i class="fas fa-credit-card"></i></div>
                <div>
                    <h4>Paid</h4>
                    <h2><?= $paidCount ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon purple"><i class="fas fa-gear"></i></div>
                <div>
                    <h4>Processing</h4>
                    <h2><?= $processingCount ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon green"><i class="fas fa-check-circle"></i></div>
                <div>
                    <h4>Completed</h4>
                    <h2><?= $completedCount ?></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon red"><i class="fas fa-ban"></i></div>
                <div>
                    <h4>Cancelled</h4>
                    <h2><?= $cancelledCount ?></h2>
                </div>
            </div>
        </div>

        <?php if ($statusMsg): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> <?= $statusMsg ?>
            </div>
        <?php endif; ?>

        <div class="pill-tabs">
            <?php foreach ($allowedFilters as $f): ?>
                <a href="?filter=<?= e($f) ?>&search=<?= urlencode($search) ?>"
                    class="pill-tab <?= $filter === $f ? 'active' : '' ?>">
                    <?= e(ucfirst($f)) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="toolbar">
            <form method="GET" action="">
                <input type="hidden" name="filter" value="<?= e($filter) ?>">

                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="<?= e($search) ?>"
                        placeholder="Search by name, product, contact…">
                </div>

                <button type="submit" class="btn-search">
                    <i class="fas fa-filter"></i> Filter
                </button>

                <?php if ($search || $filter !== 'all'): ?>
                    <a href="admin_dashboard.php" class="btn-search" style="background:#aaa;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Payment Method</th>
                        <th>Payment</th>
                        <th>Order Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="no-orders">
                                <i class="fas fa-inbox" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>
                                No orders found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $id = (int) orderValue($order, 'id', 0);
                            $name = orderValue($order, 'name', '');
                            $contact = orderValue($order, 'contact', '');
                            $fbLink = orderValue($order, 'fb_link', '');
                            $address = orderValue($order, 'address', '');
                            $product = orderValue($order, 'product', '');
                            $price = (float) orderValue($order, 'price', 0);
                            $notes = orderValue($order, 'notes', '');
                            $paymentMethod = orderValue($order, 'payment_method', '');
                            $paymentStatus = orderValue($order, 'payment_status', 'pending');
                            $orderStatus = orderValue($order, 'order_status', 'pending');
                            $createdAt = orderValue($order, 'created_at', '');

                            $color = $statusColors[$orderStatus] ?? '#aaa';
                            ?>
                            <tr>
                                <td><strong>#<?= $id ?></strong></td>

                                <td>
                                    <?= e($name) ?>
                                    <small><?= e($contact) ?></small>
                                </td>

                                <td><?= e($product) ?></td>

                                <td style="color:var(--pink);font-weight:bold;">
                                    ₱<?= number_format($price, 2) ?>
                                </td>

                                <td><?= e($paymentMethod ?: '—') ?></td>

                                <td>
                                    <span class="badge"
                                        style="background:#3498db22;color:#3498db;border:.1rem solid #3498db55;">
                                        <?= e(ucfirst($paymentStatus)) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge"
                                        style="background:<?= e($color) ?>22;color:<?= e($color) ?>;border:.1rem solid <?= e($color) ?>55;">
                                        <?= e(ucfirst($orderStatus)) ?>
                                    </span>
                                </td>

                                <td style="font-size:1.3rem;">
                                    <?php if ($createdAt): ?>
                                        <?= e(date('M d, Y', strtotime($createdAt))) ?>
                                        <small><?= e(date('h:i A', strtotime($createdAt))) ?></small>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <button class="btn-update"
                                        style="padding:.6rem 1.5rem;font-size:1.3rem;border-radius:5rem;display:inline-block;width:auto;"
                                        onclick="openModal(
                                            <?= js($id) ?>,
                                            <?= js($name) ?>,
                                            <?= js($contact) ?>,
                                            <?= js($fbLink) ?>,
                                            <?= js($address) ?>,
                                            <?= js($product) ?>,
                                            <?= js($price) ?>,
                                            <?= js($notes) ?>,
                                            <?= js($orderStatus) ?>,
                                            <?= js($paymentMethod) ?>,
                                            <?= js($paymentStatus) ?>
                                        )">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ── Contact Messages ──────────────────────────── -->
        <div class="table-wrap" style="margin-top:3rem;">
            <h2 style="font-size:2rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.8rem;">
                <i class="fas fa-envelope" style="color:#3498db;"></i>
                Customer Messages
                <span style="background:rgba(52,152,219,.1);color:#3498db;font-size:1.2rem;padding:.3rem 1rem;border-radius:5rem;"><?= $contactCount ?></span>
            </h2>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Facebook</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contactMessages)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:3rem;color:#999;">No customer messages yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contactMessages as $msg): ?>
                            <tr>
                                <td><?= htmlspecialchars($msg['name']) ?></td>
                                <td><?= htmlspecialchars($msg['number'] ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($msg['fb_link'])): ?>
                                        <a href="<?= htmlspecialchars($msg['fb_link']) ?>" target="_blank" style="color:#3498db;">View Profile</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:30rem;white-space:normal;line-height:1.5;"><?= htmlspecialchars($msg['message'] ?? '') ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="modal-overlay" id="orderModal">
        <div class="modal-box">
            <span class="modal-close" onclick="closeModal()">×</span>

            <h3>
                <i class="fas fa-shopping-bag" style="margin-right:.6rem;"></i>
                Order Details
            </h3>

            <div class="detail-row"><span class="lbl">Order ID</span><span class="val" id="d_id"></span></div>
            <div class="detail-row"><span class="lbl">Customer</span><span class="val" id="d_name"></span></div>
            <div class="detail-row"><span class="lbl">Contact</span><span class="val" id="d_contact"></span></div>
            <div class="detail-row"><span class="lbl">Facebook</span><span class="val" id="d_fb"></span></div>
            <div class="detail-row"><span class="lbl">Address</span><span class="val" id="d_address"></span></div>
            <div class="detail-row"><span class="lbl">Product</span><span class="val" id="d_product"></span></div>
            <div class="detail-row"><span class="lbl">Price</span><span class="val" id="d_price"></span></div>
            <div class="detail-row"><span class="lbl">Payment Method</span><span class="val" id="d_payment_method"></span></div>
            <div class="detail-row"><span class="lbl">Payment Status</span><span class="val" id="d_payment_status"></span></div>
            <div class="detail-row"><span class="lbl">Notes</span><span class="val" id="d_notes"></span></div>

            <div class="status-form">
                <form method="POST" action="">
                    <input type="hidden" name="order_id" id="modal_order_id">

                    <label for="modal_status">
                        <i class="fas fa-tag"></i> Update Order Status
                    </label>

                    <select name="order_status" id="modal_status">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="processing">Processing</option>
                        <option value="ready_for_pickup">Ready for Pickup</option>
                        <option value="shipped">Shipped</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <button type="submit" name="update_status" class="btn-update">
                        <i class="fas fa-save"></i> Save Status
                    </button>
                </form>
            </div>

            <div class="status-form">
                <form method="POST" action=""
                    onsubmit="return confirm('Are you sure you want to mark this order as cancelled?');">
                    <input type="hidden" name="order_id" id="modal_cancel_order_id">

                    <button type="submit" name="cancel_order" class="btn-cancel-order">
                        <i class="fas fa-ban"></i> Mark as Cancelled
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(id, name, contact, fb, address, product, price, notes, status, paymentMethod, paymentStatus) {
            document.getElementById('d_id').textContent = '#' + id;
            document.getElementById('d_name').textContent = name || '—';
            document.getElementById('d_contact').textContent = contact || '—';

            if (fb) {
                document.getElementById('d_fb').innerHTML = '<a href="' + fb + '" target="_blank">' + fb + '</a>';
            } else {
                document.getElementById('d_fb').textContent = '—';
            }

            document.getElementById('d_address').textContent = address || '—';
            document.getElementById('d_product').textContent = product || '—';
            document.getElementById('d_price').textContent = '₱' + parseFloat(price || 0).toFixed(2);
            document.getElementById('d_payment_method').textContent = paymentMethod || '—';
            document.getElementById('d_payment_status').textContent = paymentStatus || '—';
            document.getElementById('d_notes').textContent = notes || '—';

            document.getElementById('modal_order_id').value = id;
            document.getElementById('modal_cancel_order_id').value = id;

            const sel = document.getElementById('modal_status');

            for (let i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === status) {
                    sel.selectedIndex = i;
                    break;
                }
            }

            document.getElementById('orderModal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('orderModal').classList.remove('open');
        }

        document.getElementById('orderModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

</body>

</html>
