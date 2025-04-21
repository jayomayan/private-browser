<div class="relative">
    {{-- Fullscreen Loading Overlay --}}
    <div wire:loading.flex wire:target="submit" class="fixed inset-0 items-center justify-center z-50 bg-white/60 backdrop-blur-sm">
        <svg class="animate-spin h-16 w-16 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
    </div>

    {{-- Main Content --}}
    <div class="p-6 bg-white rounded-lg shadow-md transition-all duration-200"
         wire:loading.class="blur-sm" wire:target="submit">

        <h2 class="text-lg font-semibold mb-4">SNMP Walk Tool</h2>

        {{-- Input Form --}}
        <div class="space-y-4 mb-6">
            <div>
                <label for="host" class="block text-sm font-medium text-gray-700 mb-1">Host</label>
                <input
                    id="host"
                    type="text"
                    wire:model="host"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="e.g., 10.194.67.249"
                >
            </div>

            <div>
                <label for="port" class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                <input
                    id="port"
                    type="number"
                    wire:model="port"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="2161"
                >
            </div>

            <div>
                <label for="community" class="block text-sm font-medium text-gray-700 mb-1">Community</label>
                <input
                    id="community"
                    type="text"
                    wire:model="community"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="e.g., axinple"
                >
            </div>

            <div>
                <label for="oid" class="block text-sm font-medium text-gray-700 mb-1">OID</label>
                <input
                    id="oid"
                    type="text"
                    wire:model="oid"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder=".1.3.6.1.4.1.53318.8.1.2.1.2.1"
                >
            </div>

            <div>
                <button
                    wire:click="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                >
                    Run SNMP Walk
                </button>
            </div>

            @if ($error)
                <p class="text-sm text-red-600 mt-2">{{ $error }}</p>
            @endif
        </div>

        {{-- Output --}}
        @if (!empty($results))
            <div class="border rounded-lg p-4 bg-gray-50">
                <h3 class="font-medium text-gray-700 mb-2">Results:</h3>
                <ul class="space-y-1 text-sm text-gray-800">
                    @foreach ($results as $result)
                        <li><strong>{{ $result['oid'] }}</strong> = {{ $result['value'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
</div>
