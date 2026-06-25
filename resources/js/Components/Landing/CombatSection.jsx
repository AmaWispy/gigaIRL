import { Gem, Sparkles, Swords, Trophy } from 'lucide-react';

const rewards = [
    { icon: Trophy, label: 'Опыт и деньги', desc: 'За каждую победу над монстром' },
    { icon: Gem, label: 'Снаряжение', desc: 'Редкие монстры и боссы' },
    { icon: Sparkles, label: 'Артефакты', desc: 'Из финальных боссов данжей' },
];

const rarities = [
    { name: 'Обычное', color: 'text-white', dot: 'bg-white' },
    { name: 'Необычное', color: 'text-[oklch(0.65_0.14_160)]', dot: 'bg-[oklch(0.65_0.14_160)]' },
    { name: 'Редкое', color: 'text-[oklch(0.55_0.22_220)]', dot: 'bg-[oklch(0.55_0.22_220)]' },
    { name: 'Эпическое', color: 'text-[oklch(0.65_0.25_300)]', dot: 'bg-[oklch(0.65_0.25_300)]' },
    { name: 'Легендарное', color: 'text-[oklch(0.55_0.22_22)]', dot: 'bg-[oklch(0.55_0.22_22)]' },
];

export default function CombatSection() {
    return (
        <section className="relative overflow-hidden px-4 py-24 lg:py-36">
            <div className="absolute inset-0 bg-[oklch(0.10_0.008_260)]" />
            <div className="section-divider absolute left-0 right-0 top-0" />
            <div className="section-divider absolute bottom-0 left-0 right-0" />

            <div className="relative mx-auto max-w-7xl">
                <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-20">
                    <div className="relative order-2 lg:order-1">
                        <div className="rune-border relative aspect-square overflow-hidden rounded-2xl lg:aspect-auto lg:h-[520px]">
                            <img
                                src="/images/combat.png"
                                alt="Пошаговый бой с монстром в gigaIRL"
                                className="h-full w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-[oklch(0.08_0.005_260/70%)]" />

                            <div className="absolute bottom-6 left-6 right-6">
                                <div className="rounded-xl border border-[oklch(0.75_0.18_75/20%)] bg-[oklch(0.08_0.005_260/90%)] p-4 backdrop-blur-sm">
                                    <div className="mb-3 flex items-center justify-between">
                                        <div>
                                            <div className="font-body text-xs uppercase tracking-wider text-muted-foreground">Герой</div>
                                            <div className="font-body text-sm font-bold">Воин Уровень 12</div>
                                        </div>
                                        <Swords className="h-5 w-5 text-[oklch(0.75_0.18_75)]" />
                                        <div className="text-right">
                                            <div className="font-body text-xs uppercase tracking-wider text-muted-foreground">Враг</div>
                                            <div className="font-body text-sm font-bold text-[oklch(0.55_0.22_22)]">Тёмный рыцарь</div>
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <div>
                                            <div className="mb-1 flex justify-between text-xs text-muted-foreground">
                                                <span>HP</span><span>248/310</span>
                                            </div>
                                            <div className="h-2 overflow-hidden rounded-full bg-[oklch(0.14_0.01_260)]">
                                                <div className="h-full rounded-full bg-[oklch(0.55_0.22_22)]" style={{ width: '80%' }} />
                                            </div>
                                        </div>
                                        <div>
                                            <div className="mb-1 flex justify-between text-xs text-muted-foreground">
                                                <span>HP врага</span><span>95/400</span>
                                            </div>
                                            <div className="h-2 overflow-hidden rounded-full bg-[oklch(0.14_0.01_260)]">
                                                <div className="h-full rounded-full bg-[oklch(0.65_0.22_22)]" style={{ width: '24%' }} />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="order-1 lg:order-2">
                        <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-[oklch(0.55_0.22_22/30%)] bg-[oklch(0.55_0.22_22/5%)] px-3 py-1.5">
                            <span className="font-body text-xs uppercase tracking-widest text-[oklch(0.55_0.22_22)]">
                                Сражения
                            </span>
                        </div>
                        <h2 className="mb-6 font-display text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">
                            Пошаговый бой <br />
                            <span className="text-shimmer">с умом и силой</span>
                        </h2>
                        <p className="mb-8 font-body text-lg leading-relaxed text-muted-foreground">
                            Атаки чередуются с применением навыков. Побеждай монстров, получай опыт,
                            деньги и ресурсы. Чем опаснее враг — тем богаче награда.
                        </p>

                        <div className="mb-8 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            {rewards.map((r) => {
                                const Icon = r.icon;
                                return (
                                    <div key={r.label} className="card-glow flex flex-col items-center rounded-xl border border-[oklch(0.75_0.18_75/15%)] bg-[oklch(0.11_0.008_260)] p-4 text-center">
                                        <Icon className="mb-2 h-6 w-6 text-[oklch(0.75_0.18_75)]" />
                                        <div className="mb-1 font-body text-sm font-bold">{r.label}</div>
                                        <div className="font-body text-xs text-muted-foreground">{r.desc}</div>
                                    </div>
                                );
                            })}
                        </div>

                        <div className="rune-border rounded-xl bg-[oklch(0.11_0.008_260)] p-5">
                            <div className="mb-4 font-body text-xs uppercase tracking-widest text-muted-foreground">
                                Редкости снаряжения
                            </div>
                            <div className="flex flex-wrap gap-3">
                                {rarities.map((r) => (
                                    <div key={r.name} className="flex items-center gap-2">
                                        <span className={`h-2.5 w-2.5 flex-shrink-0 rounded-full ${r.dot}`} />
                                        <span className={`font-body text-sm font-semibold ${r.color}`}>
                                            {r.name}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
