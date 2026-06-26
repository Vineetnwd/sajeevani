<?php
// praanveda/index.php — Home Page
require_once __DIR__ . '/includes/config.php';

$currentPage = 'index.php';
$pageTitle = SITE_NAME . ' — ' . SITE_TAGLINE;
$pageDesc = 'PraanVeda AyurShakti offers authentic Ayurvedic herbal formulations for holistic wellness. Natural healthcare solutions rooted in ancient Ayurveda for modern families.';

// Fetch sliders
$sliderUrls = [];
for ($i = 1; $i <= 3; $i++) {
  $matches = glob(SLIDERS_DIR . "slider_{$i}.*");
  if (!empty($matches)) {
    $sliderUrls[] = SLIDERS_URL . basename($matches[0]) . '?v=' . time();
  }
}

require_once __DIR__ . '/includes/header.php';
?>
<!-- ========== HERO ========== -->
<section class="hero" style="padding: 0; background: transparent;">
  <?php if (!empty($sliderUrls)): ?>
    <div class="hero-slider" id="hero-slider" style="position: relative; width: 100%; line-height: 0;">
      <!-- Dummy image sets the container height perfectly to match the image ratio -->
      <img src="<?php echo htmlspecialchars($sliderUrls[0]); ?>"
        style="visibility: hidden; display: block; width: 100%; height: auto; pointer-events: none;" alt="spacer">

      <?php foreach ($sliderUrls as $index => $url): ?>
        <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>"
          style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: <?php echo $index === 0 ? '1' : '0'; ?>; transition: opacity 1.5s ease-in-out;">
          <img src="<?php echo htmlspecialchars($url); ?>"
            style="display: block; width: 100%; height: 100%; object-fit: contain;" alt="Banner <?php echo $index + 1; ?>">
        </div>
      <?php endforeach; ?>
      <?php if (count($sliderUrls) > 1): ?>
        <button class="slider-btn prev"
          style="position:absolute; top:50%; left:16px; transform:translateY(-50%); background:rgba(0,0,0,0.4); color:white; border:none; border-radius:50%; width:44px; height:44px; cursor:pointer; z-index:10; display:flex; align-items:center; justify-content:center; transition:background 0.3s;"
          onmouseover="this.style.background='rgba(0,0,0,0.8)'" onmouseout="this.style.background='rgba(0,0,0,0.4)'">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M15 18l-6-6 6-6"></path>
          </svg>
        </button>
        <button class="slider-btn next"
          style="position:absolute; top:50%; right:16px; transform:translateY(-50%); background:rgba(0,0,0,0.4); color:white; border:none; border-radius:50%; width:44px; height:44px; cursor:pointer; z-index:10; display:flex; align-items:center; justify-content:center; transition:background 0.3s;"
          onmouseover="this.style.background='rgba(0,0,0,0.8)'" onmouseout="this.style.background='rgba(0,0,0,0.4)'">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M9 18l6-6-6-6"></path>
          </svg>
        </button>
      <?php endif; ?>
    </div>

    <?php if (count($sliderUrls) > 1): ?>
      <script>
        document.addEventListener("DOMContentLoaded", function () {
          const slides = document.querySelectorAll('#hero-slider .hero-slide');
          let currentSlide = 0;
          let slideInterval;

          function goToSlide(index) {
            slides[currentSlide].style.opacity = '0';
            slides[currentSlide].classList.remove('active');

            // Handle wrap around
            currentSlide = (index + slides.length) % slides.length;

            slides[currentSlide].style.opacity = '1';
            slides[currentSlide].classList.add('active');
          }

          function startAutoSlide() {
            clearInterval(slideInterval);
            slideInterval = setInterval(() => goToSlide(currentSlide + 1), 5000);
          }

          document.querySelector('.slider-btn.prev').addEventListener('click', () => {
            goToSlide(currentSlide - 1);
            startAutoSlide(); // Reset timer on manual navigation
          });

          document.querySelector('.slider-btn.next').addEventListener('click', () => {
            goToSlide(currentSlide + 1);
            startAutoSlide(); // Reset timer on manual navigation
          });

          startAutoSlide();
        });
      </script>
    <?php endif; ?>
  <?php else: ?>
    <!-- Fallback if no sliders are uploaded -->
    <div style="width:100%; padding:100px 20px; text-align:center; background:var(--color-primary); color:white;">
      <h1 class="heading-lg"><?php echo htmlspecialchars(SITE_TAGLINE); ?></h1>
      <p style="margin-top:20px;">
        <?php echo htmlspecialchars(siteConfig('hero_tagline', 'Balancing Body, Mind and Spirit')); ?></p>
    </div>
  <?php endif; ?>
</section>

<!-- ========== TRUST STRIP ========== -->
<div class="trust-strip">
  <div class="container">
    <div class="trust-strip__inner">
      <span class="trust-pill">100% Ayurvedic Formulations</span>
      <span class="trust-pill">Quality Assured Manufacturing</span>
      <span class="trust-pill">Natural Plant-Based Ingredients</span>
      <span class="trust-pill">Preventive Healthcare</span>
      <span class="trust-pill">Family Wellness</span>
    </div>
  </div>
</div>

