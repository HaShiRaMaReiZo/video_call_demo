import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router, usePage } from '@inertiajs/react';
import { StreamVideoClient } from '@stream-io/video-react-sdk';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

export default function Dashboard() {
    const { auth, stream, i18n } = usePage().props;
    const [incoming, setIncoming] = useState(null);
    const clientRef = useRef(null);
    const unsubscribeRef = useRef(null);

    useEffect(() => {
        if (!auth?.user?.stream_user_id || !stream?.configured) {
            return;
        }

        let cancelled = false;

        (async () => {
            try {
                const { data } = await axios.post(route('stream.token'));
                const client = new StreamVideoClient({
                    apiKey: data.apiKey,
                    user: {
                        id: data.user.id,
                        name: data.user.name,
                        type: 'authenticated',
                    },
                    token: data.token,
                });
                await client.connectUser(
                    {
                        id: data.user.id,
                        name: data.user.name,
                        type: 'authenticated',
                    },
                    data.token,
                );
                if (cancelled) {
                    await client.disconnectUser().catch(() => {});

                    return;
                }
                clientRef.current = client;
                unsubscribeRef.current = client.on('call.ring', (event) => {
                    const cid = event.call_cid || '';
                    const i = cid.indexOf(':');
                    if (i === -1) {
                        return;
                    }
                    setIncoming({
                        callType: cid.slice(0, i),
                        callId: cid.slice(i + 1),
                    });
                });
            } catch (e) {
                console.error(e);
            }
        })();

        return () => {
            cancelled = true;
            unsubscribeRef.current?.();
            unsubscribeRef.current = null;
            if (clientRef.current) {
                clientRef.current.disconnectUser().catch(() => {});
                clientRef.current = null;
            }
        };
    }, [auth?.user?.stream_user_id, stream?.configured]);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Client dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="space-y-4 p-6 text-gray-900">
                            <p>
                                {stream?.configured
                                    ? 'You are connected for incoming Stream calls when this page is open.'
                                    : 'Set STREAM_API_KEY and STREAM_API_SECRET in your .env file to enable video.'}
                            </p>
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    {i18n?.dashboard_phone_label}
                                </p>
                                <code className="mt-1 block rounded bg-gray-100 px-3 py-2 text-sm">
                                    {auth?.user?.phone || '—'}
                                </code>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-600">
                                    Stream user id (internal)
                                </p>
                                <code className="mt-1 block rounded bg-gray-100 px-3 py-2 text-sm">
                                    {auth?.user?.stream_user_id || '—'}
                                </code>
                                <p className="mt-2 text-sm text-gray-600">
                                    {i18n?.dashboard_video_help}
                                </p>
                            </div>

                            <div className="border-t border-gray-200 pt-4">
                                <p className="text-sm font-medium text-gray-600">
                                    {i18n?.incoming_call}
                                </p>
                                <p className="mt-1 text-sm text-gray-600">
                                    {incoming
                                        ? i18n?.join_ready
                                        : i18n?.join_waiting}
                                </p>
                                <div className="mt-4 flex flex-wrap items-center gap-3">
                                    <PrimaryButton
                                        type="button"
                                        disabled={
                                            !incoming || !stream?.configured
                                        }
                                        onClick={() => {
                                            if (!incoming) {
                                                return;
                                            }
                                            router.visit(
                                                route('video.call', {
                                                    callType: incoming.callType,
                                                    callId: incoming.callId,
                                                }),
                                            );
                                        }}
                                    >
                                        {i18n?.join_call}
                                    </PrimaryButton>
                                    {incoming && (
                                        <SecondaryButton
                                            type="button"
                                            onClick={() =>
                                                setIncoming(null)
                                            }
                                        >
                                            {i18n?.dismiss}
                                        </SecondaryButton>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
