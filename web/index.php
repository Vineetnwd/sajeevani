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
    <link rel="icon" href="favicon.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .login-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(249, 250, 251, 1) 100%);
        }
        .input-field {
            transition: all 0.2s ease-in-out;
        }
        .input-field:focus {
            box-shadow: 0 0 0 4px rgba(26, 92, 46, 0.12);
        }
        .btn-primary {
            background: linear-gradient(135deg, #1a5c2e 0%, #2d7a44 100%);
            transition: all 0.2s ease-in-out;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(26, 92, 46, 0.35);
            background: linear-gradient(135deg, #154d26 0%, #256637 100%);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .brand-accent {
            color: #f4900c;
        }
        .logo-wrapper {
            background: linear-gradient(135deg, #f0faf4 0%, #fef8ee 100%);
            border: 1px solid #d1e9da;
        }
        .corner-accent-1 {
            background: radial-gradient(circle, #d1e9da 0%, transparent 70%);
        }
        .corner-accent-2 {
            background: radial-gradient(circle, #fde8c3 0%, transparent 70%);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen relative overflow-hidden px-4 sm:px-6 lg:px-8">
    
    <!-- Subtle Brand-Matched Background -->
    <div class="fixed inset-0 w-full h-full z-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-[28rem] h-[28rem] rounded-full opacity-40 blur-3xl corner-accent-1"></div>
        <div class="absolute -bottom-32 -left-32 w-[28rem] h-[28rem] rounded-full opacity-40 blur-3xl corner-accent-2"></div>
    </div>

    <div class="z-10 w-full max-w-md p-8 sm:p-10 login-card rounded-2xl shadow-lg border border-gray-200 relative overflow-hidden">
        <!-- Decorative corner accents matching brand -->
        <div class="absolute -right-10 -top-10 w-28 h-28 bg-green-50/80 rounded-full"></div>
        <div class="absolute -left-10 -bottom-10 w-24 h-24 bg-amber-50/80 rounded-full"></div>
        
        <!-- Header -->
        <div class="relative z-10 text-center mb-8">
            <div class="flex justify-center mb-5">
                <div class="p-3 logo-wrapper rounded-2xl shadow-sm">
                    <img src="logo.png" alt="Praanveda Ayurshakti" class="h-16 sm:h-20 w-auto object-contain" onerror="this.onerror=null; this.src='https://via.placeholder.com/150x60?text=PRAANVEDA&bg=f0faf4&textColor=1a5c2e';">
                </div>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-1">Welcome Back</h1>
            <p class="text-sm font-medium" style="color:#1a5c2e;">
                <span class="brand-accent">Praanveda</span> &bull; Ayurshakti Management
            </p>
        </div>

        <!-- Form -->
        <form id="loginForm" class="relative z-10 space-y-5">
            <div class="space-y-1.5">
                <label for="phone" class="block text-sm font-semibold text-gray-700 ml-1">Phone or User ID</label>
                <input type="text" id="phone" name="phone" required
                    class="input-field block w-full px-4 py-3 sm:text-sm rounded-xl border border-gray-300 focus:border-green-700 focus:ring-0 text-gray-900 bg-white"
                    placeholder="Enter your ID">
            </div>

            <div class="space-y-1.5">
                <label for="password" class="block text-sm font-semibold text-gray-700 ml-1">Password</label>
                <input type="password" id="password" name="password" required
                    class="input-field block w-full px-4 py-3 sm:text-sm rounded-xl border border-gray-300 focus:border-green-700 focus:ring-0 text-gray-900 bg-white"
                    placeholder="••••••••">
            </div>

            <div class="pt-4">
                <button type="submit" class="btn-primary w-full flex justify-center py-3 px-4 border border-transparent rounded-xl text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 shadow-sm" style="focus-ring-color:#1a5c2e;">
                    <span class="flex items-center gap-2">
                        Sign In
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </span>
                </button>
            </div>
        </form>

        <!-- Divider with brand tagline -->
        <div class="relative z-10 my-6">
            <div class="border-t border-gray-100"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="bg-white px-3 text-xs font-semibold tracking-widest uppercase" style="color:#f4900c;">Where Life Meets Ayurveda</span>
            </div>
        </div>

        <!-- Environment Indicator -->
        <div class="relative z-10 text-center flex items-center justify-center gap-2">
            <div class="flex items-center px-3 py-1 rounded-full border" style="background:#f0faf4; border-color:#c3dfc9;">
                <span class="w-2 h-2 rounded-full animate-pulse mr-2" style="background:#1a5c2e;"></span>
                <span class="text-xs font-bold uppercase tracking-wider" style="color:#1a5c2e;">
                    System Active &bull; <?php echo $is_local ? 'Local' : 'Live'; ?>
                </span>
            </div>
        </div>
        
        <div id="loginMessage" class="relative z-10 mt-4 text-center text-sm hidden p-3 rounded-xl border"></div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('phone', document.getElementById('phone').value);
            formData.append('password', document.getElementById('password').value);
            
            const btn = this.querySelector('button[type="submit"]');
            const originalBtnContent = btn.innerHTML;
            btn.innerHTML = '<span class="flex items-center gap-2"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Authenticating...</span>';
            btn.disabled = true;
            btn.style.opacity = '0.8';

            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                const msgEl = document.getElementById('loginMessage');
                if(data.status === 'success') {
                    msgEl.className = 'mt-4 text-center text-sm font-medium p-3 rounded-xl border';
                    msgEl.style.background = '#f0faf4';
                    msgEl.style.color = '#1a5c2e';
                    msgEl.style.borderColor = '#c3dfc9';
                } else {
                    msgEl.className = 'mt-4 text-center text-sm font-medium p-3 rounded-xl bg-red-50 text-red-800 border border-red-200';
                    msgEl.style.background = '';
                    msgEl.style.color = '';
                    msgEl.style.borderColor = '';
                }
                msgEl.textContent = data.message;
                msgEl.style.display = 'block';

                if(data.status === 'success') {
                    setTimeout(() => {
                        window.location.href = "dashboard.php";
                    }, 500);
                } else {
                    btn.innerHTML = originalBtnContent;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            } catch(error) {
                console.error('Error:', error);
                const msgEl = document.getElementById('loginMessage');
                msgEl.className = 'mt-4 text-center text-sm font-medium p-3 rounded-xl bg-red-50 text-red-800 border border-red-200';
                msgEl.style.background = '';
                msgEl.style.color = '';
                msgEl.style.borderColor = '';
                msgEl.textContent = 'A network error occurred. Please try again.';
                msgEl.style.display = 'block';
                
                btn.innerHTML = originalBtnContent;
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
    </script>
</body>
</html>
