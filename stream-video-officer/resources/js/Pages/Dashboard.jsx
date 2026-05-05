import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { stream, i18n } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        client_phone: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('calls.ring'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Officer dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {!stream?.configured && (
                                <p className="mb-4 text-amber-800">
                                    Set STREAM_API_KEY and STREAM_API_SECRET in your
                                    .env (same Video app as the client demo).
                                </p>
                            )}
                            {!stream?.client_lookup_configured && (
                                <p className="mb-4 text-amber-800">
                                    Set CLIENT_APP_URL (client app base URL) and
                                    OFFICER_LOOKUP_TOKEN (same value as on the client
                                    app) to look up clients by phone.
                                </p>
                            )}
                            <h3 className="text-lg font-medium">
                                {i18n?.officer_ring_title}
                            </h3>
                            <p className="mt-2 text-sm text-gray-600">
                                {i18n?.officer_ring_help}
                            </p>
                            <form onSubmit={submit} className="mt-6 max-w-xl space-y-4">
                                <div>
                                    <InputLabel
                                        htmlFor="client_phone"
                                        value={i18n?.officer_target_label}
                                    />
                                    <TextInput
                                        id="client_phone"
                                        type="tel"
                                        className="mt-1 block w-full"
                                        value={data.client_phone}
                                        onChange={(e) =>
                                            setData(
                                                'client_phone',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="tel national"
                                        required
                                    />
                                    <InputError
                                        message={errors.client_phone}
                                        className="mt-2"
                                    />
                                </div>
                                <PrimaryButton disabled={processing}>
                                    {i18n?.officer_start_call}
                                </PrimaryButton>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
