<div class="relative">
    {{-- Fullscreen Loading Overlay --}}
    <div wire:loading.flex wire:target="submit"
        class="fixed inset-0 items-center justify-center z-50 bg-white/60 backdrop-blur-sm">
        <svg class="animate-spin h-16 w-16 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
        </svg>
    </div>

    {{-- Main Content --}}
    <div class="p-6 bg-white rounded-lg shadow-md transition-all duration-200" wire:loading.class="blur-sm"
        wire:target="submit">

        <h2 class="text-lg font-semibold mb-4">Device NTP Configuration</h2>

        {{-- Input Form --}}
        <div class="space-y-4 mb-6">
            <!-- Host Input -->
            <div class="flex-1">
                <label for="host" class="block text-sm font-medium text-gray-700 mb-1">
                    Host (IP Address). You can enter a single IP or multiple IPs separated by commas.
                </label>
                <input id="host" type="text" wire:model="host"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="e.g., 10.194.67.249">
            </div>

            <!-- Device Brand Dropdown -->
            <div class="flex-1">
                <label for="device_brand" class="block text-sm font-medium text-gray-700 mb-1">
                    Device Brand
                </label>
                <select id="device_brand" wire:model="device_brand"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">-- Select a brand --</option>
                    <option value="enetek">Enetek</option>
                    <option value="vnt" disabled>VNT</option>
                    <option value="eltek" disabled>Eltek</option>
                    <option value="huawei" disabled>Huawei</option>
                </select>
            </div>

            <!-- Button -->
            <div>
                <x-button wire:click="submit">
                    Configure NTP
                </x-button>
            </div>
        </div>

        @if ($error)
        <p class="text-sm text-red-600 mt-2">{{ $error }}</p>
        @endif
    </div>

    {{-- Output --}}
    @if ($results)
    <table class="m-4 w-full text-sm">
        <thead>
            <tr>
                <th class="text-left">IP</th>
                <th class="text-left">Status</th>
                <th class="text-left">Message</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($results as $r)
            <tr>
                <td>{{ $r['ip'] }}</td>
                <td class="{{ $r['status']==='ok' ? 'text-green-600' : 'text-red-600' }}">
                    {{ strtoupper($r['status']) }}
                </td>
                <td>
                    <pre class="whitespace-pre-wrap">{{ $r['message'] }}</pre>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

</div>
</div>
