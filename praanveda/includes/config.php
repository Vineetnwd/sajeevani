<?php
// praanveda/includes/config.php
// Dynamically loads site config & products from the shared admin-panel database.
// Falls back gracefully to defaults if DB is unavailable.

// ── Environment detection ─────────────────────────────────────────────────────
$_isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || str_contains($_SERVER['HTTP_HOST'] ?? '', '127.0.0'));

// ── Shared DB credentials (mirror web/config.php) ────────────────────────────
$_dbHost = 'localhost';
$_dbUser = $_isLocal ? 'root'                  : 'u673864504_praanved';
$_dbPass = $_isLocal ? ''                      : '@Pranveda_2001';
$_dbName = $_isLocal ? 'sanjeevni'             : 'u673864504_praanved';

// ── Auto-detect BASE_URL & UPLOADS_URL ──────────────────────────────────────
$_protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_docRoot    = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$_selfDir    = rtrim(dirname(dirname(__FILE__)), '/');   // praanveda/
$_basePath   = rtrim(str_replace($_docRoot, '', $_selfDir), '/');
define('BASE_URL', $_protocol . '://' . $_host . $_basePath);

// Compute URLs to web directory assets
$_parsed     = parse_url(BASE_URL);
$_baseDir    = dirname($_parsed['path'] ?? '');
if ($_baseDir === '\\' || $_baseDir === '/') $_baseDir = '';
if ($_baseDir === '.') $_baseDir = '';
define('UPLOADS_URL', $_protocol . '://' . $_host . $_baseDir . '/web/uploads/products/');
define('LOGO_URL', $_protocol . '://' . $_host . $_baseDir . '/web/logo.png');
define('FAVICON_URL', $_protocol . '://' . $_host . $_baseDir . '/web/favicon.png');
define('SLIDERS_URL', $_protocol . '://' . $_host . $_baseDir . '/web/uploads/sliders/');
define('SLIDERS_DIR', rtrim($_docRoot . $_baseDir, '/') . '/web/uploads/sliders/');

