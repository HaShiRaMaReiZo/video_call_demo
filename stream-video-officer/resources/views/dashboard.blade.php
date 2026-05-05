<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Officer dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (! $stream['configured'])
                        <p class="mb-4 text-amber-800 dark:text-amber-200">
                            {{ __('Set STREAM_API_KEY and STREAM_API_SECRET in your .env (same Video app as the client demo).') }}
                        </p>
                    @endif
                    @if (! $stream['client_lookup_configured'])
                        <p class="mb-4 text-amber-800 dark:text-amber-200">
                            {{ __('Set CLIENT_APP_URL (client app base URL) and OFFICER_LOOKUP_TOKEN (same value as on the client app) to look up clients by phone.') }}
                        </p>
                    @endif

                    <h3 class="text-lg font-medium">
                        {{ __('dashboard.officer_ring_title') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('dashboard.officer_ring_help') }}
                    </p>

                    <form method="POST" action="{{ route('calls.ring') }}" class="mt-6 max-w-xl space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="client_phone" :value="__('dashboard.officer_target_label')" />
                            <x-text-input id="client_phone" class="block mt-1 w-full" type="tel" name="client_phone" :value="old('client_phone')" required autocomplete="tel national" />
                            <x-input-error :messages="$errors->get('client_phone')" class="mt-2" />
                        </div>
                        <x-primary-button>
                            {{ __('dashboard.officer_start_call') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
