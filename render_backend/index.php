<?php
/**
 * YardHandicraft Homepage
 * Best Sellers section powered by Smart Recommendation Engine
 */
$bestSellers = [];
$allProducts = [];
$dbConnected = false;

if (getenv("DATABASE_URL")) {
    try {
        require_once 'db.php';
        $dbConnected = true;
    } catch (Exception $e) {
        // DB unavailable, continue without recommendations
    }
}

if ($dbConnected && isset($pdo)) {
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
        // Table creation failed, continue without recommendations
    }

    // Fetch best sellers (by order count from preorders table)
    try {
        $orderCountsSql = "(SELECT product, SUM(COALESCE(quantity, 1)) as total_ordered FROM preorders WHERE order_status NOT IN ('cancelled') GROUP BY product)";
        $bsStmt = $pdo->query("
            SELECT p.id, p.name, p.description, p.price, p.old_price, p.category, p.image, p.stock, p.discount_label,
                   COALESCE(oc.total_ordered, 0) as total_ordered
            FROM products p
            LEFT JOIN $orderCountsSql oc ON p.name = oc.product
            WHERE p.stock > 0
            ORDER BY total_ordered DESC, p.created_at DESC
            LIMIT 8
        ");
        $bestSellers = $bsStmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Best sellers query error: ' . $e->getMessage());
        $bestSellers = [];
    }

    // Fetch all products for product grid
    try {
        $allStmt = $pdo->query("SELECT id, name FROM products WHERE stock > 0 ORDER BY id ASC");
        $allProducts = $allStmt->fetchAll();
    } catch (PDOException $e) {
        $allProducts = [];
    }
}

// Helper to find product ID by name
function findProductId($allProducts, $name) {
    foreach ($allProducts as $p) {
        if ($p['name'] === $name) {
            return (int) $p['id'];
        }
    }
    return 0;
}

