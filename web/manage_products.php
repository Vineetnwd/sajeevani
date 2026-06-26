<?php
// web/manage_products.php — Comprehensive Product Management (Basic Info, Website Data, Photos)
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
  header("Location: index.php");
  exit();
}

$successMsg = $_GET['success'] ?? '';
$errorMsg = $_GET['error'] ?? '';

// ── Upload directory ──────────────────────────────────────────────────────────
$uploadDir = __DIR__ . '/uploads/products/';
$uploadDirRel = str_replace($_SERVER['DOCUMENT_ROOT'], '', $uploadDir);
$uploadDirRel = '/' . ltrim($uploadDirRel, '/');
if (!is_dir($uploadDir))
  mkdir($uploadDir, 0755, true);

$allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxFileSize = 3 * 1024 * 1024; // 3 MB
$maxPerProduct = 3;

// ══════════════════════════════════════════════════════════════════════════════
// POST HANDLERS
// ══════════════════════════════════════════════════════════════════════════════



// 2. DELETE Product ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
  $pid = (int) $_POST['product_id'];
  try {
    // Delete associated images from disk
    $imgs = $pdo->prepare("SELECT filename FROM product_images WHERE product_id=?");
    $imgs->execute([$pid]);
    foreach ($imgs->fetchAll() as $img) {
      $filePath = $uploadDir . $img['filename'];
      if (file_exists($filePath))
        unlink($filePath);
    }
    // DB cascade will delete rows in product_images
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$pid]);
    $successMsg = 'Product deleted successfully.';
  } catch (PDOException $e) {
    $errorMsg = 'Error deleting product: ' . $e->getMessage();
  }
}

// 3. SAVE Product Details (Basic + Website) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
  try {
    $pid = (int) $_POST['product_id'];
    $name = trim($_POST['name'] ?? '');
    $pkg = trim($_POST['package_qty'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');

    $category = trim($_POST['category'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $sort = (int) ($_POST['sort_order'] ?? 0);
    $showOnWeb = isset($_POST['show_on_website']) ? 1 : 0;

    $benefitsRaw = trim($_POST['benefits'] ?? '');
    $benefitsArr = array_filter(array_map('trim', explode("\n", $benefitsRaw)));
    $benefitsJson = json_encode(array_values($benefitsArr));

    if (empty($name)) {
      $errorMsg = 'Product name is required.';
    } else {
      $stmt = $pdo->prepare("UPDATE products 
                                   SET name=?, package_qty=?, price=?, description=?, 
                                       category=?, tagline=?, benefits=?, show_on_website=?, sort_order=? 
                                   WHERE id=?");
      $stmt->execute([$name, $pkg, $price, $desc, $category, $tagline, $benefitsJson, $showOnWeb, $sort, $pid]);
      $successMsg = 'Product updated successfully.';
    }
  } catch (PDOException $e) {
    $errorMsg = 'Error updating product: ' . $e->getMessage();
  }
}

// 4. UPLOAD Product Images ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images'])) {
  $pid = (int) ($_POST['product_id'] ?? 0);
  if ($pid > 0 && !empty($_FILES['product_images']['name'][0])) {
    try {
      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id=?");
      $countStmt->execute([$pid]);
      $existingCount = (int) $countStmt->fetchColumn();

      $files = $_FILES['product_images'];
      $uploaded = 0;
      $errors = [];

      foreach ($files['tmp_name'] as $i => $tmp) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK)
          continue;
        if ($existingCount + $uploaded >= $maxPerProduct) {
          $errors[] = "Maximum {$maxPerProduct} photos allowed.";
          break;
        }
        if ($files['size'][$i] > $maxFileSize) {
          $errors[] = htmlspecialchars($files['name'][$i]) . ' exceeds 3 MB limit.';
          continue;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedMime)) {
          $errors[] = htmlspecialchars($files['name'][$i]) . ' is not a valid image type.';
          continue;
        }
        $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $filename = 'product_' . $pid . '_' . uniqid() . '.' . strtolower($ext);
        if (move_uploaded_file($tmp, $uploadDir . $filename)) {
          $ins = $pdo->prepare("INSERT INTO product_images (product_id, filename, sort_order) VALUES (?,?,?)");
          $ins->execute([$pid, $filename, $existingCount + $uploaded]);
          $uploaded++;
        }
      }
      if ($uploaded > 0)
        $successMsg = "{$uploaded} photo(s) uploaded.";
      if ($errors)
        $errorMsg = implode(' ', $errors);
    } catch (PDOException $e) {
      $errorMsg = 'Upload error: ' . $e->getMessage();
    }
  }
}

// 5. DELETE Image ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
  $imgId = (int) $_POST['image_id'];
  try {
    $row = $pdo->prepare("SELECT filename FROM product_images WHERE id=?");
    $row->execute([$imgId]);
    $img = $row->fetch();
    if ($img) {
      $filePath = $uploadDir . $img['filename'];
      if (file_exists($filePath))
        unlink($filePath);
      $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$imgId]);
      $successMsg = 'Photo deleted.';
    }
  } catch (PDOException $e) {
    $errorMsg = 'Delete error: ' . $e->getMessage();
  }
}

// ── Fetch Products & Images ───────────────────────────────────────────────────
$products = $pdo->query("SELECT * FROM products ORDER BY sort_order ASC, id ASC")->fetchAll();

$allImagesStmt = $pdo->query("SELECT * FROM product_images ORDER BY sort_order ASC, id ASC");
$allImages = [];
foreach ($allImagesStmt->fetchAll() as $img) {
  $allImages[$img['product_id']][] = $img;
}

$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$imgBase = $proto . '://' . $host . rtrim($uploadDirRel, '/') . '/';

