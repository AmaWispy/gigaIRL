import { ArrowUpCircle, Crown, Hammer, ShoppingBag } from 'lucide-react';

const slots = [
    'Шлем', 'Ожерелье', 'Броня',
    'Перчатки', 'Пояс', 'Кольцо ×2',
    'Оружие', 'Плащ', 'Штаны',
    'Ботинки', 'Артефакт',
];

const sources = [
    {
        icon: ShoppingBag,
        title: 'Торговцы',
        desc: 'Базовое снаряжение в городских магазинах',
        tier: 'Слабое',
        tierColor: 'text-white',
        color: 'text-muted-foreground',
        border: 'border-[oklch(0.75_0.18_75/15%)]',
    },
    {
        icon: Hammer,
        title: 'Крафт',
        desc: 'Кузнечная профессия для создания хорошего снаряжения',
        tier: 'Среднее',
        tierColor: 'text-[oklch(0.65_0.14_160)]',
        color: 'text-[oklch(0.65_0.14_160)]',
        border: 'border-[oklch(0.65_0.14_160/25%)]',
    },
    {
        icon: Crown,
        title: 'Данжи',
        desc: 'Сетовые предметы с бонусами за полный комплект',
        tier: 'Эпическое',
        tierColor: 'text-[oklch(0.75_0.18_75)]',
        color: 'text-[oklch(0.75_0.18_75)]',
        border: 'border-[oklch(0.75_0.18_75/30%)]',
    },
];

export default function EquipmentSection() {
    return (
        <section id="equipment" className="relative overflow-hidden px-4 py-24 lg:py-36">
            <div className="pointer-events-none absolute right-0 top-1/3 h-[400px] w-[400px] rounded-full bg-[oklch(0.75_0.18_75/4%)] blur-[100px]" />

            <div className="mx-auto max-w-7xl">
                <div className="mb-16 text-center">
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-[oklch(0.75_0.18_75/25%)] bg-[oklch(0.75_0.18_75/5%)] px-3 py-1.5">
                        <span className="font-body text-xs uppercase tracking-widest text-[oklch(0.75_0.18_75)]">
                            Снаряжение
                        </span>
                    </div>
                    <h2 className="mb-4 font-display text-4xl font-black sm:text-5xl lg:text-6xl">
                        11 слотов брони. <br />
                        <span className="text-shimmer">Бесконечные возможности.</span>
                    </h2>
                    <p className="mx-auto max-w-xl font-body text-lg leading-relaxed text-muted-foreground">
                        Собирай сеты, улучшай предметы и становись непобедимым
                    </p>
                </div>

                <div className="grid grid-cols-1 items-start gap-12 lg:grid-cols-2">
                    <div>
                        <div className="rune-border relative mb-8 aspect-video overflow-hidden rounded-2xl">
                            <img
                                src="/images/equipment.png"
                                alt="Эпическое снаряжение и артефакты в gigaIRL"
                                className="h-full w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-[oklch(0.08_0.005_260/70%)] to-transparent" />
                            <div className="absolute bottom-4 left-4 right-4 flex items-center gap-2">
                                <ArrowUpCircle className="h-5 w-5 flex-shrink-0 text-[oklch(0.75_0.18_75)]" />
                                <span className="font-body text-sm text-[oklch(0.75_0.18_75)]">
                                    Улучшай предметы для повышения характеристик
                                </span>
                            </div>
                        </div>

                        <div className="rune-border rounded-xl bg-[oklch(0.11_0.008_260)] p-5">
                            <div className="mb-4 font-body text-xs uppercase tracking-widest text-muted-foreground">
                                Слоты экипировки
                            </div>
                            <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                {slots.map((slot) => (
                                    <div
                                        key={slot}
                                        className="rounded-lg border border-[oklch(0.75_0.18_75/15%)] bg-[oklch(0.14_0.01_260)] px-2 py-2.5 text-center transition-colors hover:border-[oklch(0.75_0.18_75/40%)]"
                                    >
                                        <span className="font-body text-xs text-muted-foreground">
                                            {slot}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col gap-5">
                        {sources.map((src) => {
                            const Icon = src.icon;
                            return (
                                <div
                                    key={src.title}
                                    className={`card-glow flex gap-5 rounded-xl border p-6 ${src.border} bg-[oklch(0.11_0.008_260)]`}
                                >
                                    <div className={`flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl border ${src.border}`}>
                                        <Icon className={`h-6 w-6 ${src.color}`} />
                                    </div>
                                    <div>
                                        <div className="mb-1 flex items-center gap-2">
                                            <span className="font-body text-lg font-bold">{src.title}</span>
                                            <span className={`rounded-full bg-[oklch(1_0_0/8%)] px-2 py-0.5 font-body text-xs font-semibold ${src.tierColor}`}>
                                                {src.tier}
                                            </span>
                                        </div>
                                        <p className="font-body leading-relaxed text-muted-foreground">
                                            {src.desc}
                                        </p>
                                    </div>
                                </div>
                            );
                        })}

                        <div className="rounded-xl border border-[oklch(0.75_0.18_75/25%)] bg-[oklch(0.75_0.18_75/8%)] p-6">
                            <div className="flex items-start gap-3">
                                <Crown className="mt-0.5 h-6 w-6 flex-shrink-0 text-[oklch(0.75_0.18_75)]" />
                                <div>
                                    <div className="mb-1 font-body font-bold text-[oklch(0.75_0.18_75)]">
                                        Сетовые бонусы
                                    </div>
                                    <p className="font-body text-sm leading-relaxed text-muted-foreground">
                                        Собери полный комплект данжевого сета — получи мощные уникальные бонусы к характеристикам персонажа.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
