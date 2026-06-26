<?php
// web/add_product.php — Dedicated page to add a product
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    header("Location: index.php");
    exit();
}

$errorMsg   = '';
$successMsg = '';

// ── Upload directory ──────────────────────────────────────────────────────────
$uploadDir = __DIR__ . '/uploads/products/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxFileSize = 3 * 1024 * 1024; // 3 MB

// ══════════════════════════════════════════════════════════════════════════════
// POST HANDLER
// ══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    try {
        $name         = trim($_POST['name'] ?? '');
        $pkg          = trim($_POST['package_qty'] ?? '');
        $price        = (float)($_POST['price'] ?? 0);
        $desc         = trim($_POST['description'] ?? '');
        
        $category     = trim($_POST['category'] ?? '');
        $tagline      = trim($_POST['tagline'] ?? '');
        $sort         = (int)($_POST['sort_order'] ?? 0);
        $showOnWeb    = isset($_POST['show_on_website']) ? 1 : 0;
        
        $benefitsRaw  = trim($_POST['benefits'] ?? '');
        $benefitsArr  = array_filter(array_map('trim', explode("\n", $benefitsRaw)));
        $benefitsJson = json_encode(array_values($benefitsArr));

        if (empty($name)) {
            $errorMsg = 'Product name is required.';
        } else {
            // Insert Product
            $stmt = $pdo->prepare("INSERT INTO products 
                                   (name, package_qty, price, description, category, tagline, benefits, show_on_website, sort_order) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $pkg, $price, $desc, $category, $tagline, $benefitsJson, $showOnWeb, $sort]);
            $newProductId = $pdo->lastInsertId();

            // Handle Image Uploads
            if (isset($_FILES['photos'])) {
                $files = $_FILES['photos'];
                $count = count($files['name']);
                
                // Get max sort_order
                $sortStmt = $pdo->prepare("SELECT MAX(sort_order) FROM product_images WHERE product_id=?");
                $sortStmt->execute([$newProductId]);
                $maxSort = (int)$sortStmt->fetchColumn();

                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName  = $files['tmp_name'][$i];
                        $size     = $files['size'][$i];
                        $mime     = mime_content_type($tmpName);
                        $origName = basename($files['name'][$i]);

                        if (!in_array($mime, $allowedMime)) {
                            $errorMsg .= "File '$origName' rejected: invalid type. ";
                            continue;
                        }
                        if ($size > $maxFileSize) {
                            $errorMsg .= "File '$origName' rejected: too large. ";
                            continue;
                        }

                        $ext = pathinfo($origName, PATHINFO_EXTENSION);
                        if (empty($ext)) $ext = 'jpg';
                        $newName = "prod_{$newProductId}_" . time() . "_" . uniqid() . "." . strtolower($ext);
                        $dest    = $uploadDir . $newName;

                        if (move_uploaded_file($tmpName, $dest)) {
                            $maxSort++;
                            $ins = $pdo->prepare("INSERT INTO product_images (product_id, filename, sort_order) VALUES (?, ?, ?)");
                            $ins->execute([$newProductId, $newName, $maxSort]);
                        } else {
                            $errorMsg .= "Failed to save '$origName'. ";
                        }
                    }
                }
            }

            if (empty($errorMsg)) {
                // Redirect back to catalog
                header("Location: manage_products.php?success=Product added successfully");
                exit();
            }
        }
    } catch (PDOException $e) {
        $errorMsg = 'Error saving product: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Product — <?php echo APP_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Summernote Lite -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
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
        <h1 class="text-xl font-bold text-gray-800">Add New Product</h1>
        <p class="text-xs text-gray-500 mt-0.5">Fill in the details to create a new product</p>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <a href="manage_products.php" class="text-sm px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-100 font-semibold rounded-lg shadow-sm transition-colors">
        Cancel
      </a>
    </div>
  </header>

  <div class="flex-1 overflow-y-auto p-4 sm:p-6">
    <?php if ($errorMsg): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm font-medium px-4 py-3 rounded-lg mb-6">
      <?php echo htmlspecialchars($errorMsg); ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-4xl mx-auto">
      <form method="POST" enctype="multipart/form-data" class="p-6 sm:p-8">
        
        <!-- Section: Basic Information -->
        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Basic Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Product Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Package/Size</label>
            <input type="text" name="package_qty" placeholder="e.g. 100 ML or 60 Caps"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Price (₹)</label>
            <input type="number" step="0.01" name="price" placeholder="0.00"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
          </div>
          <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Description</label>
            <textarea name="description" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm"></textarea>
          </div>
        </div>

        <!-- Section: Website Display Data -->
        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Website Display Data</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Category (Tag)</label>
            <input type="text" name="category" placeholder="e.g. Cough Care"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Tagline (Subtitle)</label>
            <input type="text" name="tagline" placeholder="Short compelling tagline"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Sort Order</label>
            <input type="number" name="sort_order" value="0"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
          </div>
          <div class="flex items-center pt-6">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-800 cursor-pointer">
              <input type="checkbox" name="show_on_website" value="1" checked
                     class="w-4 h-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
              Show on Website
            </label>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Benefits (One per line)</label>
            <textarea name="benefits" rows="4" placeholder="Supports immunity&#10;Relieves cough"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm"></textarea>
          </div>
        </div>

        <!-- Section: Product Photos -->
        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Product Photos</h2>
        <div class="mb-8">
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Upload Images (Max 3, Max 3MB each)</label>
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition-colors">
            <input type="file" name="photos[]" multiple accept="image/jpeg, image/png, image/webp, image/gif" 
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
            <p class="mt-2 text-xs text-gray-500">Allowed: JPG, PNG, WEBP, GIF.</p>
          </div>
        </div>

        <!-- Submit -->
        <div class="pt-6 border-t border-gray-200 flex justify-end">
          <button type="submit" name="save_product"
                  class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm transition-colors text-sm">
            Save Product
          </button>
        </div>

      </form>
    </div>
    
  </div>
</main>

<script>
$(document).ready(function() {
    $('textarea[name="description"]').summernote({
        placeholder: 'Enter product description...',
        tabsize: 2,
        height: 200,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'underline', 'clear']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['table', ['table']],
          ['insert', ['link', 'picture']],
          ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
});
</script>

</body>
</html>
