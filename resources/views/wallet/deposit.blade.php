<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Deposit Funds') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                    {{ __('Choose Deposit Method') }}
                </h3>

                <!-- Bank Transfer -->
                <div class="mb-6 p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-3">
                        {{ __('Bank Transfer') }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('Transfer funds from your bank account. Processing time: 1-3 business days.') }}
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-4 mb-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Transfer Details:') }}</p>
                        <dl class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('Bank Name:') }}</dt>
                                <dd class="font-mono text-gray-900 dark:text-gray-100">Paysera LT</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('IBAN:') }}</dt>
                                <dd class="font-mono text-gray-900 dark:text-gray-100">LT12 3456 7890 1234 5678</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('BIC/SWIFT:') }}</dt>
                                <dd class="font-mono text-gray-900 dark:text-gray-100">EVPALT21XXX</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600 dark:text-gray-400">{{ __('Reference:') }}</dt>
                                <dd class="font-mono text-gray-900 dark:text-gray-100">{{ auth()->user()->id }}-{{ auth()->user()->accounts->first()->uuid ?? 'ACCOUNT' }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        {{ __('Important: Include the reference number to ensure your deposit is credited to the correct account.') }}
                    </p>
                </div>

                <!-- Card Deposit -->
                <div class="mb-6 p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-3">
                        {{ __('Card Deposit') }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('Instant deposit using debit or credit card. Processing time: Immediate.') }}
                    </p>
                    
                    @if(!auth()->user()->accounts->first())
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 text-center">
                            <p class="text-gray-600 dark:text-gray-400">Create an account to get started with deposits</p>
                        </div>
                    @else
                        <form id="deposit-form" class="space-y-4">
                        @csrf
                        
                        <div>
                            <x-label for="card_amount" value="{{ __('Amount') }}" />
                            <x-input id="card_amount" type="number" step="0.01" min="10" max="10000" name="amount" placeholder="100.00" class="mt-1 block w-full" required />
                            <div id="amount-error" class="text-red-600 text-sm mt-1 hidden"></div>
                        </div>
                        
                        <div>
                            <x-label for="currency" value="{{ __('Currency') }}" />
                            <select id="currency" name="asset_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->code }}">{{ $asset->code }} - {{ $asset->name }}</option>
                                @endforeach
                            </select>
                            <div id="asset-error" class="text-red-600 text-sm mt-1 hidden"></div>
                        </div>
                        
                        <p class="text-xs text-gray-500 dark:text-gray-500">
                            {{ __('Card processing fee: 2.9% + $0.30') }}
                        </p>
                        
                        <div id="success-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded hidden"></div>
                        <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded hidden"></div>
                        
                        <x-button type="button" id="deposit-btn" class="w-full justify-center">
                            {{ __('Continue to Payment') }}
                        </x-button>
                    </form>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const depositForm = document.getElementById('deposit-form');
                        const depositBtn = document.getElementById('deposit-btn');
                        const successMessage = document.getElementById('success-message');
                        const errorMessage = document.getElementById('error-message');
                        
                        depositBtn.addEventListener('click', async function(e) {
                            e.preventDefault();
                            
                            // Clear previous errors
                            clearErrors();
                            
                            const formData = new FormData(depositForm);
                            const accountUuid = '{{ auth()->user()->accounts->first()->uuid ?? '' }}';
                            
                            const requestData = {
                                amount: parseFloat(formData.get('amount')),
                                asset_code: formData.get('asset_code')
                            };
                            
                            try {
                                depositBtn.disabled = true;
                                depositBtn.textContent = 'Processing...';
                                
                                const response = await fetch(`/api/accounts/${accountUuid}/deposit`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'Authorization': `Bearer {{ auth()->user()->currentAccessToken()->token ?? auth()->user()->createToken('wallet-access')->plainTextToken }}`
                                    },
                                    body: JSON.stringify(requestData)
                                });
                                
                                const result = await response.json();
                                
                                if (response.ok) {
                                    showSuccess(result.message || 'Deposit initiated successfully');
                                    depositForm.reset();
                                    setTimeout(() => {
                                        window.location.href = '{{ route("dashboard") }}';
                                    }, 2000);
                                } else {
                                    if (result.errors) {
                                        showValidationErrors(result.errors);
                                    } else {
                                        showError(result.message || 'Deposit failed');
                                    }
                                }
                            } catch (error) {
                                showError('Network error occurred. Please try again.');
                                console.error('Deposit error:', error);
                            } finally {
                                depositBtn.disabled = false;
                                depositBtn.textContent = 'Continue to Payment';
                            }
                        });
                        
                        function clearErrors() {
                            document.getElementById('amount-error').classList.add('hidden');
                            document.getElementById('asset-error').classList.add('hidden');
                            successMessage.classList.add('hidden');
                            errorMessage.classList.add('hidden');
                        }
                        
                        function showSuccess(message) {
                            successMessage.textContent = message;
                            successMessage.classList.remove('hidden');
                        }
                        
                        function showError(message) {
                            errorMessage.textContent = message;
                            errorMessage.classList.remove('hidden');
                        }
                        
                        function showValidationErrors(errors) {
                            if (errors.amount) {
                                const amountError = document.getElementById('amount-error');
                                amountError.textContent = errors.amount[0];
                                amountError.classList.remove('hidden');
                            }
                            if (errors.asset_code) {
                                const assetError = document.getElementById('asset-error');
                                assetError.textContent = errors.asset_code[0];
                                assetError.classList.remove('hidden');
                            }
                        }
                    });
                    </script>
                    @endif
                </div>

                <!-- Crypto Deposit -->
                <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg opacity-50">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-3">
                        {{ __('Cryptocurrency Deposit') }}
                        <span class="ml-2 text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded">{{ __('Coming Soon') }}</span>
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Deposit Bitcoin, Ethereum, and other cryptocurrencies. Processing time: 10-30 minutes.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>