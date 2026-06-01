<?php
require_once 'config.php';
// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-fx {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen relative overflow-hidden">
    
    <!-- Dynamic Background Aesthetics -->
    <div class="absolute top-0 left-0 w-full h-full z-0 overflow-hidden">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-green-500 opacity-20 blur-3xl mix-blend-multiply animate-blob"></div>
        <div class="absolute top-32 -right-32 w-96 h-96 rounded-full bg-teal-500 opacity-20 blur-3xl mix-blend-multiply animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-32 left-1/2 w-96 h-96 rounded-full bg-indigo-500 opacity-20 blur-3xl mix-blend-multiply animate-blob animation-delay-4000"></div>
    </div>

    <div class="z-10 w-full max-w-md p-8 bg-white/70 glass-fx rounded-2xl shadow-xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Praanveda Ayurshakti</h1>
            <p class="text-sm text-gray-600 mt-2">Ayurvedic Platform Management</p>
        </div>

        <form id="loginForm" class="space-y-6">
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone or User ID</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <input type="text" id="phone" name="phone" required class="block w-full pl-3 pr-3 py-2 sm:text-sm border-gray-300 rounded-md border focus:ring-green-500 focus:border-green-500" placeholder="admin">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <input type="password" id="password" name="password" required class="block w-full pl-3 pr-3 py-2 sm:text-sm border-gray-300 rounded-md border focus:ring-green-500 focus:border-green-500" placeholder="••••••••">
                </div>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-lg shadow-green-500/30 transition-all duration-300">
                    Sign In
                </button>
            </div>
        </form>

        <!-- Environment Indicator -->
        <div class="mt-6 text-center text-xs text-gray-500">
            System Status: 
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                DB Connected (<?php echo $is_local ? 'Local' : 'Live'; ?>)
            </span>
        </div>
        
        <div id="loginMessage" class="mt-4 text-center text-sm hidden"></div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('phone', document.getElementById('phone').value);
            formData.append('password', document.getElementById('password').value);
            
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = 'Signing in...';
            btn.disabled = true;

            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                const msgEl = document.getElementById('loginMessage');
                msgEl.className = `mt-4 text-center text-sm font-medium p-2 rounded ${data.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
                msgEl.textContent = data.message;
                msgEl.style.display = 'block';

                if(data.status === 'success') {
                    // Redirect based on role
                    setTimeout(() => {
                        window.location.href = "dashboard.php";
                    }, 500);
                } else {
                    btn.innerHTML = 'Sign In';
                    btn.disabled = false;
                }
            } catch(error) {
                console.error('Error:', error);
                btn.innerHTML = 'Sign In';
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
