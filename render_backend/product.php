<?php
/**
 * Product Detail Page with Smart Recommendations
 * Shows product info + Recommended, Similar, and Best Seller products.
 */

// Validate product ID
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: index.php#products');
    exit;
}

// Connect to database
$databaseUrl = getenv("DATABASE_URL");
if (!$databaseUrl) {
    header('Location: index.php#products');
    exit;
}

require_once 'db.php';

// Ensure products table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id SERIAL PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            price NUMERIC(10, 2) DEFAULT 0,
            old_price NUMERIC(10, 2) DEFAULT 0,
            category VARCHAR(100) DEFAULT 'Satin Flowers',
            image VARCHAR(255),
            stock INTEGER DEFAULT 10,
            discount_label VARCHAR(20),
            created_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");
} catch (PDOException $e) {
    error_log('Products table setup error: ' . $e->getMessage());
}

// Fetch current product
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Product fetch error: ' . $e->getMessage());
    $product = null;
}

if (!$product) {
    header('Location: index.php#products');
    exit;
}

$pName = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
$pDesc = htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8');
$pPrice = (float) $product['price'];
$pOldPrice = (float) $product['old_price'];
$pCategory = htmlspecialchars($product['category'] ?? 'Handmade', ENT_QUOTES, 'UTF-8');
$pImage = htmlspecialchars($product['image'] ?? 'images/img-1.jpg', ENT_QUOTES, 'UTF-8');
$pStock = (int) ($product['stock'] ?? 0);
$pDiscount = htmlspecialchars($product['discount_label'] ?? '', ENT_QUOTES, 'UTF-8');

// Format price for display
function formatPrice($amount) {
    if ($amount >= 1000) {
        return '₱' . number_format($amount, 0);
    }
    return '₱' . number_format($amount, 0);
}

// Order counts subquery
$orderCountsSql = "(SELECT product, SUM(COALESCE(quantity, 1)) as total_ordered FROM preorders WHERE order_status NOT IN ('cancelled') GROUP BY product)";

// Fetch Recommended Products (same category, by popularity)
$recommended = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.price, p.old_price, p.category, p.image, p.stock, p.discount_label,
               COALESCE(oc.total_ordered, 0) as total_ordered
        FROM products p
        LEFT JOIN $orderCountsSql oc ON p.name = oc.product
        WHERE p.category = :category AND p.id != :pid AND p.stock > 0
        ORDER BY total_ordered DESC, p.created_at DESC
        LIMIT 4
    ");
    $stmt->execute([':category' => $product['category'], ':pid' => $productId]);
    $recommended = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Recommended products error: ' . $e->getMessage());
}

// Fetch Similar Products (same category + close price range ±40%)
$similar = [];
try {
    $minPrice = $pPrice * 0.6;
    $maxPrice = $pPrice * 1.4;
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
        ':category' => $product['category'],
        ':pid' => $productId,
        ':min_price' => $minPrice,
        ':max_price' => $maxPrice,
        ':sort_price' => $pPrice
    ]);
    $similar = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Similar products error: ' . $e->getMessage());
}

