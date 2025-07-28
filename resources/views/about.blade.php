@extends('layouts.public')

@section('title', 'About FinAegis - Our Mission & Team')

@section('seo')
    @include('partials.seo', [
        'title' => 'About FinAegis - Our Mission & Team',
        'description' => 'Learn about FinAegis - revolutionizing banking with democratic governance and the Global Currency Unit. Our mission, team, and journey.',
        'keywords' => 'FinAegis about, mission, team, democratic banking, financial revolution, open banking platform, core banking solution',
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
                    <img src="{{ asset('images/mission-illustration.svg') }}" alt="Our Mission" class="w-full">
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
                <p class="mt-4 text-xl text-gray-600">From vision to reality</p>
            </div>
            <div class="max-w-3xl mx-auto">
                <div class="timeline-item mb-12">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">2022 - The Vision</h3>
                    <p class="text-gray-600">
                        Born from the idea that banking should be democratic, transparent, and accessible to all. Our founders envisioned a new financial paradigm.
                    </p>
                </div>
                <div class="timeline-item mb-12">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">2023 - Building the Foundation</h3>
                    <p class="text-gray-600">
                        Developed the core banking infrastructure, established partnerships with financial institutions, and introduced the GCU concept.
                    </p>
                </div>
                <div class="timeline-item mb-12">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">2024 - Launch & Growth</h3>
                    <p class="text-gray-600">
                        Launched the platform with real banking integrations, onboarded first users, and established democratic governance structures.
                    </p>
                </div>
                <div class="timeline-item">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Today & Beyond</h3>
                    <p class="text-gray-600">
                        Expanding globally, adding new financial services, and empowering communities worldwide to take control of their financial future.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900">Meet Our Team</h2>
                <p class="mt-4 text-xl text-gray-600">The people making democratic banking a reality</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="team-member bg-white rounded-xl shadow-lg overflow-hidden">
                    <img src="{{ asset('images/team/ceo.jpg') }}" alt="CEO" class="w-full h-64 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900">Dr. Sarah Chen</h3>
                        <p class="text-indigo-600 mb-2">CEO & Co-Founder</p>
                        <p class="text-gray-600">
                            Former World Bank economist with 15 years of experience in financial inclusion and monetary policy.
                        </p>
                    </div>
                </div>
                <div class="team-member bg-white rounded-xl shadow-lg overflow-hidden">
                    <img src="{{ asset('images/team/cto.jpg') }}" alt="CTO" class="w-full h-64 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900">Marcus Thompson</h3>
                        <p class="text-indigo-600 mb-2">CTO & Co-Founder</p>
                        <p class="text-gray-600">
                            Blockchain pioneer and former tech lead at major fintech companies, architecting secure financial systems.
                        </p>
                    </div>
                </div>
                <div class="team-member bg-white rounded-xl shadow-lg overflow-hidden">
                    <img src="{{ asset('images/team/cfo.jpg') }}" alt="CFO" class="w-full h-64 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900">Elena Rodriguez</h3>
                        <p class="text-indigo-600 mb-2">CFO</p>
                        <p class="text-gray-600">
                            Investment banking veteran with expertise in financial modeling and regulatory compliance.
                        </p>
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