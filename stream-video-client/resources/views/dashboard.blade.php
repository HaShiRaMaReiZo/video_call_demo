<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Client dashboard') }}
        </h2>
    </x-slot>

    <div
        id="client-dashboard"
        class="py-12"
        data-token-url="{{ route('stream.token') }}"
        data-call-base-url="{{ url('/call') }}"
        data-stream-configured="{{ $stream['configured'] ? '1' : '0' }}"
        data-user-stream-id="{{ auth()->user()->stream_user_id }}"
        data-i18n-connected="{{ e(__('You are connected for incoming Stream calls when this page is open.')) }}"
        data-i18n-not-configured="{{ e(__('Set STREAM_API_KEY and STREAM_API_SECRET in your .env file to enable video.')) }}"
        data-i18n-phone-label="{{ e(__('dashboard.phone_label')) }}"
        data-i18n-video-help="{{ e(__('dashboard.video_id_help')) }}"
        data-i18n-incoming="{{ e(__('dashboard.incoming_call')) }}"
        data-i18n-join="{{ e(__('dashboard.join_call')) }}"
        data-i18n-dismiss="{{ e(__('dashboard.dismiss')) }}"
        data-i18n-waiting="{{ e(__('dashboard.join_waiting')) }}"
        data-i18n-ready="{{ e(__('dashboard.join_ready')) }}"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="space-y-4 p-6 text-gray-900 dark:text-gray-100">
                    <p id="dashboard-stream-status"></p>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400" id="dashboard-phone-label"></p>
                        <code class="mt-1 block rounded bg-gray-100 dark:bg-gray-900 px-3 py-2 text-sm">{{ auth()->user()->phone ?? '—' }}</code>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Stream user id (internal)</p>
                        <code class="mt-1 block rounded bg-gray-100 dark:bg-gray-900 px-3 py-2 text-sm">{{ auth()->user()->stream_user_id ?? '—' }}</code>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" id="dashboard-video-help"></p>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400" id="dashboard-incoming-label"></p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" id="dashboard-join-status"></p>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <x-primary-button type="button" id="dashboard-join-btn" disabled>
                                <span id="dashboard-join-btn-label"></span>
                            </x-primary-button>
                            <x-secondary-button type="button" id="dashboard-dismiss-btn" class="hidden">
                                <span id="dashboard-dismiss-label"></span>
                            </x-secondary-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script type="module" src="{{ asset('build/assets/dashboard-client-qH3LpZ_U.js') }}"></script>
    @endpush
</x-app-layout>
