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
            <p class="text-sm text-gray-600">Connected to: <span class="font-semibold">{{ $privateIp }}</span></p>
            <button
                wire:click="disconnect"
                class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
            >
                Disconnect
            </button>
        </div>

        <div class="border rounded-lg overflow-hidden m-4" style="height: 200px;">
            <img src="{{ asset("storage/snapshots/{$privateIp}.png") }}" alt="Snapshot" style="height: 200px;">
        </div>

        <div class="border rounded-lg overflow-hidden m-4" style="height: 200px;">
            <ul>
                @foreach ($pingresponse['raw_output'] as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>

        </div>
    @endif
</div>
