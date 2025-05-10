<div class="relative">
    {{-- Fullscreen Loading Overlay --}}
    <div wire:loading.flex wire:target="connect" class="fixed inset-0 items-center justify-center z-50 bg-white/60 backdrop-blur-sm">
        <svg class="animate-spin h-16 w-16 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
    </div>

    {{-- Main Content --}}
    <div class="p-6 bg-white rounded-lg shadow-md transition-all duration-200"
         wire:loading.class="blur-sm" wire:target="connect">

        <h2 class="text-lg font-semibold mb-4">Private IP Browser</h2>

        @if (!$isConnected)
            <div class="mb-6">
                <label for="privateIp" class="block text-sm font-medium text-gray-700 mb-1">
                    Enter the Site Private IP:
                </label>
                <div class="flex">
                    <input
                        type="text"
                        id="privateIp"
                        wire:model="privateIp"
                        class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        placeholder="e.g., 10.194.78.135"
                    >
                    <button
                        wire:click="connect"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-r-md hover:bg-indigo-700"
                    >
                        Connect
                    </button>
                </div>
                @if ($connectionError)
                    <p class="mt-1 text-sm text-red-600">{{ $connectionError }}</p>
                @endif
            </div>
        @else
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Connected to: <span class="font-semibold">{{ $privateIp }}</span>
                </p>
                <button
                    wire:click="disconnect"
                    class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                >
                    Disconnect
                </button>
            </div>

            <div class="flex flex-col md:flex-row border rounded-lg overflow-hidden m-4 h-auto md:h-[250px]">
                <div class="relative w-full md:w-1/2">
                    <img src="{{ asset("storage/snapshots/{$privateIp}.png") }}" alt="Snapshot" class="h-full w-full object-contain">
                </div>
                <div class="w-full md:w-1/2 p-4 flex items-center justify-center text-sm text-gray-700 overflow-auto">
                    <div class="whitespace-pre-wrap break-words break-all w-full text-center">
                        {{ $deviceInfo1 }}
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row border rounded-lg overflow-hidden m-4 h-auto md:h-[250px]">
                <div class="relative w-full md:w-1/2">
                    <img src="{{ asset("storage/snapshots/{$privateIp}-s.png") }}" alt="Snapshot" class="h-full w-full object-contain">
                </div>
                <div class="w-full md:w-1/2 p-4 flex items-center justify-center text-sm text-gray-700 overflow-auto">
                    <div class="whitespace-pre-wrap break-words break-all w-full text-center">
                        {{ $deviceInfo2 }}
                    </div>
                </div>
            </div>

            <div class="border rounded-lg overflow-hidden m-4 p-4 text-sm text-gray-700 " style="height: 250px;">
                <ul>
                    @foreach ($pingresponse['raw_output'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
