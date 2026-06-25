import { CheckCircle2, Coins, Dumbbell, Star, TrendingUp, Zap } from 'lucide-react';

const steps = [
    {
        number: '01',
        icon: CheckCircle2,
        title: 'Отмечай реальные дела',
        description:
            'Зарабатывай энергию за финансовые успехи, спорт, обучение и другие личные достижения. Каждое дело — очки силы.',
        color: 'text-[oklch(0.75_0.18_75)]',
        borderColor: 'border-[oklch(0.75_0.18_75/25%)]',
        bgColor: 'bg-[oklch(0.75_0.18_75/8%)]',
    },
    {
        number: '02',
        icon: Zap,
        title: 'Трать энергию в игре',
        description:
            'Путешествуй по миру, сражайся с монстрами, проходи данжи и занимайся ремеслом — всё стоит энергию.',
        color: 'text-[oklch(0.55_0.22_22)]',
        borderColor: 'border-[oklch(0.55_0.22_22/25%)]',
        bgColor: 'bg-[oklch(0.55_0.22_22/8%)]',
    },
    {
        number: '03',
        icon: TrendingUp,
        title: 'Расти и побеждай',
        description:
            'Набирай уровни, улучшай снаряжение, изучай навыки. Твой герой растёт вместе с тобой.',
        color: 'text-[oklch(0.65_0.14_160)]',
        borderColor: 'border-[oklch(0.65_0.14_160/25%)]',
        bgColor: 'bg-[oklch(0.65_0.14_160/8%)]',
    },
];

const energySources = [
    { icon: Coins, label: 'Финансовые', desc: 'Основной и доп. доход', color: 'text-[oklch(0.75_0.18_75)]' },
    { icon: Dumbbell, label: 'Обычные', desc: 'Спорт, чтение, домашние дела', color: 'text-[oklch(0.55_0.22_22)]' },
    { icon: Star, label: 'Редкие', desc: 'Крупные свершения раз в жизни', color: 'text-[oklch(0.65_0.14_160)]' },
];

export default function GameplaySection() {
    return (
        <section id="gameplay" className="relative px-4 py-24 lg:py-36">
            <div className="mx-auto max-w-7xl">
                <div className="mb-16 text-center">
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-[oklch(0.75_0.18_75/25%)] bg-[oklch(0.75_0.18_75/5%)] px-3 py-1.5">
                        <span className="font-body text-xs uppercase tracking-widest text-[oklch(0.75_0.18_75)]">
                            Геймплей
                        </span>
                    </div>
                    <h2 className="mb-4 font-display text-4xl font-black sm:text-5xl lg:text-6xl">
                        Реальная жизнь — <br />
                        <span className="text-shimmer">твой источник силы</span>
                    </h2>
                    <p className="mx-auto max-w-xl font-body text-lg leading-relaxed text-muted-foreground">
                        gigaIRL — единственная RPG, где твоя продуктивность напрямую влияет на силу персонажа
                    </p>
                </div>

                <div className="mb-20 grid grid-cols-1 gap-6 md:grid-cols-3">
                    {steps.map((step) => {
                        const Icon = step.icon;
                        return (
                            <div
                                key={step.number}
                                className={`card-glow rune-border relative overflow-hidden rounded-xl p-8 ${step.bgColor}`}
                            >
                                <div className="absolute right-4 top-4 select-none font-display text-6xl font-black leading-none text-[oklch(0.95_0.01_80/5%)]">
                                    {step.number}
                                </div>
                                <div className={`mb-6 flex h-12 w-12 items-center justify-center rounded-lg border ${step.borderColor} ${step.bgColor}`}>
                                    <Icon className={`h-6 w-6 ${step.color}`} />
                                </div>
                                <h3 className={`mb-3 font-body text-xl font-bold ${step.color}`}>
                                    {step.title}
                                </h3>
                                <p className="font-body leading-relaxed text-muted-foreground">
                                    {step.description}
                                </p>
                            </div>
                        );
                    })}
                </div>

                <div className="rune-border rounded-2xl bg-[oklch(0.11_0.008_260)] p-8 lg:p-12">
                    <h3 className="mb-2 text-center font-display text-2xl font-black sm:text-3xl">
                        Откуда берётся энергия
                    </h3>
                    <p className="mb-10 text-center font-body text-muted-foreground">
                        Три категории достижений, каждый день
                    </p>
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        {energySources.map((src) => {
                            const Icon = src.icon;
                            return (
                                <div
                                    key={src.label}
                                    className="flex flex-col items-center rounded-xl border border-[oklch(0.75_0.18_75/10%)] p-6 text-center transition-colors hover:border-[oklch(0.75_0.18_75/25%)]"
                                >
                                    <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full border border-[oklch(0.75_0.18_75/20%)]">
                                        <Icon className={`h-7 w-7 ${src.color}`} />
                                    </div>
                                    <div className={`mb-1 font-body text-lg font-bold ${src.color}`}>
                                        {src.label}
                                    </div>
                                    <div className="font-body text-sm text-muted-foreground">
                                        {src.desc}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}
