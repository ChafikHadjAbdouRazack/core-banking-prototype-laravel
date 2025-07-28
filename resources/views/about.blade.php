@extends('layouts.public')

@section('title', 'About FinAegis - Our Mission & Community')

@section('seo')
    @include('partials.seo', [
        'title' => 'About FinAegis - Our Mission & Community',
        'description' => 'Learn about FinAegis - a community-driven prototype for transparent banking. Join our open-source movement to democratize finance through the Global Currency Unit.',
        'keywords' => 'FinAegis about, mission, community, open source banking, transparent banking, democratic finance, prototype, GitHub community',
    ])
    
    {{-- Schema.org Markup --}}
    <x-schema type="organization" />
    <x-schema type="breadcrumb" :data="[
        ['name' => 'Home', 'url' => url('/')],
        ['name' => 'About', 'url' => url('/about')]
    ]" />
@endsection

@push('styles')
<style>
    .team-member {
        transition: transform 0.3s ease;
    }
    .team-member:hover {
        transform: translateY(-5px);
    }
    .timeline-item {
        position: relative;
        padding-left: 30px;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 10px;
        width: 10px;
        height: 10px;
        background: #4f46e5;
        border-radius: 50%;
    }
    .timeline-item::after {
        content: '';
        position: absolute;
        left: 4px;
        top: 20px;
        width: 2px;
        height: calc(100% + 20px);
        background: #e5e7eb;
    }
    .timeline-item:last-child::after {
        display: none;
    }
</style>
@endpush

