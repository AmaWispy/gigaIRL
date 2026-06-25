import { BookOpen, Hammer, Heart, Package, Shield, Swords } from 'lucide-react';

const stats = [
    { icon: Heart, label: 'Здоровье', desc: 'Восстанавливается раз в сутки. Зелья и гостиница ускоряют восстановление.', color: 'text-[oklch(0.55_0.22_22)]', bg: 'bg-[oklch(0.55_0.22_22/10%)]' },
    { icon: Shield, label: 'Защита', desc: 'Снижает урон в бою. Улучшается с помощью брони и щитов.', color: 'text-[oklch(0.55_0.22_220)]', bg: 'bg-[oklch(0.55_0.22_220/10%)]' },
    { icon: Swords, label: 'Сила', desc: 'Определяет урон по врагам. Растёт с уровнем и оружием.', color: 'text-[oklch(0.75_0.18_75)]', bg: 'bg-[oklch(0.75_0.18_75/10%)]' },
];

const features = [
    {
        icon: BookOpen,
        title: 'Уровень и опыт',
        desc: 'Набирай опыт в боях — повышай уровень и получай бонус ко всем характеристикам.',
    },
    {
        icon: Swords,
        title: 'Навыки',
        desc: 'Активируются автоматически в бою. Открываются с уровнем персонажа.',
    },
    {
        icon: Package,
        title: 'Инвентарь',
        desc: 'Храни предметы, ресурсы и трофеи. Управляй экипировкой и расходниками.',
    },
    {
        icon: Hammer,
        title: 'Профессия кузнеца',
        desc: 'Добывай ценные ресурсы и создавай мощное снаряжение для себя и на продажу.',
    },
];

export default function CharacterSection() {
    return (
        <section id="character" className="relative overflow-hidden px-4 py-24 lg:py-36">
            <div className="absolute inset-0 bg-[oklch(0.10_0.008_260)]" />
            <div className="section-divider absolute left-0 right-0 top-0" />
            <div className="section-divider absolute bottom-0 left-0 right-0" />

            <div className="relative mx-auto max-w-7xl">
                <div className="mb-16 text-center">
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-[oklch(0.75_0.18_75/25%)] bg-[oklch(0.75_0.18_75/5%)] px-3 py-1.5">
                        <span className="font-body text-xs uppercase tracking-widest text-[oklch(0.75_0.18_75)]">
                            Персонаж
                        </span>
                    </div>
                    <h2 className="mb-4 font-display text-4xl font-black sm:text-5xl lg:text-6xl">
                        Твой герой — <br />
                        <span className="text-shimmer">отражение тебя</span>
                    </h2>
                    <p className="mx-auto max-w-xl font-body text-lg leading-relaxed text-muted-foreground">
                        Все характеристики растут вместе с твоими реальными успехами
                    </p>
                </div>

                <div className="mb-16 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    {stats.map((stat) => {
                        const Icon = stat.icon;
                        return (
                            <div
                                key={stat.label}
                                className="card-glow rune-border rounded-xl bg-[oklch(0.11_0.008_260)] p-8 text-center"
                            >
                                <div className={`mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full ${stat.bg}`}>
                                    <Icon className={`h-8 w-8 ${stat.color}`} />
                                </div>
                                <h3 className={`mb-3 font-body text-xl font-black ${stat.color}`}>
                                    {stat.label}
                                </h3>
                                <p className="font-body text-sm leading-relaxed text-muted-foreground">
                                    {stat.desc}
                                </p>
                            </div>
                        );
                    })}
                </div>

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {features.map((feat) => {
                        const Icon = feat.icon;
                        return (
                            <div
                                key={feat.title}
                                className="card-glow flex flex-col rounded-xl border border-[oklch(0.75_0.18_75/12%)] bg-[oklch(0.11_0.008_260)] p-6 transition-colors hover:border-[oklch(0.75_0.18_75/30%)]"
                            >
                                <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-[oklch(0.75_0.18_75/10%)]">
                                    <Icon className="h-5 w-5 text-[oklch(0.75_0.18_75)]" />
                                </div>
                                <h4 className="mb-2 font-body font-bold">{feat.title}</h4>
                                <p className="flex-1 font-body text-sm leading-relaxed text-muted-foreground">
                                    {feat.desc}
                                </p>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
