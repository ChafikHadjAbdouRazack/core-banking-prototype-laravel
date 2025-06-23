<x-guest-layout>
    <div class="bg-white">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="text-center">
                    <div class="flex justify-center items-center mb-4">
                        <div class="w-4 h-4 bg-green-400 rounded-full animate-pulse mr-3"></div>
                        <span class="text-green-100 text-lg font-medium">All Systems Operational</span>
                    </div>
                    <h1 class="text-4xl font-bold text-white sm:text-5xl">
                        System Status
                    </h1>
                    <p class="mt-4 text-xl text-green-100">
                        Real-time status of FinAegis platform services
                    </p>
                </div>
            </div>
        </div>

        <!-- Current Status Overview -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Platform Status</h3>
                    <p class="text-2xl font-bold text-green-600 mt-2">Operational</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Uptime</h3>
                    <p class="text-2xl font-bold text-green-600 mt-2">99.98%</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Response Time</h3>
                    <p class="text-2xl font-bold text-blue-600 mt-2">127ms</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6 text-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7v10c0 5.55 3.84 9.74 9 11 5.16-1.26 9-5.45 9-11V7l-10-5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Security Status</h3>
                    <p class="text-2xl font-bold text-purple-600 mt-2">Secure</p>
                </div>
            </div>

            <!-- Service Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Service Status</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded-full mr-4"></div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Web Application</h3>
                                <p class="text-gray-600">Main platform interface</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Operational
                        </span>
                    </div>

                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded-full mr-4"></div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">API Services</h3>
                                <p class="text-gray-600">REST API endpoints and webhooks</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Operational
                        </span>
                    </div>

                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded-full mr-4"></div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Payment Processing</h3>
                                <p class="text-gray-600">Transaction and transfer services</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Operational
                        </span>
                    </div>

                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded-full mr-4"></div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Bank Connectors</h3>
                                <p class="text-gray-600">Integration with partner banks</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Operational
                        </span>
                    </div>

                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded-full mr-4"></div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Exchange Rate Service</h3>
                                <p class="text-gray-600">Real-time currency conversion</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Operational
                        </span>
                    </div>

                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-400 rounded-full mr-4"></div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Governance System</h3>
                                <p class="text-gray-600">Voting and governance features</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Operational
                        </span>
                    </div>

                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-400 rounded-full mr-4"></div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Mobile Applications</h3>
                                <p class="text-gray-600">iOS and Android apps</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            In Development
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Incidents -->
        <div class="bg-gray-50 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Recent Incidents</h2>
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-8 text-center">
                        <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Recent Incidents</h3>
                        <p class="text-gray-600">All systems have been running smoothly with no reported incidents in the past 30 days.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Schedule -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Scheduled Maintenance</h2>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Upcoming Maintenance</h3>
                </div>
                <div class="px-6 py-8">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center mt-1 mr-4">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2v20M2 12h20"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-blue-900 mb-2">Database Optimization</h4>
                                <p class="text-blue-800 mb-2">Sunday, January 7, 2024 • 02:00 - 04:00 UTC</p>
                                <p class="text-blue-700">
                                    Scheduled database maintenance to improve performance. Services may experience brief interruptions during this window.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="bg-blue-900 py-16">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white mb-4">Need Support?</h2>
                <p class="text-xl text-blue-100 mb-8">If you're experiencing issues not reflected on this page, please contact our support team.</p>
                <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                    <a href="{{ route('support.contact') }}" class="bg-white text-blue-900 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-200 block sm:inline-block">
                        Contact Support
                    </a>
                    <a href="{{ route('support') }}" class="bg-blue-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200 block sm:inline-block">
                        Help Center
                    </a>
                </div>
            </div>
        </div>

        <!-- Historical Data -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Historical Uptime (Last 90 Days)</h2>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">99.98%</div>
                        <div class="text-gray-600">Overall Uptime</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">142ms</div>
                        <div class="text-gray-600">Avg Response Time</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600">0</div>
                        <div class="text-gray-600">Critical Incidents</div>
                    </div>
                </div>
                
                <!-- Uptime Chart Placeholder -->
                <div class="mt-8 h-32 bg-gray-100 rounded-lg flex items-center justify-center">
                    <p class="text-gray-500">Uptime Chart (90-day trend)</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh status every 30 seconds
        setInterval(function() {
            // In a real implementation, this would fetch fresh status data
            console.log('Status refresh');
        }, 30000);

        // Add real-time timestamp
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const statusElement = document.querySelector('.text-green-100');
            if (statusElement) {
                statusElement.innerHTML += ` • Last updated: ${now.toLocaleTimeString()}`;
            }
        });
    </script>
</x-guest-layout>