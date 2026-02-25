@extends('layouts.public')

@section('title', 'System Status - FinAegis')

@section('seo')
    @include('partials.seo', [
        'title' => 'System Status - FinAegis',
        'description' => 'Real-time status of FinAegis platform services and infrastructure.',
        'keywords' => 'FinAegis status, system status, platform uptime, service availability',
    ])
@endsection

@section('content')
    <div class="bg-gray-50 min-h-screen">
        <!-- Status Header -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        @if($status['overall'] === 'operational')
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        @elseif($status['overall'] === 'degraded')
                            <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path>
                                </svg>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        @endif
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                @if($status['overall'] === 'operational')
                                    All Systems Operational
                                @elseif($status['overall'] === 'degraded')
                                    Some Systems Degraded
                                @else
                                    Major Outage
                                @endif
                            </h1>
                            <p class="text-sm text-gray-500">Updated {{ $status['last_checked']->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-6 text-sm text-gray-500">
                        <div class="text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $uptime['percentage'] }}%</div>
                            <div>Uptime</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $status['response_time'] }}ms</div>
                            <div>Avg Response</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Services grouped by category -->
            @php
                $grouped = collect($services)->groupBy('category');
            @endphp

            @foreach($grouped as $category => $categoryServices)
            <div class="mb-6">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-1">{{ $category }}</h2>
                <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
                    @foreach($categoryServices as $service)
                    <div class="px-5 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full {{ $service['status'] === 'operational' ? 'bg-green-500' : ($service['status'] === 'degraded' ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
                            <div>
                                <span class="text-sm font-medium text-gray-900">{{ $service['name'] }}</span>
                                <span class="text-sm text-gray-400 ml-2 hidden sm:inline">{{ $service['description'] }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-gray-400 hidden sm:inline">{{ $service['uptime'] }} uptime</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $service['status'] === 'operational' ? 'bg-green-50 text-green-700' : ($service['status'] === 'degraded' ? 'bg-yellow-50 text-yellow-700' : 'bg-red-50 text-red-700') }}">
                                {{ ucfirst($service['status']) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <!-- System Health Checks -->
            <div class="mb-6">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-1">System Health</h2>
                <div class="bg-white rounded-lg border border-gray-200">
                    <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                        @foreach($status['checks'] as $check => $result)
                        <div class="px-5 py-4 flex items-start gap-3">
                            @if($result['status'] === 'operational')
                                <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($result['status'] === 'degraded')
                                <svg class="w-5 h-5 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $check) }}</div>
                                <div class="text-xs text-gray-500">{{ $result['message'] }}</div>
                                @if(isset($result['response_time']))
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $result['response_time'] }}ms</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Incidents -->
            @if(count($incidents) > 0)
            <div class="mb-6">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-1">Recent Incidents</h2>
                <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
                    @foreach($incidents as $incident)
                    <div class="px-5 py-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">{{ $incident['title'] }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $incident['started_at']->format('M d, Y H:i') }}
                                    @if($incident['resolved_at'])
                                        &mdash; {{ $incident['resolved_at']->format('M d, Y H:i') }}
                                    @else
                                        &mdash; Ongoing
                                    @endif
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $incident['status'] === 'resolved' ? 'bg-green-50 text-green-700' : ($incident['status'] === 'in_progress' ? 'bg-yellow-50 text-yellow-700' : 'bg-red-50 text-red-700') }}">
                                {{ ucfirst(str_replace('_', ' ', $incident['status'])) }}
                            </span>
                        </div>

                        @if(count($incident['updates']) > 0)
                        <div class="ml-4 border-l-2 border-gray-200 pl-4 mt-3 space-y-2">
                            @foreach($incident['updates'] as $update)
                            <div>
                                <p class="text-xs text-gray-600">{{ $update['message'] }}</p>
                                <p class="text-xs text-gray-400">{{ $update['created_at']->format('M d, H:i') }}</p>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Footer -->
            <div class="text-center py-8">
                <p class="text-sm text-gray-500">
                    Programmatic access:
                    <a href="{{ route('status.api') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Status API</a>
                </p>
            </div>
        </div>
    </div>
@endsection