@section('content')
    <!-- Hero Section -->
    <section class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <h1 class="text-5xl font-bold text-gray-900 mb-6">About FinAegis</h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    We're building the future of democratic banking, where financial services are transparent, accessible, and governed by the community.
                </p>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Mission</h2>
                    <p class="text-lg text-gray-600 mb-4">
                        At FinAegis, we believe that financial systems should serve everyone, not just the privileged few. Our mission is to democratize banking through technology, transparency, and community governance.
                    </p>
                    <p class="text-lg text-gray-600 mb-4">
                        We're introducing the Global Currency Unit (GCU) - a revolutionary multi-asset currency basket that provides stability and democratic participation in financial governance.
                    </p>
                    <div class="mt-8">
                        <a href="{{ route('platform') }}" class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-700">
                            Learn about our platform
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="relative">
                    <div class="bg-gradient-to-br from-indigo-100 to-purple-100 rounded-2xl p-8">
                        <svg class="w-full h-72" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Globe representing global reach -->
                            <circle cx="200" cy="150" r="70" fill="#E0E7FF" stroke="#4F46E5" stroke-width="2"/>
                            
                            <!-- Continents simplified -->
                            <path d="M170 120C170 120 180 110 190 110C200 110 210 115 215 120C220 125 225 130 225 140C225 150 220 155 210 160C200 165 190 160 185 155C180 150 175 140 175 130C175 125 170 120 170 120Z" fill="#4F46E5" opacity="0.2"/>
                            <path d="M155 140C155 140 160 135 165 135C170 135 175 140 175 145C175 150 170 155 165 155C160 155 155 150 155 145V140Z" fill="#4F46E5" opacity="0.2"/>
                            <path d="M215 170C215 170 220 165 225 165C230 165 235 170 235 175C235 180 230 185 225 185C220 185 215 180 215 175V170Z" fill="#4F46E5" opacity="0.2"/>
                            
                            <!-- Network connections representing community -->
                            <circle cx="200" cy="150" r="4" fill="#4F46E5"/>
                            <circle cx="160" cy="130" r="3" fill="#4F46E5"/>
                            <circle cx="240" cy="130" r="3" fill="#4F46E5"/>
                            <circle cx="160" cy="170" r="3" fill="#4F46E5"/>
                            <circle cx="240" cy="170" r="3" fill="#4F46E5"/>
                            <circle cx="200" cy="110" r="3" fill="#4F46E5"/>
                            <circle cx="200" cy="190" r="3" fill="#4F46E5"/>
                            
                            <!-- Connecting lines -->
                            <line x1="200" y1="150" x2="160" y2="130" stroke="#4F46E5" stroke-width="1" opacity="0.5"/>
                            <line x1="200" y1="150" x2="240" y2="130" stroke="#4F46E5" stroke-width="1" opacity="0.5"/>
                            <line x1="200" y1="150" x2="160" y2="170" stroke="#4F46E5" stroke-width="1" opacity="0.5"/>
                            <line x1="200" y1="150" x2="240" y2="170" stroke="#4F46E5" stroke-width="1" opacity="0.5"/>
                            <line x1="200" y1="150" x2="200" y2="110" stroke="#4F46E5" stroke-width="1" opacity="0.5"/>
                            <line x1="200" y1="150" x2="200" y2="190" stroke="#4F46E5" stroke-width="1" opacity="0.5"/>
                            
                            <!-- Currency symbols around the globe -->
                            <text x="140" y="100" font-family="Arial, sans-serif" font-size="14" fill="#4F46E5" opacity="0.6">$</text>
                            <text x="250" y="100" font-family="Arial, sans-serif" font-size="14" fill="#4F46E5" opacity="0.6">€</text>
                            <text x="120" y="150" font-family="Arial, sans-serif" font-size="14" fill="#4F46E5" opacity="0.6">¥</text>
                            <text x="270" y="150" font-family="Arial, sans-serif" font-size="14" fill="#4F46E5" opacity="0.6">£</text>
                            <text x="140" y="200" font-family="Arial, sans-serif" font-size="14" fill="#4F46E5" opacity="0.6">₹</text>
                            <text x="250" y="200" font-family="Arial, sans-serif" font-size="14" fill="#4F46E5" opacity="0.6">元</text>
                            
                            <!-- Orbit rings representing global reach -->
                            <ellipse cx="200" cy="150" rx="90" ry="30" fill="none" stroke="#4F46E5" stroke-width="1" opacity="0.3" stroke-dasharray="5,5"/>
                            <ellipse cx="200" cy="150" rx="110" ry="40" fill="none" stroke="#4F46E5" stroke-width="1" opacity="0.2" stroke-dasharray="5,5"/>
                            
                            <!-- Star representing community -->
                            <path d="M200 60L207 75H223L210 85L217 100L200 90L183 100L190 85L177 75H193L200 60Z" fill="#4F46E5" opacity="0.5"/>
                        </svg>
                    </div>
                    <div class="absolute -bottom-10 -right-10 w-72 h-72 bg-indigo-100 rounded-full filter blur-3xl opacity-70"></div>
                    <div class="absolute -top-10 -left-10 w-72 h-72 bg-purple-100 rounded-full filter blur-3xl opacity-70"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900">Our Core Values</h2>
                <p class="mt-4 text-xl text-gray-600">The principles that guide everything we do</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Transparency</h3>
                    <p class="text-gray-600">
                        Every transaction, every decision, every vote is recorded on an immutable ledger. We believe in complete transparency as the foundation of trust.
                    </p>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Democracy</h3>
                    <p class="text-gray-600">
                        One GCU, one vote. We're building a financial system where every participant has a voice in governance, not just the largest stakeholders.
                    </p>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Security</h3>
                    <p class="text-gray-600">
                        Bank-grade security meets blockchain immutability. Your assets are protected by the most advanced security measures in the industry.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Journey Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900">Our Journey</h2>
                <p class="mt-4 text-xl text-gray-600">Building the future of transparent banking</p>
            </div>
            <div class="max-w-3xl mx-auto">
                <div class="timeline-item mb-12">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">The Vision</h3>
                    <p class="text-gray-600">
                        Born from the belief that banking should be democratic, transparent, and accessible to all. A vision for a new financial paradigm where communities have control.
                    </p>
                </div>
                <div class="timeline-item mb-12">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Prototype Development</h3>
                    <p class="text-gray-600">
                        Built a working prototype demonstrating core banking functionality, API integrations, and the revolutionary GCU (Global Currency Unit) concept. Open-sourced for transparency.
                    </p>
                </div>
                <div class="timeline-item mb-12">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Community Growth</h3>
                    <p class="text-gray-600">
                        Gained support from developers and believers in transparent banking worldwide. Our GitHub stars represent a growing community committed to financial democracy.
                    </p>
                </div>
                <div class="timeline-item">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Current Stage: Seeking Investment</h3>
                    <p class="text-gray-600">
                        With a working prototype and community support, we're now seeking funding to transform this vision into a fully licensed, operational banking platform that serves millions.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Community Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900">Our Community</h2>
                <p class="mt-4 text-xl text-gray-600">Powered by believers in transparent banking</p>
            </div>
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                    <div class="flex items-center justify-center mb-6">
                        <svg class="w-16 h-16 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 text-center mb-4">Open Source Community</h3>
                    <p class="text-lg text-gray-600 text-center mb-6">
                        FinAegis is built in the open. Our code is transparent, our development is collaborative, and our community drives innovation.
                    </p>
                    <div class="grid grid-cols-3 gap-4 text-center mb-6">
                        <div>
                            <div class="text-3xl font-bold text-indigo-600">⭐</div>
                            <div class="text-sm text-gray-600 mt-1">GitHub Stars</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-indigo-600">🔀</div>
                            <div class="text-sm text-gray-600 mt-1">Contributors</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-indigo-600">🌍</div>
                            <div class="text-sm text-gray-600 mt-1">Global Reach</div>
                        </div>
                    </div>
                    <p class="text-gray-600 text-center">
                        Join our GitHub community and help shape the future of transparent banking. Every contribution matters, from code to ideas to spreading the word.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h4 class="text-xl font-bold text-gray-900 mb-3">Developers</h4>
                        <p class="text-gray-600 mb-4">
                            Contributing code, reviewing PRs, and building the technical foundation for democratic banking.
                        </p>
                        <a href="https://github.com/FinAegis" target="_blank" class="text-indigo-600 font-semibold hover:text-indigo-700">
                            Join on GitHub →
                        </a>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h4 class="text-xl font-bold text-gray-900 mb-3">Believers</h4>
                        <p class="text-gray-600 mb-4">
                            Supporting the vision through stars, shares, and spreading awareness about transparent banking.
                        </p>
                        <a href="{{ route('cgo') }}" class="text-indigo-600 font-semibold hover:text-indigo-700">
                            Support the Project →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Join Us Section -->
    <section class="py-20 bg-indigo-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-6">Join the Financial Revolution</h2>
            <p class="text-xl text-indigo-100 mb-8 max-w-3xl mx-auto">
                Be part of a global community that's reshaping banking for the better. Your voice matters in our democratic financial ecosystem.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                    Get Started
                </a>
                <a href="{{ route('platform') }}" class="inline-flex items-center justify-center px-8 py-3 border border-white text-base font-medium rounded-md text-white hover:bg-indigo-700">
                    Learn More
                </a>
            </div>
        </div>
    </section>
@endsection