import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';

export default function HeroSection({ auth }) {
    const isAuthed = Boolean(auth?.user);

    return (
        <section className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden pt-24 lg:pt-28">
            <div
                className="absolute inset-0 bg-cover bg-center bg-no-repeat"
                style={{ backgroundImage: "url('/images/hero-bg.png')" }}
            />
            <div className="absolute inset-0 bg-[oklch(0.08_0.005_260/70%)]" />
            <div className="absolute bottom-0 left-0 right-0 h-48 bg-gradient-to-t from-[oklch(0.08_0.005_260)] to-transparent" />
            <div className="absolute left-0 right-0 top-0 h-32 bg-gradient-to-b from-[oklch(0.08_0.005_260/80%)] to-transparent" />

            <div
                className="absolute inset-0 opacity-5"
                style={{
                    backgroundImage:
                        'linear-gradient(oklch(0.75 0.18 75) 1px, transparent 1px), linear-gradient(90deg, oklch(0.75 0.18 75) 1px, transparent 1px)',
                    backgroundSize: '80px 80px',
                }}
            />

            <div className="relative z-10 mx-auto max-w-5xl px-4 text-center">
                <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-[oklch(0.75_0.18_75/30%)] bg-[oklch(0.75_0.18_75/8%)] px-4 py-2">
                    <span className="h-2 w-2 animate-pulse rounded-full bg-[oklch(0.75_0.18_75)]" />
                    <span className="font-body text-xs uppercase tracking-widest text-[oklch(0.75_0.18_75)]">
                        Браузерная текстовая RPG
                    </span>
                </div>

                <h1 className="mb-4 font-display text-6xl font-black leading-none tracking-tight sm:text-7xl lg:text-9xl">
                    <span className="text-shimmer">giga</span>
                    <span className="text-foreground">IRL</span>
                </h1>

                <p className="mx-auto mb-4 max-w-3xl font-body text-xl leading-relaxed text-muted-foreground sm:text-2xl lg:text-3xl">
                    Живи продуктивно.{' '}
                    <span className="text-[oklch(0.75_0.18_75)]">Прокачивай героя.</span>{' '}
                    Покоряй миры.
                </p>

                <p className="mx-auto mb-12 max-w-xl font-body text-base leading-relaxed text-muted-foreground sm:text-lg">
                    Каждое реальное достижение превращается в энергию для твоего персонажа.
                    Чем лучше ты — тем сильнее герой.
                </p>

                <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    {isAuthed ? (
                        <Link
                            href={route('dashboard')}
                            className="btn-gold w-full animate-glow-pulse rounded px-8 py-4 text-base font-bold uppercase tracking-widest sm:w-auto"
                        >
                            Продолжить игру
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={route('register')}
                                className="btn-gold w-full animate-glow-pulse rounded px-8 py-4 text-base font-bold uppercase tracking-widest sm:w-auto"
                            >
                                Создать героя
                            </Link>
                            <Link
                                href={route('login')}
                                className="btn-outline-gold w-full rounded px-8 py-4 text-base uppercase tracking-widest sm:w-auto"
                            >
                                Войти в игру
                            </Link>
                        </>
                    )}
                </div>

                <div className="mt-16 flex items-center justify-center gap-8 text-muted-foreground">
                    <div className="text-center">
                        <div className="font-display text-3xl font-black text-[oklch(0.75_0.18_75)]">11</div>
                        <div className="mt-1 font-body text-xs uppercase tracking-wider">Слотов брони</div>
                    </div>
                    <div className="h-10 w-px bg-[oklch(0.75_0.18_75/20%)]" />
                    <div className="text-center">
                        <div className="font-display text-3xl font-black text-[oklch(0.75_0.18_75)]">∞</div>
                        <div className="mt-1 font-body text-xs uppercase tracking-wider">Данжей</div>
                    </div>
                    <div className="h-10 w-px bg-[oklch(0.75_0.18_75/20%)]" />
                    <div className="text-center">
                        <div className="font-display text-3xl font-black text-[oklch(0.75_0.18_75)]">5</div>
                        <div className="mt-1 font-body text-xs uppercase tracking-wider">Редкостей</div>
                    </div>
                </div>
            </div>

            <a
                href="#gameplay"
                className="absolute bottom-8 left-1/2 -translate-x-1/2 animate-float text-muted-foreground transition-colors hover:text-[oklch(0.75_0.18_75)]"
                aria-label="Прокрутить вниз"
            >
                <ChevronDown className="h-7 w-7" />
            </a>
        </section>
    );
}