function escHtml($val) {
    return htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>YardHandicraft - Handcrafted Products from Davao</title>
<meta name="description" content="Discover unique handcrafted satin flowers and artisan products from Davao Region. Order now!">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header id="mainHeader">
<input type="checkbox" id="toggler">
<a href="#home" class="logo"><span class="logo-icon"><i class="fas fa-leaf"></i></span>Yard<span>Handicraft</span></a>
<label for="toggler" class="menu-btn"><i class="fas fa-bars"></i></label>
<nav class="navbar">
<a href="#home">Home</a>
<a href="#products">Products</a>
<a href="#about">About</a>
<a href="#how-to-order">How to Order</a>
<a href="#faq">FAQ</a>
<a href="#contact">Contact</a>
</nav>
</header>

<section class="hero" id="home">
<div class="hero-content fade-up">
<span class="hero-badge"><i class="fas fa-heart"></i> Handmade with Love</span>
<h1>Handmade Crafts<br>Made with <em>Care</em></h1>
<p>Discover unique handcrafted items made with creativity, passion, and attention to detail. Perfect for gifts, home decoration, souvenirs, and personalized orders.</p>
<div class="hero-buttons">
<a href="#products" class="btn btn-primary"><i class="fas fa-shopping-bag"></i> Shop Now</a>
<a href="#about" class="btn btn-outline"><i class="fas fa-info-circle"></i> Learn More</a>
</div>
</div>
</section>

<?php if (!empty($bestSellers)): ?>
<!-- Best Sellers - Smart Recommendation Engine -->
<section class="best-sellers-section" id="best-sellers">
<div class="text-center reveal">
<div class="rec-ai-badge"><i class="fas fa-fire"></i> Trending Now</div>
<span class="section-badge">Most Popular</span>
<h2 class="section-title">Best Sellers</h2>
<p class="section-subtitle">Our most loved handcrafted products based on real customer orders.</p>
</div>
<div class="product-grid rec-grid reveal">
<?php foreach ($bestSellers as $bs):
    $bsId = (int) $bs['id'];
    $bsName = escHtml($bs['name']);
    $bsDesc = escHtml($bs['description']);
    $bsPrice = (float) $bs['price'];
    $bsOldPrice = (float) $bs['old_price'];
    $bsCategory = escHtml($bs['category'] ?? 'Handmade');
    $bsImage = escHtml($bs['image'] ?? 'images/img-1.jpg');
    $bsDiscount = escHtml($bs['discount_label'] ?? '');
    $bsTotalOrdered = (int) ($bs['total_ordered'] ?? 0);
    $bsPriceFormatted = '₱' . number_format($bsPrice, 0);
    $bsOldPriceFormatted = $bsOldPrice > 0 ? '₱' . number_format($bsOldPrice, 0) : '';
?>
<div class="product-card rec-card">
<?php if ($bsDiscount): ?><span class="badge-discount"><?= $bsDiscount ?></span><?php endif; ?>
<?php if ($bsTotalOrdered > 0): ?><span class="badge-sold"><i class="fas fa-fire"></i> <?= $bsTotalOrdered ?> Sold</span><?php endif; ?>
<div class="img-wrap"><img src="<?= $bsImage ?>" alt="<?= $bsName ?>"></div>
<div class="card-body">
<span class="rec-category-badge"><i class="fas fa-tag"></i> <?= $bsCategory ?></span>
<h3><?= $bsName ?></h3>
<p class="card-desc"><?= $bsDesc ?></p>
<div class="card-footer">
<span class="price"><?= $bsPriceFormatted ?><?php if ($bsOldPriceFormatted): ?> <span class="old"><?= $bsOldPriceFormatted ?></span><?php endif; ?></span>
<a href="product.php?id=<?= $bsId ?>" class="btn-order">View Product</a>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<section class="products-section" id="products">
<div class="text-center reveal">
<span class="section-badge">Our Collection</span>
<h2 class="section-title">Handcrafted Products</h2>
<p class="section-subtitle">Each piece is carefully handmade by skilled artisans from Davao Region.</p>
</div>
<div class="product-grid reveal">
<div class="product-card"><span class="badge-discount">-10%</span><div class="img-wrap"><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Rose Elegance') ?>"><img src="images/img-1.jpg" alt="Rose Elegance"></a></div><div class="card-body"><h3><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Rose Elegance') ?>" style="color:inherit;text-decoration:none;">Satin Flower Pot - Rose Elegance</a></h3><p class="card-desc">Elegant rose arrangement perfect for home decor</p><div class="card-footer"><span class="price">₱1 <span class="old">₱50</span></span><button class="btn-order" onclick="openPreOrder('Satin Flower Pot - Rose Elegance',1)">Order Now</button></div></div></div>
<div class="product-card"><span class="badge-discount">-15%</span><div class="img-wrap"><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Davao Delight') ?>"><img src="images/img-2.jpg" alt="Davao Delight"></a></div><div class="card-body"><h3><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Davao Delight') ?>" style="color:inherit;text-decoration:none;">Satin Flower Pot - Davao Delight</a></h3><p class="card-desc">Vibrant Davao-inspired floral centerpiece</p><div class="card-footer"><span class="price">₱1 <span class="old">₱10</span></span><button class="btn-order" onclick="openPreOrder('Satin Flower Pot - Davao Delight',1)">Order Now</button></div></div></div>
<div class="product-card"><span class="badge-discount">-8%</span><div class="img-wrap"><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Blush Bouquet') ?>"><img src="images/img-3.jpg" alt="Blush Bouquet"></a></div><div class="card-body"><h3><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Blush Bouquet') ?>" style="color:inherit;text-decoration:none;">Satin Flower Pot - Blush Bouquet</a></h3><p class="card-desc">Soft blush arrangement for special occasions</p><div class="card-footer"><span class="price">₱1 <span class="old">₱100</span></span><button class="btn-order" onclick="openPreOrder('Satin Flower Pot - Blush Bouquet',1)">Order Now</button></div></div></div>
<div class="product-card"><span class="badge-discount">-12%</span><div class="img-wrap"><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Sunburst') ?>"><img src="images/img-4.jpg" alt="Sunburst"></a></div><div class="card-body"><h3><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Sunburst') ?>" style="color:inherit;text-decoration:none;">Satin Flower Pot - Sunburst</a></h3><p class="card-desc">Bright and cheerful sunburst design</p><div class="card-footer"><span class="price">₱1 <span class="old">₱10</span></span><button class="btn-order" onclick="openPreOrder('Satin Flower Pot - Sunburst',1)">Order Now</button></div></div></div>
<div class="product-card"><span class="badge-discount">-10%</span><div class="img-wrap"><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Lavender Dream') ?>"><img src="images/image-5.png" alt="Lavender Dream"></a></div><div class="card-body"><h3><a href="product.php?id=<?= findProductId($allProducts, 'Satin Flower Pot - Lavender Dream') ?>" style="color:inherit;text-decoration:none;">Satin Flower Pot - Lavender Dream</a></h3><p class="card-desc">Premium lavender-themed floral arrangement</p><div class="card-footer"><span class="price">₱1,200 <span class="old">₱1,340</span></span><button class="btn-order" onclick="openPreOrder('Satin Flower Pot - Lavender Dream',1200)">Order Now</button></div></div></div>
</div>
</section>

