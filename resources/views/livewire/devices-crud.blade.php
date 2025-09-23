<div class="mx-auto p-4">
    {{-- flash --}}
    @if (session('message'))
    <div class="rounded-md  bg-green-50 px-4 py-2 text-sm text-green-800">
        {{ session('message') }}
    </div>
    @endif
    <form wire:submit.prevent>
        <div class="rounded-xl  bg-white">
            {{-- header controls: search + button inline --}}
            <div class="gap-3  sm:flex sm:items-center">
                <div class="sm:flex-1">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="IP / Site ID / Name"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                </div>

                <div class="mt-3 sm:mt-0 sm:w-auto sm:shrink-0 self-end">
                    <x-button wire:click="create">
                        <svg class="w-[21px] h-[21px]text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            width="20" height="20" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 7.757v8.486M7.757 12h8.486M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        New Device
                    </x-button>
                    <x-button wire:click="exportCsv">
                        <svg class="w-[21px] h-[21px] text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            width="20" height="20" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 10V4a1 1 0 0 0-1-1H9.914a1 1 0 0 0-.707.293L5.293 7.207A1 1 0 0 0 5 7.914V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2M10 3v4a1 1 0 0 1-1 1H5m5 6h9m0 0-2-2m2 2-2 2" />
                        </svg>
                        Export Devices to CSV
                    </x-button>
                    <x-button wire:click="exportLogsCsv">
                        <svg class="w-[21px] h-[21px] text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            width="20" height="20" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 10V4a1 1 0 0 0-1-1H9.914a1 1 0 0 0-.707.293L5.293 7.207A1 1 0 0 0 5 7.914V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2M10 3v4a1 1 0 0 1-1 1H5m5 6h9m0 0-2-2m2 2-2 2" />
                        </svg>
                        Export Logs to CSV
                    </x-button>
                </div>
            </div>
    </form>

    {{-- devices table --}}
    <div class="py-1 overflow-x-auto rounded-lg">
        <table class="w-full min-w-full table-auto">
            <thead class="bg-gray-200 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-2 py-2">ID</th>
                    <th class="px-2 py-2">IP</th>
                    <th class="px-2 py-2">Site ID</th>
                    <th class="px-2 py-2">Name</th>
                    <th class="px-2 py-2">FW Version</th>
                    <th class="px-2 py-2">Date Added</th>
                    <th class="px-2 py-2">Last Log Pulled</th>
                    <th class="px-2 py-2">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse ($devices as $d)
                <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/40">
                    <td class="px-2 py-1 font-medium text-slate-700">{{ $d->id }}</td>
                    <td class="px-2 py-1 tabular-nums">{{ $d->ip }}</td>
                    <td class="px-2 py-1">{{ $d->site_id }}</td>
                    <td class="px-2 py-1">{{ $d->name }}</td>
                    <td class="px-2 py-1">{{ $d->arm_version ?? 'N/A' }}</td>
                    <td class="px-2 py-1">{{ $d->created_at ? $d->created_at->format('F j, Y h:i A') : '' }}</td>
                    <td class="px-2 py-1">{{ $d->last_log_pulled_at ? $d->last_log_pulled_at->format('F j, Y h:i A') : 'Never' }}</td>
                    <td class="px-2 py-1">
                        <div class="flex gap-2">
                            <button wire:click="edit({{ $d->id }})"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 hover:shadow-md hover:shadow-gray"
                                aria-label="Edit" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 256 256"
                                    fill="currentColor">
                                    <g transform="scale(2.81)">
                                        <path
                                            d="M 63.409 90 H 8.08 C 3.625 90 0 86.375 0 81.92 V 8.08 C 0 3.625 3.625 0 8.08 0 h 73.84 C 86.375 0 90 3.625 90 8.08 v 57.44 c 0 0.553 -0.447 1 -1 1 s -1 -0.447 -1 -1 V 8.08 C 88 4.728 85.272 2 81.92 2 H 8.08 C 4.728 2 2 4.728 2 8.08 v 73.84 C 2 85.272 4.728 88 8.08 88 h 55.329 c 0.553 0 1 0.447 1 1 S 63.962 90 63.409 90 z" />
                                        <path
                                            d="M 79.181 89.997 c -1.626 0 -3.252 -0.619 -4.489 -1.856 L 52.982 66.433 c -1.323 -1.324 -2.312 -2.971 -2.858 -4.76 l -3.479 -11.384 c -0.316 -1.034 -0.038 -2.151 0.728 -2.916 c 0.765 -0.766 1.887 -1.046 2.916 -0.728 l 11.384 3.479 c 1.789 0.547 3.436 1.535 4.76 2.858 l 21.708 21.709 c 2.476 2.476 2.476 6.503 0 8.979 l -4.471 4.471 C 82.433 89.378 80.807 89.997 79.181 89.997 z" />
                                        <path
                                            d="M 70.886 83.922 c -0.256 0 -0.512 -0.098 -0.707 -0.293 c -0.391 -0.391 -0.391 -1.023 0 -1.414 l 12.036 -12.036 c 0.391 -0.391 1.023 -0.391 1.414 0 s 0.391 1.023 0 1.414 L 71.593 83.629 C 71.397 83.824 71.142 83.922 70.886 83.922 z" />
                                        <path
                                            d="M 72.719 22 H 17.281 c -0.552 0 -1 -0.448 -1 -1 s 0.448 -1 1 -1 h 55.438 c 0.553 0 1 0.448 1 1 S 73.271 22 72.719 22 z" />
                                        <path
                                            d="M 72.719 38 H 17.281 c -0.552 0 -1 -0.448 -1 -1 s 0.448 -1 1 -1 h 55.438 c 0.553 0 1 0.448 1 1 S 73.271 38 72.719 38 z" />
                                        <path
                                            d="M 32.719 54 H 17.281 c -0.552 0 -1 -0.447 -1 -1 s 0.448 -1 1 -1 h 15.438 c 0.552 0 1 0.447 1 1 S 33.271 54 32.719 54 z" />
                                        <path
                                            d="M 42.719 70 H 17.281 c -0.552 0 -1 -0.447 -1 -1 s 0.448 -1 1 -1 h 25.438 c 0.552 0 1 0.447 1 1 S 43.271 70 42.719 70 z" />
                                    </g>
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $d->id }})"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 hover:shadow-md hover:shadow-gray"
                                aria-label="Delete" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 256 256"
                                    fill="currentColor">
                                    <g transform="scale(2.81)">
                                        <path
                                            d="M 65.179 90 H 24.821 c -4.238 0 -7.752 -3.314 -8 -7.545 L 12.8 13.769 c -0.032 -0.55 0.164 -1.088 0.542 -1.489 c 0.378 -0.401 0.904 -0.628 1.455 -0.628 h 60.408 c 0.551 0 1.077 0.227 1.455 0.628 c 0.378 0.4 0.573 0.939 0.542 1.489 L 73.18 82.455 C 72.931 86.686 69.417 90 65.179 90 z M 16.917 15.652 l 3.897 66.568 C 20.938 84.34 22.698 86 24.821 86 h 40.358 c 2.123 0 3.883 -1.66 4.008 -3.779 l 3.897 -66.568 H 16.917 z" />
                                        <path
                                            d="M 81.546 15.652 H 8.454 c -1.104 0 -2 -0.896 -2 -2 s 0.896 -2 2 -2 h 73.092 c 1.104 0 2 0.896 2 2 S 82.65 15.652 81.546 15.652 z" />
                                        <path
                                            d="M 59.056 15.652 H 30.944 c -1.104 0 -2 -0.896 -2 -2 V 7.927 C 28.944 3.556 32.5 0 36.872 0 h 16.256 c 4.371 0 7.928 3.556 7.928 7.927 v 5.725 C 61.056 14.757 60.16 15.652 59.056 15.652 z M 32.944 11.652 h 24.111 V 7.927 C 57.056 5.762 55.294 4 53.128 4 H 36.872 c -2.166 0 -3.927 1.762 -3.927 3.927 V 11.652 z" />
                                        <path
                                            d="M 58.646 74.634 c -0.039 0 -0.079 -0.001 -0.119 -0.004 c -1.103 -0.064 -1.944 -1.011 -1.88 -2.113 l 2.533 -43.25 c 0.063 -1.103 0.999 -1.946 2.113 -1.88 c 1.103 0.064 1.944 1.011 1.879 2.113 l -2.532 43.25 C 60.579 73.813 59.697 74.634 58.646 74.634 z" />
                                        <path
                                            d="M 31.354 74.634 c -1.051 0 -1.933 -0.82 -1.995 -1.883 L 26.827 29.5 c -0.064 -1.103 0.777 -2.049 1.88 -2.113 c 1.087 -0.07 2.049 0.777 2.113 1.88 l 2.532 43.25 c 0.064 1.103 -0.777 2.049 -1.88 2.113 C 31.433 74.633 31.393 74.634 31.354 74.634 z" />
                                        <path
                                            d="M 45 74.634 c -1.104 0 -2 -0.896 -2 -2 v -43.25 c 0 -1.104 0.896 -2 2 -2 s 2 0.896 2 2 v 43.25 C 47 73.738 46.104 74.634 45 74.634 z" />
                                    </g>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12">
                        <div class="flex flex-col items-center justify-center text-center">
                            <div
                                class="mb-2 inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                🗂️</div>
                            <p class="text-sm font-medium text-slate-700">No devices found</p>
                            <p class="mt-1 text-xs text-slate-500">Try adjusting your search or add a new device.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class=" px-4 py-3">
        {{ $devices->onEachSide(1)->links() }}
    </div>
