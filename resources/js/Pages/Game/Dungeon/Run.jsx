import GameLayout from '@/Layouts/GameLayout';
import InputError from '@/Components/InputError';
import { monsterNameClass, monsterTierPrefix } from '@/utils/mobTierDisplay';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function DungeonRun({
    character,
    run,
    effectiveMaxHp,
    potions = [],
    floorEnergy,
    combatEnergy,
    resourceEnergy,
    treasureEnergy,
}) {
    const fightForm = useForm({});
    const resourceForm = useForm({});
    const treasureForm = useForm({});
    const advanceForm = useForm({});
    const potionForm = useForm({});
    const { errors } = usePage().props;

    const floor = run.current_floor_state;
    const isLastFloor = run.current_floor >= run.floors_total;
    const canAdvance = floor?.mob_defeated && !isLastFloor;

    return (
        <GameLayout
            header={
                <h2 className="text-xl font-semibold text-gray-800">
                    {run.dungeon.name} — этаж {run.current_floor}/{run.floors_total}
                </h2>
            }
        >
            <Head title={run.dungeon.name} />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4">
                    <Link href={route('world.map')} className="mb-4 inline-block text-sm text-indigo-600 hover:underline">
                        ← К карте
                    </Link>
                    <p className="mb-4 text-sm text-amber-800">
                        Уход с локации данжа на карте сбросит прогресс по этажам. Полученный лут сохранится.
                    </p>

                    <InputError message={errors.dungeon} className="mb-4" />

                    <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <StatCard label="HP в данже" value={`${run.character_hp}/${effectiveMaxHp}`} />
                        <StatCard label="Опыт забега" value={run.run_xp_earned} />
                        <StatCard label="Золото забега" value={`${run.run_money_earned} 💰`} />
                        <StatCard label="Энергия" value={`⚡ ${character.energy}`} />
                    </div>

                    {potions.length > 0 && run.character_hp < effectiveMaxHp && (
                        <div className="mb-6 rounded-lg border bg-white p-5 shadow-sm">
                            <h3 className="mb-3 font-semibold">Зелья лечения</h3>
                            <ul className="space-y-2">
                                {potions.map((potion) => (
                                    <li key={potion.id} className="flex items-center justify-between text-sm">
                                        <span>
                                            {potion.name} (+{potion.hp_restore} HP) x{potion.quantity}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => potionForm.post(route('dungeon.potion', [run.id, potion.id]))}
                                            disabled={potionForm.processing}
                                            className="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700 disabled:opacity-50"
                                        >
                                            Выпить
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {floor && (
                        <div className="space-y-4">
                            <div className="rounded-lg border bg-white p-5 shadow-sm">
                                <h3 className="mb-3 font-semibold">Обязательно</h3>
                                <p className={`text-sm ${monsterNameClass(floor.mob_role)}`}>
                                    {monsterTierPrefix(floor.mob_role)}
                                    {floor.monster_name}
                                </p>
                                {floor.mob_defeated ? (
                                    <p className="mt-2 text-sm text-green-700">Побеждён</p>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => fightForm.post(route('dungeon.fight', run.id))}
                                        disabled={fightForm.processing || character.energy < combatEnergy}
                                        className="mt-3 rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                                    >
                                        Сразиться (⚡ {combatEnergy})
                                    </button>
                                )}
                            </div>

                            {floor.has_resource_pile && !floor.resource_claimed && (
                                <div className="rounded-lg border bg-white p-5 shadow-sm">
                                    <h3 className="mb-2 font-semibold">Скопление ресурсов</h3>
                                    <button
                                        type="button"
                                        onClick={() => resourceForm.post(route('dungeon.resource', run.id))}
                                        disabled={resourceForm.processing || character.energy < resourceEnergy}
                                        className="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50"
                                    >
                                        Собрать (⚡ {resourceEnergy})
                                    </button>
                                </div>
                            )}

                            {floor.has_treasure && !floor.treasure_claimed && (
                                <div className="rounded-lg border bg-white p-5 shadow-sm">
                                    <h3 className="mb-2 font-semibold">Сокровище</h3>
                                    <button
                                        type="button"
                                        onClick={() => treasureForm.post(route('dungeon.treasure', run.id))}
                                        disabled={treasureForm.processing || character.energy < treasureEnergy}
                                        className="rounded bg-yellow-600 px-4 py-2 text-sm text-white hover:bg-yellow-700 disabled:opacity-50"
                                    >
                                        Открыть (⚡ {treasureEnergy})
                                    </button>
                                </div>
                            )}

                            <div className="flex flex-wrap gap-3 pt-2">
                                {canAdvance && (
                                    <button
                                        type="button"
                                        onClick={() => advanceForm.post(route('dungeon.advance', run.id))}
                                        disabled={advanceForm.processing || character.energy < floorEnergy}
                                        className="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        Следующий этаж (⚡ {floorEnergy})
                                    </button>
                                )}
                            </div>

                            {isLastFloor && floor.mob_defeated && run.completed && (
                                <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                                    Данж пройден! Печать исследователя получена.
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </GameLayout>
    );
}

function StatCard({ label, value }) {
    return (
        <div className="rounded-lg border bg-white p-3 text-center shadow-sm">
            <p className="text-xs text-gray-500">{label}</p>
            <p className="font-semibold">{value}</p>
        </div>
    );
}
