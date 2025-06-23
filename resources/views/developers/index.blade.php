<x-guest-layout>
    <div class="bg-white">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-900 to-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-white sm:text-5xl lg:text-6xl">
                        Developer Resources
                    </h1>
                    <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                        Build powerful financial applications with the FinAegis API. Everything you need to integrate multi-asset banking into your applications.
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Start -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Get Started in Minutes</h2>
                <p class="mt-4 text-xl text-gray-600">Follow our quick start guide to begin integrating</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-3.586l4.293-4.293A6 6 0 0119 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">1. Get API Keys</h3>
                    <p class="text-gray-600 mb-6">Register for a developer account and get your API credentials</p>
                    <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800 font-medium">Sign Up →</a>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">2. Explore API</h3>
                    <p class="text-gray-600 mb-6">Browse our comprehensive API documentation and examples</p>
                    <a href="{{ route('developers.show', 'api-docs') }}" class="text-blue-600 hover:text-blue-800 font-medium">View Docs →</a>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">3. Start Building</h3>
                    <p class="text-gray-600 mb-6">Use our SDKs and code examples to integrate quickly</p>
                    <a href="{{ route('developers.show', 'sdks') }}" class="text-blue-600 hover:text-blue-800 font-medium">Get SDKs →</a>
                </div>
            </div>
        </div>

        <!-- Developer Resources -->
        <div class="bg-gray-50 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">Developer Tools & Resources</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- API Documentation -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">API Documentation</h3>
                        <p class="text-gray-600 mb-4">Complete reference for all API endpoints, parameters, and responses</p>
                        <a href="{{ route('developers.show', 'api-docs') }}" class="text-blue-600 hover:text-blue-800 font-medium">View Documentation →</a>
                    </div>

                    <!-- SDKs -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Official SDKs</h3>
                        <p class="text-gray-600 mb-4">Ready-to-use libraries for popular programming languages</p>
                        <a href="{{ route('developers.show', 'sdks') }}" class="text-blue-600 hover:text-blue-800 font-medium">Download SDKs →</a>
                    </div>

                    <!-- Code Examples -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Code Examples</h3>
                        <p class="text-gray-600 mb-4">Working examples and integration patterns for common use cases</p>
                        <a href="{{ route('developers.show', 'examples') }}" class="text-blue-600 hover:text-blue-800 font-medium">Browse Examples →</a>
                    </div>

                    <!-- Postman Collection -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Postman Collection</h3>
                        <p class="text-gray-600 mb-4">Pre-configured API collection for testing and development</p>
                        <a href="{{ route('developers.show', 'postman') }}" class="text-blue-600 hover:text-blue-800 font-medium">Download Collection →</a>
                    </div>

                    <!-- Webhooks -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v2a2 2 0 002 2h4a2 2 0 002-2v-2h2a2 2 0 002-2V9a2 2 0 00-2-2h-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v2H4a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Webhooks</h3>
                        <p class="text-gray-600 mb-4">Real-time notifications for transaction events and status updates</p>
                        <a href="{{ route('developers.show', 'webhooks') }}" class="text-blue-600 hover:text-blue-800 font-medium">Setup Webhooks →</a>
                    </div>

                    <!-- Support -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Developer Support</h3>
                        <p class="text-gray-600 mb-4">Get help from our technical team and developer community</p>
                        <a href="{{ route('support.contact') }}" class="text-blue-600 hover:text-blue-800 font-medium">Get Support →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Features -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">What You Can Build</h2>
                <p class="mt-4 text-xl text-gray-600">Powerful financial features at your fingertips</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Core Features</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Multi-Asset Wallets</h4>
                                <p class="text-gray-600">Create and manage wallets supporting multiple currencies and assets</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Instant Transfers</h4>
                                <p class="text-gray-600">Enable instant transfers between accounts and external recipients</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Currency Exchange</h4>
                                <p class="text-gray-600">Real-time currency conversion with competitive rates</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Bank Integration</h4>
                                <p class="text-gray-600">Connect to multiple banks for enhanced security and coverage</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Advanced Features</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Governance & Voting</h4>
                                <p class="text-gray-600">Implement democratic decision-making for platform governance</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Batch Processing</h4>
                                <p class="text-gray-600">Handle large volumes of transactions efficiently</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Transaction Reversal</h4>
                                <p class="text-gray-600">Critical error recovery with full audit trails</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Compliance Tools</h4>
                                <p class="text-gray-600">Built-in KYC, AML, and regulatory compliance features</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Code Example -->
        <div class="bg-gray-900 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-white">Simple Integration</h2>
                    <p class="mt-4 text-xl text-gray-300">Get started with just a few lines of code</p>
                </div>

                <div class="bg-gray-800 rounded-lg p-8 overflow-x-auto">
                    <pre class="text-green-400 text-sm"><code># Install the FinAegis SDK
npm install @finaegis/sdk

# Create a transfer
import { FinAegis } from '@finaegis/sdk';

const client = new FinAegis({ 
  apiKey: 'your-api-key',
  environment: 'sandbox' 
});

// Create a multi-currency transfer
const transfer = await client.transfers.create({
  fromAccount: 'acct_123',
  toAccount: 'acct_456',
  amount: 100.00,
  assetCode: 'USD',
  reference: 'Payment for services'
});

console.log(`Transfer created: ${transfer.id}`);</code></pre>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-blue-900 py-16">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white mb-4">Ready to start building?</h2>
                <p class="text-xl text-blue-100 mb-8">Join our developer community and build the future of finance.</p>
                <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                    <a href="{{ route('register') }}" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-200 block sm:inline-block">
                        Get API Keys
                    </a>
                    <a href="{{ route('developers.show', 'api-docs') }}" class="bg-blue-800 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200 block sm:inline-block">
                        View Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>