// Public site URL
$webDirRel = str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__);
$parentRel = str_replace('/web', '', rtrim($webDirRel, '/'));
$publicSiteUrl = $proto . '://' . $host . $parentRel . '/praanveda/products.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Products — <?php echo APP_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }

    .img-slot {
      position: relative;
      width: 100px;
      height: 100px;
      border-radius: 8px;
      overflow: hidden;
      border: 1.5px solid #e5e7eb;
      background: #f9fafb;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .img-slot img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .img-slot .del-btn {
      position: absolute;
      top: 4px;
      right: 4px;
      width: 22px;
      height: 22px;
      background: rgba(239, 68, 68, 0.9);
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 13px;
      font-weight: 700;
      line-height: 1;
      border: none;
      transition: background 0.15s;
    }

    .img-slot .del-btn:hover {
      background: #dc2626;
    }

    .upload-zone {
      border: 2px dashed #d1d5db;
      border-radius: 10px;
      padding: 16px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }

    .upload-zone:hover,
    .upload-zone.drag-over {
      border-color: #0d9488;
      background: #f0fdfa;
    }

    .preview-thumb {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 6px;
      border: 1.5px solid #d1d5db;
    }
  </style>
  <link rel="stylesheet" href="admin-style.css">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

  <?php include 'sidebar.php'; ?>

  <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">

    <!-- Header -->
    <header
      class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
      <div class="flex items-center gap-3">
        <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 mr-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <div>
          <h1 class="text-xl font-bold text-gray-800">Product Catalog</h1>
          <p class="text-xs text-gray-500 mt-0.5">Manage product info, photos, and website display</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <input type="text" id="searchInput" oninput="filterProducts()" placeholder="Search Products..."
          class="hidden sm:block text-sm border border-gray-200 rounded-lg px-2 py-1.5 sm:px-3 sm:py-2 focus:ring-2 focus:ring-teal-100 outline-none w-32 sm:w-48 transition-all duration-300 focus:w-64">
        <a href="add_product.php"
          class="text-sm px-4 py-2 border border-teal-600 text-teal-700 hover:bg-teal-50 font-semibold rounded-lg shadow-sm transition-colors whitespace-nowrap">
          + Add Product
        </a>
        <a href="<?php echo htmlspecialchars($publicSiteUrl); ?>" target="_blank"
          class="text-sm px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm transition-colors hidden sm:block">
          View Public Site
        </a>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 relative">

      <?php if ($successMsg): ?>
        <div
          class="bg-teal-50 border border-teal-200 text-teal-800 text-sm font-medium px-4 py-3 rounded-lg flex items-center gap-2 mb-4">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <?php echo htmlspecialchars($successMsg); ?>
        </div>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm font-medium px-4 py-3 rounded-lg mb-4">
          <?php echo htmlspecialchars($errorMsg); ?>
        </div>
      <?php endif; ?>

      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="overflow-x-auto w-full">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product
                </th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Price /
                  Size</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($products as $prod):
                $pid = $prod['id'];
                $prodImages = $allImages[$pid] ?? [];
                $imgCount = count($prodImages);
                ?>
                <tr class="hover:bg-gray-50 transition-colors product-row"
                  data-name="<?php echo htmlspecialchars($prod['name']); ?>">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div
                        class="flex-shrink-0 h-10 w-10 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border border-gray-200">
                        <?php if (!empty($prodImages)): ?>
                          <img class="h-10 w-10 object-cover"
                            src="<?php echo htmlspecialchars($imgBase . $prodImages[0]['filename']); ?>" alt="">
                        <?php else: ?>
                          <span class="text-xs font-medium text-gray-400">No Img</span>
                        <?php endif; ?>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($prod['name']); ?></div>
                        <div class="text-xs text-gray-500">
                          <?php echo htmlspecialchars($prod['category'] ?: 'Uncategorized'); ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 font-semibold">₹<?php echo number_format($prod['price'], 2); ?>
                    </div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($prod['package_qty'] ?: '-'); ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php if ($prod['show_on_website']): ?>
                      <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800 border border-teal-200">Visible</span>
                    <?php else: ?>
                      <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">Hidden</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="edit_product.php?id=<?php echo $pid; ?>"
                      class="text-teal-600 hover:text-teal-900 mr-4 font-semibold">Edit</a>
                    <button onclick="deleteProduct(<?php echo $pid; ?>)"
                      class="text-red-600 hover:text-red-900 font-semibold">Delete</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if (empty($products)): ?>
        <div class="text-center py-16">
          <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <h3 class="text-lg font-medium text-gray-900">No products yet</h3>
          <p class="mt-1 text-sm text-gray-500">Get started by creating your first product.</p>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <!-- Hidden forms for delete actions -->
  <form id="delete-img-form" method="POST" style="display:none">
    <input type="hidden" name="delete_image" value="1">
    <input type="hidden" name="image_id" id="delete-img-id">
  </form>

  <form id="delete-prod-form" method="POST" style="display:none">
    <input type="hidden" name="delete_product" value="1">
    <input type="hidden" name="product_id" id="delete-prod-id">
  </form>

  <script>
    function filterProducts() {
      const search = document.getElementById('searchInput') ? document.getElementById('searchInput').value.toLowerCase() : '';
      const rows = document.querySelectorAll('.product-row');

      rows.forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        if (!search || name.includes(search)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    function deleteProduct(pid) {
      if (confirm('Are you sure you want to delete this entire product? This action cannot be undone.')) {
        document.getElementById('delete-prod-id').value = pid;
        document.getElementById('delete-prod-form').submit();
      }
    }
  </script>

</body>

</html>