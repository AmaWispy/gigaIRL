import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { usePage } from '@inertiajs/react';

function SelectField({ field, value, onChange }) {
    return (
        <select
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            value={value ?? ''}
            onChange={(e) => onChange(e.target.value === '' ? null : e.target.value)}
        >
            <option value="">—</option>
            {Object.entries(field.options || {}).map(([key, label]) => (
                <option key={key} value={key}>
                    {label}
                </option>
            ))}
        </select>
    );
}

function LookupSelect({ field, value, onChange, options }) {
    return (
        <select
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            value={value ?? ''}
            onChange={(e) => onChange(e.target.value === '' ? null : Number(e.target.value))}
        >
            <option value="">—</option>
            {Object.entries(options || {}).map(([id, label]) => (
                <option key={id} value={id}>
                    {label}
                </option>
            ))}
        </select>
    );
}

function KeyValueField({ value, onChange }) {
    const entries = Object.entries(value || {});

    const update = (index, key, val) => {
        const next = [...entries];
        next[index] = [key, val];
        onChange(Object.fromEntries(next.filter(([k]) => k !== '')));
    };

    const add = () => onChange({ ...(value || {}), '': '' });
    const remove = (key) => {
        const next = { ...value };
        delete next[key];
        onChange(next);
    };

    return (
        <div className="space-y-2">
            {entries.map(([k, v], i) => (
                <div key={i} className="flex gap-2">
                    <TextInput
                        className="flex-1"
                        value={k}
                        onChange={(e) => update(i, e.target.value, v)}
                        placeholder="Ключ"
                    />
                    <TextInput
                        className="flex-1"
                        value={v}
                        onChange={(e) => update(i, k, e.target.value)}
                        placeholder="Значение"
                    />
                    <button
                        type="button"
                        onClick={() => remove(k)}
                        className="text-red-600 text-sm"
                    >
                        ✕
                    </button>
                </div>
            ))}
            <button type="button" onClick={add} className="text-sm text-indigo-600">
                + Добавить
            </button>
        </div>
    );
}

function LootTableField({ value, onChange, items }) {
    const rows = Array.isArray(value) ? value : [];

    const updateRow = (index, key, val) => {
        const next = [...rows];
        next[index] = { ...next[index], [key]: val };
        onChange(next);
    };

    const addRow = () => onChange([...rows, { item_id: '', chance: 10, quantity: 1 }]);
    const removeRow = (index) => onChange(rows.filter((_, i) => i !== index));

    return (
        <div className="space-y-3">
            {rows.map((row, i) => (
                <div key={i} className="rounded border border-gray-200 bg-gray-50 p-3">
                    <div className="grid gap-2 sm:grid-cols-4">
                        <div>
                            <InputLabel value="Предмет" />
                            <LookupSelect
                                field={{ type: 'item_select' }}
                                value={row.item_id}
                                onChange={(v) => updateRow(i, 'item_id', v)}
                                options={items}
                            />
                        </div>
                        <div>
                            <InputLabel value="Шанс %" />
                            <TextInput
                                type="number"
                                value={row.chance ?? ''}
                                onChange={(e) => updateRow(i, 'chance', Number(e.target.value))}
                            />
                        </div>
                        <div>
                            <InputLabel value="Кол-во" />
                            <TextInput
                                type="number"
                                value={row.quantity ?? 1}
                                onChange={(e) => updateRow(i, 'quantity', Number(e.target.value))}
                            />
                        </div>
                        <div>
                            <InputLabel value="Качество" />
                            <SelectField
                                field={{
                                    options: {
                                        white: 'Белое',
                                        green: 'Зелёное',
                                        blue: 'Синее',
                                        purple: 'Фиолетовое',
                                        red: 'Красное',
                                    },
                                }}
                                value={row.equipment_quality}
                                onChange={(v) => updateRow(i, 'equipment_quality', v)}
                            />
                        </div>
                    </div>
                    <button type="button" onClick={() => removeRow(i)} className="mt-2 text-sm text-red-600">
                        Удалить
                    </button>
                </div>
            ))}
            <button type="button" onClick={addRow} className="text-sm text-indigo-600">
                + Добавить дроп
            </button>
        </div>
    );
}

function IngredientsField({ value, onChange, items }) {
    const rows = Array.isArray(value) ? value : [];

    const updateRow = (index, key, val) => {
        const next = [...rows];
        next[index] = { ...next[index], [key]: val };
        onChange(next);
    };

    return (
        <div className="space-y-2">
            {rows.map((row, i) => (
                <div key={i} className="flex gap-2">
                    <LookupSelect
                        field={{ type: 'item_select' }}
                        value={row.item_id}
                        onChange={(v) => updateRow(i, 'item_id', v)}
                        options={items}
                    />
                    <TextInput
                        type="number"
                        className="w-24"
                        value={row.quantity ?? 1}
                        onChange={(e) => updateRow(i, 'quantity', Number(e.target.value))}
                    />
                    <button type="button" onClick={() => onChange(rows.filter((_, j) => j !== i))} className="text-red-600">
                        ✕
                    </button>
                </div>
            ))}
            <button
                type="button"
                onClick={() => onChange([...rows, { item_id: '', quantity: 1 }])}
                className="text-sm text-indigo-600"
            >
                + Ингредиент
            </button>
        </div>
    );
}

const lookupMap = {
    item_select: 'items',
    location_select: 'locations',
    monster_select: 'monsters',
    dungeon_select: 'dungeons',
    skill_select: 'skills',
    user_select: 'users',
    character_select: 'characters',
};

export default function FieldRenderer({ field, value, onChange, error }) {
    const { admin } = usePage().props;

    return (
        <div>
            <InputLabel value={field.label} />
            {field.type === 'textarea' && (
                <textarea
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows={3}
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            )}
            {field.type === 'text' && (
                <TextInput
                    className="mt-1 block w-full"
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            )}
            {field.type === 'number' && (
                <TextInput
                    type="number"
                    className="mt-1 block w-full"
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value === '' ? null : Number(e.target.value))}
                />
            )}
            {field.type === 'boolean' && (
                <label className="mt-2 flex items-center gap-2">
                    <input
                        type="checkbox"
                        checked={!!value}
                        onChange={(e) => onChange(e.target.checked)}
                        className="rounded border-gray-300 text-indigo-600"
                    />
                    <span className="text-sm text-gray-600">Да</span>
                </label>
            )}
            {field.type === 'select' && (
                <SelectField field={field} value={value} onChange={onChange} />
            )}
            {lookupMap[field.type] && (
                <LookupSelect
                    field={field}
                    value={value}
                    onChange={onChange}
                    options={admin?.lookups?.[lookupMap[field.type]]}
                />
            )}
            {field.type === 'keyvalue' && <KeyValueField value={value} onChange={onChange} />}
            {field.type === 'loot_table' && (
                <LootTableField
                    value={value}
                    onChange={onChange}
                    items={admin?.lookups?.items}
                />
            )}
            {field.type === 'ingredients' && (
                <IngredientsField
                    value={value}
                    onChange={onChange}
                    items={admin?.lookups?.items}
                />
            )}
            <InputError message={error} className="mt-1" />
        </div>
    );
}
