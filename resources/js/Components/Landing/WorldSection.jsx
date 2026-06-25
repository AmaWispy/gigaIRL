import { Flame, Layers, Shield } from 'lucide-react';

const zones = [
    {
        icon: Shield,
        title: 'Безопасные зоны',
        description:
            'Рынки, гостиницы, торговцы, кузнецы. Здесь ты восстанавливаешься и готовишься к следующему походу.',
        tag: 'МИРНАЯ',
        tagColor: 'text-[oklch(0.65_0.14_160)]',
        tagBg: 'bg-[oklch(0.65_0.14_160/10%)]',
        borderColor: 'border-[oklch(0.65_0.14_160/25%)]',
        iconColor: 'text-[oklch(0.65_0.14_160)]',
    },
    {
        icon: Flame,
        title: 'Опасные места',
        description:
            'Используй «Осмотреться», чтобы открыть 2–4 активности: бои, ресурсы, сокровища и редкие боссы.',
        tag: 'ОПАСНАЯ',
        tagColor: 'text-[oklch(0.55_0.22_22)]',
        tagBg: 'bg-[oklch(0.55_0.22_22/10%)]',
        borderColor: 'border-[oklch(0.55_0.22_22/25%)]',
        iconColor: 'text-[oklch(0.55_0.22_22)]',
    },
    {
        icon: Layers,
        title: 'Данжи',
        description:
            'Многоэтажные испытания с финальным боссом. Здесь выпадают лучшие сетовые предметы и артефакты.',
        tag: 'ЭПИК',
        tagColor: 'text-[oklch(0.75_0.18_75)]',
        tagBg: 'bg-[oklch(0.75_0.18_75/10%)]',
        borderColor: 'border-[oklch(0.75_0.18_75/25%)]',
        iconColor: 'text-[oklch(0.75_0.18_75)]',
    },
];

export default function WorldSection() {
    return (
        <section id="world" className="relative overflow-hidden px-4 py-24 lg:py-36">
            <div className="pointer-events-none absolute left-1/2 top-1/2 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[oklch(0.55_0.22_22/5%)] blur-[120px]" />

            <div className="mx-auto max-w-7xl">
                <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-20">
                    <div>
                        <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-[oklch(0.55_0.22_22/30%)] bg-[oklch(0.55_0.22_22/5%)] px-3 py-1.5">
                            <span className="font-body text-xs uppercase tracking-widest text-[oklch(0.55_0.22_22)]">
                                Мир
                            </span>
                        </div>
                        <h2 className="mb-6 font-display text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">
                            Огромный мир <br />
                            <span className="text-shimmer">ждёт тебя</span>
                        </h2>
                        <p className="mb-8 font-body text-lg leading-relaxed text-muted-foreground">
                            От мирных городов до смертельных подземелий — каждая локация таит в себе опасности и награды.
                            Камень перемещения поможет путешествовать без траты энергии.
                        </p>

                        <div className="flex flex-col gap-4">
                            {zones.map((zone) => {
                                const Icon = zone.icon;
                                return (
                                    <div
                                        key={zone.title}
                                        className={`card-glow flex gap-4 rounded-xl border p-5 ${zone.borderColor} bg-[oklch(0.11_0.008_260)]`}
                                    >
                                        <div className={`flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg ${zone.tagBg}`}>
                                            <Icon className={`h-5 w-5 ${zone.iconColor}`} />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="mb-1 flex items-center gap-2">
                                                <span className="font-body font-bold text-foreground">
                                                    {zone.title}
                                                </span>
                                                <span className={`rounded-full px-2 py-0.5 font-body text-xs font-semibold tracking-wider ${zone.tagBg} ${zone.tagColor}`}>
                                                    {zone.tag}
                                                </span>
                                            </div>
                                            <p className="font-body text-sm leading-relaxed text-muted-foreground">
                                                {zone.description}
                                            </p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <div className="relative">
                        <div className="rune-border relative aspect-square overflow-hidden rounded-2xl lg:aspect-auto lg:h-[560px]">
                            <img
                                src="/images/world.png"
                                alt="Карта мира gigaIRL с локациями и подземельями"
                                className="h-full w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-[oklch(0.08_0.005_260/80%)] via-transparent to-transparent" />
                        </div>
                        <div className="absolute -bottom-4 -left-4 rounded-xl border border-[oklch(0.75_0.18_75/30%)] bg-[oklch(0.11_0.008_260)] px-5 py-4 shadow-2xl">
                            <div className="font-display text-2xl font-black text-[oklch(0.75_0.18_75)]">15%</div>
                            <div className="mt-0.5 max-w-[120px] font-body text-xs text-muted-foreground">
                                шанс нападения в опасных зонах
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
