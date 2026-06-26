<?php
// praanveda/products.php — Products Page
require_once __DIR__ . '/includes/config.php';

$currentPage = 'products.php';
$pageTitle   = 'Our Products — ' . SITE_NAME;
$pageDesc    = 'Explore PraanVeda AyurShakti\'s authentic Ayurvedic herbal formulations for pain relief, liver health, digestion, respiratory care, and women\'s wellness.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ========== PAGE HERO ========== -->
<section class="page-hero">
  <div class="container">
    <div class="page-hero__breadcrumb">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
      &nbsp;/&nbsp;
      <span>Our Products</span>
    </div>
    <h1 class="page-hero__title">Our Herbal Formulations</h1>
    <p class="page-hero__sub">
      <?php echo count($PRODUCTS); ?> carefully crafted Ayurvedic products for every family's wellness needs
    </p>
  </div>
</section>

<!-- ========== INTRO STRIP ========== -->
<div class="trust-strip">
  <div class="container">
    <div class="trust-strip__inner">
      <span class="trust-pill">100% Ayurvedic</span>
      <span class="trust-pill">Quality Assured</span>
      <span class="trust-pill">Natural Ingredients</span>
      <span class="trust-pill">Safe &amp; Effective</span>
      <span class="trust-pill">Family Wellness</span>
    </div>
  </div>
</div>

<!-- ========== PRODUCTS GRID ========== -->
<section class="section">
  <div class="container">
    <div class="text-center reveal" style="margin-bottom:56px;">
      <span class="section-label">Ayurvedic Formulations</span>
      <h2 class="heading-lg">All Products</h2>
      <div class="section-divider section-divider--center"></div>
      <p class="body-md" style="max-width:560px;margin:0 auto;">
        Each product is a result of meticulous research into traditional Ayurvedic texts,
        blended with modern quality standards to ensure the highest efficacy and safety.
      </p>
    </div>

    <div class="card-grid card-grid--3 reveal">
      <?php foreach ($PRODUCTS as $p): ?>
        <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
          <?php if (!empty($p['images'])): ?>
            <div class="card__image">
              <img src="<?php echo htmlspecialchars($p['images'][0]); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
            </div>
          <?php endif; ?>
          <div class="card__content">
            <div style="margin-bottom: auto;">
              <div class="card__tag"><?php echo htmlspecialchars($p['category']); ?></div>
              <div class="card__title"><?php echo htmlspecialchars($p['name']); ?></div>
              <div class="card__subtitle"><?php echo htmlspecialchars($p['type']); ?></div>
              <div class="card__body"><?php echo htmlspecialchars(mb_strimwidth(strip_tags($p['desc']), 0, 100, "...")); ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/product-details.php?id=<?php echo htmlspecialchars($p['id']); ?>"
              class="btn btn--outline-green btn--sm" style="align-self: flex-start; margin-top: 16px;">View Details</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ========== COMMITMENT ========== -->
<section class="commitment reveal">
  <div class="container">
    <span class="section-label" style="color:var(--color-accent-lt);">Our Commitment</span>
    <h2 class="heading-lg heading-lg--white" style="margin:16px 0 24px;">Dedicated to Quality and Authenticity</h2>
    <p>
      At PraanVeda AyurShakti, every product reflects our dedication to quality, authenticity, and wellness.
      We continuously strive to provide safe and effective Ayurvedic solutions that empower individuals
      to live healthier lives.
    </p>
    <a href="<?php echo BASE_URL; ?>/contact.php" class="btn btn--primary">Contact Us</a>
  </div>
</section>

<!-- Image gallery switcher script -->
<script>
function switchImg(pid, url, thumb) {
  var main = document.getElementById('main-' + pid);
  if (main) main.querySelector('img').src = url;
  var thumbs = thumb.closest('.product-card__gallery-thumbs');
  if (thumbs) thumbs.querySelectorAll('.product-card__gallery-thumb').forEach(function(t){ t.classList.remove('active'); });
  thumb.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