<!-- ========== ABOUT SNAPSHOT ========== -->
<section class="section">
  <div class="container">
    <div class="about-intro reveal">
      <div>
        <span class="section-label">Who We Are</span>
        <h2 class="heading-lg">Trusted Ayurvedic Healthcare, Rooted in Tradition</h2>
        <div class="section-divider"></div>
        <p class="body-lg" style="margin-bottom:20px;">
          <?php echo htmlspecialchars(siteConfig('about_short')); ?>
        </p>
        <p class="body-md" style="margin-bottom:32px;">
          Our mission is to combine the ancient science of Ayurveda with modern quality standards,
          offering products that help people maintain better health naturally — making Ayurveda
          accessible to every household.
        </p>
        <a href="<?php echo BASE_URL; ?>/about.php" class="btn btn--outline-green">Read Our Story</a>
      </div>

      <div>
        <div
          style="background:var(--color-primary);border-radius:var(--radius-md);padding:32px;text-align:center;margin-bottom:20px;">
          <p
            style="font-family:var(--font-serif);font-size:1.25rem;font-style:italic;color:rgba(255,255,255,0.9);line-height:1.65;">
            "<?php echo htmlspecialchars(siteConfig('vision_text', 'To become a leading Ayurvedic wellness brand trusted by families across India.')); ?>"
          </p>
          <div
            style="margin-top:16px;font-size:0.76rem;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-accent-lt);">
            Our Vision</div>
        </div>
        <div class="mini-pillar-grid">
          <div class="mini-pillar">
            <div class="mini-pillar__mark"></div>
            <div class="mini-pillar__title">Natural Healthcare</div>
            <div class="mini-pillar__body">Promoting plant-based solutions for daily wellness</div>
          </div>
          <div class="mini-pillar">
            <div class="mini-pillar__mark"></div>
            <div class="mini-pillar__title">Quality Standards</div>
            <div class="mini-pillar__body">Manufactured under strict quality control</div>
          </div>
          <div class="mini-pillar">
            <div class="mini-pillar__mark"></div>
            <div class="mini-pillar__title">Preventive Care</div>
            <div class="mini-pillar__body">Supporting healthy living proactively</div>
          </div>
          <div class="mini-pillar">
            <div class="mini-pillar__mark"></div>
            <div class="mini-pillar__title">Accessible Ayurveda</div>
            <div class="mini-pillar__body">Bringing ancient wisdom to every home</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== WHY CHOOSE US ========== -->
<section class="section section--cream">
  <div class="container">
    <div class="text-center reveal" style="margin-bottom:56px;">
      <span class="section-label">Why Choose Us</span>
      <h2 class="heading-lg">Why PraanVeda AyurShakti</h2>
      <div class="section-divider section-divider--center"></div>
      <p class="body-md" style="max-width:560px;margin:0 auto;">
        We bring together the best of traditional Ayurveda and modern quality assurance to deliver
        wellness solutions your family can trust every day.
      </p>
    </div>
    <div class="pillar-grid reveal">
      <?php
      $pillars = [
        ['100% Ayurvedic Formulations', 'Prepared using traditional Ayurvedic knowledge passed down through generations.'],
        ['Quality Assured', 'Manufactured under strict quality control standards for your safety.'],
        ['Natural Ingredients', 'Carefully selected herbs and plant-based ingredients — nothing synthetic.'],
        ['Trusted Wellness Solutions', 'Designed to support daily health and well-being for the entire family.'],
        ['Customer-Centric Approach', 'Committed to satisfaction and spreading healthcare awareness at every step.'],
      ];
      foreach ($pillars as [$title, $body]): ?>
        <div class="pillar">
          <div class="pillar__mark"></div>
          <div class="pillar__title"><?php echo htmlspecialchars($title); ?></div>
          <div class="pillar__body"><?php echo htmlspecialchars($body); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ========== PRODUCTS PREVIEW ========== -->
<section class="section">
  <div class="container">
    <div class="text-center reveal" style="margin-bottom:56px;">
      <span class="section-label">Our Products</span>
      <h2 class="heading-lg">Our Herbal Formulations</h2>
      <div class="section-divider section-divider--center"></div>
      <p class="body-md" style="max-width:540px;margin:0 auto;">
        <?php echo count($PRODUCTS); ?> carefully crafted Ayurvedic products to address the health needs of every family
        member.
      </p>
    </div>

    <div class="card-grid card-grid--3 reveal">
      <?php foreach ($PRODUCTS as $i => $p): ?>
        <div class="card<?php echo ($i === 1) ? ' card--featured' : ''; ?>"
          style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
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
              <div class="card__body">
                <?php echo htmlspecialchars(mb_strimwidth(strip_tags($p['desc']), 0, 100, "...")); ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/product-details.php?id=<?php echo htmlspecialchars($p['id']); ?>"
              class="btn btn--outline-green btn--sm" style="align-self: flex-start; margin-top: 16px;">View Details</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center" style="margin-top:48px;">
      <a href="<?php echo BASE_URL; ?>/products.php" class="btn btn--primary">View All Products</a>
    </div>
  </div>
</section>

<!-- ========== COMMITMENT ========== -->
<section class="commitment reveal">
  <div class="container">
    <span class="section-label" style="color:var(--color-accent-lt);">Our Commitment</span>
    <h2 class="heading-lg heading-lg--white" style="margin:16px 0 24px;">Every Product, A Promise of Quality</h2>
    <p><?php echo htmlspecialchars(siteConfig('commitment_text')); ?></p>
    <a href="<?php echo BASE_URL; ?>/contact.php" class="btn btn--primary">Get in Touch</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>