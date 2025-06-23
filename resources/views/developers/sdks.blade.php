<x-guest-layout>
    <div class="bg-white">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-white sm:text-5xl lg:text-6xl">
                        Official SDKs
                    </h1>
                    <p class="mt-6 text-xl text-purple-100 max-w-3xl mx-auto">
                        Ready-to-use libraries for popular programming languages to accelerate your integration with FinAegis.
                    </p>
                </div>
            </div>
        </div>

        <!-- SDK Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- JavaScript/Node.js SDK -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M0 0h24v24H0V0zm22.034 18.276c-.175-1.095-.888-2.015-3.003-2.873-.736-.345-1.554-.585-1.797-1.14-.091-.33-.105-.51-.046-.705.15-.646.915-.84 1.515-.66.39.12.75.42.976.9 1.034-.676 1.034-.676 1.755-1.125-.27-.42-.404-.601-.586-.78-.63-.705-1.469-1.065-2.834-1.034l-.705.089c-.676.165-1.32.525-1.71 1.005-1.14 1.291-.811 3.541.569 4.471 1.365 1.02 3.361 1.244 3.616 2.205.24 1.17-.87 1.545-1.966 1.41-.811-.18-1.26-.586-1.755-1.336l-1.83 1.051c.21.48.45.689.81 1.109 1.74 1.756 6.09 1.666 6.871-1.004.029-.09.24-.705.074-1.65l.046.067zm-8.983-7.245h-2.248c0 1.938-.009 3.864-.009 5.805 0 1.232.063 2.363-.138 2.711-.33.689-1.18.601-1.566.48-.396-.196-.597-.466-.83-.855-.063-.105-.11-.196-.127-.196l-1.825 1.125c.305.63.75 1.172 1.324 1.517.855.51 2.004.675 3.207.405.783-.226 1.458-.691 1.811-1.411.51-.93.402-2.07.397-3.346.012-2.054 0-4.109 0-6.179l.004-.056z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">JavaScript/Node.js</h3>
                            <p class="text-gray-600">@finaegis/sdk</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 mb-6">Full-featured SDK for Node.js applications and browser environments with TypeScript support.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Installation</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <code class="text-green-400 text-sm">npm install @finaegis/sdk</code>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Quick Start</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>import { FinAegis } from '@finaegis/sdk';

const client = new FinAegis({
  apiKey: 'your-api-key',
  environment: 'sandbox'
});

const accounts = await client.accounts.list();</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <a href="#" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition duration-200">Download</a>
                        <a href="#" class="text-yellow-600 hover:text-yellow-800">Documentation</a>
                    </div>
                </div>

                <!-- Python SDK -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.25.18l.9.2.73.26.59.3.45.32.34.34.25.34.16.33.1.3.04.26.02.2-.01.13V8.5l-.05.63-.13.55-.21.46-.26.38-.3.31-.33.25-.35.19-.35.14-.33.1-.3.07-.26.04-.21.02H8.77l-.69.05-.59.14-.5.22-.41.27-.33.32-.27.35-.2.36-.15.37-.1.35-.07.32-.04.27-.02.21v3.06H3.17l-.21-.03-.28-.07-.32-.12-.35-.18-.36-.26-.36-.36-.35-.46-.32-.59-.28-.73-.21-.88-.14-1.05L0 11.97l.06-1.22.16-1.04.24-.87.32-.71.36-.57.4-.44.42-.33.42-.24.4-.16.36-.1.32-.05.24-.01h.16l.06.01h8.16v-.83H6.18l-.01-2.75-.02-.37.05-.34.11-.31.17-.28.25-.26.31-.23.38-.2.44-.18.51-.15.58-.12.64-.1.71-.06.77-.04.84-.02 1.27.05zm-6.3 1.98l-.23.33-.08.41.08.41.23.34.33.22.41.09.41-.09.33-.22.23-.34.08-.41-.08-.41-.23-.33-.33-.22-.41-.09-.41.09zm13.09 3.95l.28.06.32.12.35.18.36.27.36.35.35.47.32.59.28.73.21.88.14 1.04.05 1.23-.06 1.23-.16 1.04-.24.86-.32.71-.36.57-.4.45-.42.33-.42.24-.4.16-.36.09-.32.05-.24.02-.16-.01h-8.22v.82h5.84l.01 2.76.02.36-.05.34-.11.31-.17.29-.25.25-.31.24-.38.2-.44.17-.51.15-.58.13-.64.09-.71.07-.77.04-.84.01-1.27-.04-1.07-.14-.9-.2-.73-.25-.59-.3-.45-.33-.34-.34-.25-.34-.16-.33-.1-.3-.04-.25-.02-.2.01-.13v-5.34l.05-.64.13-.54.21-.46.26-.38.3-.32.33-.24.35-.2.35-.14.33-.1.3-.06.26-.04.21-.02.13-.01h5.84l.69-.05.59-.14.5-.21.41-.28.33-.32.27-.35.2-.36.15-.36.1-.35.07-.32.04-.28.02-.21V6.07h2.09l.14.01zm-6.47 14.25l-.23.33-.08.41.08.41.23.33.33.23.41.08.41-.08.33-.23.23-.33.08-.41-.08-.41-.23-.33-.33-.23-.41-.08-.41.08z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Python</h3>
                            <p class="text-gray-600">finaegis-python</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 mb-6">Pythonic SDK with support for async/await and comprehensive type hints.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Installation</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <code class="text-green-400 text-sm">pip install finaegis</code>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Quick Start</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>from finaegis import FinAegis

