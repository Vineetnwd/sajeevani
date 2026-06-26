<?php
// web/manage_enquiries.php — Manage Website Enquiries
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    header("Location: index.php");
    exit();
}

$successMsg = '';
$errorMsg   = '';

// ══════════════════════════════════════════════════════════════════════════════
// POST HANDLERS
// ══════════════════════════════════════════════════════════════════════════════

// 1. UPDATE Status ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $enquiryId = (int)$_POST['enquiry_id'];
    $status    = $_POST['status'];
    if (in_array($status, ['Pending', 'In Progress', 'Closed'])) {
        try {
            $stmt = $pdo->prepare("UPDATE website_enquiries SET status = ? WHERE id = ?");
            $stmt->execute([$status, $enquiryId]);
            $successMsg = 'Status updated successfully.';
        } catch (PDOException $e) {
            $errorMsg = 'Error updating status: ' . $e->getMessage();
        }
    }
}

// 2. ADD Follow-up Note ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_followup'])) {
    $enquiryId = (int)$_POST['enquiry_id'];
    $note      = trim($_POST['note'] ?? '');
    $addedBy   = $_SESSION['name'] ?? 'Admin';

    if (!empty($note)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO enquiry_followups (enquiry_id, note, added_by) VALUES (?, ?, ?)");
            $stmt->execute([$enquiryId, $note, $addedBy]);
            $successMsg = 'Follow-up note added successfully.';
        } catch (PDOException $e) {
            $errorMsg = 'Error adding note: ' . $e->getMessage();
        }
    }
}

