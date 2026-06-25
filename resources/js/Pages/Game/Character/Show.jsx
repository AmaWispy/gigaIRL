import GameLayout from '@/Layouts/GameLayout';
import EquipmentStatsPreview from '@/Components/EquipmentStatsPreview';
import { itemDisplayName } from '@/utils/itemDisplayName';
import { Head, useForm } from '@inertiajs/react';

const slotLabels = {
    boots: 'Ботинки', pants: 'Штаны', weapon: 'Оружие', gloves: 'Перчатки',
    helmet: 'Шлем', belt: 'Пояс', ring1: 'Кольцо 1', ring2: 'Кольцо 2',
    necklace: 'Ожерелье', armor: 'Броня', cloak: 'Плащ',
};

export default function CharacterShow({ character, inventory, equipped, equipmentSlots, skills = [], maxSkillSlots = 1 }) {
    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">Персонаж</h2>}>
            <Head title="Персонаж" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-6 lg:grid-cols-2">
                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <h3 className="mb-4 text-lg font-semibold">Характеристики</h3>
                            <dl className="grid grid-cols-2 gap-3 text-sm">
                                <Stat label="Уровень" value={character.level} />
                                <Stat label="Опыт" value={`${character.xp} / ${character.xp_to_next_level}`} />
                                <Stat
                                    label="HP"
                                    value={formatHp(character.hp, character.effective_max_hp ?? character.max_hp, character.max_hp)}
                                />
                                <Stat
                                    label="Сила"
                                    value={formatStat(character.strength, character.effective_strength)}
                                />
                                <Stat
                                    label="Защита"
                                    value={formatStat(character.defense, character.effective_defense)}
                                />
                                <Stat label="Мощь" value={character.power} />
                                <Stat label="Деньги" value={character.money} />
                                <Stat label="Энергия" value={character.energy} />
                            </dl>
                            <div className="mt-4">
                                <div className="text-xs text-gray-500 mb-1">Опыт до следующего уровня</div>
                                <div className="h-3 w-full rounded-full bg-gray-200">
                                    <div
                                        className="h-3 rounded-full bg-blue-500"
                                        style={{ width: `${(character.xp / character.xp_to_next_level) * 100}%` }}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="rounded-lg bg-white p-6 shadow-sm">
                            <h3 className="mb-4 text-lg font-semibold">Экипировка</h3>
                            <div className="space-y-2">
                                {equipmentSlots.map((slot) => (
                                    <EquippedSlot key={slot} slot={slot} item={equipped[slot]} />
                                ))}
                            </div>
                        </div>

                        <div className="rounded-lg bg-white p-6 shadow-sm lg:col-span-2">
                            <h3 className="mb-1 text-lg font-semibold">Боевые навыки</h3>
                            <p className="mb-4 text-xs text-gray-500">
                                Экипировано {skills.filter((s) => s.is_equipped).length} / {maxSkillSlots}
                            </p>
                            {skills.length === 0 ? (
                                <p className="text-sm text-gray-500">
                                    Нет изученных приёмов.{' '}
                                    <a href={route('world.skills')} className="text-indigo-600 hover:underline">
                                        Наставник в городе
                                    </a>
                                </p>
                            ) : (
                                <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    {skills.map((skill) => (
                                        <SkillRow key={skill.id} skill={skill} maxSkillSlots={maxSkillSlots} equippedCount={skills.filter((s) => s.is_equipped).length} />
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="rounded-lg bg-white p-6 shadow-sm lg:col-span-2">
                            <h3 className="mb-4 text-lg font-semibold">Инвентарь</h3>
                            {inventory.length === 0 ? (
                                <p className="text-sm text-gray-500">Инвентарь пуст</p>
                            ) : (
                                <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                                    {inventory.map((inv) => (
                                        <InventoryRow key={inv.id} inv={inv} />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </GameLayout>
    );
}

function Stat({ label, value }) {
    return (
        <div>
            <dt className="text-gray-500">{label}</dt>
            <dd className="font-semibold text-gray-800">{value}</dd>
        </div>
    );
}

function formatStat(base, effective) {
    if (effective > base) {
        return `${effective} (${base}+${effective - base})`;
    }

    return String(effective ?? base);
}

function formatHp(current, effectiveMax, baseMax) {
    if (effectiveMax > baseMax) {
        return `${current} / ${effectiveMax} (${baseMax}+${effectiveMax - baseMax})`;
    }

    return `${current} / ${effectiveMax}`;
}

function SkillRow({ skill, maxSkillSlots, equippedCount }) {
    const equipForm = useForm({});
    const unequipForm = useForm({});

    return (
        <div className="rounded border px-3 py-2 text-sm">
            <div className="flex items-start justify-between gap-2">
                <div>
                    <p className="font-medium">{skill.name}</p>
                    <p className="text-xs text-gray-500">{skill.description}</p>
                </div>
                {skill.is_equipped ? (
                    <button
                        onClick={() => unequipForm.post(route('character.skills.unequip', skill.id), { preserveScroll: true })}
                        className="shrink-0 text-xs text-red-600 hover:underline"
                    >
                        Снять
                    </button>
                ) : (
                    <button
                        onClick={() => equipForm.post(route('character.skills.equip', skill.id), { preserveScroll: true })}
                        disabled={equipForm.processing || equippedCount >= maxSkillSlots}
                        className="shrink-0 text-xs text-indigo-600 hover:underline disabled:opacity-50"
                    >
                        Экипировать
                    </button>
                )}
            </div>
            {skill.is_equipped && (
                <p className="mt-1 text-xs text-green-700">Слот {skill.equip_slot}</p>
            )}
        </div>
    );
}

function EquippedSlot({ slot, item }) {
    const form = useForm({ slot });

    return (
        <div className="rounded border px-3 py-2 text-sm">
            <div className="flex items-center justify-between gap-2">
                <span className="text-gray-500">{slotLabels[slot] || slot}</span>
                {item ? (
                    <button
                        onClick={() => form.post(route('character.unequip'), { preserveScroll: true })}
                        className="text-xs text-red-600 hover:underline"
                    >
                        Снять
                    </button>
                ) : (
                    <span className="text-gray-400">—</span>
                )}
            </div>
            {item && (
                <div className="mt-1">
                    <p className="font-medium">{itemDisplayName(item.name, { stripQualityPrefix: true })}</p>
                    <EquipmentStatsPreview preview={equipmentPreview(item)} />
                </div>
            )}
        </div>
    );
}

function InventoryRow({ inv }) {
    const equipForm = useForm({});
    const useForm_ = useForm({});

    return (
        <div className="flex items-start justify-between gap-4 rounded-lg border border-gray-100 bg-gray-50/50 px-3 py-3 text-sm">
            <div className="min-w-0">
                <span className="font-medium">
                    {inv.item.type === 'equipment'
                        ? itemDisplayName(inv.item.name, { stripQualityPrefix: true })
                        : inv.item.name}
                </span>
                {inv.item.type !== 'equipment' && (
                    <span className="ml-2 text-gray-500">x{inv.quantity}</span>
                )}
                {inv.item.type === 'equipment' && (
                    <EquipmentStatsPreview preview={equipmentPreview(inv)} className="mt-0.5" />
                )}
                {inv.is_equipped && <span className="ml-2 text-xs text-blue-600">экипировано</span>}
            </div>
            <div className="flex shrink-0 gap-2">
                {inv.item.type === 'equipment' && !inv.is_equipped && (
                    <button
                        onClick={() => equipForm.post(route('character.equip', inv.id), { preserveScroll: true })}
                        className="text-indigo-600 hover:underline"
                    >
                        Надеть
                    </button>
                )}
                {inv.item.type === 'consumable' && (
                    <button
                        onClick={() => useForm_.post(route('character.use', inv.id), { preserveScroll: true })}
                        className="text-green-600 hover:underline"
                    >
                        Использовать
                    </button>
                )}
            </div>
        </div>
    );
}

function equipmentPreview(item) {
    if (!item?.stats && !item?.computed_stats) {
        return null;
    }

    return {
        quality_label: item.quality_label ?? '⚪',
        level: item.equipment_level ?? 1,
        stats: item.stats ?? item.computed_stats,
    };
}
