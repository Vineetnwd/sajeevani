<?php
// praanveda/about.php — About Us Page
require_once __DIR__ . '/includes/config.php';

$currentPage = 'about.php';
$pageTitle   = 'About Us — ' . SITE_NAME;
$pageDesc    = 'Learn about PraanVeda AyurShakti — our story, vision, mission, and commitment to authentic Ayurvedic wellness for modern families across India.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ========== PAGE HERO ========== -->
<section class="page-hero">
  <div class="container">
    <div class="page-hero__breadcrumb">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
      &nbsp;/&nbsp;
      <span>About Us</span>
    </div>
    <h1 class="page-hero__title">About PraanVeda AyurShakti</h1>
    <p class="page-hero__sub">Rooted in ancient wisdom, committed to modern wellness</p>
  </div>
</section>

<!-- ========== WHO WE ARE ========== -->
<section class="section">
  <div class="container">
    <div class="about-intro">

      <div class="reveal">
        <span class="section-label">Who We Are</span>
        <h2 class="heading-lg">A Trusted Name in Ayurvedic Healthcare</h2>
        <div class="section-divider"></div>
        <p class="body-lg" style="margin-bottom:24px;">
          PraanVeda AyurShakti is a trusted Ayurvedic healthcare company committed to delivering
          authentic herbal formulations that promote holistic wellness.
        </p>
        <p class="body-md" style="margin-bottom:24px;">
          Our mission is to combine the ancient science of Ayurveda with modern quality standards,
          offering products that help people maintain better health naturally.
        </p>
        <p class="body-md">
          We believe that true health comes from balancing the body, mind, and spirit. Every product
          we create is a reflection of this belief — thoughtfully formulated, rigorously quality-checked,
          and designed to serve the wellness needs of the entire family.
        </p>
      </div>

      <div class="about-visual reveal">
        <p class="about-visual__quote">
          "Inspired by the timeless wisdom of Ayurveda, we offer high-quality herbal formulations
          designed to support a healthier and happier life."
        </p>
        <div class="about-visual__rule"></div>
        <div class="about-visual__cite"><?php echo SITE_NAME; ?></div>
        <div class="about-visual__stats">
          <div>
            <div class="about-stat-val"><?php echo count($PRODUCTS); ?></div>
            <div class="about-stat-lbl">Products</div>
          </div>
          <div>
            <div class="about-stat-val">100%</div>
            <div class="about-stat-lbl">Ayurvedic</div>
          </div>
          <div>
            <div class="about-stat-val">All</div>
            <div class="about-stat-lbl">Natural Ingredients</div>
          </div>
          <div>
            <div class="about-stat-val">Pan</div>
            <div class="about-stat-lbl">India Reach</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== VISION & MISSION ========== -->
<section class="section section--cream">
  <div class="container">
    <div class="text-center reveal" style="margin-bottom:56px;">
      <span class="section-label">Our Purpose</span>
      <h2 class="heading-lg">Vision and Mission</h2>
      <div class="section-divider section-divider--center"></div>
    </div>

    <div class="mission-grid reveal">

      <div class="mission-item mission-item--dark">
        <div class="mission-split">
          <div style="flex:1;">
            <div style="font-size:0.72rem;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:var(--color-accent-lt);margin-bottom:12px;">Our Vision</div>
            <p style="font-family:var(--font-serif);font-size:1.4rem;font-weight:700;color:var(--color-white);line-height:1.3;">
              To become a leading Ayurvedic wellness brand trusted by families across India.
            </p>
          </div>
          <div class="mission-split__divider"></div>
          <div style="flex:1;">
            <div style="font-size:0.72rem;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:var(--color-accent-lt);margin-bottom:12px;">Our Commitment</div>
            <p style="font-size:0.94rem;color:rgba(255,255,255,0.75);line-height:1.75;">
              Every product we create reflects our dedication to quality, authenticity, and wellness.
              We continuously strive to provide safe and effective Ayurvedic solutions that empower
              individuals and families to live healthier lives.
            </p>
          </div>
        </div>
      </div>

      <div class="mission-item">
        <div class="mission-item__title">Our Mission</div>
        <?php
        $missions = [
            'Promote natural healthcare solutions',
            'Deliver quality Ayurvedic products',
            'Support preventive healthcare and healthy living',
            'Make Ayurveda accessible to every household',
        ];
        ?>
        <ul class="mission-list">
          <?php foreach ($missions as $m): ?>
          <li class="mission-list__item"><?php echo htmlspecialchars($m); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="mission-item">
        <div class="mission-item__title">Our Values</div>
        <?php
        $values = [
            'Authenticity in every formulation',
            'Transparency in ingredients and process',
            'Respect for traditional Ayurvedic wisdom',
            'Commitment to customer well-being',
        ];
        ?>
        <ul class="mission-list">
          <?php foreach ($values as $v): ?>
          <li class="mission-list__item"><?php echo htmlspecialchars($v); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>
  </div>
</section>

<!-- ========== WHY CHOOSE US ========== -->
<section class="section">
  <div class="container">
    <div class="text-center reveal" style="margin-bottom:56px;">
      <span class="section-label">Why Choose PraanVeda</span>
      <h2 class="heading-lg">What Sets Us Apart</h2>
      <div class="section-divider section-divider--center"></div>
      <p class="body-md" style="max-width:540px;margin:0 auto;">
        Our approach combines traditional Ayurvedic wisdom with modern quality assurance to deliver
        wellness solutions your family can rely on every day.
      </p>
    </div>
    <div class="pillar-grid reveal">
      <?php
      $pillars = [
          ['100% Ayurvedic Formulations', 'Prepared using traditional Ayurvedic knowledge refined across generations.'],
          ['Quality Assured',             'Manufactured under strict quality control for safety, efficacy, and consistency.'],
          ['Natural Ingredients',         'Carefully selected herbs and plant-based ingredients sourced with care.'],
          ['Trusted Wellness',            'Designed to support daily health and well-being for men, women, and children.'],
          ['Customer-Centric',            'Committed to customer satisfaction and spreading healthcare awareness.'],
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

<!-- ========== COMMITMENT ========== -->
<section class="commitment reveal">
  <div class="container">
    <span class="section-label" style="color:var(--color-accent-lt);">Healing Naturally, Living Better</span>
    <h2 class="heading-lg heading-lg--white" style="margin:16px 0 24px;">Experience the Power of Ayurveda</h2>
    <p>
      Explore our range of carefully crafted herbal formulations — each designed to address specific
      wellness needs using nature's finest ingredients.
    </p>
    <a href="<?php echo BASE_URL; ?>/products.php" class="btn btn--primary">Explore Our Products</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
