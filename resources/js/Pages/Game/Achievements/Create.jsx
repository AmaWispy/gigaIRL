import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import GameLayout from '@/Layouts/GameLayout';
import { Head, useForm } from '@inertiajs/react';

export default function AchievementsCreate({ frequencies }) {
    const form = useForm({
        type: 'routine',
        title: '',
        reward_points: 1,
        frequency: 'none',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('achievements.store'));
    };

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">Новая задача</h2>}>
            <Head title="Создать задачу" />

            <div className="py-8">
                <div className="mx-auto max-w-lg px-4">
                    <form onSubmit={submit} className="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                        <div>
                            <InputLabel value="Тип" />
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                value={form.data.type}
                                onChange={(e) => form.setData('type', e.target.value)}
                            >
                                <option value="routine">Обычное дело</option>
                                <option value="rare">Редкое (одноразовое)</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel htmlFor="title" value="Название" />
                            <TextInput
                                id="title"
                                className="mt-1 block w-full"
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.title} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="reward_points" value="Награда (очки энергии)" />
                            <TextInput
                                id="reward_points"
                                type="number"
                                min="1"
                                max="100"
                                className="mt-1 block w-full"
                                value={form.data.reward_points}
                                onChange={(e) => form.setData('reward_points', e.target.value)}
                                required
                            />
                        </div>

                        {form.data.type === 'routine' && (
                            <div>
                                <InputLabel value="Частота" />
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    value={form.data.frequency}
                                    onChange={(e) => form.setData('frequency', e.target.value)}
                                >
                                    {frequencies.map((f) => (
                                        <option key={f.value} value={f.value}>{f.label}</option>
                                    ))}
                                </select>
                            </div>
                        )}

                        <PrimaryButton disabled={form.processing}>Создать</PrimaryButton>
                    </form>
                </div>
            </div>
        </GameLayout>
    );
}