</div>

{{-- MEDIUM MODAL (create/edit) --}}
@if ($showForm)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
    <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl"> {{-- max-w-xl = medium --}}
        <div class="flex items-center justify-between  px-6 py-4">
            <h2 class="text-base font-semibold text-slate-800">
                {{ $deviceId ? 'Edit Device' : 'New Device' }}
            </h2>
            <button wire:click="$set('showForm', false)"
                class="rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                ✕
            </button>
        </div>

        <div class="space-y-4 px-6 py-5">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">IP <span
                        class="text-rose-600">*</span></label>
                <input type="text" wire:model.defer="ip"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="e.g., 10.194.67.249" />
                @error('ip') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Site ID</label>
                    <input type="text" wire:model.defer="site_id"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        placeholder="e.g., PH410199" />
                    @error('site_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Name</label>
                    <select wire:model.defer="name"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="">-- Select a Device Brand --</option>
                        <option value="Enetek">Enetek</option>
                        <option value="VNT">VNT</option>
                        <option value="Eltek">Eltek</option>
                        <option value="Huawei">Huawei</option>
                    </select>
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button wire:click="$set('showForm', false)"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <x-button wire:click="save">
                Save
            </x-button>
        </div>
    </div>
</div>
@endif

{{-- DELETE CONFIRM (Livewire boolean + device details) --}}
@if ($showDelete)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    wire:keydown.escape="$set('showDelete', false)" tabindex="0">

    <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="border-b border-slate-100 px-6 py-4">
            <h3 class="text-base font-semibold text-slate-800">Delete device?</h3>
            <p class="mt-1 text-sm text-slate-600">This action cannot be undone.</p>
        </div>

        {{-- Device details preview --}}
        <div class="px-6 py-5">
            <div class="rounded-lg border border-slate-200 bg-slate-50">
                <dl class="divide-y divide-slate-200">
                    <div class="grid grid-cols-3 gap-3 px-4 py-3">
                        <dt class="text-xs font-medium text-slate-500">ID</dt>
                        <dd class="col-span-2 text-sm text-slate-800">{{ $devicePreview['id'] ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-3">
                        <dt class="text-xs font-medium text-slate-500">IP</dt>
                        <dd class="col-span-2 text-sm text-slate-800">{{ $devicePreview['ip'] ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-3">
                        <dt class="text-xs font-medium text-slate-500">Site ID</dt>
                        <dd class="col-span-2 text-sm text-slate-800">{{ $devicePreview['site_id'] ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-3">
                        <dt class="text-xs font-medium text-slate-500">Name</dt>
                        <dd class="col-span-2 text-sm text-slate-800">{{ $devicePreview['name'] ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-3">
                        <dt class="text-xs font-medium text-slate-500">Created</dt>
                        <dd class="col-span-2 text-sm text-slate-800">{{ $devicePreview['created_at'] ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button wire:click="$set('showDelete', false)"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>

            <button wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="delete">Delete</span>
                <span wire:loading wire:target="delete">Deleting…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- livewire loading hint --}}
<div wire:loading class="fixed bottom-4 right-4 rounded-lg bg-slate-900/90 px-3 py-1.5 text-xs text-white shadow">
    Loading…
</div>
</div>