client = FinAegis(
    api_key='your-api-key',
    environment='sandbox'
)

accounts = client.accounts.list()</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <a href="#" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-200">Download</a>
                        <a href="#" class="text-blue-600 hover:text-blue-800">Documentation</a>
                    </div>
                </div>

                <!-- PHP SDK -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M7.01 10.207h-.944l-.515 2.648h.838c.556 0 .982-.122 1.292-.43.31-.307.464-.749.464-1.319 0-.838-.394-1.319-1.135-1.319zm.02 2.04c-.212 0-.384.005-.514.011l.324-1.617c.13-.007.28-.011.514-.011.458 0 .674.262.674.787 0 .524-.216.83-.998.83zm7.162 5.393c-.314.183-.688.275-1.122.275-.434 0-.801-.092-1.081-.275-.281-.183-.421-.419-.421-.708 0-.27.14-.506.421-.708.28-.183.647-.275 1.081-.275.434 0 .808.092 1.122.275.314.202.471.438.471.708 0 .289-.157.525-.471.708zm-6.79 0c-.314.183-.688.275-1.122.275-.434 0-.801-.092-1.081-.275-.281-.183-.421-.419-.421-.708 0-.27.14-.506.421-.708.28-.183.647-.275 1.081-.275.434 0 .808.092 1.122.275.314.202.471.438.471.708 0 .289-.157.525-.471.708zm0-12.837c-.314.183-.688.275-1.122.275-.434 0-.801-.092-1.081-.275-.281-.183-.421-.419-.421-.708 0-.27.14-.506.421-.708.28-.183.647-.275 1.081-.275.434 0 .808.092 1.122.275.314.202.471.438.471.708 0 .289-.157.525-.471.708zm8.82 5.46h.993l-.515 2.648h-.838c-.556 0-.982-.122-1.292-.43-.31-.307-.464-.749-.464-1.319 0-.838.394-1.319 1.135-1.319h.981zm-.019 2.04c.212 0 .384.005.514.011l-.324-1.617c-.13-.007-.28-.011-.514-.011-.458 0-.674.262-.674.787 0 .524.216.83.998.83zM24 12c0 6.627-5.373 12-12 12S0 18.627 0 12 5.373 0 12 0s12 5.373 12 12zm-19.5 0c0 4.142 3.358 7.5 7.5 7.5s7.5-3.358 7.5-7.5-3.358-7.5-7.5-7.5-7.5 3.358-7.5 7.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">PHP</h3>
                            <p class="text-gray-600">finaegis/php-sdk</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 mb-6">Modern PHP SDK compatible with PHP 8.0+ and PSR-4 autoloading.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Installation</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <code class="text-green-400 text-sm">composer require finaegis/php-sdk</code>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Quick Start</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>use FinAegis\Client;

