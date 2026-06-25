import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ProfileSetup() {
    const { data, setData, post, processing, errors } = useForm({
        nickname: '',
        status: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('profile.setup.store'));
    };

    return (
        <GuestLayout>
            <Head title="Настройка профиля" />

            <div className="mb-4 text-sm text-gray-600">
                Email подтверждён. Укажите ник и статус, чтобы завершить
                регистрацию.
            </div>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="nickname" value="Ник" />

                    <TextInput
                        id="nickname"
                        name="nickname"
                        value={data.nickname}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('nickname', e.target.value)}
                        required
                    />

                    <InputError message={errors.nickname} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="status" value="Статус" />

                    <TextInput
                        id="status"
                        name="status"
                        value={data.status}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('status', e.target.value)}
                        required
                    />

                    <InputError message={errors.status} className="mt-2" />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>
                        Продолжить
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