<section id="about">
<div class="about-grid">
<div class="about-media reveal-left"><video src="images/about-vid.mp4" loop autoplay muted></video></div>
<div class="about-text reveal-right">
<span class="section-badge">Our Story</span>
<h2>About YardHandicraft</h2>
<p>YardHandicraft creates unique handmade products designed for gifts, decorations, souvenirs, and personal use. Each item is crafted with creativity, patience, and care to bring customers meaningful and beautifully made pieces.</p>
<p>We use only premium materials and creative designs from skilled artisans in Davao Region, ensuring uniqueness and lasting beauty for your space or celebration.</p>
<div class="about-features">
<div class="about-feature"><i class="fas fa-hand-holding-heart"></i> Handcrafted with Love</div>
<div class="about-feature"><i class="fas fa-gem"></i> Premium Materials</div>
<div class="about-feature"><i class="fas fa-truck"></i> Safe Delivery</div>
<div class="about-feature"><i class="fas fa-star"></i> 5-Star Reviews</div>
</div>
</div>
</div>
</section>

<section id="how-to-order" style="background:var(--cream);">
<div class="text-center reveal">
<span class="section-badge">Simple Process</span>
<h2 class="section-title">How to Order</h2>
<p class="section-subtitle">Ordering your favorite handmade product is quick and easy.</p>
</div>
<div class="steps-grid reveal">
<div class="step-card"><div class="step-num">1</div><h4>Browse Products</h4><p>Explore our collection of handcrafted items</p></div>
<div class="step-card"><div class="step-num">2</div><h4>Choose Your Item</h4><p>Select the product that catches your eye</p></div>
<div class="step-card"><div class="step-num">3</div><h4>Fill Out the Form</h4><p>Enter your details in the order form</p></div>
<div class="step-card"><div class="step-num">4</div><h4>Secure Payment</h4><p>Pay safely through our payment gateway</p></div>
<div class="step-card"><div class="step-num">5</div><h4>Order Confirmed</h4><p>Receive confirmation via email or SMS</p></div>
<div class="step-card"><div class="step-num">6</div><h4>Receive Your Order</h4><p>Get your item via pickup or delivery</p></div>
</div>
</section>

