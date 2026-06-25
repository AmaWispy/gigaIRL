import InputError from '@/Components/InputError';
import GameLayout from '@/Layouts/GameLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function SkillTrainer({ character, skills, maxEquipSlots, greeting }) {
    const { errors, flash } = usePage().props;
    const equippedCount = skills.filter((skill) => skill.is_equipped).length;

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">Наставник приёмов</h2>}>
            <Head title="Наставник приёмов" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4">
                    <Link href={route('world.city')} className="text-sm text-indigo-600 hover:underline">
                        ← Назад в город
                    </Link>

                    {greeting && (
                        <p className="mt-4 rounded-lg bg-violet-50 p-4 text-sm italic text-violet-900">{greeting}</p>
                    )}

                    <p className="mt-4 text-sm text-gray-600">
                        Ваше золото: {character.money} 💰 · Слотов навыков: {equippedCount}/{maxEquipSlots}
                    </p>
                    <p className="mt-1 text-xs text-gray-500">
                        Экипировать навыки можно на странице персонажа.
                    </p>

                    {flash?.success && (
                        <p className="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">{flash.success}</p>
                    )}

                    <InputError message={errors.skill} className="mt-4" />

                    <div className="mt-6 space-y-4">
                        {skills.map((skill) => (
                            <SkillRow key={skill.id} skill={skill} characterLevel={character.level} />
                        ))}
                    </div>
                </div>
            </div>
        </GameLayout>
    );
}

function SkillRow({ skill, characterLevel }) {
    const form = useForm({});

    return (
        <div className="rounded-lg border bg-white p-4 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="font-semibold">{skill.name}</p>
                    <p className="mt-1 text-sm text-gray-600">{skill.description}</p>
                    <p className="mt-2 text-xs text-gray-500">Требуется уровень {skill.min_learn_level}</p>
                    {skill.learned && (
                        <p className="mt-1 text-xs text-green-700">
                            Изучено{skill.is_equipped ? ` · экипирован (слот ${skill.equip_slot})` : ''}
                        </p>
                    )}
                    {!skill.learned && characterLevel < skill.min_learn_level && (
                        <p className="mt-1 text-xs text-amber-700">Пока недоступно по уровню</p>
                    )}
                </div>

                {skill.learned ? (
                    <span className="shrink-0 rounded bg-gray-100 px-3 py-2 text-sm text-gray-600">Выучено</span>
                ) : (
                    <button
                        onClick={() => form.post(route('world.skills.learn', skill.id), { preserveScroll: true })}
                        disabled={form.processing || !skill.can_learn}
                        className="shrink-0 rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {skill.teach_price} 💰
                    </button>
                )}
            </div>
        </div>
    );
}
