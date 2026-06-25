import GameLayout from '@/Layouts/GameLayout';
import { monsterNameClass, monsterTierPrefix } from '@/utils/mobTierDisplay';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const ROUND_DELAY_MS = 750;

export default function CombatShow({ combat, returnRoute = route('exploration.show'), dungeonDefeat = false }) {
    const isWon = combat.status === 'won';
    const isLost = combat.status === 'lost';

    const [charHp, setCharHp] = useState(combat.character_hp_start);
    const [monsterHp, setMonsterHp] = useState(combat.monster_hp_start);
    const [visibleRounds, setVisibleRounds] = useState([]);
    const [isPlaying, setIsPlaying] = useState(combat.rounds.length > 0);
    const logContainerRef = useRef(null);
    const timerRef = useRef(null);

    const skipAnimation = () => {
        if (timerRef.current) {
            clearInterval(timerRef.current);
            timerRef.current = null;
        }

        setVisibleRounds(combat.rounds);
        setCharHp(combat.character_hp);
        setMonsterHp(combat.monster_hp);
        setIsPlaying(false);
    };

    useEffect(() => {
        const el = logContainerRef.current;
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }, [visibleRounds.length]);

    useEffect(() => {
        if (combat.rounds.length === 0) {
            return;
        }

        let index = 0;
        timerRef.current = setInterval(() => {
            if (index >= combat.rounds.length) {
                if (timerRef.current) {
                    clearInterval(timerRef.current);
                    timerRef.current = null;
                }
                setCharHp(combat.character_hp);
                setMonsterHp(combat.monster_hp);
                setIsPlaying(false);
                return;
            }

            const round = combat.rounds[index];
            setCharHp(round.character_hp_after);
            setMonsterHp(round.monster_hp_after);
            setVisibleRounds((prev) => [...prev, round]);
            index++;
        }, ROUND_DELAY_MS);

        return () => {
            if (timerRef.current) {
                clearInterval(timerRef.current);
                timerRef.current = null;
            }
        };
    }, [combat]);

    const showResult = !isPlaying;

    return (
        <GameLayout header={<h2 className="text-xl font-semibold text-gray-800">Бой: {combat.monster.name}</h2>}>
            <Head title="Бой" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4">
                    {combat.monster.flavor_text && (
                        <p className="mb-4 rounded-lg bg-slate-50 p-3 text-sm italic text-slate-700">
                            {combat.monster.flavor_text}
                        </p>
                    )}

                    {isPlaying && (
                        <div className="mb-4 flex items-center justify-center gap-4">
                            <p className="text-sm font-medium text-amber-700 animate-pulse">
                                Идёт бой...
                            </p>
                            <button
                                type="button"
                                onClick={skipAnimation}
                                className="rounded border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Пропустить
                            </button>
                        </div>
                    )}

                    <div className="mb-6 grid grid-cols-2 gap-4">
                        <HpCard
                            label="Вы"
                            current={charHp}
                            max={combat.character_hp_start}
                            animate
                        />
                        <HpCard
                            label={combat.monster.name}
                            current={monsterHp}
                            max={combat.monster_hp_start}
                            tier={combat.monster.tier}
                            animate
                        />
                    </div>

                    {showResult && isWon && (
                        <div className="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
                            <h3 className="font-semibold text-green-800">Победа!</h3>
                            {combat.rewards && (
                                <ul className="mt-2 text-sm text-green-700">
                                    <li>+{combat.rewards.xp} опыта</li>
                                    <li>+{combat.rewards.money} денег</li>
                                    {combat.rewards.items?.map((item, i) => (
                                        <li key={i}>Получено: {item.name} x{item.quantity}</li>
                                    ))}
                                </ul>
                            )}
                            {combat.rewards?.cleared && (
                                <p className="mt-2 text-sm font-medium text-green-800">
                                    Данж пройден! Печать исследователя получена.
                                </p>
                            )}
                            <Link href={returnRoute} className="mt-3 inline-block text-sm text-indigo-600 hover:underline">
                                {combat.rewards?.cleared ? 'К списку данжей' : 'Вернуться'}
                            </Link>
                        </div>
                    )}

                    {showResult && isLost && (
                        <div className="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                            <h3 className="font-semibold text-red-800">Поражение</h3>
                            <p className="text-sm text-red-700">
                                {dungeonDefeat
                                    ? 'Вы погибли в данже. Вас доставили в город с 1 HP.'
                                    : 'Вы едва выжили с 1 HP.'}
                            </p>
                            <Link href={returnRoute} className="mt-3 inline-block text-sm text-indigo-600 hover:underline">
                                {dungeonDefeat ? 'В город' : 'Вернуться'}
                            </Link>
                        </div>
                    )}

                    <div className="rounded-lg bg-white p-4 shadow-sm">
                        <h3 className="mb-3 font-semibold">Лог боя</h3>
                        {visibleRounds.length === 0 && !isPlaying ? (
                            <p className="text-sm text-gray-500">Бой завершён без раундов</p>
                        ) : (
                            <ul ref={logContainerRef} className="max-h-64 space-y-1 overflow-y-auto text-sm">
                                {visibleRounds.map((r, i) => (
                                    <li
                                        key={i}
                                        className={`text-gray-700 ${i === visibleRounds.length - 1 && isPlaying ? 'font-medium text-indigo-700' : ''}`}
                                    >
                                        {formatRoundLog(r, combat.monster.name)}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </GameLayout>
    );
}

function formatRoundLog(round, monsterName) {
    const actor = round.actor === 'character' ? 'Вы' : monsterName;
    const skills = round.meta?.skills?.length ? ` [${round.meta.skills.join(', ')}]` : '';
    const blocked = round.meta?.blocked > 0 ? `, ${round.meta.blocked} заблокировано бронёй` : '';

    if (round.action === 'stunned') {
        return `${monsterName} — оглушён, пропускает ход`;
    }

    if (round.action === 'retribution') {
        return `Возмездие${skills} — ${round.damage} урона по ${monsterName}`;
    }

    if (round.actor === 'character' && round.heal > 0) {
        return `${actor} — атака (${round.damage} урона, +${round.heal} HP${blocked})${skills}`;
    }

    return `${actor} — атака (${round.damage} урона${blocked})${skills}`;
}

function HpCard({ label, current, max, tier, animate = false }) {
    const percent = max > 0 ? Math.min(100, Math.round((current / max) * 100)) : 0;
    const tierClass = tier ? monsterNameClass(tier) : 'text-gray-500';

    return (
        <div className="rounded-lg border bg-white p-4 shadow-sm">
            <p className={`text-sm ${tierClass}`}>
                {tier && <span>{monsterTierPrefix(tier)}</span>}
                {label}
            </p>
            <p className="text-xl font-bold">{current} HP</p>
            <div className="mt-2 h-3 rounded-full bg-gray-200 overflow-hidden">
                <div
                    className={`h-3 rounded-full bg-red-500 ${animate ? 'transition-all duration-500 ease-out' : ''}`}
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}
