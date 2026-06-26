<?php
// web/website_settings.php — Admin panel page to manage the public website configuration
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['Admin'])) {
    header("Location: index.php");
    exit();
}

$successMsg = '';
$errorMsg   = '';

// ── Ensure website_config table exists ─────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS `website_config` (
    `id`         int(11)      NOT NULL AUTO_INCREMENT,
    `config_key` varchar(100) NOT NULL,
    `config_val` text         DEFAULT NULL,
    `label`      varchar(150) DEFAULT NULL,
    `updated_at` timestamp    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// ── Handle: Save website config ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    try {
        // Handle text configuration
        $configFields = [
            'site_name', 'site_tagline', 'contact_address', 'contact_phone',
            'contact_email', 'contact_website', 'business_hours',
            'hero_tagline', 'hero_body', 'hero_quote',
            'about_short', 'vision_text', 'commitment_text',
        ];
        $upsert = $pdo->prepare("INSERT INTO website_config (config_key, config_val, label)
                                  VALUES (:k, :v, :l)
                                  ON DUPLICATE KEY UPDATE config_val = VALUES(config_val)");
        $labels = [
            'site_name'       => 'Site Name',        'site_tagline'    => 'Site Tagline',
            'contact_address' => 'Address',           'contact_phone'   => 'Phone',
            'contact_email'   => 'Email',             'contact_website' => 'Website URL',
            'business_hours'  => 'Business Hours',   'hero_tagline'    => 'Hero Sub-tagline',
            'hero_body'       => 'Hero Body Text',   'hero_quote'      => 'Hero Quote',
            'about_short'     => 'About Text',       'vision_text'     => 'Vision Statement',
            'commitment_text' => 'Commitment Text',
        ];
        foreach ($configFields as $key) {
            if (isset($_POST[$key])) {
                $upsert->execute([':k' => $key, ':v' => trim($_POST[$key]), ':l' => $labels[$key] ?? $key]);
            }
        }
        
        // Handle Logo Upload
        if (!empty($_FILES['site_logo']['tmp_name'])) {
            $tmp = $_FILES['site_logo']['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            
            if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
                move_uploaded_file($tmp, __DIR__ . '/logo.png');
            } else {
                throw new Exception("Invalid logo image format. Only JPG, PNG, WEBP, and GIF are allowed.");
            }
        }

        // Handle Favicon Upload
        if (!empty($_FILES['site_favicon']['tmp_name'])) {
            $tmp = $_FILES['site_favicon']['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            
            if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'])) {
                move_uploaded_file($tmp, __DIR__ . '/favicon.png');
            } else {
                throw new Exception("Invalid favicon image format. Only JPG, PNG, ICO, WEBP, and GIF are allowed.");
            }
        }
        // Handle Sliders Delete
        $slidersDir = __DIR__ . '/uploads/sliders';
        if (!is_dir($slidersDir)) {
            mkdir($slidersDir, 0755, true);
        }
        if (!empty($_POST['delete_slider'])) {
            $idx = (int)$_POST['delete_slider'];
            if ($idx >= 1 && $idx <= 3) {
                array_map('unlink', glob("{$slidersDir}/slider_{$idx}.*"));
            }
        }

        // Handle Sliders Upload
        for ($i = 1; $i <= 3; $i++) {
            $inputName = "hero_slider_{$i}";
            if (!empty($_FILES[$inputName]['tmp_name'])) {
                $tmp = $_FILES[$inputName]['tmp_name'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                
                if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                    // Remove existing slider image for this slot
                    array_map('unlink', glob("{$slidersDir}/slider_{$i}.*"));
                    $ext = ($mime === 'image/png') ? 'png' : (($mime === 'image/webp') ? 'webp' : 'jpg');
                    move_uploaded_file($tmp, "{$slidersDir}/slider_{$i}.{$ext}");
                } else {
                    throw new Exception("Invalid image format for Slider {$i}. Only JPG, PNG, and WEBP are allowed.");
                }
            }
        }
        
        $successMsg = 'Website settings saved successfully.';
    } catch (Exception $e) {
        $errorMsg = 'Error saving settings: ' . $e->getMessage();
    }
}