// ── DB connection (shared with admin panel) ───────────────────────────────────
$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host={$_dbHost};dbname={$_dbName};charset=utf8mb4",
        $_dbUser, $_dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // ── Auto-migrate: ensure website_config table exists ─────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `website_config` (
        `id`         int(11)      NOT NULL AUTO_INCREMENT,
        `config_key` varchar(100) NOT NULL,
        `config_val` text         DEFAULT NULL,
        `label`      varchar(150) DEFAULT NULL,
        `updated_at` timestamp    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `config_key` (`config_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ── Auto-migrate: product_images table ────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `product_images` (
        `id`         int(11)      NOT NULL AUTO_INCREMENT,
        `product_id` int(11)      NOT NULL,
        `filename`   varchar(255) NOT NULL,
        `sort_order` int(11)      DEFAULT 0,
        `created_at` timestamp    DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ── Auto-migrate: website_enquiries table ─────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `website_enquiries` (
        `id`         int(11)      NOT NULL AUTO_INCREMENT,
        `name`       varchar(150) NOT NULL,
        `phone`      varchar(20)  DEFAULT NULL,
        `email`      varchar(150) DEFAULT NULL,
        `subject`    varchar(100) DEFAULT NULL,
        `message`    text         NOT NULL,
        `status`     enum('Pending', 'In Progress', 'Closed') DEFAULT 'Pending',
        `created_at` timestamp    DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ── Auto-migrate: enquiry_followups table ─────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `enquiry_followups` (
        `id`         int(11)      NOT NULL AUTO_INCREMENT,
        `enquiry_id` int(11)      NOT NULL,
        `note`       text         NOT NULL,
        `added_by`   varchar(100) DEFAULT 'Admin',
        `created_at` timestamp    DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `enquiry_id` (`enquiry_id`),
        FOREIGN KEY (`enquiry_id`) REFERENCES `website_enquiries`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ── Seed defaults if table is empty ──────────────────────────────────────
    $count = (int)$pdo->query("SELECT COUNT(*) FROM website_config")->fetchColumn();
    if ($count === 0) {
        $defaults = [
            ['site_name',        'PraanVeda AyurShakti',                           'Site Name'],
            ['site_tagline',     'Ancient Ayurveda for Modern Wellness',            'Site Tagline'],
            ['contact_address',  '22, Hemanta Basu Sarani, 4th Floor, Suite 4E, Kolkata - 700001', 'Address'],
            ['contact_phone',    '+91 90620 05055',                                'Phone'],
            ['contact_email',    'care@praanveda.net',                              'Email'],
            ['contact_website',  'www.praanveda.net',                              'Website URL'],
            ['business_hours',   'Monday – Saturday, 10:00 AM – 6:00 PM',          'Business Hours'],
            ['hero_tagline',     'Balancing Body, Mind and Spirit',                 'Hero Sub-tagline'],
            ['hero_body',        'We believe that true health comes from within. Inspired by the timeless wisdom of Ayurveda, we offer high-quality herbal formulations designed to support a healthier and happier life.', 'Hero Body Text'],
            ['hero_quote',       "Nature's Healing Power for a Healthier Tomorrow.", 'Hero Quote'],
            ['about_short',      'PraanVeda AyurShakti is a trusted Ayurvedic healthcare company committed to delivering authentic herbal formulations that promote holistic wellness.', 'Short About Text'],
            ['vision_text',      'To become a leading Ayurvedic wellness brand trusted by families across India.', 'Vision Statement'],
            ['commitment_text',  'Every product we create reflects our dedication to quality, authenticity, and wellness. We continuously strive to provide safe and effective Ayurvedic solutions.', 'Commitment Text'],
        ];
        $ins = $pdo->prepare("INSERT IGNORE INTO website_config (config_key, config_val, label) VALUES (?, ?, ?)");
        foreach ($defaults as $row) {
            $ins->execute($row);
        }
    }

    // ── Auto-migrate: extend products table for website display ──────────────
    $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('category', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN category       varchar(100) DEFAULT NULL AFTER description");
    }
    if (!in_array('tagline', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN tagline        varchar(255) DEFAULT NULL AFTER category");
    }
    if (!in_array('benefits', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN benefits       text         DEFAULT NULL AFTER tagline");
        // benefits stored as JSON array: ["Benefit 1","Benefit 2"]
    }
    if (!in_array('show_on_website', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN show_on_website tinyint(1)  DEFAULT 1 AFTER benefits");
    }
    if (!in_array('sort_order', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN sort_order     int(11)      DEFAULT 0 AFTER show_on_website");
    }

    // ── Seed website product data if all products have no category set ────────
    $uncategorised = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE category IS NULL")->fetchColumn();
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

    if ($totalProducts === 0) {
        // No products at all — insert the 6 defaults
        $webProducts = [
            [
                'name'     => 'Algovexa Fort Pain Oil',
                'desc'     => 'An Ayurvedic formulation developed to provide relief from muscle pain, joint discomfort, stiffness, and body aches.',
                'price'    => 0.00,
                'category' => 'Pain Relief',
                'tagline'  => 'Natural Pain Relief Solution',
                'benefits' => json_encode(['Helps relieve joint pain','Supports muscle relaxation','Reduces stiffness','Promotes better mobility','Suitable for daily massage']),
                'pkg'      => 'Oil',
                'sort'     => 1,
            ],
            [
                'name'     => 'Algovexa Fort Pain Capsule',
                'desc'     => 'A herbal capsule formulated to support joint health and overall musculoskeletal wellness for an active lifestyle.',
                'price'    => 0.00,
                'category' => 'Joint Support',
                'tagline'  => 'Ayurvedic Joint & Pain Support',
                'benefits' => json_encode(['Supports joint flexibility','Helps manage body pain naturally','Supports bone and muscle health','Promotes active lifestyle']),
                'pkg'      => 'Capsules',
                'sort'     => 2,
            ],
            [
                'name'     => 'Livazyme Plus Syrup',
                'desc'     => 'Designed to support healthy liver function and improve digestion, appetite, and metabolic wellness.',
                'price'    => 0.00,
                'category' => 'Liver Health',
                'tagline'  => 'Ayurvedic Liver & Digestive Tonic',
                'benefits' => json_encode(['Supports liver health','Helps improve appetite','Promotes digestion','Supports metabolic wellness','Helps maintain digestive comfort']),
                'pkg'      => 'Syrup',
                'sort'     => 3,
            ],
            [
                'name'     => 'Cofzyra Syrup',
                'desc'     => 'An Ayurvedic syrup formulated to support respiratory wellness and throat comfort.',
                'price'    => 0.00,
                'category' => 'Respiratory',
                'tagline'  => 'Herbal Cough & Respiratory Care',
                'benefits' => json_encode(['Soothes throat irritation','Supports respiratory health','Helps maintain clear breathing','Provides herbal respiratory support']),
                'pkg'      => 'Syrup',
                'sort'     => 4,
            ],
            [
                'name'     => 'Dygesryn Syrup',
                'desc'     => 'A herbal digestive syrup to support healthy digestion and gastrointestinal comfort.',
                'price'    => 0.00,
                'category' => 'Digestion',
                'tagline'  => 'Digestive Wellness Formula',
                'benefits' => json_encode(['Supports digestion','Helps reduce indigestion','Promotes gut comfort','Supports appetite']),
                'pkg'      => 'Syrup',
                'sort'     => 5,
            ],
            [
                'name'     => 'Femcyra Syrup',
                'desc'     => "An Ayurvedic formulation designed to support women's health, overall vitality, and natural hormonal balance.",
                'price'    => 0.00,
                'category' => "Women's Health",
                'tagline'  => "Women's Wellness Support",
                'benefits' => json_encode(["Supports women's wellness",'Helps maintain overall vitality','Supports hormonal balance naturally','Promotes healthy living']),
                'pkg'      => 'Syrup',
                'sort'     => 6,
            ],
        ];
        $ins = $pdo->prepare("INSERT INTO products (name, description, price, package_qty, category, tagline, benefits, show_on_website, sort_order)
                               VALUES (:name,:desc,:price,:pkg,:cat,:tag,:ben,1,:sort)");
        foreach ($webProducts as $wp) {
            $ins->execute([
                ':name'  => $wp['name'],  ':desc'  => $wp['desc'],
                ':price' => $wp['price'], ':pkg'   => $wp['pkg'],
                ':cat'   => $wp['category'], ':tag' => $wp['tagline'],
                ':ben'   => $wp['benefits'], ':sort' => $wp['sort'],
            ]);
        }
    } elseif ($uncategorised > 0) {
        // Products exist but have no website data — seed category/tagline/benefits by name match
        $seedMap = [
            'Algovexa Fort Pain Oil'      => ['Pain Relief',    'Natural Pain Relief Solution',         json_encode(['Helps relieve joint pain','Supports muscle relaxation','Reduces stiffness','Promotes better mobility','Suitable for daily massage']), 1],
            'Algovexa Fort Pain Capsule'  => ['Joint Support',  'Ayurvedic Joint & Pain Support',       json_encode(['Supports joint flexibility','Helps manage body pain naturally','Supports bone and muscle health','Promotes active lifestyle']), 2],
            'Livazyme Plus Syrup'         => ['Liver Health',   'Ayurvedic Liver & Digestive Tonic',    json_encode(['Supports liver health','Helps improve appetite','Promotes digestion','Supports metabolic wellness','Helps maintain digestive comfort']), 3],
            'Cofzyra Syrup'               => ['Respiratory',    'Herbal Cough & Respiratory Care',      json_encode(['Soothes throat irritation','Supports respiratory health','Helps maintain clear breathing','Provides herbal respiratory support']), 4],
            'Dygesryn Syrup'              => ['Digestion',      'Digestive Wellness Formula',           json_encode(['Supports digestion','Helps reduce indigestion','Promotes gut comfort','Supports appetite']), 5],
            'Femcyra Syrup'               => ["Women's Health", "Women's Wellness Support",             json_encode(["Supports women's wellness",'Helps maintain overall vitality','Supports hormonal balance naturally','Promotes healthy living']), 6],
        ];
        $upd = $pdo->prepare("UPDATE products SET category=?, tagline=?, benefits=?, sort_order=?, show_on_website=1 WHERE name=? AND category IS NULL");
        foreach ($seedMap as $name => [$cat, $tag, $ben, $sort]) {
            $upd->execute([$cat, $tag, $ben, $sort, $name]);
        }
    }

    // ── Load website_config into a flat associative array ────────────────────
    $configRows   = $pdo->query("SELECT config_key, config_val FROM website_config")->fetchAll();
    $SITE_CONFIG  = array_column($configRows, 'config_val', 'config_key');

    // ── Load all product images grouped by product_id ─────────────────────────
    $imgStmt   = $pdo->query("SELECT * FROM product_images ORDER BY sort_order ASC, id ASC");
    $imgByProd = [];
    foreach ($imgStmt->fetchAll() as $img) {
        $imgByProd[$img['product_id']][] = $img['filename'];
    }

    // ── Load active products for the public website ───────────────────────────
    $stmt    = $pdo->prepare("SELECT * FROM products WHERE show_on_website = 1 ORDER BY sort_order ASC, id ASC");
    $stmt->execute();
    $PRODUCTS = [];
    foreach ($stmt->fetchAll() as $i => $row) {
        $num      = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
        $benefits = [];
        if (!empty($row['benefits'])) {
            $decoded  = json_decode($row['benefits'], true);
            $benefits = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode("\n", $row['benefits'])));
        }
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($row['name'])));
        // Build full image URLs
        $images = [];
        foreach ($imgByProd[$row['id']] ?? [] as $filename) {
            $images[] = UPLOADS_URL . $filename;
        }
        $PRODUCTS[] = [
            'id'       => $slug,
            'db_id'    => $row['id'],
            'number'   => $num,
            'name'     => $row['name'],
            'type'     => $row['package_qty'] ?? '',
            'category' => $row['category']    ?? 'Ayurvedic',
            'tagline'  => $row['tagline']     ?? '',
            'desc'     => $row['description'] ?? '',
            'price'    => $row['price']       ?? 0,
            'benefits' => $benefits,
            'images'   => $images,  // array of full URLs, max 3
        ];
    }

} catch (PDOException $e) {
    // Graceful fallback — use static defaults so site never shows an error page
    $SITE_CONFIG = [];
    $PRODUCTS    = [];
    error_log('PraanVeda website DB error: ' . $e->getMessage());
}

// ── Helper: get config value with fallback ────────────────────────────────────
function siteConfig(string $key, string $fallback = ''): string {
    global $SITE_CONFIG;
    return $SITE_CONFIG[$key] ?? $fallback;
}

// ── Define convenient constants (used in header/footer templates) ─────────────
define('SITE_NAME',        siteConfig('site_name',       'PraanVeda AyurShakti'));
define('SITE_TAGLINE',     siteConfig('site_tagline',    'Ancient Ayurveda for Modern Wellness'));
define('CONTACT_ADDRESS',  siteConfig('contact_address', '22, Hemanta Basu Sarani, 4th Floor, Suite 4E, Kolkata - 700001'));
define('CONTACT_PHONE',    siteConfig('contact_phone',   '+91 90620 05055'));
define('CONTACT_EMAIL',    siteConfig('contact_email',   'care@praanveda.net'));
define('CONTACT_WEBSITE',  siteConfig('contact_website', 'www.praanveda.net'));
define('BUSINESS_HOURS',   siteConfig('business_hours',  'Monday – Saturday, 10:00 AM – 6:00 PM'));

date_default_timezone_set('Asia/Kolkata');