<section class="trust-section">
<div class="text-center reveal">
<span class="section-badge">Your Trust Matters</span>
<h2 class="section-title">Shop with Confidence</h2>
</div>
<div class="trust-grid reveal">
<div class="trust-card">
<div class="trust-icon"><i class="fas fa-shield-halved"></i></div>
<h3>Secure Payment</h3>
<p>Your payment is processed through a secure payment gateway. YardHandicraft does not store your card, e-wallet, or banking information. After successful payment, your order details will be safely recorded and verified by our admin team.</p>
</div>
<div class="trust-card">
<div class="trust-icon"><i class="fas fa-clipboard-check"></i></div>
<h3>After Payment</h3>
<ul class="trust-steps">
<li><i class="fas fa-check"></i> Your order is automatically recorded in our system</li>
<li><i class="fas fa-check"></i> Our admin verifies your payment and order details</li>
<li><i class="fas fa-check"></i> You receive confirmation via email, SMS, or Messenger</li>
<li><i class="fas fa-check"></i> Your item is prepared for pickup or delivery</li>
<li><i class="fas fa-check"></i> Keep your receipt or reference number for verification</li>
</ul>
</div>
<div class="trust-card">
<div class="trust-icon"><i class="fas fa-heart"></i></div>
<h3>Order Assurance</h3>
<p>At YardHandicraft, every order is carefully reviewed before processing. Customers receive confirmation after payment verification. We make sure that your selected handmade product, order details, and payment status are properly recorded before preparation, pickup, or delivery.</p>
</div>
</div>
</section>

<section id="testimonials">
<div class="text-center reveal">
<span class="section-badge">Customer Love</span>
<h2 class="section-title">What Our Customers Say</h2>
</div>
<div class="testimonial-grid reveal">
<div class="testimonial-card"><span class="quote-icon"><i class="fas fa-quote-right"></i></span><div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><p class="quote">"I ordered a satin flower centerpiece for my daughter's graduation and it was absolutely perfect. The arrangement looked so real and added a special touch to our table."</p><div class="reviewer"><img src="images/picwww.jpg" alt="Mike"><div><h4>Mike Balaga</h4><span>Tagum City, Davao del Norte</span></div></div></div>
<div class="testimonial-card"><span class="quote-icon"><i class="fas fa-quote-right"></i></span><div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><p class="quote">"I ordered a bouquet for my mom in Davao City and she loved the vibrant satin flowers. Thank you for the wonderful service and fast delivery!"</p><div class="reviewer"><img src="images/jhzz.jpg" alt="Jhazmin"><div><h4>Jhazmin Nepomuceno</h4><span>Davao City, Davao del Sur</span></div></div></div>
<div class="testimonial-card"><span class="quote-icon"><i class="fas fa-quote-right"></i></span><div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div><p class="quote">"I'm impressed by the quality and detail of the satin flowers. Customer service was very helpful and the flowers arrived safely to Panabo. Highly recommended!"</p><div class="reviewer"><img src="images/pic3.jpg" alt="Rolly"><div><h4>Rolly Abregoso</h4><span>Panabo City, Davao del Norte</span></div></div></div>
</div>
</section>

<section class="faq-section" id="faq">
<div class="text-center reveal">
<span class="section-badge">Questions?</span>
<h2 class="section-title">Frequently Asked Questions</h2>
</div>
<div class="faq-list reveal">
<div class="faq-item"><button class="faq-question">How do I place an order? <i class="fas fa-chevron-down"></i></button><div class="faq-answer"><p>Select a product, fill out the order form with your details, and proceed to secure payment. Your order will be automatically recorded after payment.</p></div></div>
<div class="faq-item"><button class="faq-question">What happens after I pay? <i class="fas fa-chevron-down"></i></button><div class="faq-answer"><p>Your order will be recorded, verified by the admin team, and confirmed through email, SMS, or Messenger. Your item will then be prepared for pickup or delivery.</p></div></div>
<div class="faq-item"><button class="faq-question">Can I cancel my order? <i class="fas fa-chevron-down"></i></button><div class="faq-answer"><p>Orders may be cancelled before processing, depending on the current order status. Please contact us as soon as possible if you need to cancel.</p></div></div>
<div class="faq-item"><button class="faq-question">Do you accept custom orders? <i class="fas fa-chevron-down"></i></button><div class="faq-answer"><p>Yes! Custom orders may be accepted depending on design, materials, and availability. Contact us through the form or our Facebook page to discuss your request.</p></div></div>
<div class="faq-item"><button class="faq-question">How will I receive my order? <i class="fas fa-chevron-down"></i></button><div class="faq-answer"><p>Orders may be delivered or picked up depending on your location and the available option. We will coordinate with you after order confirmation.</p></div></div>
<div class="faq-item"><button class="faq-question">Is my payment secure? <i class="fas fa-chevron-down"></i></button><div class="faq-answer"><p>Yes. Payments are processed through a secure payment gateway. YardHandicraft does not store sensitive payment information such as card numbers or e-wallet details.</p></div></div>
</div>
</section>

