@extends('layouts.public')

@section('title', 'Democratic Governance - FinAegis')

@section('seo')
    @include('partials.seo', [
        'title' => 'Democratic Governance',
        'description' => 'Participate in platform decisions through weighted voting. Your voice matters in shaping the future of finance.',
        'keywords' => 'democratic governance, voting, community decisions, decentralized finance, FinAegis',
    ])

    {{-- Schema.org Markup --}}
    <x-schema type="software" />
    <x-schema type="breadcrumb" :data="[
        ['name' => 'Home', 'url' => url('/')],
        ['name' => 'Features', 'url' => url('/features')],
        ['name' => 'Democratic Governance', 'url' => url('/features/governance')]
    ]" />
@endsection

@push('styles')
<style>
    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .vote-card {
        transition: all 0.3s ease;
    }
    .vote-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
</style>
@endpush

@section('content')

    <!-- Hero Section -->
    <section class="gradient-bg text-white pt-24 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl font-bold mb-6">Democratic Governance</h1>
                <p class="text-xl text-purple-100 max-w-3xl mx-auto">
                    Your money, your rules. Participate in shaping the future of finance through transparent, democratic voting.
                </p>
            </div>
        </div>
    </section>

    <!-- Overview Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6">Power to the People</h2>
                    <p class="text-lg text-gray-600 mb-4">
                        FinAegis is the first banking platform where users have real decision-making power. Through our democratic governance system, you directly influence how the platform operates.
                    </p>
                    <p class="text-lg text-gray-600 mb-4">
                        Every GCU holder can participate in votes that shape monetary policy, platform features, and strategic direction. Your voting power is proportional to your GCU holdings, ensuring those with the most at stake have a meaningful voice.
                    </p>
                    <p class="text-lg text-gray-600">
                        This isn't just about technology—it's about creating a financial system that truly serves its users.
                    </p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8">
                    <h3 class="text-2xl font-bold mb-6 text-gray-900">Governance Stats</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-4 bg-white rounded-lg">
                            <span class="text-gray-600">Active Voters</span>
                            <span class="text-2xl font-bold text-indigo-600">15,000+</span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-white rounded-lg">
                            <span class="text-gray-600">Proposals Passed</span>
                            <span class="text-2xl font-bold text-purple-600">127</span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-white rounded-lg">
                            <span class="text-gray-600">Average Participation</span>
                            <span class="text-2xl font-bold text-green-600">73%</span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-white rounded-lg">
                            <span class="text-gray-600">Next Vote</span>
                            <span class="text-lg font-bold text-pink-600">In 5 days</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How Voting Works -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-12">How Voting Works</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="vote-card bg-white rounded-xl p-8 shadow-md">
                    <div class="text-4xl font-bold text-indigo-600 mb-4">1</div>
                    <h3 class="text-xl font-bold mb-3">Proposal Creation</h3>
                    <p class="text-gray-600">
                        Community members or the platform team submit proposals for consideration. Each proposal includes detailed information about the change and its impact.
                    </p>
                </div>
                
                <div class="vote-card bg-white rounded-xl p-8 shadow-md">
                    <div class="text-4xl font-bold text-purple-600 mb-4">2</div>
                    <h3 class="text-xl font-bold mb-3">Discussion Period</h3>
                    <p class="text-gray-600">
                        The community discusses proposals for 7 days. Members can ask questions, share opinions, and suggest modifications before voting begins.
                    </p>
                </div>
                
                <div class="vote-card bg-white rounded-xl p-8 shadow-md">
                    <div class="text-4xl font-bold text-green-600 mb-4">3</div>
                    <h3 class="text-xl font-bold mb-3">Voting & Execution</h3>
                    <p class="text-gray-600">
                        GCU holders vote for 3 days. If the proposal passes with majority support, it's automatically executed or scheduled for implementation.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- What You Vote On -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-12">What You Vote On</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-indigo-50 rounded-xl p-8">
                    <h3 class="text-2xl font-bold mb-6 text-indigo-900">Monetary Policy</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-indigo-600 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold mb-1">GCU Basket Composition</h4>
                                <p class="text-gray-700">Monthly votes on currency weightings and additions/removals</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-indigo-600 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold mb-1">Interest Rates</h4>
                                <p class="text-gray-700">Set lending and borrowing rates across the platform</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-indigo-600 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold mb-1">Reserve Requirements</h4>
                                <p class="text-gray-700">Determine collateral ratios and reserve policies</p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-purple-50 rounded-xl p-8">
                    <h3 class="text-2xl font-bold mb-6 text-purple-900">Platform Features</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-purple-600 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold mb-1">New Product Launches</h4>
                                <p class="text-gray-700">Decide which features and products to develop next</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-purple-600 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold mb-1">Fee Structure</h4>
                                <p class="text-gray-700">Set transaction fees and revenue distribution</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-purple-600 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold mb-1">Partnership Decisions</h4>
                                <p class="text-gray-700">Vote on strategic partnerships and integrations</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Voting Power -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-12">Your Voting Power</h2>
            
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <h3 class="text-2xl font-bold mb-6">Asset-Weighted Voting</h3>
                    <p class="text-lg text-gray-600 mb-6">
                        Your voting power is directly proportional to your GCU holdings. This ensures that those with the most at stake have appropriate influence in decisions.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-3xl font-bold text-indigo-600 mb-2">1 GCU</div>
                            <p class="text-gray-600">=</p>
                            <div class="text-xl font-semibold">1 Vote</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-3xl font-bold text-purple-600 mb-2">No Minimum</div>
                            <p class="text-gray-600">Required to vote</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-3xl font-bold text-green-600 mb-2">Real-time</div>
                            <p class="text-gray-600">Vote calculation</p>
                        </div>
                    </div>
                    
                    <div class="border-t pt-6">
                        <h4 class="font-bold mb-4">Additional Voting Mechanisms</h4>
                        <ul class="space-y-2 text-gray-600">
                            <li>• Quadratic voting for certain proposals to prevent whale domination</li>
                            <li>• Delegation options to trusted community members</li>
                            <li>• Time-locked voting for long-term commitment rewards</li>
                            <li>• Participation incentives through governance tokens</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Votes -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-12">Recent Governance Decisions</h2>
            
            <div class="space-y-6 max-w-4xl mx-auto">
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="font-bold text-lg">Add Japanese Yen to GCU Basket</h4>
                            <p class="text-gray-600">Proposal to include JPY at 3% weight</p>
                        </div>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">Passed</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Participation: 78%</span>
                        <span>Yes: 84% | No: 16%</span>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="font-bold text-lg">Reduce Transaction Fees by 20%</h4>
                            <p class="text-gray-600">Lower fees to increase adoption</p>
                        </div>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">Passed</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Participation: 82%</span>
                        <span>Yes: 91% | No: 9%</span>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="font-bold text-lg">Launch FinAegis Lending Platform</h4>
                            <p class="text-gray-600">New P2P lending feature development</p>
                        </div>
                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">Voting</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Ends in: 2 days</span>
                        <span>Current: Yes: 67% | No: 33%</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Transparency -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 mb-12">Complete Transparency</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Public Proposals</h3>
                    <p class="text-gray-600">All proposals and their details are publicly accessible before, during, and after voting.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">On-Chain Voting</h3>
                    <p class="text-gray-600">All votes are recorded on-chain, ensuring complete transparency and immutability.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Automatic Execution</h3>
                    <p class="text-gray-600">Approved proposals are automatically executed, eliminating centralized control.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg text-white">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold mb-6">Join the Revolution</h2>
            <p class="text-xl mb-8 text-purple-100">
                Be part of the first truly democratic financial platform. Your voice, your vote, your future.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="bg-white text-indigo-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transition">
                    Start Voting
                </a>
                <a href="{{ route('gcu') }}" class="border-2 border-white text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-white hover:text-indigo-600 transition">
                    Get GCU
                </a>
            </div>
        </div>
    </section>

@endsection