$client = new Client([
    'api_key' => 'your-api-key',
    'environment' => 'sandbox'
]);

$accounts = $client->accounts()->list();</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <a href="#" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition duration-200">Download</a>
                        <a href="#" class="text-purple-600 hover:text-purple-800">Documentation</a>
                    </div>
                </div>

                <!-- Go SDK -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-cyan-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M1.811 10.231c-.047 0-.058-.023-.035-.059l.246-.315c.023-.035.081-.058.128-.058h4.172c.046 0 .058.035.035.07l-.199.303c-.023.036-.082.07-.117.07zM.047 11.306c-.047 0-.059-.023-.035-.058l.245-.316c.023-.035.082-.058.129-.058h5.328c.047 0 .07.035.058.07l-.093.28c-.012.047-.058.07-.105.07zM2.828 12.381c-.047 0-.059-.035-.035-.07l.163-.292c.023-.035.070-.070.117-.070h2.337c.047 0 .070.035.070.082l-.023.28c0 .047-.047.082-.082.082zm8.967-2.978c-1.678.209-2.848.907-2.848 1.644 0 .878 1.063 1.191 2.313 1.191 1.295 0 2.590-.35 2.590-1.284 0-.537-.444-.932-1.227-1.098-.315-.070-.547-.14-.828-.453zm2.941-1.367c.697.814 1.017 1.944 1.017 3.1 0 2.037-1.018 3.265-2.711 3.451l-.35.046c-.117.012-.175.035-.175.105 0 .07.058.116.198.14l1.052.175c1.179.199 1.66.652 1.66 1.414 0 1.32-1.367 2.037-3.637 2.037-2.36 0-3.915-.65-3.915-1.74 0-.419.21-.816.595-1.145l.70-.594c.397-.35.595-.628.595-.977 0-.314-.117-.548-.362-.792l-.315-.302c-.14-.117-.233-.233-.233-.35 0-.093.047-.175.14-.175zm-.315-2.094c.175 0 .362.023.537.07.14.046.269.139.362.279.152.257.222.594.222 1.063 0 1.005-.302 1.678-.952 2.048-.14.082-.233.128-.233.198 0 .07.082.105.175.152.222.117.501.395.501.814 0 .582-.488 1.343-1.413 1.343-.222 0-.397-.035-.537-.07l-.362-.128c-.14-.082-.152-.117-.152-.199 0-.105.093-.210.245-.350.093-.093.175-.163.175-.233 0-.07-.058-.105-.14-.128-.35-.116-.619-.35-.795-.746-.163-.373-.21-.746-.21-1.145 0-.966.315-1.49.745-1.944.152-.151.339-.222.537-.222zm7.314-.77c.47 0 .967.117 1.274.35.245.175.35.42.35.746 0 .467-.315.84-.979 1.064-.117.046-.128.070-.01.128.35.175.559.49.559.932 0 .746-.49 1.18-1.413 1.343-.222.035-.432.046-.652.046h-2.8c-.315 0-.48-.117-.48-.373 0-.14.046-.303.105-.466l.152-.327c.175-.373.35-.699.735-.699h.477c.152 0 .245-.023.35-.082.117-.07.175-.175.175-.315 0-.222-.117-.35-.35-.35h-.735c-.35 0-.525-.116-.525-.373 0-.117.047-.233.105-.327l.152-.315c.175-.35.385-.641.77-.641zm-5.328 6.985c.315 0 .652.023.979.058.467.058.828.175 1.063.35.105.07.152.14.152.21 0 .105-.082.175-.233.175-.234 0-.5-.046-.792-.082-.792-.093-1.343-.14-1.898-.14-.467 0-.792.035-.979.105-.058.023-.117.046-.175.070-.093.035-.152.058-.21.058-.14 0-.175-.07-.175-.175 0-.233.175-.466.49-.652.35-.21.828-.35 1.413-.35zm.525-9.728c.315 0 .652.058.932.175.315.117.583.35.652.652.07.292.07.631.035.956-.047.467-.175.863-.35 1.226-.175.362-.42.699-.722.933-.175.117-.362.175-.548.175-.303 0-.652-.117-.956-.35-.35-.233-.583-.583-.652-.979-.07-.315-.035-.698.035-1.063.07-.467.21-.933.42-1.295.21-.35.49-.699.77-.886.14-.082.29-.12.445-.12zm9.61.14c.07.07.117.175.117.315 0 .14-.047.292-.128.385-.175.222-.42.315-.722.315-.245 0-.49-.07-.687-.175-.233-.117-.397-.292-.397-.548 0-.175.07-.35.21-.49.175-.175.42-.245.722-.245.245 0 .49.07.687.175.175.105.292.245.292.42zm-.595 3.382c.07.07.117.175.117.315 0 .14-.047.292-.128.385-.175.222-.42.315-.722.315-.245 0-.49-.07-.687-.175-.233-.117-.397-.292-.397-.548 0-.175.07-.35.21-.49.175-.175.42-.245.722-.245.245 0 .49.07.687.175.175.105.292.245.292.42z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Go</h3>
                            <p class="text-gray-600">finaegis-go</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 mb-6">High-performance Go SDK with context support and comprehensive error handling.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Installation</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <code class="text-green-400 text-sm">go get github.com/finaegis/finaegis-go</code>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Quick Start</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>import "github.com/finaegis/finaegis-go"