<section id="contact">
<div class="text-center reveal">
<span class="section-badge">Get in Touch</span>
<h2 class="section-title">Contact Us</h2>
<p class="section-subtitle">Have questions or need a custom order? We'd love to hear from you.</p>
</div>
<div class="contact-grid">
<form class="contact-form reveal-left" action="contact.php" method="POST" id="contactForm" novalidate>
<h3>Send Us a Message</h3>
<p class="form-subtitle">We'll get back to you as soon as possible.</p>
<div class="form-group"><label>Full Name <span style="color:var(--rose);">*</span></label><input type="text" class="form-input" name="name" id="contactName" required placeholder="e.g. Juan Dela Cruz"><span class="field-error" id="cErrorName"></span></div>
<div class="form-group"><label>Contact Number <span style="color:var(--rose);">*</span></label><input type="tel" class="form-input" name="number" id="contactNumber" maxlength="11" required placeholder="e.g. 09266092122"><span class="field-error" id="cErrorNumber"></span></div>
<div class="form-group"><label>Facebook Profile Link <span class="optional">(optional)</span></label><input type="text" class="form-input" name="fb_link" placeholder="e.g. https://facebook.com/yourprofile"></div>
<div class="form-group"><label>Message <span style="color:var(--rose);">*</span></label><textarea class="form-input" name="message" required placeholder="Tell us about your inquiry..."></textarea></div>
<button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-paper-plane"></i> Send Message</button>
</form>
<div class="contact-info reveal-right">
<h3>Contact Information</h3>
<p>Reach out to us anytime. We're here to help with your orders, custom requests, and inquiries.</p>
<div class="contact-detail"><i class="fas fa-phone"></i><div><h4>Phone</h4><a href="tel:+639266092122">+63 926 609 2122</a></div></div>
<div class="contact-detail"><i class="fab fa-facebook"></i><div><h4>Facebook</h4><a href="https://www.facebook.com/profile.php?id=61571860299181" target="_blank">YardHandicraft</a></div></div>
<div class="contact-detail"><i class="fas fa-map-marker-alt"></i><div><h4>Location</h4><span>Bunawan, Davao City</span></div></div>
</div>
</div>
</section>