// Helper to render a recommendation card
function renderRecCard($item) {
    $id = (int) $item['id'];
    $name = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $price = (float) $item['price'];
    $oldPrice = (float) $item['old_price'];
    $category = htmlspecialchars($item['category'] ?? 'Handmade', ENT_QUOTES, 'UTF-8');
    $image = htmlspecialchars($item['image'] ?? 'images/img-1.jpg', ENT_QUOTES, 'UTF-8');
    $discount = htmlspecialchars($item['discount_label'] ?? '', ENT_QUOTES, 'UTF-8');
    $priceFormatted = '₱' . number_format($price, 0);
    $oldPriceFormatted = $oldPrice > 0 ? '₱' . number_format($oldPrice, 0) : '';

    $html = '<div class="product-card rec-card">';
    if ($discount) {
        $html .= '<span class="badge-discount">' . $discount . '</span>';
    }
    $html .= '<div class="img-wrap"><img src="' . $image . '" alt="' . $name . '"></div>';
    $html .= '<div class="card-body">';
    $html .= '<span class="rec-category-badge"><i class="fas fa-tag"></i> ' . $category . '</span>';
    $html .= '<h3>' . $name . '</h3>';
    $html .= '<p class="card-desc">' . $desc . '</p>';
    $html .= '<div class="card-footer">';
    $html .= '<span class="price">' . $priceFormatted;
    if ($oldPriceFormatted) {
        $html .= ' <span class="old">' . $oldPriceFormatted . '</span>';
    }
    $html .= '</span>';
    $html .= '<a href="product.php?id=' . $id . '" class="btn-order">View Product</a>';
    $html .= '</div></div></div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pName ?> - YardHandicraft</title>
<meta name="description" content="<?= $pDesc ?> - Handcrafted by YardHandicraft from Davao Region.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header id="mainHeader">
<input type="checkbox" id="toggler">
<a href="index.php#home" class="logo"><span class="logo-icon"><i class="fas fa-leaf"></i></span>Yard<span>Handicraft</span></a>
<label for="toggler" class="menu-btn"><i class="fas fa-bars"></i></label>
<nav class="navbar">
<a href="index.php#home">Home</a>
<a href="index.php#products">Products</a>
<a href="index.php#about">About</a>
<a href="index.php#how-to-order">How to Order</a>
<a href="index.php#faq">FAQ</a>
<a href="index.php#contact">Contact</a>
</nav>
</header>

<!-- Breadcrumb -->
<section class="breadcrumb-section" style="padding-top:9rem;padding-bottom:1rem;">
<div class="breadcrumb-nav">
<a href="index.php"><i class="fas fa-home"></i> Home</a>
<span><i class="fas fa-chevron-right"></i></span>
<a href="index.php#products">Products</a>
<span><i class="fas fa-chevron-right"></i></span>
<span class="current"><?= $pName ?></span>
</div>
</section>

<!-- Product Detail -->
<section class="product-detail-section">
<div class="product-detail-grid fade-up">
<div class="product-detail-image">
<?php if ($pDiscount): ?>
<span class="badge-discount"><?= $pDiscount ?></span>
<?php endif; ?>
<div class="detail-img-wrap">
<img src="<?= $pImage ?>" alt="<?= $pName ?>" id="productMainImage">
</div>
</div>
<div class="product-detail-info">
<span class="rec-category-badge"><i class="fas fa-tag"></i> <?= $pCategory ?></span>
<h1><?= $pName ?></h1>
<div class="product-detail-price">
<span class="detail-price"><?= formatPrice($pPrice) ?></span>
<?php if ($pOldPrice > 0 && $pOldPrice > $pPrice): ?>
<span class="detail-old-price"><?= formatPrice($pOldPrice) ?></span>
<?php endif; ?>
</div>
<p class="product-detail-desc"><?= $pDesc ?></p>

<div class="product-detail-meta">
<div class="meta-item">
<i class="fas fa-layer-group"></i>
<span>Category: <strong><?= $pCategory ?></strong></span>
</div>
<div class="meta-item">
<i class="fas fa-box"></i>
<span>Stock: <strong class="<?= $pStock > 0 ? 'in-stock' : 'out-of-stock' ?>"><?= $pStock > 0 ? $pStock . ' Available' : 'Out of Stock' ?></strong></span>
</div>
<div class="meta-item">
<i class="fas fa-hand-holding-heart"></i>
<span>Handmade in <strong>Davao Region</strong></span>
</div>
</div>

<?php if ($pStock > 0): ?>
<button class="btn btn-primary btn-order-detail" onclick="openPreOrder('<?= addslashes($pName) ?>', <?= $pPrice ?>)">
<i class="fas fa-shopping-bag"></i> Order Now
</button>
<?php else: ?>
<button class="btn btn-primary btn-order-detail" disabled style="opacity:0.5;cursor:not-allowed;">
<i class="fas fa-ban"></i> Out of Stock
</button>
<?php endif; ?>
</div>
</div>
</section>

<?php if (!empty($recommended)): ?>
<!-- Recommended for You -->
<section class="rec-section" id="recommendedSection">
<div class="text-center reveal">
<div class="rec-ai-badge"><i class="fas fa-brain"></i> Smart Recommendation</div>
<span class="section-badge">Curated for You</span>
<h2 class="section-title">Recommended for You</h2>
<p class="section-subtitle">Products you might love based on category and popularity.</p>
</div>
<div class="product-grid rec-grid reveal">
<?php foreach ($recommended as $item): ?>
<?= renderRecCard($item) ?>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<?php if (!empty($similar)): ?>
<!-- Similar Handmade Products -->
<section class="rec-section rec-section-alt" id="similarSection">
<div class="text-center reveal">
<div class="rec-ai-badge"><i class="fas fa-wand-magic-sparkles"></i> Smart Match</div>
<span class="section-badge">Price Match</span>
<h2 class="section-title">Similar Handmade Products</h2>
<p class="section-subtitle">Products in a similar price range within the same category.</p>
</div>
<div class="product-grid rec-grid reveal">
<?php foreach ($similar as $item): ?>
<?= renderRecCard($item) ?>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<!-- Order Modal (same as index.php) -->
<div id="preorderModal" class="modal">
<div class="modal-content">
<span class="close" onclick="closePreOrder()">&times;</span>
<h2>Place Your Order</h2>
<p class="modal-sub" id="productTitle">Product Name</p>
<form action="preorder.php" method="POST" id="preOrderForm" novalidate>
<input type="hidden" name="product" id="modalProduct">
<input type="hidden" name="price" id="modalPrice">
<div class="form-group"><label>Full Name <span style="color:var(--rose);">*</span></label><input type="text" class="form-input" id="orderName" name="name" required placeholder="e.g. Juan Dela Cruz"><span class="field-error" id="errorName"></span></div>
<div class="form-group"><label>Email Address <span style="color:var(--rose);">*</span></label><input type="email" class="form-input" id="orderEmail" name="email" required placeholder="e.g. customer@gmail.com"><span class="field-error" id="errorEmail"></span></div>
<div class="form-group"><label>Delivery Address <span style="color:var(--rose);">*</span></label><input type="text" class="form-input" id="orderAddress" name="address" required placeholder="e.g. Brgy. Bucana, Davao City"><span class="field-error" id="errorAddress"></span></div>
<div class="form-group"><label>Contact Number <span style="color:var(--rose);">*</span></label><input type="tel" class="form-input" id="orderContact" name="contact" required maxlength="11" placeholder="e.g. 09266092122"><span class="field-error" id="errorContact"></span></div>
<div class="form-group"><label>Quantity <span style="color:var(--rose);">*</span></label><input type="number" class="form-input" id="orderQuantity" name="quantity" required min="1" value="1"><span class="field-error" id="errorQuantity"></span></div>
<div class="form-group"><label>Facebook Link <span class="optional">(optional)</span></label><input type="url" class="form-input" id="orderFb" name="fb_link" placeholder="e.g. https://facebook.com/yourprofile"><span class="field-error" id="errorFb"></span></div>
<div class="form-group"><label>Notes / Requests <span class="optional">(optional)</span></label><textarea class="form-input" id="orderNotes" name="notes" placeholder="Any special instructions..."></textarea></div>
<button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-lock"></i> Proceed to Payment</button>
</form>
</div>
</div>

<footer class="footer">
<div class="footer-grid">
<div class="footer-about"><h4>YardHandicraft</h4><p>Unique handmade products crafted with creativity, patience, and care from Davao Region. Perfect for gifts, decoration, and special occasions.</p><div class="footer-socials"><a href="https://www.facebook.com/profile.php?id=61571860299181" target="_blank"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-tiktok"></i></a></div></div>
<div class="footer-links"><h4>Quick Links</h4><a href="index.php#home">Home</a><a href="index.php#products">Products</a><a href="index.php#about">About Us</a><a href="index.php#how-to-order">How to Order</a><a href="index.php#faq">FAQ</a><a href="index.php#contact">Contact</a></div>
<div class="footer-links"><h4>Service Areas</h4><a href="#">Davao City</a><a href="#">Tagum City</a><a href="#">Digos City</a><a href="#">Panabo City</a><a href="#">Mati City</a></div>
<div class="footer-links"><h4>Policies</h4><a href="#">Privacy Policy</a><a href="#">Terms & Conditions</a><a href="#">Refund Policy</a><a href="#">Cancellation Policy</a></div>
</div>
<div class="footer-bottom">
<p>&copy; 2025 YardHandicraft. All Rights Reserved.</p>
<p>Handmade with <i class="fas fa-heart" style="color:var(--rose);"></i> in Davao</p>
</div>
</footer>

<script>
// Header scroll effect
window.addEventListener('scroll',function(){document.getElementById('mainHeader').classList.toggle('scrolled',window.scrollY>50);});
// Title case
function toTitleCase(s){return s.toLowerCase().replace(/\b\w/g,function(c){return c.toUpperCase();});}
// Validation helpers
function setErr(id,msg){var e=document.getElementById(id),inp=e.previousElementSibling;e.textContent=msg;if(inp){inp.classList.add('input-error');inp.classList.remove('input-valid');}}
function setOk(id){var e=document.getElementById(id),inp=e.previousElementSibling;e.textContent='';if(inp){inp.classList.remove('input-error');inp.classList.add('input-valid');}}
function clr(id){var e=document.getElementById(id),inp=e.previousElementSibling;e.textContent='';if(inp){inp.classList.remove('input-error','input-valid');}}
// Modal
function openPreOrder(p,pr){document.getElementById('preorderModal').style.display='block';document.getElementById('productTitle').textContent=p;document.getElementById('modalProduct').value=p;document.getElementById('modalPrice').value=pr;document.getElementById('preOrderForm').reset();document.getElementById('orderQuantity').value=1;['errorName','errorEmail','errorAddress','errorContact','errorQuantity','errorFb'].forEach(clr);}
function closePreOrder(){document.getElementById('preorderModal').style.display='none';}
window.addEventListener('click',function(e){if(e.target===document.getElementById('preorderModal'))closePreOrder();});
// Order form validation
document.getElementById('orderName').addEventListener('blur',function(){var v=this.value.trim();if(!v){setErr('errorName','Please enter your full name.');return;}if(/[^a-zA-Z\s\u00C0-\u017F]/.test(v)){setErr('errorName','Name should only contain letters and spaces.');return;}this.value=toTitleCase(v);setOk('errorName');});
document.getElementById('orderEmail').addEventListener('blur',function(){var v=this.value.trim();if(!v){setErr('errorEmail','Please enter your email address.');return;}if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)){setErr('errorEmail','Please enter a valid email address.');return;}setOk('errorEmail');});
document.getElementById('orderAddress').addEventListener('blur',function(){var v=this.value.trim().replace(/\s{2,}/g,' ');if(!v){setErr('errorAddress','Please enter your delivery address.');return;}this.value=v;setOk('errorAddress');});
document.getElementById('orderContact').addEventListener('input',function(){this.value=this.value.replace(/[^0-9]/g,'');});
document.getElementById('orderContact').addEventListener('blur',function(){var v=this.value.trim();if(!v){setErr('errorContact','Please enter your contact number.');return;}if(!/^09\d{9}$/.test(v)){setErr('errorContact','Enter a valid 11-digit number (e.g. 09XXXXXXXXX).');return;}setOk('errorContact');});
document.getElementById('orderQuantity').addEventListener('blur',function(){if(isNaN(parseInt(this.value))||parseInt(this.value)<1){setErr('errorQuantity','Quantity must be at least 1.');return;}setOk('errorQuantity');});
document.getElementById('orderFb').addEventListener('blur',function(){var v=this.value.trim();if(!v){clr('errorFb');return;}if(!/facebook\.com/i.test(v)){setErr('errorFb','Please enter a valid Facebook link.');return;}setOk('errorFb');});
document.getElementById('preOrderForm').addEventListener('submit',function(e){var ok=true;['orderName','orderEmail','orderAddress','orderContact','orderQuantity','orderFb'].forEach(function(id){document.getElementById(id).dispatchEvent(new Event('blur'));});['errorName','errorEmail','errorAddress','errorContact','errorQuantity','errorFb'].forEach(function(id){if(document.getElementById(id).textContent)ok=false;});if(!ok)e.preventDefault();});
// Scroll Reveal
var revealEls=document.querySelectorAll('.reveal,.reveal-left,.reveal-right');
var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('visible');observer.unobserve(entry.target);}});},{threshold:0.15});
revealEls.forEach(function(el){observer.observe(el);});
</script>
</body>
</html>
