<?php
// praanveda/includes/header.php
// Reusable site header — included at the top of every page.
// Requires: config.php already loaded (provides BASE_URL, SITE_NAME, SITE_TAGLINE)

$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF']);
$base        = BASE_URL; // e.g. https://praanveda.net or http://localhost/sanjeevni/praanveda
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="<?php echo htmlspecialchars($pageDesc ?? 'PraanVeda AyurShakti — Authentic Ayurvedic herbal formulations for holistic wellness.'); ?>" />
  <meta name="keywords" content="Ayurvedic, herbal, wellness, PraanVeda, AyurShakti, natural health, Ayurveda India" />
  <meta property="og:title"       content="<?php echo htmlspecialchars($pageTitle ?? SITE_NAME); ?>" />
  <meta property="og:description" content="<?php echo htmlspecialchars($pageDesc ?? ''); ?>" />
  <meta property="og:type"        content="website" />
  <title><?php echo htmlspecialchars($pageTitle ?? SITE_NAME . ' — ' . SITE_TAGLINE); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <!-- CSS path derived from BASE_URL — works on any host/domain -->
  <link rel="stylesheet" href="<?php echo $base; ?>/assets/style.css?v=<?php echo time(); ?>" />
  <link rel="icon" href="<?php echo FAVICON_URL; ?>" type="image/x-icon" />
</head>
<body>

<!-- ========== NAVIGATION ========== -->
<nav class="nav" id="main-nav">
  <div class="container">
    <div class="nav__inner">

      <a href="<?php echo $base; ?>/index.php" class="nav__brand" style="display:flex; align-items:center;">
        <div style="background: white; padding: 4px 12px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
          <img src="<?php echo LOGO_URL; ?>" alt="<?php echo SITE_NAME; ?> Logo" style="height: 44px; width: auto; object-fit: contain;">
        </div>
      </a>

      <button class="nav__toggle" id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>

      <ul class="nav__links" id="nav-links" role="menubar">
        <?php
        $navItems = [
            'index.php'    => 'Home',
            'about.php'    => 'About Us',
            'products.php' => 'Our Products',
            'contact.php'  => 'Contact Us',
        ];
        foreach ($navItems as $file => $label):
            $isActive = ($currentPage === $file) ? ' active' : '';
        ?>
        <li role="none">
          <a href="<?php echo $base . '/' . $file; ?>"
             class="nav__link<?php echo $isActive; ?>"
             role="menuitem"
             <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
            <?php echo htmlspecialchars($label); ?>
          </a>
        </li>
        <?php endforeach; ?>
        <li role="none" style="margin-left: 8px;">
          <a href="<?php echo rtrim(BASE_URL, '/'); ?>/../web/index.php"
             class="nav__link nav__link--cta"
             role="menuitem">
            Login
          </a>
        </li>
      </ul>

    </div>
  </div>
</nav>
