import GameLayout from '@/Layouts/GameLayout';
import { Head, Link } from '@inertiajs/react';

export default function Hub({ character }) {
    const cards = [
        { title: 'Достижения', desc: 'Отмечайте дела из жизни и получайте энергию', href: route('achievements.index'), color: 'bg-amber-50 border-amber-200' },
        { title: 'Персонаж', desc: 'Статы, инвентарь и экипировка', href: route('character.show'), color: 'bg-blue-50 border-blue-200' },
        { title: 'Карта мира', desc: 'Путешествуйте между локациями', href: route('world.map'), color: 'bg-green-50 border-green-200' },
        { title: 'Ремесло', desc: 'Крафт предметов и профессии', href: route('crafting.index'), color: 'bg-purple-50 border-purple-200' },
    ];

    return (
        <GameLayout
            header={<h2 className="text-xl font-semibold text-gray-800">gigaIRL — Игровой хаб</h2>}
        >
            <Head title="Игра" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {character?.current_location && (
                        <div className="mb-6 rounded-lg bg-white p-4 shadow-sm">
                            <p className="text-sm text-gray-500">Текущая локация</p>
                            <p className="text-lg font-semibold">{character.current_location.name}</p>
                            <p className="text-sm text-gray-600">
                                {character.current_location.is_safe ? 'Безопасная зона' : 'Опасная зона'}
                                {!character.current_location.is_safe && (
                                    <Link href={route('exploration.show')} className="ml-4 text-indigo-600 hover:underline">
                                        Исследовать →
                                    </Link>
                                )}
                                {character.current_location.is_safe && (
                                    <Link href={route('world.city')} className="ml-4 text-indigo-600 hover:underline">
                                        Город →
                                    </Link>
                                )}
                            </p>
                        </div>
                    )}

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {cards.map((card) => (
                            <Link
                                key={card.title}
                                href={card.href}
                                className={`rounded-lg border p-6 transition hover:shadow-md ${card.color}`}
                            >
                                <h3 className="text-lg font-semibold text-gray-800">{card.title}</h3>
                                <p className="mt-2 text-sm text-gray-600">{card.desc}</p>
                            </Link>
                        ))}
                    </div>

                    <div className="mt-8 grid gap-4 sm:grid-cols-3">
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            <p className="text-sm text-gray-500">Мощь</p>
                            <p className="text-2xl font-bold text-gray-800">{character?.power}</p>
                        </div>
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            <p className="text-sm text-gray-500">Опыт</p>
                            <p className="text-2xl font-bold text-gray-800">
                                {character?.xp} / {character?.xp_to_next_level}
                            </p>
                        </div>
                        <div className="rounded-lg bg-white p-4 shadow-sm">
                            <p className="text-sm text-gray-500">Профессия</p>
                            <p className="text-2xl font-bold text-gray-800">
                                {character?.profession === 'none' ? '—' : character?.profession}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </GameLayout>
    );
}
