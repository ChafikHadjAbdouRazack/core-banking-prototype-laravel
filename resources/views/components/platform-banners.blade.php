{{-- Demo Mode Indicator — only visible when APP_ENV=demo --}}
@if(app()->environment('demo'))
<div id="demo-pill" class="fixed bottom-6 left-6 z-50" onclick="this.classList.toggle('expanded')">
    {{-- Collapsed pill --}}
    <div class="demo-pill-collapsed cursor-pointer flex items-center gap-2 px-4 py-2 bg-amber-100 border border-amber-300 rounded-full shadow-lg hover:shadow-xl transition-all">
        <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
        </span>
        <span class="text-sm font-semibold text-amber-800">Demo Mode</span>
    </div>

    {{-- Expanded card --}}
    <div class="demo-pill-expanded hidden mt-2 w-80 bg-white border border-amber-200 rounded-xl shadow-2xl p-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
            </span>
            <h3 class="text-base font-bold text-amber-900">Demo Environment</h3>
        </div>
        <p class="text-sm text-gray-600 mb-3">This is a demonstration instance. No real transactions are processed.</p>
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center px-2.5 py-1 bg-amber-50 text-amber-700 text-xs font-medium rounded-full border border-amber-200">No Real Funds</span>
            <span class="inline-flex items-center px-2.5 py-1 bg-amber-50 text-amber-700 text-xs font-medium rounded-full border border-amber-200">Test Data</span>
        </div>
    </div>
</div>

<style>
    #demo-pill.expanded .demo-pill-collapsed { display: none; }
    #demo-pill.expanded .demo-pill-expanded { display: block; }
    #demo-pill:not(.expanded) .demo-pill-expanded { display: none; }
</style>
@endif
