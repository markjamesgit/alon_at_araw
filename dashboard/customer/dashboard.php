<?php
include '../../config/db.php';

// Fetch featured products
$stmt_featured = $conn->prepare("SELECT * FROM products ORDER BY RAND() LIMIT 6");
$stmt_featured->execute();
$featured_products = $stmt_featured->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories
$stmt_categories = $conn->prepare("SELECT * FROM categories");
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Fetch all products
$stmt_all = $conn->prepare("SELECT * FROM products");
$stmt_all->execute();
$all_products = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Alon at Araw - Coffee Experience</title>
  <link rel="stylesheet" href="/alon_at_araw/assets/global.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/customer-dashboard.css"/>
  <link rel="stylesheet" href="/alon_at_araw/assets/styles/cart-sidebar.css">
  <link rel="stylesheet" href="/alon_at_araw/assets/fonts/font.css">
  <link rel="icon" type="image/png" href="../../assets/images/logo/logo.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
<?php include '../../includes/customer-header.php'; ?>
<?php include '../../includes/cart-sidebar.php'; ?>

<main class="dashboard-container">

  <!-- Hero Section -->
  <section class="hero-slider">
    <div class="slider">
      <div class="slide active">
        <img src="/alon_at_araw/assets/images/slider/slider1.jpg" alt="Starbucks Inspired Hero">
        <div class="slide-caption">
          <h1>Welcome to Alon at Araw</h1>
          <p>Cozy up with your favorite handcrafted beverage.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- About Us -->
  <section id="about" class="about-us section">
    <div class="container about-content">
      <div class="about-image">
        <img src="/alon_at_araw/assets/images/slider/slider1.jpg" alt="Coffee Lifestyle">
      </div>
      <div class="about-text">
        <h2 class="section-title">More than Coffee</h2>
        <p>
          At Alon at Araw, every cup is a reflection of passion and care. Whether you’re catching up with friends or enjoying a moment to yourself, we’re here to make it warm, rich, and memorable — one sip at a time.
        </p>
      </div>
    </div>
  </section>

<!-- Featured Products -->
<section id="featured" class="section">
  <div class="container">
    <h2 class="section-title">Featured Drinks</h2>
    <div class="product-slider-container">
      <button class="slider-arrow left" aria-label="Slide left"><i class="fas fa-chevron-left"></i></button>
      
      <div class="product-slider">
        <?php foreach ($featured_products as $row): 
          $image = $row['product_image'] 
            ? "/alon_at_araw/assets/uploads/products/" . $row['product_image']
            : "/alon_at_araw/assets/images/no-image.png";
        ?>
          <a href="product-details.php?id=<?= $row['product_id'] ?>" class="product-card <?= $row['is_best_seller'] ? 'highlight' : '' ?>" 
               data-best="<?= $row['is_best_seller'] ?>" 
               data-category="<?= $row['category_id'] ?>">
            <?php if ($row['is_best_seller']): ?>
              <span class="badge">Best Seller</span>
            <?php endif; ?>
            <img src="<?= $image ?>" alt="<?= htmlspecialchars($row['product_name']) ?>">
            <div class="card-content">
              <h3><?= htmlspecialchars($row['product_name']) ?></h3>
              <p><?= htmlspecialchars($row['description']) ?></p>
              <span class="price">₱<?= number_format($row['price'], 2) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      
      <button class="slider-arrow right" aria-label="Slide right"><i class="fas fa-chevron-right"></i></button>
      <div class="product-slider-fade-left"></div>
      <div class="product-slider-fade-right"></div>
    </div>
  </div>
</section>

<!-- Promotions & Discounts -->
<section id="promo" class="section promotions-section">
  <div class="container">
    <h2 class="section-title">Promotions & Discounts</h2>
    <ul class="promotions-list">
      <li><strong>Buy 1 Get 1 Free</strong> on all iced coffee drinks every Wednesday!</li>
      <li><strong>10% off</strong> your first online order with code <em>ARAW10</em></li>
      <li><strong>Free upgrade</strong> to large size on select espresso drinks this weekend.</li>
    </ul>
  </div>
</section>

<!-- Bento Grid Gallery -->
<section class="section bento-gallery-section">
  <div class="container">
    <h2 class="section-title">Brew & Vibes Gallery</h2>
    <div class="bento-grid">
      <div class="bento-item large">
        <img src="/alon_at_araw/assets/images/slider/slider1.jpg" alt="Fresh Brew Coffee">
      </div>
      <div class="bento-item medium">
        <img src="/alon_at_araw/assets/images/slider/slider1.jpg" alt="Coffee Beans Closeup">
      </div>
      <div class="bento-item small">
        <img src="/alon_at_araw/assets/images/slider/slider1.jpg" alt="Latte Art">
      </div>
      <div class="bento-item small">
        <img src="/alon_at_araw/assets/images/slider/slider1.jpg" alt="Cozy Coffee Shop">
      </div>
      <div class="bento-item medium">
        <img src="/alon_at_araw/assets/images/slider/slider1.jpg" alt="Pour Over Coffee">
      </div>
      <div class="bento-item large">
        <img src="/alon_at_araw/assets/images/slider/slider1.jpg" alt="Coffee & Pastries">
      </div>
    </div>
  </div>
</section>

<!-- Customer Reviews -->
<section id="reviews" class="section reviews-section">
  <div class="container">
    <h2 class="section-title">What Our Customers Say</h2>
    <div class="reviews-list">
      <blockquote>
        <p>“The best coffee experience in town! Always fresh and delicious.”</p>
        <footer>— Maria L.</footer>
      </blockquote>
      <blockquote>
        <p>“I love the cozy ambiance and friendly staff. Highly recommend!”</p>
        <footer>— Juan D.</footer>
      </blockquote>
    </div>
  </div>
</section>

<!-- Contact Us -->
<section id="contact" class="contact-us section">
  <div class="container contact-container">
    <div class="map-container">
      <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.2719089039116!2d121.04546211531898!3d14.58607309046019!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b6d49e4d367f%3A0x6d8f88a3a2b9560f!2sAlon%20at%20Araw!5e0!3m2!1sen!2sph!4v1695349659000!5m2!1sen!2sph" 
        width="100%" 
        height="100%" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade"
        aria-label="Alon at Araw location map"
      ></iframe>
    </div>
    <form class="contact-form">
      <h2 class="section-title">Let’s Connect</h2>
      <input type="text" placeholder="Your Name" required>
      <input type="email" placeholder="Your Email" required>
      <textarea rows="4" placeholder="Your Message" required></textarea>
      <button type="submit" class="material-btn">Send Message</button>
    </form>
  </div>
</section>

</main>

<script>
  const slider = document.querySelector('.product-slider');
document.querySelector('.slider-arrow.left').addEventListener('click', () => {
  slider.scrollBy({ left: -300, behavior: 'smooth' });
});
document.querySelector('.slider-arrow.right').addEventListener('click', () => {
  slider.scrollBy({ left: 300, behavior: 'smooth' });
});
</script>
</body>
</html>
