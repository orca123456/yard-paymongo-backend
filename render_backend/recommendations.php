<?php
/**
 * Smart Product Recommendation API
 * Returns JSON with recommended, similar, and best-selling products.
 * Uses content-based filtering with popularity ranking.
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Validate product_id
$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

// Connect to database
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    http_response_code(503);
    echo json_encode(['error' => 'Service unavailable']);
    exit;
}

try {
    require_once 'db.php';
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['error' => 'Service unavailable']);
    exit;
}

try {
    // Fetch current product
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    $category = $product['category'];
    $price = (float) $product['price'];
    $minPrice = $price * 0.6;
    $maxPrice = $price * 1.4;

    // Subquery for order counts (non-cancelled orders)
    $orderCountsSql = "(SELECT product, SUM(COALESCE(quantity, 1)) as total_ordered FROM preorders WHERE order_status NOT IN ('cancelled') GROUP BY product)";

    // 1. Recommended: Same category, sorted by popularity then newest
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.price, p.old_price, p.category, p.image, p.stock, p.discount_label,
               COALESCE(oc.total_ordered, 0) as total_ordered
        FROM products p
        LEFT JOIN $orderCountsSql oc ON p.name = oc.product
        WHERE p.category = :category AND p.id != :pid AND p.stock > 0
        ORDER BY total_ordered DESC, p.created_at DESC
        LIMIT 4
    ");
    $stmt->execute([':category' => $category, ':pid' => $productId]);
    $recommended = $stmt->fetchAll();

    // 2. Similar: Same category + close price range (±40%)
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.price, p.old_price, p.category, p.image, p.stock, p.discount_label,
               COALESCE(oc.total_ordered, 0) as total_ordered
        FROM products p
        LEFT JOIN $orderCountsSql oc ON p.name = oc.product
        WHERE p.category = :category AND p.id != :pid AND p.stock > 0
          AND p.price BETWEEN :min_price AND :max_price
        ORDER BY ABS(p.price - :sort_price) ASC, total_ordered DESC
        LIMIT 4
    ");
    $stmt->execute([
        ':category' => $category,
        ':pid' => $productId,
        ':min_price' => $minPrice,
        ':max_price' => $maxPrice,
        ':sort_price' => $price
    ]);
    $similar = $stmt->fetchAll();

    // 3. Best Sellers (global, exclude current product)
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.price, p.old_price, p.category, p.image, p.stock, p.discount_label,
               COALESCE(oc.total_ordered, 0) as total_ordered
        FROM products p
        LEFT JOIN $orderCountsSql oc ON p.name = oc.product
        WHERE p.stock > 0 AND p.id != :pid
        ORDER BY total_ordered DESC, p.created_at DESC
        LIMIT 8
    ");
    $stmt->execute([':pid' => $productId]);
    $bestSellers = $stmt->fetchAll();

    echo json_encode([
        'recommended' => $recommended,
        'similar' => $similar,
        'best_sellers' => $bestSellers
    ]);

} catch (PDOException $e) {
    error_log('Recommendations API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load recommendations']);
}
?>
