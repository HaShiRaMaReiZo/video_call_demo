<x-app-layout>
    <style>
        #active-call-root {
            min-height: calc(100vh - 9rem);
        }

        #active-call-ui.call-screen {
            width: min(98vw, 1800px);
            max-width: none !important;
            min-height: calc(100vh - 11rem);
            padding: 1.25rem;
        }

        #active-call-ui .call-screen__videos {
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.25rem;
        }

        #active-call-ui .call-tile__frame {
            aspect-ratio: 16 / 10;
            min-height: min(40vh, 480px);
        }

        @media (min-width: 1024px) {
            #active-call-ui .call-screen__videos {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #active-call-ui .call-tile__frame {
                min-height: min(56vh, 680px);
            }
        }
    </style>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Video call') }}
        </h2>
    </x-slot>

    <div
        id="active-call-root"
        class="py-6 px-3 sm:px-6"
        data-token-url="{{ route('stream.token') }}"
        data-dashboard-url="{{ route('dashboard') }}"
        data-call-type="{{ e($callType) }}"
        data-call-id="{{ e($callId) }}"
    >
        <div id="active-call-ui" class="call-screen mx-auto max-w-6xl">
            <div class="call-screen__header">
                <div>
                    <p class="call-screen__eyebrow">{{ __('Live call') }}</p>
                    <h3 class="call-screen__title">{{ __('Connected room') }}</h3>
                </div>
                <div id="active-call-status" class="call-status call-status--connecting">{{ __('Connecting...') }}</div>
            </div>

            <div id="active-call-error" class="hidden call-error"></div>

            <div class="call-screen__videos">
                <div class="call-tile">
                    <div class="call-tile__frame">
                        <video id="active-call-local" class="call-tile__video" playsinline autoplay muted></video>
                        <span class="call-tile__badge">{{ __('You') }}</span>
                    </div>
                    <p id="active-call-local-name" class="call-tile__name">{{ __('You') }}</p>
                </div>

                <div class="call-tile">
                    <div class="call-tile__frame">
                        <video id="active-call-remote" class="call-tile__video" playsinline autoplay></video>
                        <div id="active-call-remote-empty" class="call-tile__empty">
                            {{ __('Waiting for remote camera...') }}
                        </div>
                        <span class="call-tile__badge call-tile__badge--remote">{{ __('Remote') }}</span>
                    </div>
                    <p id="active-call-remote-name" class="call-tile__name">{{ __('Remote') }}</p>
                </div>
            </div>

            <div class="call-controls">
                <button type="button" id="active-call-toggle-mic" class="call-btn call-btn--secondary">{{ __('Microphone') }}</button>
                <button type="button" id="active-call-toggle-camera" class="call-btn call-btn--secondary">{{ __('Camera') }}</button>
                <button type="button" id="active-call-leave" class="call-btn call-btn--danger">{{ __('Leave') }}</button>
            </div>
        </div>
    </div>
    @push('scripts')
        <script type="module" src="{{ asset('build/assets/active-call-BpVD0CgI.js') }}"></script>
    @endpush
</x-app-layout>
