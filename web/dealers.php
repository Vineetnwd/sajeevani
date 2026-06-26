<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manufacturers - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Tom Select UI Improvements to match Tailwind */
        .ts-wrapper {
            padding: 0 !important;
            border: none !important;
        }

        .ts-control {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            /* matching rounded-md in stockists */
            padding: 0.5rem 0.75rem !important;
            /* matching p-2 */
            font-size: 0.875rem !important;
            /* text-sm */
            box-shadow: none !important;
            background-color: white !important;
            min-height: 38px !important;
            display: flex;
            align-items: center;
        }

        .ts-control.focus {
            border-color: #5eead4 !important;
            /* focus:ring-teal-500 */
            box-shadow: 0 0 0 2px #ccfbf1 !important;
            outline: none !important;
        }

        .ts-dropdown {
            border-radius: 0.375rem !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            font-size: 0.875rem !important;
            overflow: hidden;
            margin-top: 4px;
        }

        .ts-dropdown .option {
            padding: 0.5rem 1rem !important;
            transition: background-color 0.1s ease;
        }

        .ts-dropdown .active {
            background-color: #f3f4f6 !important;
            color: #111827 !important;
        }
    </style>
    <link rel="stylesheet" href="admin-style.css">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200 z-10 px-4 py-3 sm:px-6 sm:py-4 flex justify-between items-center sticky top-0">
    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
        <button onclick="toggleMobileSidebar()" class="block lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none shrink-0 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <div class="min-w-0">
            <h1 class="text-lg sm:text-xl truncate font-bold text-gray-800">Manufacturers</h1>
        </div>
    </div>
    <div class="flex items-center space-x-3 sm:space-x-4">
        <input type="text" id="searchInput" oninput="filterDealers()" placeholder="Search Manufacturers..." class="hidden sm:block text-sm border border-gray-200 rounded-lg px-2 py-1.5 sm:px-3 sm:py-2 focus:ring-2 focus:ring-teal-100 outline-none w-32 sm:w-48 transition-all duration-300 focus:w-64">
        <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg shadow-sm whitespace-nowrap">
            + <span class="hidden sm:inline">Add Manufacturer</span>
        </button>
    </div>
</header>
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
<table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Manufacturer</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Address</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT u.id, u.name, u.phone, u.email, u.address, u.gst_no, u.state_id, u.district_id, u.block_id, s.name as state_name, d.name as district_name, b.name as block_name FROM dealers u LEFT JOIN states s ON u.state_id = s.id LEFT JOIN districts d ON u.district_id = d.id LEFT JOIN blocks b ON u.block_id = b.id ORDER BY u.name ASC");
                            $dealers = $stmt->fetchAll();

                            if (count($dealers) == 0) {
                                echo '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No manufacturers have been added yet.</td></tr>';
                            }

                            foreach ($dealers as $d) {
                                echo '<tr class="hover:bg-gray-50 dealer-row" data-name="' . htmlspecialchars($d['name']) . '">';
                                echo '<td class="px-6 py-4 whitespace-nowrap">';
                                echo '<div class="font-bold text-gray-900">' . htmlspecialchars($d['name']) . '</div>';
                                if ($d['gst_no']) {
                                    echo '<div class="text-xs sm:text-[10px] text-gray-500">GST: ' . htmlspecialchars($d['gst_no']) . '</div>';
                                }
                                echo '</td>';

                                echo '<td class="px-6 py-4 whitespace-nowrap">';
                                echo '<div class="text-sm text-gray-900">' . htmlspecialchars($d['phone']) . '</div>';
                                echo '<div class="text-xs text-gray-500">' . htmlspecialchars($d['email'] ?? 'No email') . '</div>';
                                echo '</td>';

                                echo '<td class="px-6 py-4 whitespace-nowrap">';
                                echo '<div class="text-xs text-gray-500 truncate max-w-xs">' . htmlspecialchars($d['address'] ?? '') . '</div>';
                                if ($d['state_name']) {
                                    $loc = [];
                                    if ($d['block_name'])
                                        $loc[] = $d['block_name'];
                                    if ($d['district_name'])
                                        $loc[] = $d['district_name'];
                                    if ($d['state_name'])
                                        $loc[] = $d['state_name'];
                                    echo '<div class="text-xs sm:text-[10px] text-gray-400 mt-1">' . htmlspecialchars(implode(', ', $loc)) . '</div>';
                                }
                                echo '</td>';

                                echo '<td class="px-6 py-4 text-right whitespace-nowrap">';
                                echo '<button onclick=\'openEditModal(' . htmlspecialchars(json_encode([
                                    "id" => $d["id"],
                                    "name" => $d["name"],
                                    "phone" => $d["phone"],
                                    "email" => $d["email"],
                                    "address" => $d["address"],
                                    "gst_no" => $d["gst_no"],
                                    "state_id" => $d["state_id"],
                                    "district_id" => $d["district_id"],
                                    "block_id" => $d["block_id"]
                                ]), ENT_QUOTES, "UTF-8") . ')\' class="text-teal-600 hover:text-teal-900 font-medium text-sm">Edit</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } catch (PDOException $e) {
                        }
                        ?>
                    </tbody>
                </table>
