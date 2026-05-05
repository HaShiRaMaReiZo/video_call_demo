import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import {
    CallControls,
    SpeakerLayout,
    StreamCall,
    StreamTheme,
    StreamVideo,
    StreamVideoClient,
} from '@stream-io/video-react-sdk';
import '@stream-io/video-styling/dist/css/styles.css';
import axios from 'axios';
import { useCallback, useEffect, useState } from 'react';

export default function ActiveCall() {
    const { callType, callId } = usePage().props;
    const [client, setClient] = useState(null);
    const [call, setCall] = useState(null);
    const [error, setError] = useState(null);

    const leave = useCallback(async () => {
        try {
            await call?.leave?.();
        } catch {
            //
        }
        try {
            await client?.disconnectUser?.();
        } catch {
            //
        }
        router.visit(route('dashboard'));
    }, [call, client]);

    useEffect(() => {
        let cancelled = false;
        let videoClient = null;

        (async () => {
            try {
                const { data } = await axios.post(route('stream.token'));
                videoClient = new StreamVideoClient({
                    apiKey: data.apiKey,
                    user: {
                        id: data.user.id,
                        name: data.user.name,
                        type: 'authenticated',
                    },
                    token: data.token,
                });
                await videoClient.connectUser(
                    {
                        id: data.user.id,
                        name: data.user.name,
                        type: 'authenticated',
                    },
                    data.token,
                );
                const c = videoClient.call(callType, callId);
                await c.join({ create: true });
                if (cancelled) {
                    await c.leave?.().catch(() => {});
                    await videoClient.disconnectUser().catch(() => {});

                    return;
                }
                setClient(videoClient);
                setCall(c);
            } catch (e) {
                setError(
                    e?.response?.data?.message ||
                        e?.message ||
                        'Could not join the call.',
                );
            }
        })();

        return () => {
            cancelled = true;
            if (videoClient) {
                videoClient.disconnectUser().catch(() => {});
            }
        };
    }, [callType, callId]);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Officer video call
                </h2>
            }
        >
            <Head title="Video call" />

            <div className="py-4">
                {error && (
                    <div className="mx-auto mb-4 max-w-3xl rounded border border-red-200 bg-red-50 p-4 text-red-800">
                        {error}
                    </div>
                )}
                {client && call && (
                    <div className="mx-auto max-w-6xl">
                        <StreamVideo client={client}>
                            <StreamTheme>
                                <StreamCall call={call}>
                                    <SpeakerLayout />
                                    <div className="mt-4 flex justify-center gap-2">
                                        <CallControls onLeave={leave} />
                                    </div>
                                </StreamCall>
                            </StreamTheme>
                        </StreamVideo>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
