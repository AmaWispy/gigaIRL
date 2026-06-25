function SettingGroups({ groups }) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {groups.map((group) => (
                <div key={group.title} className="rounded border border-slate-200 p-3">
                    <h4 className="mb-2 text-sm font-semibold text-slate-800">{group.title}</h4>
                    <dl className="space-y-1 text-sm">
                        {group.fields.map((field) => (
                            <div key={field.label} className="flex justify-between gap-2">
                                <dt className="text-slate-500">{field.label}</dt>
                                <dd className="font-medium text-slate-900">{field.value ?? '—'}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            ))}
        </div>
    );
}

function LootRulesTable({ rules }) {
    if (!rules?.length) {
        return <p className="text-sm text-slate-500">Правила дропа не заданы</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-sm">
                <thead className="bg-slate-50">
                    <tr>
                        <th className="px-3 py-2 text-left font-medium text-slate-600">Источник</th>
                        <th className="px-3 py-2 text-left font-medium text-slate-600">Награда</th>
                        <th className="px-3 py-2 text-left font-medium text-slate-600">Шанс %</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {rules.map((rule) => (
                        <tr key={`${rule.source}-${rule.reward_type}`}>
                            <td className="px-3 py-2 text-slate-900">{rule.source_label}</td>
                            <td className="px-3 py-2 text-slate-900">{rule.reward_type_label}</td>
                            <td className="px-3 py-2 font-medium text-slate-900">{rule.chance_percent}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function ResourcePoolList({ title, items }) {
    if (!items?.length) {
        return (
            <div>
                <h4 className="mb-1 text-sm font-semibold text-slate-800">{title}</h4>
                <p className="text-sm text-slate-500">Пусто</p>
            </div>
        );
    }

    return (
        <div>
            <h4 className="mb-1 text-sm font-semibold text-slate-800">{title}</h4>
            <ul className="list-inside list-disc text-sm text-slate-900">
                {items.map((item) => (
                    <li key={item.catalog_key}>
                        {item.name}{' '}
                        <span className="text-slate-400">({item.catalog_key})</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export default function DungeonConfigView({ config, showHeader = true }) {
    return (
        <div className="space-y-6">
            {showHeader && (
                <div>
                    <h3 className="text-lg font-semibold text-slate-800">{config.name}</h3>
                    <p className="text-sm text-slate-500">
                        {config.catalog_key} · тир {config.tier}
                    </p>
                </div>
            )}

            <SettingGroups groups={config.setting_groups} />

            <div>
                <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">
                    Правила дропа
                </h4>
                <LootRulesTable rules={config.loot_rules} />
            </div>

            <div>
                <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">
                    Пулы ресурсов
                </h4>
                <div className="grid gap-4 sm:grid-cols-2">
                    <ResourcePoolList title="Обычные" items={config.resource_pools?.common} />
                    <ResourcePoolList title="Редкие" items={config.resource_pools?.rare} />
                </div>
            </div>
        </div>
    );
}
