import { usePage } from '@inertiajs/react';

const QUALITY_LABELS = {
    white: 'Белое',
    green: 'Зелёное',
    blue: 'Синее',
    purple: 'Фиолетовое',
    red: 'Красное',
    random: 'Случайное',
};

function LookupValue({ lookupKey, id }) {
    const lookups = usePage().props.admin?.lookups ?? {};
    const options = lookups[lookupKey] ?? {};

    if (id == null || id === '') {
        return '—';
    }

    return options[id] ?? `#${id}`;
}

function LootTableDisplay({ rows, items }) {
    if (!Array.isArray(rows) || rows.length === 0) {
        return <span className="text-slate-500">Нет дропа</span>;
    }

    return (
        <div className="mt-1 overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-sm">
                <thead className="bg-slate-50">
                    <tr>
                        <th className="px-3 py-2 text-left font-medium text-slate-600">Предмет</th>
                        <th className="px-3 py-2 text-left font-medium text-slate-600">Шанс %</th>
                        <th className="px-3 py-2 text-left font-medium text-slate-600">Кол-во</th>
                        <th className="px-3 py-2 text-left font-medium text-slate-600">Качество</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-white">
                    {rows.map((row, i) => (
                        <tr key={i}>
                            <td className="px-3 py-2 text-slate-900">
                                {items[row.item_id] ?? (row.item_id ? `#${row.item_id}` : '—')}
                            </td>
                            <td className="px-3 py-2 text-slate-900">{row.chance ?? '—'}</td>
                            <td className="px-3 py-2 text-slate-900">{row.quantity ?? 1}</td>
                            <td className="px-3 py-2 text-slate-900">
                                {row.equipment_quality
                                    ? (QUALITY_LABELS[row.equipment_quality] ?? row.equipment_quality)
                                    : '—'}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function KeyValueDisplay({ value }) {
    const entries = value && typeof value === 'object' ? Object.entries(value) : [];

    if (entries.length === 0) {
        return <span className="text-slate-500">—</span>;
    }

    return (
        <dl className="mt-1 space-y-1 text-sm">
            {entries.map(([key, val]) => (
                <div key={key} className="flex gap-2">
                    <dt className="font-medium text-slate-600">{key}:</dt>
                    <dd className="text-slate-900">{String(val)}</dd>
                </div>
            ))}
        </dl>
    );
}

function IngredientsDisplay({ rows, items }) {
    if (!Array.isArray(rows) || rows.length === 0) {
        return <span className="text-slate-500">—</span>;
    }

    return (
        <ul className="mt-1 list-inside list-disc text-sm text-slate-900">
            {rows.map((row, i) => (
                <li key={i}>
                    {items[row.item_id] ?? `#${row.item_id}`} × {row.quantity ?? 1}
                </li>
            ))}
        </ul>
    );
}

const LOOKUP_BY_FIELD_TYPE = {
    item_select: 'items',
    location_select: 'locations',
    monster_select: 'monsters',
    dungeon_select: 'dungeons',
    skill_select: 'skills',
    user_select: 'users',
    character_select: 'characters',
};

export default function FieldDisplay({ field, value }) {
    const lookups = usePage().props.admin?.lookups ?? {};
    const items = lookups.items ?? {};

    let content;

    switch (field.type) {
        case 'loot_table':
            content = <LootTableDisplay rows={value} items={items} />;
            break;
        case 'ingredients':
            content = <IngredientsDisplay rows={value} items={items} />;
            break;
        case 'keyvalue':
        case 'json':
            content = <KeyValueDisplay value={value} />;
            break;
        case 'boolean':
            content = value ? 'да' : 'нет';
            break;
        case 'select':
            content = field.options?.[value] ?? (value ?? '—');
            break;
        case 'textarea':
            content = value ? (
                <span className="whitespace-pre-wrap">{value}</span>
            ) : (
                '—'
            );
            break;
        default: {
            const lookupKey = LOOKUP_BY_FIELD_TYPE[field.type];
            if (lookupKey) {
                content = <LookupValue lookupKey={lookupKey} id={value} />;
            } else if (typeof value === 'object' && value !== null) {
                content = JSON.stringify(value, null, 2);
            } else {
                content = value ?? '—';
            }
        }
    }

    const fullWidth = ['loot_table', 'keyvalue', 'json', 'ingredients', 'textarea'].includes(field.type);

    return (
        <div className={fullWidth ? 'sm:col-span-2' : undefined}>
            <dt className="text-sm font-medium text-slate-500">{field.label}</dt>
            <dd className="mt-1 break-all text-slate-900">{content}</dd>
        </div>
    );
}
