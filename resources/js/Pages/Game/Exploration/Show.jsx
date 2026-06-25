import InputError from '@/Components/InputError';
import GameLayout from '@/Layouts/GameLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const actionLabels = {
    monster: '👹 Встреча с монстром',
    gather: '🌿 Сбор ресурсов',
    treasure: '💎 Сокровище',
    resource_cache: '📦 Скопление ресурсов',
    rare_monster: '⚔️ Редкий монстр',
    boss: '💀 Босс',
    dungeon_entrance: '🚪 Вход в данж',
};

export default function ExplorationShow({ character, location, session, lookAroundEnergy, flavor }) {
    const lookForm = useForm({});
    const { errors } = usePage().props;

    const hasUnresolvedActions = session?.actions.some((a) => !a.is_resolved);

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">Исследование: {location.name}</h2>}>
            <Head title="Исследование" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4">
                    <Link
                        href={route('world.map')}
                        className="mb-4 inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 hover:underline"
                    >
                        ← Вернуться на карту
                    </Link>

                    <p className="mb-4 text-gray-600">{location.description}</p>
                    <p className="mb-6 text-sm text-amber-700">
                        Мин. мощь локации: {location.min_power}. Ваша мощь: {character.power}.
                        {character.power < location.min_power && ' Осторожно — возможно нападение!'}
                    </p>

                    <InputError message={errors.exploration || errors.action} className="mb-4" />

                    {flavor && (
                        <p className="mb-4 rounded-lg bg-purple-50 p-3 text-sm italic text-purple-800">{flavor}</p>
                    )}

                    {!session ? (
                        <button
                            onClick={() => lookForm.post(route('exploration.look'))}
                            disabled={lookForm.processing}
                            className="rounded-lg bg-indigo-600 px-6 py-3 text-white hover:bg-indigo-700"
                        >
                            Осмотреться (⚡ {lookAroundEnergy})
                        </button>
                    ) : (
                        <div className="space-y-4">
                            <div className="rounded-lg bg-white p-6 shadow-sm">
                                <h3 className="mb-4 text-lg font-semibold">Доступные действия</h3>
                                <div className="space-y-3">
                                    {session.actions.map((action) => (
                                        <ActionButton key={action.id} action={action} />
                                    ))}
                                </div>
                            </div>

                            {hasUnresolvedActions ? (
                                <div className="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                                    <p className="mb-3 text-sm text-gray-600">
                                        Не нравятся варианты? Можно осмотреться снова — появятся новые действия.
                                    </p>
                                    <button
                                        onClick={() => lookForm.post(route('exploration.look'))}
                                        disabled={lookForm.processing}
                                        className="rounded-lg border border-indigo-600 px-4 py-2 text-sm text-indigo-600 hover:bg-indigo-50"
                                    >
                                        Осмотреться снова (⚡ {lookAroundEnergy})
                                    </button>
                                </div>
                            ) : (
                                <div className="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                                    <p className="mb-3 text-sm text-gray-600">
                                        Все действия выполнены. Осмотритесь снова, чтобы найти что-то ещё.
                                    </p>
                                    <button
                                        onClick={() => lookForm.post(route('exploration.look'))}
                                        disabled={lookForm.processing}
                                        className="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                                    >
                                        Осмотреться снова (⚡ {lookAroundEnergy})
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </GameLayout>
    );
}

function ActionButton({ action }) {
    const form = useForm({});

    if (action.is_resolved) {
        return (
            <div className="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm">
                <div className="text-gray-600 line-through">
                    {actionLabels[action.action_type] || action.action_type}
                </div>
                {action.result_message && (
                    <div className="mt-1 font-medium text-green-700">{action.result_message}</div>
                )}
            </div>
        );
    }

    return (
        <button
            onClick={() => form.post(route('exploration.resolve', action.id))}
            disabled={form.processing}
            className="flex w-full items-center justify-between rounded border border-gray-200 px-4 py-3 text-left hover:bg-gray-50"
        >
            <span>{actionLabels[action.action_type] || action.action_type}</span>
            <span className="text-amber-600">⚡ {action.energy_cost}</span>
        </button>
    );
}