<!-- Order Modal -->
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
<div class="footer-links"><h4>Quick Links</h4><a href="#home">Home</a><a href="#products">Products</a><a href="#about">About Us</a><a href="#how-to-order">How to Order</a><a href="#faq">FAQ</a><a href="#contact">Contact</a></div>
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
// Contact form validation
var cf=document.getElementById('contactForm');
document.getElementById('contactName').addEventListener('blur',function(){var v=this.value.trim();if(!v){document.getElementById('cErrorName').textContent='Please enter your name.';return;}if(/[^a-zA-Z\s\u00C0-\u017F]/.test(v)){document.getElementById('cErrorName').textContent='Name should only contain letters.';return;}this.value=toTitleCase(v);document.getElementById('cErrorName').textContent='';});
document.getElementById('contactNumber').addEventListener('input',function(){this.value=this.value.replace(/[^0-9]/g,'');});
document.getElementById('contactNumber').addEventListener('blur',function(){var v=this.value.trim();if(!v){document.getElementById('cErrorNumber').textContent='Please enter your contact number.';return;}if(!/^09\d{9}$/.test(v)){document.getElementById('cErrorNumber').textContent='Enter a valid 11-digit number.';return;}document.getElementById('cErrorNumber').textContent='';});
cf.addEventListener('submit',function(e){document.getElementById('contactName').dispatchEvent(new Event('blur'));document.getElementById('contactNumber').dispatchEvent(new Event('blur'));if(document.getElementById('cErrorName').textContent||document.getElementById('cErrorNumber').textContent)e.preventDefault();});
// FAQ Accordion
document.querySelectorAll('.faq-question').forEach(function(btn){btn.addEventListener('click',function(){var item=this.parentElement;var ans=item.querySelector('.faq-answer');var isOpen=item.classList.contains('active');document.querySelectorAll('.faq-item').forEach(function(i){i.classList.remove('active');i.querySelector('.faq-answer').style.maxHeight=null;});if(!isOpen){item.classList.add('active');ans.style.maxHeight=ans.scrollHeight+'px';}});});
// Scroll Reveal
var revealEls=document.querySelectorAll('.reveal,.reveal-left,.reveal-right');
var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('visible');observer.unobserve(entry.target);}});},{threshold:0.15});
revealEls.forEach(function(el){observer.observe(el);});
</script>
<!-- AI Gift Stylist Chat -->
<div class="ai-chat-bubble" id="aiChatBubble" title="Need a gift suggestion?">
    <i class="fas fa-magic"></i>
</div>

<div class="ai-chat-window" id="aiChatWindow">
    <div class="chat-header">
        <div class="chat-header-info">
            <i class="fas fa-brain"></i>
            <div>
                <h4>AI Gift Stylist</h4>
                <p>Always online</p>
            </div>
        </div>
        <div class="chat-close" id="aiChatClose">&times;</div>
    </div>
    <div class="chat-messages" id="aiChatMessages">
        <div class="msg msg-ai">Hi there! I'm your YardHandicraft Stylist. Need help finding the perfect handcrafted gift? Ask me anything!</div>
    </div>
    <form class="chat-input-area" id="aiChatForm">
        <input type="text" class="chat-input" id="aiChatInput" placeholder="Type your message..." autocomplete="off">
        <button type="submit" class="btn-chat-send">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>
</div>

<script>
// AI Chat Logic
const bubble = document.getElementById('aiChatBubble');
const windowChat = document.getElementById('aiChatWindow');
const closeChat = document.getElementById('aiChatClose');
const chatForm = document.getElementById('aiChatForm');
const chatInput = document.getElementById('aiChatInput');
const chatMessages = document.getElementById('aiChatMessages');

bubble.addEventListener('click', () => {
    windowChat.style.display = 'flex';
    bubble.style.display = 'none';
});

closeChat.addEventListener('click', () => {
    windowChat.style.display = 'none';
    bubble.style.display = 'flex';
});

function addMessage(text, role) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `msg msg-${role}`;
    msgDiv.textContent = text;
    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function showTyping() {
    const typingDiv = document.createElement('div');
    typingDiv.className = 'msg msg-ai typing-msg';
    typingDiv.innerHTML = '<div class="typing"><span></span><span></span><span></span></div>';
    chatMessages.appendChild(typingDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return typingDiv;
}

chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = chatInput.value.trim();
    if (!message) return;

    // Add user message
    addMessage(message, 'user');
    chatInput.value = '';

    // Show typing
    const typingIndicator = showTyping();

    try {
        const response = await fetch('ai_assistant.php', {
            method: 'POST',
            headers: { 'Content-Type: application/json' },
            body: JSON.stringify({ message: message })
        });
        const data = await response.json();
        
        typingIndicator.remove();
        
        if (data.reply) {
            addMessage(data.reply, 'ai');
        } else {
            addMessage("I'm sorry, I encountered an error. Please try again.", 'ai');
        }
    } catch (err) {
        typingIndicator.remove();
        addMessage("Connection error. Please check your internet.", 'ai');
        console.error(err);
    }
});
</script>
</body>
</html>

