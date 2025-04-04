<div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-lg font-semibold mb-4">Private IP Browser</h2>

    @if (!$isConnected)
        <div class="mb-6">
            <label for="privateIp" class="block text-sm font-medium text-gray-700 mb-1">Enter Private IP:</label>
            <div class="flex">
                <input
                    type="text"
                    id="privateIp"
                    wire:model="privateIp"
                    class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="e.g., 192.168.1.1"
                >

                <button
                    wire:click="connect"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-r-md hover:bg-indigo-700 relative"
                >
                    Connect

                    {{-- Spinner inside button --}}
                    <svg wire:loading wire:target="connect"
                         class="absolute right-2 top-1/2 -translate-y-1/2 animate-spin h-5 w-5 text-white"
                         xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </button>
            </div>

            @if ($connectionError)
                <p class="mt-1 text-sm text-red-600">{{ $connectionError }}</p>
            @endif
        </div>
    @else
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-600">Connected to: <span class="font-semibold">{{ $privateIp }}</span></p>
        <button
            wire:click="connect"
            class="px-4 py-2 bg-indigo-600 text-white rounded-r-md hover:bg-indigo-700 relative"
        >
            <span wire:loading.remove wire:target="connect">Connect</span>

            {{-- Spinner (absolute inside button) --}}
            <svg
                wire:loading
                wire:target="connect"
                class="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-white animate-spin"
                xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
        </button>
        </div>

        {{-- Show loading spinner over snapshot + ping area --}}
        <div wire:loading wire:target="connect" class="flex items-center justify-center h-[730px]">
            <svg class="animate-spin h-12 w-12 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
        </div>

        <div wire:loading.remove wire:target="connect">
            <div class="border rounded-lg overflow-hidden mb-4" style="height: 730px;">
                <img src="{{ asset("storage/snapshots/{$privateIp}.png") }}" alt="Snapshot" style="height: 730px;" class="w-full object-cover">
            </div>

            <div class="border rounded-lg overflow-hidden" style="height: 200px;">
                <ul>
                    @foreach ($pingresponse['raw_output'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>