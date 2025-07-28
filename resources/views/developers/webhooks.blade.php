@extends('layouts.public')

@section('title', 'Webhooks - FinAegis Developer Documentation')

@section('seo')
    @include('partials.seo', [
        'title' => 'Webhooks - FinAegis Developer Documentation',
        'description' => 'FinAegis Webhooks - Real-time event notifications for your application. Get instant updates on transactions, accounts, and workflows.',
        'keywords' => 'FinAegis, webhooks, real-time, notifications, API, events',
    ])
@endsection

@push('styles')
<link href="https://fonts.bunny.net/css?family=fira-code:400,500&display=swap" rel="stylesheet" />
<style>
    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .code-font {
        font-family: 'Fira Code', monospace;
    }
    .webhook-gradient {
        background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    }
    .code-block {
                font-family: 'Fira Code', monospace;
                font-size: 0.875rem;
                line-height: 1.5;
                overflow-x: auto;
                white-space: pre;
            }
            @keyframes ping {
                75%, 100% {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            .animate-ping-slow {
                animation: ping 3s cubic-bezier(0, 0, 0.2, 1) infinite;
            }
            .floating-card {
                animation: float 6s ease-in-out infinite;
            }
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-20px); }
            }
        </style>
@endpush

@section('content')

        <!-- Hero Section -->
        <section class="webhook-gradient text-white relative overflow-hidden">
            <!-- Animated Background Elements -->
            <div class="absolute inset-0">
                <div class="absolute top-20 left-10 w-72 h-72 bg-yellow-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
                <div class="absolute top-40 right-10 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
                <div class="absolute -bottom-8 left-20 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-sm mb-6">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                        <span>Real-time Event System</span>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-bold mb-6">
                        Webhooks
                    </h1>
                    <p class="text-xl md:text-2xl text-yellow-100 max-w-3xl mx-auto">
                        Real-time notifications for account events, transaction updates, and workflow completions delivered directly to your application.
                    </p>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">How Webhooks Work</h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        Never miss an important event. Get instant HTTP POST notifications when things happen in your FinAegis account.
                    </p>
                </div>

                <!-- Flow Diagram -->
                <div class="relative mb-16">
                    <div class="hidden md:block absolute top-1/2 left-0 right-0 h-0.5 bg-gradient-to-r from-yellow-200 via-orange-200 to-red-200 transform -translate-y-1/2"></div>
                    
                    <div class="grid md:grid-cols-4 gap-8 relative">
                        @php
                            $steps = [
                                ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'yellow', 'title' => 'Event Occurs', 'description' => 'Transaction completes or account changes'],
                                ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color' => 'orange', 'title' => 'Webhook Triggered', 'description' => 'Event queued for delivery'],
                                ['icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'color' => 'red', 'title' => 'POST Request Sent', 'description' => 'Secure HTTPS delivery to your endpoint'],
                                ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green', 'title' => 'You Respond', 'description' => 'Process event and return 200 OK']
                            ];
                        @endphp

                        @foreach($steps as $index => $step)
                        <div class="text-center relative">
                            <div class="relative inline-block">
                                <div class="w-20 h-20 bg-{{ $step['color'] }}-100 rounded-full flex items-center justify-center relative z-10">
                                    <svg class="w-10 h-10 text-{{ $step['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"></path>
                                    </svg>
                                </div>
                                @if($index === 0)
                                <div class="absolute inset-0 bg-{{ $step['color'] }}-400 rounded-full animate-ping-slow"></div>
                                @endif
                            </div>
                            <h3 class="text-lg font-semibold mt-4 mb-2">{{ $step['title'] }}</h3>
                            <p class="text-gray-600 text-sm">{{ $step['description'] }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Setup -->
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-3xl p-8 text-center">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Quick Setup</h3>
                    <x-code-block language="bash">
curl -X POST https://api.finaegis.org/v2/webhooks \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{"url": "https://yourapp.com/webhooks", "events": ["*"]}'
                </x-code-block>
                </div>
            </div>
        </section>

        <!-- Event Types -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Available Events</h2>
                    <p class="text-xl text-gray-600">Subscribe to exactly what you need</p>
                </div>

                <div class="grid lg:grid-cols-2 gap-8">
                    @php
                        $eventCategories = [
                            [
                                'title' => 'Account Events',
                                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                                'color' => 'blue',
                                'events' => [
                                    ['name' => 'account.created', 'desc' => 'New account opened'],
                                    ['name' => 'account.updated', 'desc' => 'Account details changed'],
                                    ['name' => 'account.balance_updated', 'desc' => 'Balance changed'],
                                    ['name' => 'account.frozen', 'desc' => 'Account frozen'],
                                    ['name' => 'account.unfrozen', 'desc' => 'Account unfrozen'],
                                    ['name' => 'account.closed', 'desc' => 'Account closed']
                                ]
                            ],
                            [
                                'title' => 'Transfer Events',
                                'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                                'color' => 'green',
                                'events' => [
                                    ['name' => 'transfer.created', 'desc' => 'Transfer initiated'],
                                    ['name' => 'transfer.pending', 'desc' => 'Transfer processing'],
                                    ['name' => 'transfer.completed', 'desc' => 'Transfer successful'],
                                    ['name' => 'transfer.failed', 'desc' => 'Transfer failed'],
                                    ['name' => 'transfer.reversed', 'desc' => 'Transfer reversed'],
                                    ['name' => 'transfer.expired', 'desc' => 'Transfer expired']
                                ]
                            ],
                            [
                                'title' => 'Workflow Events',
                                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                                'color' => 'purple',
                                'events' => [
                                    ['name' => 'workflow.started', 'desc' => 'Workflow initiated'],
                                    ['name' => 'workflow.step_completed', 'desc' => 'Step finished'],
                                    ['name' => 'workflow.completed', 'desc' => 'Workflow finished'],
                                    ['name' => 'workflow.failed', 'desc' => 'Workflow failed'],
                                    ['name' => 'workflow.timeout', 'desc' => 'Workflow timed out'],
                                    ['name' => 'workflow.compensation_executed', 'desc' => 'Rollback completed']
                                ]
                            ],
                            [
                                'title' => 'System Events',
                                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                                'color' => 'yellow',
                                'events' => [
                                    ['name' => 'system.maintenance_start', 'desc' => 'Maintenance begins'],
                                    ['name' => 'system.maintenance_end', 'desc' => 'Maintenance ends'],
                                    ['name' => 'system.rate_limit_warning', 'desc' => 'Approaching rate limit'],
                                    ['name' => 'system.api_key_expiring', 'desc' => 'API key expiring soon'],
                                    ['name' => 'system.security_alert', 'desc' => 'Security event detected'],
                                    ['name' => 'system.status_change', 'desc' => 'System status changed']
                                ]
                            ]
                        ];
                    @endphp

                    @foreach($eventCategories as $category)
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                        <div class="bg-gradient-to-r from-{{ $category['color'] }}-500 to-{{ $category['color'] }}-600 p-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $category['icon'] }}"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-white">{{ $category['title'] }}</h3>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @foreach($category['events'] as $event)
                                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                    <code class="code-font text-sm bg-gray-100 px-3 py-1 rounded text-{{ $category['color'] }}-700">{{ $event['name'] }}</code>
                                    <span class="text-sm text-gray-600">{{ $event['desc'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Implementation Guide -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Implementation Guide</h2>
                    <p class="text-xl text-gray-600 mb-8">Get up and running in minutes</p>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-8 max-w-3xl mx-auto">
                        <div class="flex items-center justify-center mb-6">
                            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Complete Code Examples Available</h3>
                        <p class="text-gray-600 mb-6">We have comprehensive webhook implementation examples with signature verification, error handling, and idempotency patterns ready for you to use.</p>
                        <a href="{{ route('developers.show', 'examples') }}#webhooks" class="inline-flex items-center bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition duration-200">
                            View Webhook Examples
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>



        <!-- CTA -->
        <section class="py-20 webhook-gradient text-white">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to receive real-time events?</h2>
                <p class="text-xl text-yellow-100 mb-8">
                    Set up your first webhook in minutes
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('developers') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-orange-600 rounded-lg font-semibold hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
                        View Documentation
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="{{ route('developers.show', 'api-docs') }}" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-white rounded-lg font-semibold hover:bg-white hover:text-orange-600 transition">
                        API Reference
                    </a>
                </div>
            </div>
        </section>

        <!-- Animation Styles -->
        <style>
            @keyframes blob {
                0% { transform: translate(0px, 0px) scale(1); }
                33% { transform: translate(30px, -50px) scale(1.1); }
                66% { transform: translate(-20px, 20px) scale(0.9); }
                100% { transform: translate(0px, 0px) scale(1); }
            }
            .animate-blob {
                animation: blob 7s infinite;
            }
            .animation-delay-2000 {
                animation-delay: 2s;
            }
            .animation-delay-4000 {
                animation-delay: 4s;
            }
        </style>
@endsection

@push('scripts')
<script>
function copyCode(button) {
    const codeBlock = button.parentElement.querySelector('code');
    const text = codeBlock.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        button.classList.add('text-green-400');
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('text-green-400');
        }, 2000);
    });
}
</script>
@endpush