<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Security - Prototype Security Overview | FinAegis</title>

    @include('partials.favicon')
    
    @include('partials.seo', [
        'title' => 'Security - Prototype Security Overview',
        'description' => 'FinAegis prototype security overview - Understanding our approach to security in this open source banking platform prototype.',
        'keywords' => 'FinAegis security, prototype security, open source security, development security practices',
    ])

    {{-- Schema.org Markup --}}
    <x-schema type="service" :data="[
        'name' => 'FinAegis Security Overview',
        'description' => 'Security practices and future plans for the FinAegis prototype',
        'category' => 'Development Security'
    ]" />
    <x-schema type="breadcrumb" :data="[
        ['name' => 'Home', 'url' => url('/')],
        ['name' => 'Security', 'url' => url('/security')]
    ]" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .security-feature {
            transition: transform 0.3s ease;
        }
        .security-feature:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="antialiased">
    <x-platform-banners />
    <!-- Navigation -->
    @include('partials.public-nav')

    <!-- Hero Section -->
    <section class="pt-16 gradient-bg text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-6">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                </div>
                <h1 class="text-5xl font-bold mb-6">Security Overview</h1>
                <p class="text-xl text-purple-100 max-w-3xl mx-auto">
                    Security considerations and future plans for the FinAegis prototype platform.
                </p>
            </div>
        </div>
    </section>

    <!-- Current State Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Prototype Security Status</h2>
                <p class="text-xl text-gray-600">Current implementation and future roadmap</p>
            </div>
            
            <div class="max-w-4xl mx-auto">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 mb-8">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-amber-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-amber-900 mb-2">Development Stage Notice</h3>
                            <p class="text-amber-800">
                                FinAegis is currently a prototype demonstrating core banking concepts. Security features shown here represent our vision and roadmap for the production platform.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Currently Implemented -->
                    <div class="bg-green-50 rounded-xl p-8">
                        <h3 class="text-xl font-semibold text-green-900 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Currently Implemented
                        </h3>
                        <ul class="space-y-3 text-gray-700">
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">•</span>
                                Laravel authentication system
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">•</span>
                                Basic password hashing (bcrypt)
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">•</span>
                                Session management
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">•</span>
                                CSRF protection
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">•</span>
                                Basic input validation
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">•</span>
                                Environment-based configuration
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Planned for Production -->
                    <div class="bg-blue-50 rounded-xl p-8">
                        <h3 class="text-xl font-semibold text-blue-900 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Planned for Production
                        </h3>
                        <ul class="space-y-3 text-gray-700">
                            <li class="flex items-start">
                                <span class="text-blue-600 mr-2">•</span>
                                Multi-factor authentication (2FA)
                            </li>
                            <li class="flex items-start">
                                <span class="text-blue-600 mr-2">•</span>
                                Advanced encryption standards
                            </li>
                            <li class="flex items-start">
                                <span class="text-blue-600 mr-2">•</span>
                                Real-time fraud detection
                            </li>
                            <li class="flex items-start">
                                <span class="text-blue-600 mr-2">•</span>
                                Regulatory compliance (KYC/AML)
                            </li>
                            <li class="flex items-start">
                                <span class="text-blue-600 mr-2">•</span>
                                Security audit logging
                            </li>
                            <li class="flex items-start">
                                <span class="text-blue-600 mr-2">•</span>
                                Rate limiting and DDoS protection
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Open Source Security -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Open Source Security Approach</h2>
                <p class="text-xl text-gray-600">Transparency and community-driven security</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Security Through Transparency</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Open Source Code</h4>
                                <p class="text-gray-600">All code is publicly available for review and audit on GitHub</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Community Review</h4>
                                <p class="text-gray-600">Security vulnerabilities can be identified and reported by the community</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Regular Updates</h4>
                                <p class="text-gray-600">Dependencies and security patches are regularly updated</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Security Best Practices</h4>
                                <p class="text-gray-600">Following Laravel and industry security guidelines</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Contributing to Security</h3>
                    <p class="text-gray-600 mb-6">
                        As an open source project, we welcome security contributions from the community.
                    </p>
                    <div class="space-y-4">
                        <a href="https://github.com/FinAegis/core-banking-prototype-laravel/security" class="block bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition">
                            <h4 class="font-semibold text-gray-900 mb-1">Security Policy</h4>
                            <p class="text-sm text-gray-600">View our security policy and reporting guidelines</p>
                        </a>
                        <a href="https://github.com/FinAegis/core-banking-prototype-laravel/issues" class="block bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition">
                            <h4 class="font-semibold text-gray-900 mb-1">Report Issues</h4>
                            <p class="text-sm text-gray-600">Report security vulnerabilities responsibly</p>
                        </a>
                        <a href="https://github.com/FinAegis/core-banking-prototype-laravel" class="block bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition">
                            <h4 class="font-semibold text-gray-900 mb-1">Code Review</h4>
                            <p class="text-sm text-gray-600">Review our code and suggest improvements</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- User Security Tips -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Protect Your Account</h2>
                <p class="text-xl text-gray-600">Best practices to keep your account secure</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-indigo-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-indigo-900 mb-4">Do's</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Enable two-factor authentication (2FA)</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Use a unique, strong password</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Verify email sender addresses</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Keep your devices updated</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Review account activity regularly</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-red-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-red-900 mb-4">Don'ts</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-700">Share your password or API keys</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-700">Click on suspicious links</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-700">Use public WiFi for banking</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-700">Install unverified browser extensions</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-700">Ignore security warnings</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Security Contact -->
    <section class="py-20 gradient-bg text-white">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold mb-6">Found a Security Issue?</h2>
            <p class="text-xl mb-8 text-purple-100">
                As an open source project, we rely on the community to help identify and fix security vulnerabilities.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="https://github.com/FinAegis/core-banking-prototype-laravel/security/advisories/new" class="bg-white text-indigo-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transition">
                    Report Security Issue
                </a>
                <a href="https://github.com/FinAegis/core-banking-prototype-laravel/discussions" class="border-2 border-white text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-white hover:text-indigo-600 transition">
                    Discuss Security
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    @include('partials.footer')
</body>
</html>