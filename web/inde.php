<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Praanveda Ayurshakti</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAF9;
        }

        .hero-pattern {
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>

<body class="text-gray-900 antialiased h-screen flex flex-col overflow-hidden hero-pattern">

    <!-- Navbar -->
    <nav
        class="w-full bg-white border-b border-gray-200 py-3 px-6 lg:px-12 flex justify-between items-center shadow-sm z-50">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-teal-800 rounded-sm flex items-center justify-center">
                <span class="text-white font-bold text-sm tracking-tighter">PA</span>
            </div>
            <span class="text-lg font-bold text-gray-900 tracking-tight">
                Praanveda Ayurshakti
            </span>
        </div>
        <div class="hidden md:flex space-x-6 items-center text-sm font-medium">
            <a href="#" class="text-gray-600 hover:text-teal-800 transition-colors">Platform</a>
            <a href="#" class="text-gray-600 hover:text-teal-800 transition-colors">Solutions</a>
            <a href="#" class="text-gray-600 hover:text-teal-800 transition-colors">Company</a>
            <!-- Added Locations dropdown/link -->
            <div class="relative group cursor-pointer">
                <a href="add_location.php" class="text-gray-600 hover:text-teal-800 transition-colors flex items-center gap-1">
                    Locations
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </a>
                <div class="absolute left-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 group-hover:opacity-100 invisible group-hover:visible transition-all z-50">
                    <a href="add_location.php?type=state" class="block px-4 py-2 text-sm text-gray-700 hover:bg-teal-50 hover:text-teal-800">Add State</a>
                    <a href="add_location.php?type=district" class="block px-4 py-2 text-sm text-gray-700 hover:bg-teal-50 hover:text-teal-800">Add District</a>
                    <a href="add_location.php?type=block" class="block px-4 py-2 text-sm text-gray-700 hover:bg-teal-50 hover:text-teal-800">Add Block</a>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php" class="text-sm font-semibold text-gray-700 hover:text-teal-800 transition-colors px-3">
                Log in
            </a>
            <a href="index.php"
                class="px-5 py-2 bg-teal-800 hover:bg-teal-900 text-white text-sm font-semibold rounded-md shadow-sm transition-colors">
                Get Started
            </a>
        </div>
    </nav>

    <!-- Main Compact Content -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div
            class="max-w-5xl w-full bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row border border-gray-100 h-[600px]">

            <!-- Left Text Content -->
            <div class="w-full md:w-1/2 p-10 lg:p-16 flex flex-col justify-center">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 bg-teal-50 text-teal-800 rounded-full text-xs font-semibold uppercase tracking-wider mb-6 w-fit border border-teal-100">
                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                    Now Available
                </div>

                <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight leading-[1.1] mb-5 text-gray-900">
                    Smart Ayurvedic <br>
                    <span class="text-teal-800">Practice Management</span>
                </h1>

                <p class="text-gray-600 text-base leading-relaxed mb-8 max-w-sm">
                    A streamlined, flat-design platform to manage patients, optimize prescriptions, and elevate your
                    holistic healthcare services.
                </p>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="index.php"
                        class="px-6 py-3 bg-teal-800 text-white rounded-lg font-semibold text-sm text-center hover:bg-teal-900 transition-colors shadow-sm">
                        Access Dashboard
                    </a>
                    <a href="#"
                        class="px-6 py-3 bg-white text-gray-700 border border-gray-200 rounded-lg font-semibold text-sm text-center hover:bg-gray-50 hover:border-gray-300 transition-all">
                        Book a Demo
                    </a>
                </div>

                <div class="mt-10 flex items-center gap-4 text-sm text-gray-500 font-medium">
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Secure
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Fast
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Reliable
                    </div>
                </div>
            </div>

            <!-- Right Creative Visual -->
            <div class="hidden md:flex w-1/2 bg-teal-800 p-8 relative items-center justify-center overflow-hidden">
                <!-- Abstract flat shapes -->
                <div class="absolute top-10 right-10 w-32 h-32 border-4 border-teal-700 rounded-full"></div>
                <div class="absolute bottom-10 left-10 w-24 h-24 bg-teal-700 rounded-lg transform rotate-12"></div>

                <!-- Mockup Card -->
                <div
                    class="relative z-10 w-full max-w-sm bg-white rounded-xl shadow-2xl p-6 transform hover:scale-105 transition-transform duration-500">
                    <div class="w-12 h-12 bg-teal-100 rounded-lg mb-4 flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="h-4 w-3/4 bg-gray-200 rounded mb-3"></div>
                    <div class="h-4 w-1/2 bg-gray-200 rounded mb-6"></div>
                    <div class="space-y-2">
                        <div class="h-2 w-full bg-gray-100 rounded"></div>
                        <div class="h-2 w-full bg-gray-100 rounded"></div>
                        <div class="h-2 w-4/5 bg-gray-100 rounded"></div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-100 flex justify-between items-center">
                        <div class="h-3 w-16 bg-teal-100 rounded"></div>
                        <div class="h-6 w-16 bg-teal-800 rounded-md"></div>
                    </div>
                </div>
            </div>

        </div>
    </main>

</body>

</html>