client := finaegis.NewClient(
    finaegis.WithAPIKey("your-api-key"),
    finaegis.WithEnvironment("sandbox"),
)

accounts, err := client.Accounts.List(ctx)</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <a href="#" class="bg-cyan-600 text-white px-4 py-2 rounded hover:bg-cyan-700 transition duration-200">Download</a>
                        <a href="#" class="text-cyan-600 hover:text-cyan-800">Documentation</a>
                    </div>
                </div>

                <!-- Ruby SDK -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.156.083c3.033.525 3.893 2.598 3.829 4.77L24 4.822 22.635 22.71 4.89 23.926c-4.075.279-5.684-2.548-5.065-5.844l4.677-12.872L20.156.083zm-2.183 13.747c0-.417-.42-.909-1.254-.909a3.28 3.28 0 0 0-1.685.49c.024-.382.025-.713.025-1.04 0-3.044-.869-4.672-2.294-4.672-1.274 0-2.138 1.137-2.138 3.034 0 1.537.37 2.645.895 3.53-.43.273-.735.489-.897.697-.416.529-.416 1.045.044 1.394.37.288.976.177 1.467-.113.32.135.653.21.971.21 1.164 0 1.992-.768 1.992-1.703 0-.655-.431-1.174-1.127-1.4.225-.286.533-.418.86-.418.328 0 .51.174.51.493 0 .376-.301.79-.753 1.143l-.175.134c-.425.331-.425.774.023 1.021.267.148.569.104.828-.073.53-.367.85-.864.85-1.424z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Ruby</h3>
                            <p class="text-gray-600">finaegis-ruby</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 mb-6">Elegant Ruby gem with ActiveRecord-like syntax and Rails integration support.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Installation</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <code class="text-green-400 text-sm">gem install finaegis</code>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Quick Start</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>require 'finaegis'

FinAegis.configure do |config|
  config.api_key = 'your-api-key'
  config.environment = 'sandbox'
end

