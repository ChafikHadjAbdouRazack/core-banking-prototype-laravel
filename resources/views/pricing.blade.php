<x-guest-layout>
    <div class="bg-white">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-white sm:text-5xl lg:text-6xl">
                        Simple, Transparent Pricing
                    </h1>
                    <p class="mt-6 text-xl text-blue-100 max-w-3xl mx-auto">
                        No hidden fees. No monthly charges. Pay only for what you use with the most competitive rates in the market.
                    </p>
                </div>
            </div>
        </div>

        <!-- Pricing Cards -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Personal Plan -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900">Personal</h3>
                        <p class="mt-4 text-gray-600">Perfect for individuals</p>
                        <div class="mt-8">
                            <span class="text-5xl font-bold text-gray-900">Free</span>
                            <span class="text-gray-600">/month</span>
                        </div>
                    </div>
                    
                    <ul class="mt-8 space-y-4">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Multi-currency wallet</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Free transfers between accounts</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Basic bank allocation (3 banks)</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Up to €300,000 deposit protection</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">GCU governance voting</span>
                        </li>
                    </ul>

                    <a href="{{ route('register') }}" class="mt-8 w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition duration-200 block text-center">
                        Get Started Free
                    </a>
                </div>

                <!-- Business Plan -->
                <div class="bg-white rounded-2xl shadow-xl border-2 border-blue-500 p-8 relative">
                    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <span class="bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-semibold">Most Popular</span>
                    </div>
                    
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900">Business</h3>
                        <p class="mt-4 text-gray-600">For growing businesses</p>
                        <div class="mt-8">
                            <span class="text-5xl font-bold text-gray-900">€29</span>
                            <span class="text-gray-600">/month</span>
                        </div>
                    </div>
                    
                    <ul class="mt-8 space-y-4">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Everything in Personal</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Advanced bank allocation (5 banks)</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Up to €500,000 deposit protection</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">API access & webhooks</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Priority support</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Batch processing</span>
                        </li>
                    </ul>

                    <a href="{{ route('register') }}" class="mt-8 w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition duration-200 block text-center">
                        Start Business Plan
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900">Enterprise</h3>
                        <p class="mt-4 text-gray-600">For large organizations</p>
                        <div class="mt-8">
                            <span class="text-5xl font-bold text-gray-900">Custom</span>
                        </div>
                    </div>
                    
                    <ul class="mt-8 space-y-4">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Everything in Business</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Unlimited bank allocation</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Unlimited deposit protection</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Dedicated account manager</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Custom integrations</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">SLA guarantees</span>
                        </li>
                    </ul>

                    <a href="{{ route('support.contact') }}" class="mt-8 w-full bg-gray-900 text-white py-3 px-6 rounded-lg font-semibold hover:bg-gray-800 transition duration-200 block text-center">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>

        <!-- Transaction Fees -->
        <div class="bg-gray-50 py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900">Transaction Fees</h2>
                    <p class="mt-4 text-xl text-gray-600">Transparent, low-cost pricing for all your transactions</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="bg-white rounded-lg p-6 text-center shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Currency Exchange</h3>
                        <div class="mt-4 text-3xl font-bold text-blue-600">0.01%</div>
                        <p class="mt-2 text-gray-600">Lowest in the market</p>
                    </div>

                    <div class="bg-white rounded-lg p-6 text-center shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">International Transfers</h3>
                        <div class="mt-4 text-3xl font-bold text-blue-600">€0.50</div>
                        <p class="mt-2 text-gray-600">Per transfer</p>
                    </div>

                    <div class="bg-white rounded-lg p-6 text-center shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Bank Deposits</h3>
                        <div class="mt-4 text-3xl font-bold text-blue-600">€1.00</div>
                        <p class="mt-2 text-gray-600">Per deposit</p>
                    </div>

                    <div class="bg-white rounded-lg p-6 text-center shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Withdrawals</h3>
                        <div class="mt-4 text-3xl font-bold text-blue-600">€2.00</div>
                        <p class="mt-2 text-gray-600">Per withdrawal</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900">Frequently Asked Questions</h2>
            </div>

            <div class="space-y-8">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Is there a minimum balance requirement?</h3>
                    <p class="text-gray-600">No, there are no minimum balance requirements for any of our plans. You can start with any amount.</p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Are my funds protected?</h3>
                    <p class="text-gray-600">Yes, your funds are distributed across multiple licensed banks, each providing deposit insurance protection up to €100,000 per bank.</p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I change plans anytime?</h3>
                    <p class="text-gray-600">Absolutely! You can upgrade or downgrade your plan at any time. Changes take effect immediately.</p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">What currencies are supported?</h3>
                    <p class="text-gray-600">We support major currencies including USD, EUR, GBP, CHF, JPY, and our flagship Global Currency Unit (GCU).</p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-blue-900 py-16">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white mb-4">Ready to get started?</h2>
                <p class="text-xl text-blue-100 mb-8">Join thousands of users who trust FinAegis with their financial future.</p>
                <a href="{{ route('register') }}" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-200">
                    Create Your Account
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>