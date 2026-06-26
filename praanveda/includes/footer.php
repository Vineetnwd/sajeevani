<?php
// praanveda/includes/footer.php
// Reusable site footer — included at the bottom of every page.
global $PRODUCTS;
$base = BASE_URL;
?>

<!-- ========== FOOTER ========== -->
<footer class="footer">
  <div class="container">
    <div class="footer__grid">

      <!-- Brand -->
      <div>
        <div class="footer__brand" style="margin-bottom: 24px; display: inline-block; background: white; padding: 6px 16px; border-radius: 8px;">
          <img src="<?php echo LOGO_URL; ?>" alt="<?php echo SITE_NAME; ?> Logo" style="height: 50px; width: auto; object-fit: contain;">
        </div>
        <p class="footer__brand-desc">
          Trusted Ayurvedic healthcare company committed to delivering authentic herbal
          formulations that promote holistic wellness for every family.
        </p>
        <div class="footer__taglines">
          <div class="footer__tagline">Healing Naturally, Living Better</div>
          <div class="footer__tagline">The Power of Ayurveda, The Promise of Wellness</div>
          <div class="footer__tagline">Where Nature Meets Wellness</div>
        </div>
      </div>

      <!-- Navigation -->
      <div>
        <div class="footer__col-title">Navigation</div>
        <ul class="footer__links">
          <li><a href="<?php echo $base; ?>/index.php"    class="footer__link">Home</a></li>
          <li><a href="<?php echo $base; ?>/about.php"    class="footer__link">About Us</a></li>
          <li><a href="<?php echo $base; ?>/products.php" class="footer__link">Our Products</a></li>
          <li><a href="<?php echo $base; ?>/contact.php"  class="footer__link">Contact Us</a></li>
        </ul>
      </div>

      <!-- Products -->
      <div>
        <div class="footer__col-title">Products</div>
        <ul class="footer__links">
          <?php foreach ($PRODUCTS as $p): ?>
          <li>
            <a href="<?php echo $base; ?>/products.php#<?php echo htmlspecialchars($p['id']); ?>"
               class="footer__link">
              <?php echo htmlspecialchars($p['name']); ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Contact -->
      <div>
        <div class="footer__col-title">Contact</div>
        <div class="footer__contact-item" style="display: flex; gap: 12px; align-items: flex-start;">
          <div style="color: var(--color-accent-lt); width: 18px; flex-shrink: 0; margin-top: 3px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          </div>
          <div class="footer__contact-value">
            <a href="tel:<?php echo preg_replace('/\s+/', '', CONTACT_PHONE); ?>"><?php echo CONTACT_PHONE; ?></a>
          </div>
        </div>
        <div class="footer__contact-item" style="display: flex; gap: 12px; align-items: flex-start;">
          <div style="color: var(--color-accent-lt); width: 18px; flex-shrink: 0; margin-top: 3px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
          <div class="footer__contact-value">
            <a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a>
          </div>
        </div>
        <div class="footer__contact-item" style="display: flex; gap: 12px; align-items: flex-start;">
          <div style="color: var(--color-accent-lt); width: 18px; flex-shrink: 0; margin-top: 3px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
          </div>
          <div class="footer__contact-value">
            <a href="https://<?php echo CONTACT_WEBSITE; ?>" target="_blank" rel="noopener"><?php echo CONTACT_WEBSITE; ?></a>
          </div>
        </div>
        <div class="footer__contact-item" style="display: flex; gap: 12px; align-items: flex-start;">
          <div style="color: var(--color-accent-lt); width: 18px; flex-shrink: 0; margin-top: 3px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div class="footer__contact-value"><?php echo htmlspecialchars(CONTACT_ADDRESS); ?></div>
        </div>
        <div class="footer__contact-item" style="display: flex; gap: 12px; align-items: flex-start;">
          <div style="color: var(--color-accent-lt); width: 18px; flex-shrink: 0; margin-top: 3px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="footer__contact-value"><?php echo htmlspecialchars(BUSINESS_HOURS); ?></div>
        </div>
      </div>

    </div><!-- /.footer__grid -->

    <div class="footer__bottom">
      <span>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</span>
      <span>Trusted Ayurvedic Healthcare for Every Family</span>
    </div>

  </div>
</footer>

<!-- ========== SHARED SCRIPTS ========== -->
<script>
  // Mobile nav toggle
  (function () {
    var toggle = document.getElementById('nav-toggle');
    var links  = document.getElementById('nav-links');
    if (!toggle || !links) return;
    toggle.addEventListener('click', function () {
      var open = links.classList.toggle('open');
      toggle.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('#main-nav')) {
        links.classList.remove('open');
        toggle.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  })();

  // Scroll-reveal
  (function () {
    var els = document.querySelectorAll('.reveal');
    if (!els.length || !window.IntersectionObserver) {
      // Fallback: show all immediately if IO not supported
      els.forEach(function(el){ el.classList.add('visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });
    els.forEach(function (el) { io.observe(el); });
  })();
</script>

</body>
</html>
