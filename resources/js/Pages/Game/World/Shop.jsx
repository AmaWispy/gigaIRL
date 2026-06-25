import InputError from '@/Components/InputError';
import EquipmentStatsPreview from '@/Components/EquipmentStatsPreview';
import GameLayout from '@/Layouts/GameLayout';
import { itemDisplayName } from '@/utils/itemDisplayName';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Shop({ character, poiTitle, greeting, offers, inventory = [], sellRatioPercent = 70 }) {
    const { errors } = usePage().props;

    const ownedByItemId = Object.fromEntries(
        inventory.map((inv) => [inv.item.id, inv.quantity]),
    );

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">{poiTitle}</h2>}>
            <Head title={poiTitle} />

            <div className="py-8">
                <div className="mx-auto max-w-6xl px-4">
                    <Link href={route('world.city')} className="text-sm text-indigo-600 hover:underline">
                        ← Назад в город
                    </Link>

                    {greeting && (
                        <p className="mt-4 rounded-lg bg-amber-50 p-4 text-sm italic text-amber-900">{greeting}</p>
                    )}

                    <p className="mt-4 text-sm text-gray-600">Ваше золото: {character.money} 💰</p>

                    <InputError message={errors.shop} className="mt-4" />

                    <div className="mt-6 grid gap-6 lg:grid-cols-3">
                        <div className="lg:col-span-2">
                            <h3 className="mb-3 text-lg font-semibold">Товары</h3>
                            <div className="space-y-3">
                                {offers.map((offer) => (
                                    <OfferRow
                                        key={offer.id}
                                        offer={offer}
                                        owned={ownedByItemId[offer.item_id] ?? 0}
                                    />
                                ))}
                            </div>
                        </div>

                        <div className="rounded-lg border bg-white p-4 shadow-sm lg:sticky lg:top-4 lg:self-start">
                            <h3 className="mb-1 text-lg font-semibold">Ваш инвентарь</h3>
                            <p className="mb-3 text-xs text-gray-500">
                                Продажа за {sellRatioPercent}% от цены
                            </p>
                            {inventory.length === 0 ? (
                                <p className="text-sm text-gray-500">Пусто — самое время закупиться</p>
                            ) : (
                                <ul className="max-h-[28rem] space-y-2 overflow-y-auto text-sm">
                                    {inventory.map((inv) => (
                                        <InventoryRow key={inv.id} inv={inv} />
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </GameLayout>
    );
}

function OfferRow({ offer, owned }) {
    const form = useForm({});

    return (
        <div className="flex items-center justify-between rounded-lg border bg-white p-4 shadow-sm">
            <div>
                <p className="font-semibold">
                    {offer.type === 'equipment'
                        ? itemDisplayName(offer.name, { stripQualityPrefix: true })
                        : offer.name}
                </p>
                <p className="text-sm text-gray-500">{offer.description}</p>
                <EquipmentStatsPreview preview={offer.equipment_preview} className="mt-1" />
                {owned > 0 && (
                    <p className="mt-1 text-xs text-indigo-600">У вас: {owned}</p>
                )}
                {offer.stock !== null && (
                    <p className="text-xs text-gray-400">Осталось: {offer.stock}</p>
                )}
            </div>
            <button
                onClick={() => form.post(route('world.shop.buy', offer.id), { preserveScroll: true })}
                disabled={form.processing || (offer.stock !== null && offer.stock <= 0)}
                className="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 disabled:opacity-50"
            >
                {offer.buy_price} 💰
            </button>
        </div>
    );
}

function InventoryRow({ inv }) {
    const sellOneForm = useForm({ quantity: 1 });
    const sellAllForm = useForm({ quantity: inv.quantity });

    if (!inv.can_sell) {
        const equipmentPreview = inv.item.type === 'equipment' && inv.computed_stats
            ? {
                level: inv.equipment_level,
                quality_label: inv.quality_label ?? '⚪',
                stats: inv.computed_stats,
            }
            : null;

        return (
            <li className="flex items-start justify-between gap-2 border-b border-gray-100 pb-2 last:border-0">
                <div>
                    <span className="font-medium">
                        {inv.item.type === 'equipment'
                            ? itemDisplayName(inv.item.name, { stripQualityPrefix: true })
                            : inv.item.name}
                    </span>
                    <EquipmentStatsPreview preview={equipmentPreview} className="mt-0.5" />
                    {inv.is_equipped && (
                        <p className="text-xs text-gray-400">экипировано</p>
                    )}
                </div>
                <span className="shrink-0 text-gray-500">×{inv.quantity}</span>
            </li>
        );
    }

    return (
        <li className="border-b border-gray-100 pb-2 last:border-0">
            <div className="flex items-start justify-between gap-2">
                <div>
                    <span className="font-medium">
                        {inv.item.type === 'equipment'
                            ? itemDisplayName(inv.item.name, { stripQualityPrefix: true })
                            : inv.item.name}
                    </span>
                    <p className="text-xs text-gray-500">{inv.sell_price} 💰 / шт.</p>
                </div>
                <span className="shrink-0 text-gray-500">×{inv.quantity}</span>
            </div>
            <div className="mt-2 flex gap-2">
                <button
                    onClick={() => sellOneForm.post(route('world.shop.sell', inv.id), { preserveScroll: true })}
                    disabled={sellOneForm.processing}
                    className="rounded bg-emerald-600 px-2 py-1 text-xs text-white hover:bg-emerald-700 disabled:opacity-50"
                >
                    Продать 1
                </button>
                {inv.quantity > 1 && (
                    <button
                        onClick={() => sellAllForm.post(route('world.shop.sell', inv.id), { preserveScroll: true })}
                        disabled={sellAllForm.processing}
                        className="rounded border border-emerald-600 px-2 py-1 text-xs text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
                    >
                        Всё ({inv.sell_price * inv.quantity} 💰)
                    </button>
                )}
            </div>
        </li>
    );
}