// ── Fetch Enquiries & Follow-ups ──────────────────────────────────────────────
try {
    $search = trim($_GET['search'] ?? '');
    $where_clause = "1=1";
    $params = [];
    if ($search !== '') {
        $where_clause .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ?)";
        $params = array_fill(0, 4, "%$search%");
    }
    
    $stmt = $pdo->prepare("SELECT * FROM website_enquiries WHERE $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    $enquiries = $stmt->fetchAll();
    
    $followupsStmt = $pdo->query("SELECT * FROM enquiry_followups ORDER BY created_at ASC");
    $allFollowups  = [];
    foreach ($followupsStmt->fetchAll() as $f) {
        $allFollowups[$f['enquiry_id']][] = $f;
    }
} catch (PDOException $e) {
    $errorMsg = 'Database error: ' . $e->getMessage();
    $enquiries = [];
    $allFollowups = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Enquiries — <?php echo APP_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        <h1 class="text-xl font-bold text-gray-800">Enquiries</h1>
        <p class="text-xs text-gray-500 mt-0.5">Manage messages from the website contact form</p>
      </div>
    </div>
  </header>

  <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 relative">

    <?php $search = trim($_GET['search'] ?? ''); ?>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-4 justify-between items-center mb-4">
        <form method="GET" action="" class="flex w-full sm:w-auto gap-3 items-center">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Name, Email, Subject..." class="w-full sm:w-64 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm hover:bg-teal-700">Search</button>
            <?php if($search): ?>
                <a href="manage_enquiries.php" class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($successMsg): ?>
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm font-medium px-4 py-3 rounded-lg flex items-center gap-2 mb-4">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <?php echo htmlspecialchars($successMsg); ?>
    </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm font-medium px-4 py-3 rounded-lg mb-4">
      <?php echo htmlspecialchars($errorMsg); ?>
    </div>
    <?php endif; ?>

    <?php foreach ($enquiries as $enq):
        $eid       = $enq['id'];
        $followups = $allFollowups[$eid] ?? [];
        $status    = $enq['status'];
        
        $statusColor = 'bg-gray-100 text-gray-600';
        if ($status === 'Pending') $statusColor = 'bg-red-100 text-red-700';
        if ($status === 'In Progress') $statusColor = 'bg-yellow-100 text-yellow-700';
        if ($status === 'Closed') $statusColor = 'bg-green-100 text-green-700';
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" id="enq-card-<?php echo $eid; ?>">
      <!-- Accordion Header -->
      <div class="flex items-start justify-between px-4 sm:px-6 py-4 bg-gray-50 border-b border-gray-200 cursor-pointer select-none gap-3"
           onclick="toggleAccordion(<?php echo $eid; ?>)">
        <div class="flex items-start gap-3 sm:gap-4 min-w-0">
          <div class="w-10 h-10 rounded-full shrink-0 bg-teal-100 text-teal-700 flex items-center justify-center font-bold mt-0.5">
            <?php echo strtoupper(substr($enq['name'], 0, 1)); ?>
          </div>
          <div class="min-w-0">
            <div class="font-semibold text-gray-800 text-sm sm:text-base flex flex-col sm:flex-row sm:items-baseline sm:gap-2 leading-tight">
              <span><?php echo htmlspecialchars($enq['name']); ?></span>
              <span class="text-[10px] sm:text-xs font-normal text-gray-400 mt-0.5 sm:mt-0"><?php echo date('M j, Y g:i A', strtotime($enq['created_at'])); ?></span>
            </div>
            <div class="text-[11px] sm:text-xs text-gray-500 mt-1.5 flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-1 sm:gap-2">
              <?php if ($enq['subject']): ?>
                <span class="font-medium text-teal-600 block sm:inline"><?php echo htmlspecialchars($enq['subject']); ?></span>
                <span class="hidden sm:inline text-gray-300">&bull;</span>
              <?php endif; ?>
              <span class="block sm:inline break-all sm:break-normal"><?php echo htmlspecialchars($enq['email']); ?></span>
              <?php if ($enq['phone']): ?>
                <span class="hidden sm:inline text-gray-300">&bull;</span>
                <span class="block sm:inline"><?php echo htmlspecialchars($enq['phone']); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="flex items-start gap-2 sm:gap-3 shrink-0 mt-0.5">
          <span class="text-[10px] sm:text-xs font-semibold px-2 sm:px-2.5 py-1 rounded-full whitespace-nowrap <?php echo $statusColor; ?>">
            <?php echo $status; ?>
          </span>
          <svg id="chevron-<?php echo $eid; ?>" class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>
      </div>

      <!-- Accordion Body -->
      <div id="accordion-<?php echo $eid; ?>" class="hidden">
        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <!-- Left: Message & Status -->
          <div class="space-y-6">
            <div>
              <h3 class="text-sm font-bold text-gray-800 mb-2 border-b border-gray-100 pb-2">Message</h3>
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($enq['message']); ?></div>
            </div>

            <div>
              <h3 class="text-sm font-bold text-gray-800 mb-2 border-b border-gray-100 pb-2">Update Status</h3>
              <form method="POST" class="flex gap-2">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="enquiry_id" value="<?php echo $eid; ?>">
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-200 outline-none flex-1">
                  <option value="Pending" <?php if($status==='Pending') echo 'selected'; ?>>Pending</option>
                  <option value="In Progress" <?php if($status==='In Progress') echo 'selected'; ?>>In Progress</option>
                  <option value="Closed" <?php if($status==='Closed') echo 'selected'; ?>>Closed</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg transition-colors">
                  Save
                </button>
              </form>
            </div>
          </div>

          <!-- Right: Follow-ups -->
          <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 flex flex-col h-[350px]">
            <h3 class="text-sm font-bold text-gray-800 mb-3 border-b border-gray-200 pb-2">Internal Follow-ups</h3>
            
            <div class="flex-1 overflow-y-auto space-y-3 mb-3 pr-2">
              <?php if (empty($followups)): ?>
                <div class="text-sm text-gray-400 italic text-center py-4">No follow-ups recorded yet.</div>
              <?php endif; ?>
              
              <?php foreach ($followups as $f): ?>
                <div class="bg-white p-3 rounded border border-gray-200 shadow-sm text-sm">
                  <div class="flex justify-between items-start mb-1">
                    <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($f['added_by']); ?></span>
                    <span class="text-xs text-gray-400"><?php echo date('M j, g:i A', strtotime($f['created_at'])); ?></span>
                  </div>
                  <div class="text-gray-600 whitespace-pre-wrap"><?php echo htmlspecialchars($f['note']); ?></div>
                </div>
              <?php endforeach; ?>
            </div>

            <form method="POST" class="mt-auto">
              <input type="hidden" name="add_followup" value="1">
              <input type="hidden" name="enquiry_id" value="<?php echo $eid; ?>">
              <textarea name="note" required rows="2" placeholder="Type a follow-up note..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-200 outline-none mb-2 resize-none"></textarea>
              <button type="submit" class="w-full px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Add Note
              </button>
            </form>
          </div>

        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($enquiries)): ?>
    <div class="text-center py-16">
      <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
      <h3 class="text-lg font-medium text-gray-900">No enquiries yet</h3>
      <p class="mt-1 text-sm text-gray-500">When someone submits the contact form, it will appear here.</p>
    </div>
    <?php endif; ?>

  </div>
</main>

<script>
function toggleAccordion(eid) {
  var body = document.getElementById('accordion-' + eid);
  var icon = document.getElementById('chevron-' + eid);
  var isHidden = body.classList.toggle('hidden');
  icon.style.transform = isHidden ? '' : 'rotate(180deg)';
}
</script>

</body>
</html>
