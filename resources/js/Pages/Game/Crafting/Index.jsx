import GameLayout from '@/Layouts/GameLayout';
import EquipmentStatsPreview from '@/Components/EquipmentStatsPreview';
import { itemDisplayName } from '@/utils/itemDisplayName';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const recipeTabs = [
    {
        id: 'basic',
        label: 'Базовые',
        description: 'Стандартные рецепты кузнеца. Качество при создании может улучшиться.',
        emptyText: 'Нет доступных базовых рецептов',
    },
    {
        id: 'rare',
        label: 'Редкие',
        description: 'Доступны только при наличии свитка в инвентаре. Свиток расходуется при крафте.',
        emptyText: 'Нет изученных редких рецептов. Купите свиток у алхимика в деревне.',
    },
];

export default function CraftingIndex({
    character,
    basicRecipes = [],
    rareRecipes = [],
    blacksmithRanks = [],
    upgradeOptions = [],
}) {
    const professionForm = useForm({ profession: 'blacksmith' });
    const [recipeTab, setRecipeTab] = useState('basic');

    const recipesByTab = {
        basic: basicRecipes,
        rare: rareRecipes,
    };

    const activeTab = recipeTabs.find((t) => t.id === recipeTab) ?? recipeTabs[0];
    const activeRecipes = recipesByTab[recipeTab] ?? [];

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">Ремесло</h2>}>
            <Head title="Ремесло" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl px-4">
                    <div className="mb-6 rounded-lg bg-white p-4 shadow-sm">
                        <p className="text-sm text-gray-500">Текущая профессия</p>
                        <p className="text-lg font-semibold">
                            {character.profession === 'none' ? 'Нет профессии' : 'Кузнец'}
                        </p>
                        {character.profession === 'blacksmith' && character.blacksmith_rank && (
                            <p className="text-sm text-amber-700">
                                Ранг: {blacksmithRanks.find((r) => r.is_current)?.label ?? character.blacksmith_rank}
                            </p>
                        )}
                        {character.profession === 'none' && (
                            <button
                                onClick={() => professionForm.post(route('crafting.profession'), { preserveScroll: true })}
                                disabled={professionForm.processing}
                                className="mt-2 rounded bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700"
                            >
                                Стать кузнецом (подмастерье)
                            </button>
                        )}
                    </div>

                    {character.profession === 'blacksmith' && blacksmithRanks.length > 0 && (
                        <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <h3 className="mb-3 font-semibold">Ранги кузнеца</h3>
                            <div className="space-y-2">
                                {blacksmithRanks.map((rank) => (
                                    <RankRow key={rank.key} rank={rank} />
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="mb-6">
                        <div className="mb-4 flex gap-2 border-b">
                            {recipeTabs.map((tab) => {
                                const count = recipesByTab[tab.id]?.length ?? 0;

                                return (
                                    <button
                                        key={tab.id}
                                        type="button"
                                        onClick={() => setRecipeTab(tab.id)}
                                        className={`px-4 py-2 text-sm font-medium ${
                                            recipeTab === tab.id
                                                ? 'border-b-2 border-indigo-600 text-indigo-600'
                                                : 'text-gray-500 hover:text-gray-700'
                                        }`}
                                    >
                                        {tab.label}
                                        {count > 0 && (
                                            <span className="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                {count}
                                            </span>
                                        )}
                                    </button>
                                );
                            })}
                        </div>

                        <p className="mb-4 text-sm text-gray-500">{activeTab.description}</p>

                        {activeRecipes.length === 0 ? (
                            <p className="text-sm text-gray-500">{activeTab.emptyText}</p>
                        ) : (
                            <div className="grid gap-4 lg:grid-cols-2">
                                {activeRecipes.map((recipe) => (
                                    <RecipeCard key={recipe.id} recipe={recipe} />
                                ))}
                            </div>
                        )}
                    </div>

                    {upgradeOptions.length > 0 && (
                        <div className="mt-10">
                            <h3 className="mb-4 text-lg font-semibold">Улучшение экипировки</h3>
                            <p className="mb-4 text-sm text-gray-500">
                                Снимите предмет перед улучшением. Крафт — ресурсы + печати; данж — сферы.
                            </p>
                            <div className="grid gap-4 lg:grid-cols-2">
                                {upgradeOptions.map((option) => (
                                    <UpgradeCard key={option.inventory_item_id} option={option} />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </GameLayout>
    );
}

function RankRow({ rank }) {
    const form = useForm({ rank: rank.key });

    return (
        <div className="flex items-center justify-between rounded bg-white p-3 text-sm">
            <div>
                <span className="font-medium">{rank.label}</span>
                <span className="ml-2 text-gray-500">ур. {rank.min_level}+ · {rank.cost} 💰</span>
                {rank.is_current && <span className="ml-2 text-green-600">(текущий)</span>}
            </div>
            {rank.can_upgrade && (
                <button
                    onClick={() => form.post(route('crafting.rank'), { preserveScroll: true })}
                    disabled={form.processing}
                    className="rounded bg-amber-600 px-3 py-1 text-xs text-white hover:bg-amber-700"
                >
                    Повысить
                </button>
            )}
        </div>
    );
}

function RecipeCard({ recipe }) {
    const form = useForm({});

    return (
        <div className={`rounded-lg border p-4 shadow-sm ${recipe.can_craft ? 'bg-white' : 'bg-gray-50 opacity-75'}`}>
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0 flex-1">
                    <h4 className="font-semibold">{recipe.name}</h4>
                    <p className="text-sm text-gray-600">
                        Результат: {recipe.result.name} x{recipe.result.quantity}
                    </p>
                    <EquipmentStatsPreview preview={recipe.result.equipment_preview} className="mt-1" />
                    {!recipe.upgradable && (
                        <p className="mt-1 text-xs text-blue-700">Не улучшается</p>
                    )}
                    <p className={`mt-2 text-sm ${recipe.energy?.has_enough ? 'text-gray-500' : 'text-red-600'}`}>
                        ⚡ {recipe.energy?.owned ?? 0}/{recipe.energy?.required ?? recipe.energy_cost}
                        {!recipe.energy?.has_enough && ' — не хватает энергии'}
                    </p>
                    <ul className="mt-2 space-y-1 text-sm">
                        {recipe.ingredients.map((ing) => (
                            <li
                                key={`${ing.item_id}-${ing.name}`}
                                className={ing.has_enough ? 'text-gray-600' : 'text-red-600'}
                            >
                                {ing.consumed ? '📜 ' : ''}{ing.name}: {ing.owned}/{ing.required}
                                {ing.has_enough ? (
                                    <span className="ml-1 text-green-600">✓</span>
                                ) : (
                                    <span className="ml-1 text-red-600">(не хватает {ing.missing})</span>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
                <button
                    onClick={() => form.post(route('crafting.craft', recipe.id), { preserveScroll: true })}
                    disabled={form.processing || !recipe.can_craft}
                    className="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    Создать
                </button>
            </div>
        </div>
    );
}

function UpgradeCard({ option }) {
    const sourceLabel = {
        vendor: 'торговая',
        crafted: 'крафт',
        dungeon: 'данж',
    }[option.equipment_source] ?? option.equipment_source;

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div className="mb-2">
                <h4 className="font-semibold">{itemDisplayName(option.name, { stripQualityPrefix: true })}</h4>
                <p className="text-sm text-gray-600">
                    {option.quality_label} · ур. {option.equipment_level} · {sourceLabel}
                </p>
                {option.stats && (
                    <p className="text-xs text-gray-500">
                        БСП {option.stats.bsp} · +{option.stats.strength} урон · +{option.stats.defense} броня
                    </p>
                )}
            </div>
            <div className="flex flex-wrap gap-2">
                {option.actions.map((action) => (
                    <button
                        key={action.type}
                        onClick={() => router.post(
                            route('crafting.upgrade', option.inventory_item_id),
                            { action: action.type },
                            { preserveScroll: true },
                        )}
                        className="rounded bg-slate-700 px-3 py-1.5 text-xs text-white hover:bg-slate-800"
                        title={formatUpgradeCost(action.cost)}
                    >
                        {action.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

function formatUpgradeCost(cost) {
    const parts = [];
    if (cost.craftsman_seal) parts.push(`печать ×${cost.craftsman_seal}`);
    if (cost.transformation_sphere) parts.push(`сфера ×${cost.transformation_sphere}`);
    if (cost.energy) parts.push(`⚡ ${cost.energy}`);
    if (cost.ingredients?.length) {
        parts.push(cost.ingredients.map((i) => `${i.name} ×${i.quantity}`).join(', '));
    }
    return parts.join(' · ');
}
