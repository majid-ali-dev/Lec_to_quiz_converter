<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Lecture to Quiz Converter')</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <style>
        /* Loader animation ke liye */
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3b82f6;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Smooth transitions */
        * {
            transition: all 0.3s ease;
        }
    </style>
    
    @yield('extra-css')
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    
    <!-- Header/Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-800">LecToQuiz</span>
                    </a>
                </div>
                <div class="text-sm text-gray-600">
                    AI-Powered Quiz Generator
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-10">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} LecToQuiz Converter. Final Year Project.
            </p>
        </div>
    </footer>

    @yield('extra-js')
</body>
</html>