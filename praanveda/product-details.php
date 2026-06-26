<?php
// praanveda/product-details.php
require_once __DIR__ . '/includes/config.php';

$productId = $_GET['id'] ?? '';
$product = null;

// Find the specific product
foreach ($PRODUCTS as $p) {
    if ($p['id'] === $productId) {
        $product = $p;
        break;
    }
}

if (!$product) {
    header("Location: " . BASE_URL . "/products.php");
    exit;
}

$currentPage = 'product-details.php';
$pageTitle   = htmlspecialchars($product['name']) . ' — ' . SITE_NAME;
$pageDesc    = htmlspecialchars($product['desc']);

require_once __DIR__ . '/includes/header.php';
?>

<!-- ========== PAGE HERO ========== -->
<section class="page-hero">
  <div class="container">
    <div class="page-hero__breadcrumb">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
      &nbsp;/&nbsp;
      <a href="<?php echo BASE_URL; ?>/products.php">Our Products</a>
      &nbsp;/&nbsp;
      <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>
    <h1 class="page-hero__title"><?php echo htmlspecialchars($product['name']); ?></h1>
    <p class="page-hero__sub">
      <?php echo htmlspecialchars($product['category']); ?>
    </p>
  </div>
</section>

<!-- ========== PRODUCT DETAILS ========== -->
<section class="section product-details-section">
  <div class="container">
    <div class="product-details-grid">
      
      <!-- Left: Image Gallery -->
      <div class="product-details__gallery-wrap">
        <?php if (!empty($product['images'])): ?>
          <div class="product-details__gallery-main" id="main-<?php echo htmlspecialchars($product['id']); ?>">
            <img src="<?php echo htmlspecialchars($product['images'][0]); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
          </div>
          <?php if (count($product['images']) > 1): ?>
            <div class="product-card__gallery-thumbs" style="display: flex; gap: 12px; padding: 16px; background: var(--color-white); overflow-x: auto;">
              <?php foreach ($product['images'] as $ti => $imgUrl): ?>
                <button class="thumb-btn <?php echo $ti === 0 ? 'active' : ''; ?>"
                        onclick="switchImg('<?php echo htmlspecialchars($product['id']); ?>','<?php echo htmlspecialchars($imgUrl, ENT_QUOTES); ?>',this)"
                        style="width: 64px; height: 64px; border: 2px solid <?php echo $ti === 0 ? 'var(--color-accent)' : 'var(--color-border)'; ?>; border-radius: 8px; overflow: hidden; cursor: pointer; padding: 4px; background: var(--color-cream); flex-shrink: 0; transition: border-color 0.2s;"
                        aria-label="View photo <?php echo $ti + 1; ?>">
                  <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Photo <?php echo $ti + 1; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div style="width: 100%; aspect-ratio: 1/1; background: #f9fafb; display: flex; align-items: center; justify-content: center; color: var(--color-text-muted);">
            No image available
          </div>
        <?php endif; ?>
      </div>

      <!-- Right: Product Info -->
      <div>
        <div class="product-details__badge">
          <?php echo htmlspecialchars($product['category']); ?>
        </div>
        
        <h1 class="product-details__title">
          <?php echo htmlspecialchars($product['name']); ?>
        </h1>
        
        <div class="product-details__type">
          <?php echo htmlspecialchars($product['type']); ?>
        </div>

        <?php if ($product['price'] > 0): ?>
          <div class="product-details__price">
            ₹<?php echo number_format($product['price'], 2); ?>
          </div>
        <?php endif; ?>

        <div class="product-details__divider"></div>

        <div style="font-size: 1.05rem; color: var(--color-text-muted); line-height: 1.8; margin-bottom: 32px;" class="summernote-content">
          <?php echo $product['desc']; ?>
        </div>

        <?php if (!empty($product['benefits'])): ?>
          <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--color-primary); margin-bottom: 20px;">Key Benefits</h3>
          <ul style="list-style: none; padding: 0; margin-bottom: 40px;">
            <?php foreach ($product['benefits'] as $b): ?>
              <li style="display: flex; align-items: flex-start; margin-bottom: 12px; font-size: 1rem; color: var(--color-text-muted); line-height: 1.6;">
                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--color-accent); margin-top: 10px; margin-right: 12px; flex-shrink: 0;"></span>
                <span><?php echo htmlspecialchars($b); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/contact.php?subject=Inquiry regarding <?php echo urlencode($product['name']); ?>" class="btn btn--primary product-details__btn">
          Inquire Now
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Gallery Script -->
<script>
function switchImg(id, url, btn) {
    const mainImg = document.querySelector('#main-' + id + ' img');
    if (mainImg) {
        mainImg.src = url;
    }
    const container = btn.parentElement;
    container.querySelectorAll('.thumb-btn').forEach(el => {
        el.style.borderColor = 'var(--color-border)';
        el.classList.remove('active');
    });
    btn.style.borderColor = 'var(--color-accent)';
    btn.classList.add('active');
}
</script>

<!-- ========== TRUST STRIP ========== -->
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
