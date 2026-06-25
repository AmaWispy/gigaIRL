import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import GameLayout from '@/Layouts/GameLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AchievementsIndex({
    defaults,
    userRoutine,
    userRare,
    todayCompletions,
    unavailableTemplateIds,
    financialRates,
}) {
    const { errors } = usePage().props;
    const [tab, setTab] = useState('financial');

    const financialForm = useForm({
        amount_rubles: '',
        is_primary_income: true,
    });

    const submitFinancial = (e) => {
        e.preventDefault();
        financialForm.post(route('achievements.financial'), {
            preserveScroll: true,
            onSuccess: () => financialForm.reset(),
        });
    };

    const tabs = [
        { id: 'financial', label: 'Финансы' },
        { id: 'routine', label: 'Дела' },
        { id: 'rare', label: 'Редкие' },
        { id: 'history', label: 'Сегодня' },
    ];

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">Достижения</h2>}>
            <Head title="Достижения" />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <InputError message={errors.achievement} className="mb-4" />

                    <div className="mb-6 flex gap-2 border-b">
                        {tabs.map((t) => (
                            <button
                                key={t.id}
                                onClick={() => setTab(t.id)}
                                className={`px-4 py-2 text-sm font-medium ${
                                    tab === t.id
                                        ? 'border-b-2 border-indigo-600 text-indigo-600'
                                        : 'text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                {t.label}
                            </button>
                        ))}
                    </div>

                    {tab === 'financial' && (
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <h3 className="mb-4 text-lg font-semibold">Финансовые достижения</h3>
                            <p className="mb-4 text-sm text-gray-600">
                                Основной доход: {financialRates.primary} ₽ = 1 очко.
                                Дополнительный: {financialRates.secondary} ₽ = 1 очко.
                            </p>
                            <form onSubmit={submitFinancial} className="space-y-4">
                                <div>
                                    <InputLabel htmlFor="amount_rubles" value="Сумма (рубли)" />
                                    <TextInput
                                        id="amount_rubles"
                                        type="number"
                                        className="mt-1 block w-full"
                                        value={financialForm.data.amount_rubles}
                                        onChange={(e) => financialForm.setData('amount_rubles', e.target.value)}
                                        required
                                    />
                                    <InputError message={financialForm.errors.amount_rubles} className="mt-2" />
                                </div>
                                <div className="flex gap-4">
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            checked={financialForm.data.is_primary_income}
                                            onChange={() => financialForm.setData('is_primary_income', true)}
                                        />
                                        Основной доход
                                    </label>
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            checked={!financialForm.data.is_primary_income}
                                            onChange={() => financialForm.setData('is_primary_income', false)}
                                        />
                                        Дополнительный
                                    </label>
                                </div>
                                <PrimaryButton disabled={financialForm.processing}>
                                    Получить энергию
                                </PrimaryButton>
                            </form>
                        </div>
                    )}

                    {tab === 'routine' && (
                        <div className="space-y-6">
                            <div className="rounded-lg bg-white p-6 shadow-sm">
                                <h3 className="mb-4 text-lg font-semibold">Быстрые дела</h3>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {defaults.map((d) => (
                                        <TemplateButton
                                            key={d.id}
                                            template={d}
                                            isCompleted={unavailableTemplateIds.includes(d.id)}
                                        />
                                    ))}
                                </div>
                            </div>
                            <div className="rounded-lg bg-white p-6 shadow-sm">
                                <div className="mb-4 flex items-center justify-between">
                                    <h3 className="text-lg font-semibold">Мои дела</h3>
                                    <Link href={route('achievements.create')} className="text-sm text-indigo-600 hover:underline">
                                        + Создать
                                    </Link>
                                </div>
                                {userRoutine.length === 0 ? (
                                    <p className="text-sm text-gray-500">Нет пользовательских дел</p>
                                ) : (
                                    <div className="space-y-2">
                                        {userRoutine.map((t) => (
                                            <TemplateButton
                                                key={t.id}
                                                template={t}
                                                isCompleted={unavailableTemplateIds.includes(t.id)}
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {tab === 'rare' && (
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="text-lg font-semibold">Редкие задачи</h3>
                                <Link href={route('achievements.create')} className="text-sm text-indigo-600 hover:underline">
                                    + Создать
                                </Link>
                            </div>
                            {userRare.length === 0 ? (
                                <p className="text-sm text-gray-500">Нет редких задач</p>
                            ) : (
                                <div className="space-y-2">
                                    {userRare.map((t) => (
                                        <TemplateButton
                                            key={t.id}
                                            template={t}
                                            isCompleted={unavailableTemplateIds.includes(t.id)}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {tab === 'history' && (
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <h3 className="mb-4 text-lg font-semibold">Выполнено сегодня</h3>
                            {todayCompletions.length === 0 ? (
                                <p className="text-sm text-gray-500">Пока ничего</p>
                            ) : (
                                <ul className="divide-y">
                                    {todayCompletions.map((c) => (
                                        <li key={c.id} className="flex justify-between py-2 text-sm">
                                            <span>{c.title}</span>
                                            <span className="text-amber-600">
                                                +{c.metadata?.points_earned ?? '?'} ⚡
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </GameLayout>
    );
}

function TemplateButton({ template, isCompleted }) {
    const form = useForm({});

    return (
        <button
            onClick={() => form.post(route('achievements.complete', template.id), { preserveScroll: true })}
            disabled={form.processing || isCompleted}
            className={`flex w-full items-center justify-between rounded border px-4 py-3 text-left ${
                isCompleted
                    ? 'border-green-200 bg-green-50 text-gray-500 cursor-not-allowed'
                    : 'border-gray-200 hover:bg-gray-50'
            }`}
        >
            <span>
                {template.title}
                {isCompleted && <span className="ml-2 text-xs text-green-600">✓ лимит исчерпан</span>}
            </span>
            <span className={`font-semibold ${isCompleted ? 'text-gray-400' : 'text-amber-600'}`}>
                +{template.reward_points || template.difficulty} ⚡
            </span>
        </button>
    );
}
