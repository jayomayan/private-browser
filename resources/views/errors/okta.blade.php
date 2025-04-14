<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="text-center">
            <h2 class="text-xl font-semibold text-red-600">
                {{ __('Login Error') }}
            </h2>

            <p class="mt-4 text-gray-700">
                Your Okta login could not be completed.<br>
                You may not be assigned to this application.
            </p>

            <p class="mt-2 text-sm text-gray-500">
                Please contact IT support or try again later.
            </p>

            <div class="mt-6">
                <a href="{{ url('/') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md
                          font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                          transition ease-in-out duration-150">
                    Return to Home
                </a>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>