accounts = FinAegis::Account.all</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <a href="#" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition duration-200">Download</a>
                        <a href="#" class="text-red-600 hover:text-red-800">Documentation</a>
                    </div>
                </div>

                <!-- Java SDK -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.851 18.56s-.917.534.653.714c1.902.218 2.874.187 4.969-.211 0 0 .552.346 1.321.646-4.699 2.013-10.633-.118-6.943-1.149M8.276 15.933s-1.028.761.542.924c2.032.209 3.636.227 6.413-.308 0 0 .384.389.987.602-5.679 1.661-12.007.13-7.942-1.218M13.116 11.475c1.158 1.333-.304 2.533-.304 2.533s2.939-1.518 1.589-3.418c-1.261-1.772-2.228-2.652 3.007-5.688 0-.001-8.216 2.051-4.292 6.573M19.33 20.504s.679.559-.747.991c-2.712.822-11.288 1.069-13.669.033-.856-.373.75-.89 1.254-.998.527-.114.828-.093.828-.093-.953-.671-6.156 1.317-2.643 1.887 9.58 1.553 17.462-.7 14.977-1.82M9.292 13.21s-4.362 1.036-1.544 1.412c1.189.159 3.561.123 5.77-.062 1.806-.152 3.618-.477 3.618-.477s-.637.272-1.098.587c-4.429 1.165-12.986.623-10.522-.568 2.082-1.006 3.776-.892 3.776-.892M17.116 17.584c4.503-2.34 2.421-4.589.968-4.285-.355.074-.515.138-.515.138s.132-.207.385-.297c2.875-1.011 5.086 2.981-.928 4.562 0-.001.07-.062.09-.118M14.401 0s2.494 2.494-2.365 6.33c-3.896 3.077-.888 4.832-.001 6.836-2.274-2.053-3.943-3.858-2.824-5.539 1.644-2.469 6.197-3.665 5.19-7.627M9.734 23.924c4.322.277 10.959-.153 11.116-2.198 0 0-.302.775-3.572 1.391-3.688.694-8.239.613-10.937.168 0-.001.553.457 3.393.639"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Java</h3>
                            <p class="text-gray-600">finaegis-java</p>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 mb-6">Enterprise-ready Java SDK with Spring Boot integration and comprehensive documentation.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Installation (Maven)</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>&lt;dependency&gt;
  &lt;groupId&gt;com.finaegis&lt;/groupId&gt;
  &lt;artifactId&gt;finaegis-java&lt;/artifactId&gt;
  &lt;version&gt;1.0.0&lt;/version&gt;
&lt;/dependency&gt;</code></pre>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Quick Start</h4>
                            <div class="bg-gray-900 rounded p-3 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>import com.finaegis.FinAegisClient;

FinAegisClient client = FinAegisClient.builder()
    .apiKey("your-api-key")
    .environment("sandbox")
    .build();

List&lt;Account&gt; accounts = client.accounts().list();</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4 mt-6">
                        <a href="#" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 transition duration-200">Download</a>
                        <a href="#" class="text-orange-600 hover:text-orange-800">Documentation</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="bg-gray-50 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">SDK Features</h2>
                    <p class="mt-4 text-xl text-gray-600">All SDKs include these powerful features</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Type Safety</h3>
                        <p class="text-gray-600">Full type definitions and compile-time safety where supported</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Performance</h3>
                        <p class="text-gray-600">Optimized for speed with connection pooling and caching</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Security</h3>
                        <p class="text-gray-600">Built-in security best practices and credential management</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Error Handling</h3>
                        <p class="text-gray-600">Comprehensive error handling with detailed error messages</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Developer Experience</h3>
                        <p class="text-gray-600">Intuitive APIs with comprehensive documentation and examples</p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Auto-retry</h3>
                        <p class="text-gray-600">Automatic retries with exponential backoff for transient failures</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-purple-900 py-16">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white mb-4">Ready to integrate?</h2>
                <p class="text-xl text-purple-100 mb-8">Choose your preferred language and start building with FinAegis today.</p>
                <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                    <a href="{{ route('register') }}" class="bg-white text-purple-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-200 block sm:inline-block">
                        Get API Keys
                    </a>
                    <a href="{{ route('developers.show', 'examples') }}" class="bg-purple-800 text-white px-8 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-200 block sm:inline-block">
                        View Examples
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>