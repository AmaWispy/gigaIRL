import GameLayout from '@/Layouts/GameLayout';
import InputError from '@/Components/InputError';
import { DescriptionWithSetTooltip } from '@/Components/SetNameTooltip';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function WorldMap({ character, destinations, dungeonPanel }) {
    const { errors } = usePage().props;

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">Карта мира</h2>}>
            <Head title="Карта" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4">
                    <InputError message={errors.travel || errors.dungeon} className="mb-4 rounded bg-red-50 p-3" />

                    <div className="mb-6 rounded-lg bg-white p-4 shadow-sm">
                        <p className="text-sm text-gray-500">Вы находитесь в</p>
                        <p className="text-xl font-semibold">{character.current_location?.name ?? '—'}</p>
                        {!character.current_location && (
                            <p className="mt-2 text-sm text-red-600">
                                Локация не задана. Обратитесь к администратору или пересоздайте персонажа.
                            </p>
                        )}
                        <p className="text-sm text-gray-600">
                            {character.current_location?.type === 'dungeon' ? '🏰 Данж' : character.current_location?.is_safe ? '🛡 Безопасно' : '⚠ Опасно'}
                            <span className="ml-3 text-amber-700">⚡ {character.energy} энергии</span>
                        </p>
                        {character.energy === 0 && (
                            <p className="mt-2 text-sm text-amber-800">
                                Для перемещения нужна энергия. Отметьте{' '}
                                <Link href={route('achievements.index')} className="font-medium text-indigo-600 hover:underline">
                                    достижение
                                </Link>
                                {' '}или используйте камень перемещения 🪨 (без траты энергии).
                            </p>
                        )}
                        {dungeonPanel ? (
                            <DungeonPanel panel={dungeonPanel} character={character} />
                        ) : !character.current_location?.is_safe ? (
                            <Link href={route('exploration.show')} className="mt-2 inline-block text-indigo-600 hover:underline">
                                Исследовать локацию →
                            </Link>
                        ) : (
                            <Link href={route('world.city')} className="mt-2 inline-block text-indigo-600 hover:underline">
                                Места в городе →
                            </Link>
                        )}
                    </div>

                    <h3 className="mb-3 text-lg font-semibold">Доступные направления</h3>
                    {dungeonPanel?.active_run_id && (
                        <p className="mb-3 text-sm text-amber-800">
                            Активный забег в данже. Уход в другую локацию сбросит прогресс по этажам (лут сохранится).
                        </p>
                    )}
                    {destinations.length === 0 ? (
                        <p className="text-sm text-gray-500">Нет доступных путей</p>
                    ) : (
                        <div className="space-y-3">
                            {destinations.map((dest) => (
                                <DestinationCard key={dest.id} dest={dest} energy={character.energy} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </GameLayout>
    );
}

function DungeonPanel({ panel, character }) {
    const startForm = useForm({});

    if (panel.active_run_id) {
        return (
            <div className="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                <p className="font-semibold text-indigo-900">{panel.name}</p>
                <p className="mt-1 text-sm text-indigo-800">
                    <DescriptionWithSetTooltip description={panel.description} set={panel.set} />
                </p>
                <Link
                    href={route('dungeon.run', panel.active_run_id)}
                    className="mt-3 inline-block rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                >
                    Продолжить забег →
                </Link>
            </div>
        );
    }

    return (
        <div className="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
            <p className="font-semibold text-indigo-900">{panel.name}</p>
            <p className="mt-1 text-sm text-indigo-800">
                <DescriptionWithSetTooltip description={panel.description} set={panel.set} />
            </p>
            <p className="mt-2 text-sm text-gray-600">
                Мин. мощь: {panel.min_power}. Ваша: {character.power}.
                {panel.pass_count > 0 && (
                    <span className="ml-2 text-indigo-700">Пропусков: {panel.pass_count}</span>
                )}
            </p>

            <div className="mt-3 flex flex-wrap gap-2">
                {panel.entrance_available && panel.pass_count === 0 && (
                    <span className="rounded bg-purple-100 px-3 py-1.5 text-sm text-purple-800">
                        Вход найден при исследовании
                    </span>
                )}

                {panel.can_enter ? (
                    <button
                        type="button"
                        onClick={() => startForm.post(route('dungeon.start', panel.id))}
                        disabled={
                            startForm.processing ||
                            character.energy < panel.entry_energy ||
                            character.power < panel.min_power
                        }
                        className="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Войти в данж (⚡ {panel.entry_energy})
                    </button>
                ) : (
                    <p className="text-sm text-amber-800">
                        Нужен пропуск в инвентаре — купите у гильдмастера в Старограде.
                    </p>
                )}
            </div>
        </div>
    );
}

function DestinationCard({ dest, energy }) {
    const travelForm = useForm({ use_teleport_stone: false });
    const teleportForm = useForm({ use_teleport_stone: true });
    const canWalk = energy >= dest.energy_cost;
    const canTravel = dest.can_travel !== false;

    return (
        <div className="flex items-center justify-between rounded-lg border bg-white p-4 shadow-sm">
            <div>
                <p className="font-semibold">{dest.name}</p>
                <p className="text-sm text-gray-500">
                    {dest.type === 'dungeon' ? 'Данж' : dest.is_safe ? 'Безопасно' : 'Опасно'} · ⚡ {dest.energy_cost}
                </p>
                {dest.requires_dungeon_pass && (
                    <p className="text-xs text-amber-700">Нужен пропуск у гильдмастера</p>
                )}
                {!canWalk && (
                    <p className="text-xs text-amber-700">Недостаточно энергии для пешего перехода</p>
                )}
            </div>
            <div className="flex gap-2">
                <button
                    onClick={() => travelForm.post(route('world.travel', dest.id), { preserveScroll: true })}
                    disabled={travelForm.processing || !canWalk || !canTravel}
                    className="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    title={
                        !canTravel
                            ? 'Нужен пропуск в данж'
                            : canWalk
                              ? 'Идти пешком'
                              : `Нужно ${dest.energy_cost} энергии`
                    }
                >
                    Идти
                </button>
                <button
                    onClick={() => teleportForm.post(route('world.travel', dest.id), { preserveScroll: true })}
                    disabled={teleportForm.processing || !canTravel}
                    className="rounded border border-indigo-600 px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 disabled:opacity-50"
                    title={canTravel ? 'Камень перемещения' : 'Нужен пропуск в данж'}
                >
                    🪨
                </button>
            </div>
        </div>
    );
}
