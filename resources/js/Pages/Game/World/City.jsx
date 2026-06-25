import GameLayout from '@/Layouts/GameLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const shopTypes = ['material_merchant', 'armorer', 'alchemist', 'guild_master'];

export default function WorldCity({ character, location, innCost, innRestorePercent }) {
    const innForm = useForm({});

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">{location.name}</h2>}>
            <Head title={location.name} />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4">
                    <Link href={route('world.map')} className="text-sm text-indigo-600 hover:underline">
                        ← К карте
                    </Link>

                    <p className="mt-4 mb-6 text-gray-600">{location.description}</p>

                    <h3 className="mb-4 text-lg font-semibold">Интересные места</h3>
                    <div className="space-y-4">
                        {location.pois?.map((poi) => (
                            <div key={poi.id} className="rounded-lg border bg-white p-4 shadow-sm">
                                <h4 className="font-semibold">{poi.name}</h4>
                                {poi.type !== 'inn' && poi.description && (
                                    <p className="text-sm text-gray-600">{poi.description}</p>
                                )}

                                {shopTypes.includes(poi.type) && (
                                    <Link
                                        href={route('world.shop', poi.type)}
                                        className="mt-2 inline-block text-sm text-indigo-600 hover:underline"
                                    >
                                        Зайти в лавку →
                                    </Link>
                                )}

                                {poi.type === 'inn' && (
                                    <div className="mt-3 space-y-2">
                                        <p className="text-sm text-gray-500">
                                            Восстановить {innRestorePercent}% за {innCost} 💰
                                        </p>
                                        <button
                                            onClick={() => innForm.post(route('world.inn'), { preserveScroll: true })}
                                            disabled={innForm.processing}
                                            className="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"
                                        >
                                            Отдохнуть
                                        </button>
                                    </div>
                                )}

                                {poi.type === 'skill_trainer' && (
                                    <Link
                                        href={route('world.skills')}
                                        className="mt-2 inline-block text-sm text-indigo-600 hover:underline"
                                    >
                                        К наставнику →
                                    </Link>
                                )}

                                {poi.type === 'forge' && (
                                    <Link href={route('crafting.index')} className="mt-2 inline-block text-sm text-indigo-600 hover:underline">
                                        Кузница и ранги →
                                    </Link>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </GameLayout>
    );
}
