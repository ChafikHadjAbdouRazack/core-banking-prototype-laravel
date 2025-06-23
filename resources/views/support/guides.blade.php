<x-guest-layout>
    <div class="bg-white">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-blue-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-white sm:text-5xl lg:text-6xl">
                        Support Guides
                    </h1>
                    <p class="mt-6 text-xl text-green-100 max-w-3xl mx-auto">
                        Comprehensive guides to help you get the most out of FinAegis platform features and services.
                    </p>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl mx-auto">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" placeholder="Search guides..." class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-gray-50 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">Popular Guides</h2>
                    <p class="mt-4 text-xl text-gray-600">Start with these frequently accessed guides</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <a href="#getting-started" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition duration-200">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Getting Started</h3>
                        <p class="text-gray-600 text-sm">Account setup and first steps</p>
                    </a>

                    <a href="#wallet-management" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition duration-200">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Wallet Management</h3>
                        <p class="text-gray-600 text-sm">Deposits, withdrawals, and transfers</p>
                    </a>

                    <a href="#security" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition duration-200">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Security Settings</h3>
                        <p class="text-gray-600 text-sm">2FA, device management, and more</p>
                    </a>

                    <a href="#api-integration" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition duration-200">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">API Integration</h3>
                        <p class="text-gray-600 text-sm">Developer documentation and examples</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Guide Categories -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="space-y-16">
                
                <!-- Getting Started -->
                <section id="getting-started">
                    <div class="border-b border-gray-200 pb-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Getting Started</h2>
                        <p class="mt-2 text-gray-600">Everything you need to begin using FinAegis</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Creating Your Account</h3>
                            <p class="text-gray-600 mb-4">Step-by-step guide to account registration and initial verification.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Email verification process</li>
                                <li>• Identity document upload</li>
                                <li>• Phone number verification</li>
                                <li>• Initial security setup</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">5 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Understanding Your Dashboard</h3>
                            <p class="text-gray-600 mb-4">Overview of the main dashboard features and navigation.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Account overview section</li>
                                <li>• Transaction history</li>
                                <li>• Quick actions menu</li>
                                <li>• Settings and preferences</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">8 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">KYC Verification Process</h3>
                            <p class="text-gray-600 mb-4">Complete your Know Your Customer verification for full access.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Required documents</li>
                                <li>• Photo ID verification</li>
                                <li>• Address verification</li>
                                <li>• Processing timelines</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">6 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Account Limits and Tiers</h3>
                            <p class="text-gray-600 mb-4">Understanding transaction limits and how to increase them.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Basic tier limits</li>
                                <li>• Verified tier benefits</li>
                                <li>• Business account features</li>
                                <li>• Limit increase requests</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">4 min read</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Wallet Management -->
                <section id="wallet-management">
                    <div class="border-b border-gray-200 pb-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Wallet Management</h2>
                        <p class="mt-2 text-gray-600">Managing your funds across multiple currencies and banks</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Making Deposits</h3>
                            <p class="text-gray-600 mb-4">How to add funds to your FinAegis account from various sources.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Bank transfer deposits</li>
                                <li>• Supported currencies</li>
                                <li>• Processing times</li>
                                <li>• Deposit confirmation</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">7 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Withdrawing Funds</h3>
                            <p class="text-gray-600 mb-4">Secure withdrawal process to your designated bank accounts.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Withdrawal methods</li>
                                <li>• Bank account verification</li>
                                <li>• Processing schedules</li>
                                <li>• Fee structure</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">6 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Currency Exchange</h3>
                            <p class="text-gray-600 mb-4">Converting between different currencies at competitive rates.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Real-time exchange rates</li>
                                <li>• Supported currency pairs</li>
                                <li>• Exchange fee calculation</li>
                                <li>• Rate alerts and limits</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">9 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Multi-Bank Distribution</h3>
                            <p class="text-gray-600 mb-4">How your funds are automatically distributed across partner banks.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Distribution algorithms</li>
                                <li>• Bank allocation preferences</li>
                                <li>• Insurance coverage</li>
                                <li>• Risk management</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">10 min read</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Security -->
                <section id="security">
                    <div class="border-b border-gray-200 pb-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Security & Privacy</h2>
                        <p class="mt-2 text-gray-600">Protecting your account and personal information</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Two-Factor Authentication</h3>
                            <p class="text-gray-600 mb-4">Setting up and managing 2FA for enhanced account security.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• SMS authentication</li>
                                <li>• Authenticator apps</li>
                                <li>• Hardware key support</li>
                                <li>• Backup codes</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">8 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Device Management</h3>
                            <p class="text-gray-600 mb-4">Managing trusted devices and login sessions.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Active sessions overview</li>
                                <li>• Device registration</li>
                                <li>• Remote logout</li>
                                <li>• Suspicious activity alerts</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">5 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Privacy Settings</h3>
                            <p class="text-gray-600 mb-4">Control your data sharing and privacy preferences.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Data sharing controls</li>
                                <li>• Marketing preferences</li>
                                <li>• Cookie settings</li>
                                <li>• Data export options</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">6 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Account Recovery</h3>
                            <p class="text-gray-600 mb-4">Regaining access to your account if you're locked out.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Password reset process</li>
                                <li>• 2FA recovery codes</li>
                                <li>• Identity verification</li>
                                <li>• Support contact methods</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">7 min read</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- API Integration -->
                <section id="api-integration">
                    <div class="border-b border-gray-200 pb-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">API Integration</h2>
                        <p class="mt-2 text-gray-600">Developer resources and integration guides</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Getting Started with APIs</h3>
                            <p class="text-gray-600 mb-4">Basic setup and authentication for FinAegis APIs.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• API key generation</li>
                                <li>• Authentication methods</li>
                                <li>• Rate limiting guidelines</li>
                                <li>• Testing environment</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">12 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Webhook Configuration</h3>
                            <p class="text-gray-600 mb-4">Setting up real-time notifications for your application.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Webhook endpoint setup</li>
                                <li>• Event types and payloads</li>
                                <li>• Security verification</li>
                                <li>• Error handling</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">15 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">SDK Documentation</h3>
                            <p class="text-gray-600 mb-4">Using our official SDKs for faster integration.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• JavaScript/Node.js SDK</li>
                                <li>• Python SDK</li>
                                <li>• PHP SDK</li>
                                <li>• Code examples</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">20 min read</span>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">API Reference</h3>
                            <p class="text-gray-600 mb-4">Complete documentation of all available endpoints.</p>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Account management</li>
                                <li>• Transaction operations</li>
                                <li>• Reporting endpoints</li>
                                <li>• Error codes reference</li>
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-blue-600 font-medium text-sm">Reference</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="bg-gray-50 py-16">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Still Need Help?</h2>
                <p class="text-xl text-gray-600 mb-8">Our support team is here to assist you with any questions.</p>
                <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                    <a href="{{ route('support.contact') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200 block sm:inline-block">
                        Contact Support
                    </a>
                    <a href="{{ route('support') }}" class="bg-white text-blue-600 border border-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition duration-200 block sm:inline-block">
                        Help Center
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[type="text"]');
            const sections = document.querySelectorAll('section');
            
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                
                sections.forEach(section => {
                    const content = section.textContent.toLowerCase();
                    if (content.includes(query) || query === '') {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</x-guest-layout>