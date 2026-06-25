import DungeonConfigView from '@/Components/Admin/DungeonConfigView';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

function ConfigSection({ title, children }) {
    return (
        <div className="rounded-lg bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-semibold text-slate-800">{title}</h2>
            {children}
        </div>
    );
}

export default function Config({ game, equipment, dungeons }) {
    return (
        <AdminLayout title="Настройки баланса">
            <Head title="Баланс" />

            <div className="space-y-6">
                <ConfigSection title="Уровни и опыт">
                    <dl className="grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm text-gray-500">XP за уровень</dt>
                            <dd className="font-semibold">{game.level.xp_per_level}</dd>
                        </div>
                        <div>
                            <dt className="text-sm text-gray-500">Множитель XP</dt>
                            <dd className="font-semibold">×{game.level.xp_level_multiplier}</dd>
                        </div>
                        <div>
                            <dt className="text-sm text-gray-500">Макс. уровень</dt>
                            <dd className="font-semibold">{game.level.max_level}</dd>
                        </div>
                        <div>
                            <dt className="text-sm text-gray-500">Бонус HP за уровень</dt>
                            <dd className="font-semibold">+{game.level.hp_bonus}</dd>
                        </div>
                    </dl>
                    <p className="mt-3 text-sm text-gray-500">
                        Редактирование: config/game.php
                    </p>
                </ConfigSection>

                <ConfigSection title="Глобальные шансы дропа">
                    <div className="grid gap-4 sm:grid-cols-2">
                        {Object.entries(equipment.drop || {}).map(([item, sources]) => (
                            <div key={item} className="rounded border p-3">
                                <h3 className="mb-2 font-medium">{item}</h3>
                                <ul className="space-y-1 text-sm">
                                    {Object.entries(sources).map(([source, chance]) => (
                                        <li key={source} className="flex justify-between">
                                            <span>{source}</span>
                                            <span className="font-medium">{chance}%</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                    <p className="mt-3 text-sm text-gray-500">
                        Редактирование: config/equipment.php
                    </p>
                </ConfigSection>

                <ConfigSection title="Ранги кузнеца">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2">Ранг</th>
                                <th>Название</th>
                                <th>Мин. ур.</th>
                                <th>Стоимость</th>
                            </tr>
                        </thead>
                        <tbody>
                            {Object.entries(game.blacksmith_ranks || {}).map(([key, rank]) => (
                                <tr key={key} className="border-b">
                                    <td className="py-2">{key}</td>
                                    <td>{rank.label}</td>
                                    <td>{rank.min_level}</td>
                                    <td>{rank.cost?.toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </ConfigSection>

                {dungeons?.map((dungeon) => (
                    <ConfigSection key={dungeon.id} title={`Данж: ${dungeon.name}`}>
                        <DungeonConfigView config={dungeon} showHeader={false} />
                        <p className="mt-4 text-sm text-slate-500">
                            Редактирование числовых параметров: раздел «Данжи» в админке. Правила дропа и пулы ресурсов — через сидер / БД (
                            <code className="text-xs">dungeon_loot_rules</code>,{' '}
                            <code className="text-xs">dungeon_resource_pools</code>).
                        </p>
                    </ConfigSection>
                ))}
            </div>
        </AdminLayout>
    );
}
