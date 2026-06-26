<?php
// praanveda/contact.php — Contact Page
// Handles form submission server-side (no JS required for basic submission)
require_once __DIR__ . '/includes/config.php';

$currentPage = 'contact.php';
$pageTitle   = 'Contact Us — ' . SITE_NAME;
$pageDesc    = 'Get in touch with PraanVeda AyurShakti. Reach us by phone, email, or visit our office in Kolkata. We would love to hear from you.';

// ── Form Processing ────────────────────────────────────────────────────────────
$formSuccess = false;
$formError   = '';
$formData    = ['name' => '', 'phone' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_enquiry'])) {

    // Sanitise inputs
    $name    = trim(htmlspecialchars($_POST['name']    ?? '', ENT_QUOTES));
    $phone   = trim(htmlspecialchars($_POST['phone']   ?? '', ENT_QUOTES));
    $email   = trim(htmlspecialchars($_POST['email']   ?? '', ENT_QUOTES));
    $subject = trim(htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES));
    $message = trim(htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES));

    $formData = compact('name', 'phone', 'email', 'subject', 'message');

    // Basic validation
    if (empty($name) || strlen($name) < 2) {
        $formError = 'Please enter your full name.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please enter a valid email address.';
    } elseif (empty($message) || strlen($message) < 10) {
        $formError = 'Please enter a message (at least 10 characters).';
    } else {
        // ── Option 1: Save to DB ────────────────────────────────────────────────
        if (!empty($pdo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO website_enquiries (name, phone, email, subject, message)
                                       VALUES (:name, :phone, :email, :subject, :message)");
                $stmt->execute([
                    ':name'    => $name,
                    ':phone'   => $phone,
                    ':email'   => $email,
                    ':subject' => $subject,
                    ':message' => $message,
                ]);
                $formSuccess = true;
                $formData    = ['name' => '', 'phone' => '', 'email' => '', 'subject' => '', 'message' => ''];
            } catch (PDOException $e) {
                $formError = 'Could not save your enquiry. Please try again.';
                error_log('Contact form error: ' . $e->getMessage());
            }
        } else {
            $formError = 'System is temporarily unavailable. Please call us directly.';
        }

        // ── Option 2: Send via mail() (as backup notification) ──────────────────
        if ($formSuccess) {
            $to      = CONTACT_EMAIL;
            $subLine = 'New Website Enquiry' . ($subject ? ': ' . $subject : '');
            $body    = "Name: $name\nPhone: $phone\nEmail: $email\n\nMessage:\n$message";
            $headers = "From: noreply@praanveda.net\r\nReply-To: $email";

            // mail() may not work in all local environments — suppress errors gracefully
            @mail($to, $subLine, $body, $headers);
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ========== PAGE HERO ========== -->
<section class="page-hero">
  <div class="container">
    <div class="page-hero__breadcrumb">
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
      &nbsp;/&nbsp;
      <span>Contact Us</span>
    </div>
    <h1 class="page-hero__title">Get in Touch</h1>
    <p class="page-hero__sub">We would love to hear from you</p>
  </div>
</section>

<!-- ========== CONTACT BODY ========== -->
<section class="section">
  <div class="container">
    <div class="contact-grid reveal">

      <!-- LEFT: Contact Info -->
      <div>
        <span class="section-label">Contact Information</span>
        <h2 class="heading-lg" style="margin-bottom:8px;"><?php echo SITE_NAME; ?></h2>
        <div class="section-divider"></div>
        <p class="body-md" style="margin-bottom:32px;">
          Reach out to us through any of the channels below. Our team is happy to answer your
          questions about our products, wellness guidance, or business enquiries.
        </p>

        <div>
          <div class="contact-info__item">
            <div class="contact-info__label">Address</div>
            <div class="contact-info__value"><?php echo htmlspecialchars(CONTACT_ADDRESS); ?></div>
          </div>
          <div class="contact-info__item">
            <div class="contact-info__label">Phone</div>
            <div class="contact-info__value">
              <a href="tel:<?php echo preg_replace('/\s+/', '', CONTACT_PHONE); ?>">
                <?php echo htmlspecialchars(CONTACT_PHONE); ?>
              </a>
            </div>
          </div>
          <div class="contact-info__item">
            <div class="contact-info__label">Email</div>
            <div class="contact-info__value">
              <a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a>
            </div>
          </div>
          <div class="contact-info__item">
            <div class="contact-info__label">Website</div>
            <div class="contact-info__value">
              <a href="https://<?php echo CONTACT_WEBSITE; ?>" target="_blank" rel="noopener">
                <?php echo CONTACT_WEBSITE; ?>
              </a>
            </div>
          </div>
          <div class="contact-info__item">
            <div class="contact-info__label">Business Hours</div>
            <div class="contact-info__value"><?php echo htmlspecialchars(BUSINESS_HOURS); ?></div>
          </div>
        </div>


      </div>

      <!-- RIGHT: Contact Form -->
      <div>
        <div class="contact-form">
          <div class="contact-form__title">Send Us a Message</div>

          <?php if ($formSuccess): ?>
          <div class="alert alert--success">
            Thank you for reaching out! We have received your message and will get back to you within 1–2 business days.
          </div>
          <?php endif; ?>

          <?php if ($formError): ?>
          <div class="alert alert--error">
            <?php echo htmlspecialchars($formError); ?>
          </div>
          <?php endif; ?>

          <form method="POST" action="<?php echo BASE_URL; ?>/contact.php" novalidate>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="name">Full Name <span style="color:var(--color-accent);">*</span></label>
                <input type="text" id="name" name="name" class="form-input"
                       placeholder="Your full name"
                       value="<?php echo htmlspecialchars($formData['name']); ?>"
                       required />
              </div>
              <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-input"
                       placeholder="+91 00000 00000"
                       value="<?php echo htmlspecialchars($formData['phone']); ?>" />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="email">Email Address</label>
              <input type="email" id="email" name="email" class="form-input"
                     placeholder="your@email.com"
                     value="<?php echo htmlspecialchars($formData['email']); ?>" />
            </div>

            <div class="form-group">
              <label class="form-label" for="subject">Subject</label>
              <select id="subject" name="subject" class="form-select">
                <?php
                $subjects = [
                    ''                          => '— Select a subject —',
                    'Product Enquiry'           => 'Product Enquiry',
                    'Business / Dealer Enquiry' => 'Business / Dealer Enquiry',
                    'Healthcare Guidance'       => 'Healthcare Guidance',
                    'Feedback'                  => 'Feedback',
                    'Other'                     => 'Other',
                ];
                foreach ($subjects as $val => $label): ?>
                <option value="<?php echo htmlspecialchars($val); ?>"
                  <?php echo ($formData['subject'] === $val) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($label); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="message">Message <span style="color:var(--color-accent);">*</span></label>
              <textarea id="message" name="message" class="form-textarea"
                        placeholder="Write your message here..."
                        required><?php echo htmlspecialchars($formData['message']); ?></textarea>
            </div>

            <button type="submit" name="submit_enquiry" class="btn btn--primary" style="width:100%;">
              Send Message
            </button>

          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========== COMMITMENT ========== -->
<section class="commitment reveal">
  <div class="container">
    <span class="section-label" style="color:var(--color-accent-lt);">PraanVeda AyurShakti</span>
    <h2 class="heading-lg heading-lg--white" style="margin:16px 0 24px;">Trusted Ayurvedic Healthcare for Every Family</h2>
    <p>
      From our facility in Kolkata, we serve families across India with authentic Ayurvedic
      formulations backed by quality assurance and customer-centric care.
    </p>
    <a href="<?php echo BASE_URL; ?>/products.php" class="btn btn--primary">View Our Products</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