</div>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-base font-bold text-gray-800">Add a Manufacturer</h3>
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <form id="dealerForm" class="p-6">
                <input type="hidden" name="action" value="add_dealer">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Company / Manufacturer Name
                                *</label>
                            <input type="text" name="name" required
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Contact Phone</label>
                            <input type="text" name="phone"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                            <input type="email" name="email"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">GST No. / PAN No.</label>
                            <input type="text" name="gst_no"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Full Address</label>
                        <textarea name="address" rows="2"
                            class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500"></textarea>
                    </div>

                    <div class="hidden grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">State</label>
                            <select name="state_id" id="add_state" onchange="fetchDistricts(this.value, 'add_district')"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none bg-white">
                                <option value="">-- State --</option>
                                <?php
                                try {
                                    $states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll();
                                    foreach ($states as $s)
                                        echo '<option value="' . $s['id'] . '">' . htmlspecialchars($s['name']) . '</option>';
                                } catch (Exception $e) {
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">District</label>
                            <select name="district_id" id="add_district" onchange="fetchBlocks(this.value, 'add_block')"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none bg-white">
                                <option value="">-- District --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Block</label>
                            <select name="block_id" id="add_block"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none bg-white">
                                <option value="">-- Block --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 bg-teal-600 text-white font-medium rounded-md text-sm shadow-sm hover:bg-teal-700">Save
                        Manufacturer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal"
        class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-base font-bold text-gray-800">Edit Manufacturer</h3>
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <form id="editDealerForm" class="p-6">
                <input type="hidden" name="action" value="edit_dealer">
                <input type="hidden" name="dealer_id" id="edit_id">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Company / Manufacturer Name
                                *</label>
                            <input type="text" name="name" id="edit_name" required
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Contact Phone</label>
                            <input type="text" name="phone" id="edit_phone"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                            <input type="email" name="email" id="edit_email"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">GST No. / PAN No.</label>
                            <input type="text" name="gst_no" id="edit_gst_no"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Full Address</label>
                        <textarea name="address" id="edit_address" rows="2"
                            class="w-full border border-gray-300 p-2 rounded text-sm outline-none focus:ring-1 focus:ring-teal-500"></textarea>
                    </div>

                    <div class="hidden grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">State</label>
                            <select name="state_id" id="edit_state"
                                onchange="fetchDistricts(this.value, 'edit_district')"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none bg-white">
                                <option value="">-- State --</option>
                                <?php
                                try {
                                    $states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll();
                                    foreach ($states as $s)
                                        echo '<option value="' . $s['id'] . '">' . htmlspecialchars($s['name']) . '</option>';
                                } catch (Exception $e) {
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">District</label>
                            <select name="district_id" id="edit_district"
                                onchange="fetchBlocks(this.value, 'edit_block')"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none bg-white">
                                <option value="">-- District --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Block</label>
                            <select name="block_id" id="edit_block"
                                class="w-full border border-gray-300 p-2 rounded text-sm outline-none bg-white">
                                <option value="">-- Block --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 bg-teal-600 text-white font-medium rounded-md text-sm shadow-sm hover:bg-teal-700">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('dealerForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = "Saving...";
            try {
                const formData = new FormData(this);
                const response = await fetch('api/inventory.php?action=add_dealer', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                    btn.innerHTML = "Save Dealer";
                }
            } catch (e) {
                alert('Connection error');
                btn.innerHTML = "Save Dealer";
            }
        });

        document.getElementById('editDealerForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = "Saving...";
            try {
                const formData = new FormData(this);
                const response = await fetch('api/inventory.php?action=edit_dealer', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                    btn.innerHTML = "Save Changes";
                }
            } catch (e) {
                alert('Connection error');
                btn.innerHTML = "Save Changes";
            }
        });

        function openEditModal(d) {
            document.getElementById('edit_id').value = d.id;
            document.getElementById('edit_name').value = d.name;
            document.getElementById('edit_phone').value = d.phone;
            document.getElementById('edit_email').value = d.email || '';
            document.getElementById('edit_address').value = d.address || '';
            document.getElementById('edit_gst_no').value = d.gst_no || '';

            document.getElementById('edit_state').value = d.state_id || '';
            if (document.getElementById('edit_state').tomselect) {
                document.getElementById('edit_state').tomselect.setValue(d.state_id || '');
            }

            if (d.state_id) {
                fetchDistricts(d.state_id, 'edit_district', d.district_id, function () {
                    if (d.district_id) {
                        fetchBlocks(d.district_id, 'edit_block', d.block_id);
                    } else {
                        updateSelectOptions('edit_block', [], '-- Block --');
                    }
                });
            } else {
                updateSelectOptions('edit_district', [], '-- District --');
                updateSelectOptions('edit_block', [], '-- Block --');
            }

            document.getElementById('editModal').classList.remove('hidden');
        }

        function updateSelectOptions(targetId, dataList, placeholder, selectedId = null) {
            const target = document.getElementById(targetId);
            const ts = target.tomselect;

            if (ts) {
                ts.clear(true);
                ts.clearOptions();
                ts.addOption({ value: '', text: placeholder });
                dataList.forEach(item => {
                    ts.addOption({ value: item.id, text: item.name });
                });
                ts.setValue(selectedId || '', true);
            } else {
                target.innerHTML = `<option value="">${placeholder}</option>`;
                dataList.forEach(item => {
                    const selected = item.id == selectedId ? 'selected' : '';
                    target.innerHTML += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                });
            }
        }

        function filterDealers() {
            const search = document.getElementById('searchInput') ? document.getElementById('searchInput').value.toLowerCase() : '';
            const rows = document.querySelectorAll('.dealer-row');
            
            rows.forEach(row => {
                const name = (row.dataset.name || '').toLowerCase();
                if (!search || name.includes(search)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function fetchDistricts(stateId, targetId, selectedId = null, callback = null) {
            const target = document.getElementById(targetId);
            const blockTargetId = targetId === 'add_district' ? 'add_block' : 'edit_block';
            updateSelectOptions(blockTargetId, [], '-- Block --');

            if (!stateId) {
                updateSelectOptions(targetId, [], '-- District --');
                return;
            }

            if (target.tomselect) {
                target.tomselect.clearOptions();
                target.tomselect.addOption({ value: '', text: 'Loading...' });
                target.tomselect.setValue('');
            } else {
                target.innerHTML = '<option value="">Loading...</option>';
            }

            fetch(`mrs.php?action=get_districts&state_id=${stateId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateSelectOptions(targetId, data.data, '-- District --', selectedId);
                    } else {
                        updateSelectOptions(targetId, [], '-- District --');
                    }
                    if (callback) callback();
                });
        }

        function fetchBlocks(districtId, targetId, selectedId = null) {
            const target = document.getElementById(targetId);

            if (!districtId) {
                updateSelectOptions(targetId, [], '-- Block --');
                return;
            }

            if (target.tomselect) {
                target.tomselect.clearOptions();
                target.tomselect.addOption({ value: '', text: 'Loading...' });
                target.tomselect.setValue('');
            } else {
                target.innerHTML = '<option value="">Loading...</option>';
            }

            fetch(`mrs.php?action=get_blocks&district_id=${districtId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateSelectOptions(targetId, data.data, '-- Block --', selectedId);
                    } else {
                        updateSelectOptions(targetId, [], '-- Block --');
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const tsConfig = {
                create: false,
                sortField: { field: "text", direction: "asc" }
            };
            document.querySelectorAll('select').forEach((el) => {
                new TomSelect(el, tsConfig);
            });
        });
    </script>
</body>

</html>