// ── Fetch current config ──────────────────────────────────────────────────────
$configRows  = $pdo->query("SELECT config_key, config_val FROM website_config")->fetchAll();
$siteConfig  = array_column($configRows, 'config_val', 'config_key');

function cfg(array $conf, string $key, string $fallback = ''): string {
    return htmlspecialchars($conf[$key] ?? $fallback, ENT_QUOTES);
}

// Build public site URL
$proto       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'];
$webDirRel   = str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__);
$parentRel   = str_replace('/web', '', rtrim($webDirRel, '/'));
$publicSiteUrl = $proto . '://' . $host . $parentRel . '/praanveda/index.php';
$logoUrl     = $proto . '://' . $host . rtrim($webDirRel, '/') . '/logo.png?v=' . time(); // cache buster
$faviconUrl  = $proto . '://' . $host . rtrim($webDirRel, '/') . '/favicon.png?v=' . time(); // cache buster

$sliderUrls = [];
for ($i = 1; $i <= 3; $i++) {
    $matches = glob(__DIR__ . "/uploads/sliders/slider_{$i}.*");
    if (!empty($matches)) {
        $sliderUrls[$i] = $proto . '://' . $host . rtrim($webDirRel, '/') . '/uploads/sliders/' . basename($matches[0]) . '?v=' . time();
    } else {
        $sliderUrls[$i] = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website Settings — <?php echo APP_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style> body { font-family: 'Inter', sans-serif; } </style>
  <link rel="stylesheet" href="admin-style.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

<?php include 'sidebar.php'; ?>

<main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">

  <!-- Header -->
  <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
    <div class="flex items-center gap-3">
      <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 mr-2">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div>
        <h1 class="text-xl font-bold text-gray-800">Website Settings</h1>
        <p class="text-xs text-gray-500 mt-0.5">Manage configuration for the public website</p>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <a href="<?php echo htmlspecialchars($publicSiteUrl); ?>" target="_blank"
         class="text-sm px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
        View Public Site
      </a>
    </div>
  </header>

  <div class="flex-1 overflow-y-auto p-4 sm:p-6">

    <?php if ($successMsg): ?>
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded-lg flex items-center gap-2 mb-6">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <?php echo htmlspecialchars($successMsg); ?>
    </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm font-medium px-4 py-3 rounded-lg mb-6">
      <?php echo htmlspecialchars($errorMsg); ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="save_config" value="1">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Brand -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100">Brand Identity</h2>
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-4 mb-4 border-b border-gray-100">
              <!-- Logo Upload -->
              <div class="flex items-start gap-4">
                <div class="flex-shrink-0 bg-gray-50 border border-gray-200 rounded-lg p-2" style="width: 100px; height: 100px; display:flex; align-items:center; justify-content:center;">
                  <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Site Logo" id="logo-preview" style="max-width:100%; max-height:100%; object-fit:contain;" onerror="this.style.display='none'">
                </div>
                <div class="flex-1">
                  <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Upload New Logo</label>
                  <input type="file" name="site_logo" accept="image/*" onchange="document.getElementById('logo-preview').src = window.URL.createObjectURL(this.files[0]); document.getElementById('logo-preview').style.display='block';"
                         class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                  <p class="text-[11px] text-gray-400 mt-2">Recommended: PNG with transparent background.</p>
                </div>
              </div>
              
              <!-- Favicon Upload -->
              <div class="flex items-start gap-4">
                <div class="flex-shrink-0 bg-gray-50 border border-gray-200 rounded-lg p-2" style="width: 100px; height: 100px; display:flex; align-items:center; justify-content:center;">
                  <img src="<?php echo htmlspecialchars($faviconUrl); ?>" alt="Favicon" id="favicon-preview" style="max-width:100%; max-height:100%; object-fit:contain;" onerror="this.style.display='none'">
                </div>
                <div class="flex-1">
                  <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Upload Favicon</label>
                  <input type="file" name="site_favicon" accept="image/*" onchange="document.getElementById('favicon-preview').src = window.URL.createObjectURL(this.files[0]); document.getElementById('favicon-preview').style.display='block';"
                         class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                  <p class="text-[11px] text-gray-400 mt-2">Recommended: Square PNG or ICO (e.g., 32x32px).</p>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Site Name</label>
              <input type="text" name="site_name" value="<?php echo cfg($siteConfig, 'site_name', 'PraanVeda AyurShakti'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Site Tagline</label>
              <input type="text" name="site_tagline" value="<?php echo cfg($siteConfig, 'site_tagline', 'Ancient Ayurveda for Modern Wellness'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none">
            </div>
          </div>
        </div>

        <!-- Contact -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100">Contact Information</h2>
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Phone</label>
              <input type="text" name="contact_phone" value="<?php echo cfg($siteConfig, 'contact_phone', '+91 90620 05055'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Email</label>
              <input type="email" name="contact_email" value="<?php echo cfg($siteConfig, 'contact_email', 'care@praanveda.net'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Website URL</label>
              <input type="text" name="contact_website" value="<?php echo cfg($siteConfig, 'contact_website', 'www.praanveda.net'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Business Hours</label>
              <input type="text" name="business_hours" value="<?php echo cfg($siteConfig, 'business_hours', 'Monday – Saturday, 10:00 AM – 6:00 PM'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Address</label>
              <textarea name="contact_address" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none resize-none"><?php echo cfg($siteConfig, 'contact_address'); ?></textarea>
            </div>
          </div>
        </div>

        <!-- Home Page Hero -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
          <h2 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100">Home Page — Hero Section</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Sub-tagline</label>
              <input type="text" name="hero_tagline" value="<?php echo cfg($siteConfig, 'hero_tagline', 'Balancing Body, Mind and Spirit'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Quote</label>
              <input type="text" name="hero_quote" value="<?php echo cfg($siteConfig, 'hero_quote', "Nature's Healing Power for a Healthier Tomorrow."); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none">
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Body Text</label>
              <textarea name="hero_body" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none resize-y"><?php echo cfg($siteConfig, 'hero_body'); ?></textarea>
            </div>
            
            <div class="md:col-span-2 mt-4 pt-6 border-t border-gray-100">
              <h3 class="text-sm font-bold text-gray-800 mb-2">Background Slider Images</h3>
              <p class="text-xs text-gray-500 mb-5">Upload up to 3 high-quality images (JPG/PNG/WEBP) to slide automatically in the hero background. Recommended size: 1920&times;1080px.</p>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                  <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 relative">
                    <?php if ($sliderUrls[$i]): ?>
                      <div class="w-full h-32 bg-gray-200 rounded-lg mb-3 overflow-hidden">
                        <img src="<?php echo $sliderUrls[$i]; ?>" class="w-full h-full object-cover">
                      </div>
                      <button type="submit" name="delete_slider" value="<?php echo $i; ?>" formnovalidate class="absolute top-6 right-6 bg-red-600/90 text-white p-1.5 rounded shadow-sm hover:bg-red-700 transition" title="Delete Slider">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                      </button>
                    <?php else: ?>
                      <div class="w-full h-32 bg-gray-100 rounded-lg mb-3 border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400 flex-col">
                        <svg class="w-8 h-8 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-[11px] font-semibold tracking-wider uppercase">Slot <?php echo $i; ?></span>
                      </div>
                    <?php endif; ?>
                    <label class="block text-[10px] font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Upload Image <?php echo $i; ?></label>
                    <input type="file" name="hero_slider_<?php echo $i; ?>" accept="image/jpeg, image/png, image/webp" 
                           class="w-full text-[10px] text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:font-semibold file:bg-white file:border file:border-gray-200 file:text-gray-700 hover:file:bg-gray-50">
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- About / Vision / Commitment -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
          <h2 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100">About / Vision / Commitment Text</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Short About Text</label>
              <textarea name="about_short" rows="4"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none resize-y"><?php echo cfg($siteConfig, 'about_short'); ?></textarea>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Vision Statement</label>
              <textarea name="vision_text" rows="4"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none resize-y"><?php echo cfg($siteConfig, 'vision_text'); ?></textarea>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Commitment Text</label>
              <textarea name="commitment_text" rows="4"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 outline-none resize-y"><?php echo cfg($siteConfig, 'commitment_text'); ?></textarea>
            </div>
          </div>
        </div>

      </div>

      <div class="mt-6 flex justify-end">
        <button type="submit"
                class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm transition-colors text-sm">
          Save All Settings
        </button>
      </div>
    </form>

  </div>
</main>

</body>
</html>
