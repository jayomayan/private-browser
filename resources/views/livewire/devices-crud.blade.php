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
        <input
          type="text"
          wire:model.live.debounce.400ms="search"
          placeholder="IP / Site ID / Name"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        />
      </div>

      <div class="mt-3 sm:mt-0 sm:w-auto sm:shrink-0 self-end">
        <button
          wire:click="create"
          class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 self-end"
        >
          + New Device
        </button>
      </div>
    </div>
</form>

    {{-- devices table --}}
    <div class="overflow-x-auto">
      <table class="w-full min-w-full table-auto">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
          <tr>
            <th class="px-3 py-2">ID</th>
            <th class="px-3 py-2">IP</th>
            <th class="px-3 py-2">Site ID</th>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          @forelse ($devices as $d)
            <tr class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50/40">
              <td class="px-3 py-2 font-medium text-slate-700">{{ $d->id }}</td>
              <td class="px-3 py-2 tabular-nums">{{ $d->ip }}</td>
              <td class="px-3 py-2">{{ $d->site_id }}</td>
              <td class="px-3 py-2">{{ $d->name }}</td>
              <td class="px-3 py-2">
                <div class="flex gap-2">
                  <button wire:click="edit({{ $d->id }})"
                          class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    Edit
                  </button>
                  <button wire:click="confirmDelete({{ $d->id }})"
                          class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-4 py-12">
                <div class="flex flex-col items-center justify-center text-center">
                  <div class="mb-2 inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500">🗂️</div>
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
                  class="rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                  aria-label="Close">
            ✕
          </button>
        </div>

        <div class="space-y-4 px-6 py-5">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">IP <span class="text-rose-600">*</span></label>
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
          <button wire:click="save"
                  class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Save
          </button>
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
        <button
          wire:click="$set('showDelete', false)"
          class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
        >Cancel</button>

        <button
          wire:click="delete"
          wire:loading.attr="disabled"
          class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
        >
          <span wire:loading.remove>Delete</span>
          <span wire:loading>Deleting…</span>
        </button>
      </div>
    </div>
  </div>
@endif

  {{-- livewire loading hint --}}
  <div wire:loading
       class="fixed bottom-4 right-4 rounded-lg bg-slate-900/90 px-3 py-1.5 text-xs text-white shadow">
    Loading…
  </div>
</div>
