import { Link } from '@inertiajs/react';
import { ChevronRight, LogIn, UserPlus, Zap } from 'lucide-react';

export default function CtaSection({ auth }) {
    const isAuthed = Boolean(auth?.user);

    return (
        <section id="register" className="relative overflow-hidden px-4 py-24 lg:py-36">
            <div className="absolute inset-0">
                <img
                    src="/images/dungeon.png"
                    alt=""
                    aria-hidden="true"
                    className="h-full w-full object-cover opacity-30"
                />
                <div className="absolute inset-0 bg-[oklch(0.08_0.005_260/75%)]" />
                <div className="absolute inset-0 bg-gradient-to-t from-[oklch(0.08_0.005_260)] via-transparent to-[oklch(0.08_0.005_260/80%)]" />
            </div>

            <div className="relative mx-auto max-w-3xl text-center">
                <div className="mb-8 inline-flex items-center gap-2 rounded-full border border-[oklch(0.75_0.18_75/40%)] bg-[oklch(0.75_0.18_75/10%)] px-4 py-2">
                    <Zap className="h-4 w-4 text-[oklch(0.75_0.18_75)]" />
                    <span className="font-body text-sm font-semibold tracking-wider text-[oklch(0.75_0.18_75)]">
                        Начни прямо сейчас — это бесплатно
                    </span>
                </div>

                <h2 className="mb-6 font-display text-5xl font-black leading-none sm:text-6xl lg:text-7xl">
                    Готов стать <br />
                    <span className="text-shimmer">легендой?</span>
                </h2>

                <p className="mx-auto mb-12 max-w-xl font-body text-lg leading-relaxed text-muted-foreground sm:text-xl">
                    Создай героя, начни отмечать дела и отправляйся в своё первое приключение.
                    Твой продуктивный день — это уже победа.
                </p>

                {isAuthed ? (
                    <div className="mb-8">
                        <Link
                            href={route('dashboard')}
                            className="btn-gold mx-auto w-full max-w-sm gap-2 rounded-lg py-3.5 text-sm font-bold uppercase tracking-wider"
                        >
                            Продолжить приключение
                            <ChevronRight className="h-4 w-4" />
                        </Link>
                    </div>
                ) : (
                    <div className="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div className="card-glow rune-border rounded-2xl border border-[oklch(0.75_0.18_75/30%)] bg-[oklch(0.13_0.01_260)] p-8 text-left">
                            <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-[oklch(0.75_0.18_75/30%)] bg-[oklch(0.75_0.18_75/20%)]">
                                <UserPlus className="h-6 w-6 text-[oklch(0.75_0.18_75)]" />
                            </div>
                            <h3 className="mb-2 font-body text-xl font-black text-[oklch(0.95_0.01_80)]">
                                Регистрация
                            </h3>
                            <p className="mb-6 font-body text-sm leading-relaxed text-[oklch(0.6_0.01_80)]">
                                Создай своего героя и начни строить легенду прямо сейчас
                            </p>
                            <Link
                                href={route('register')}
                                className="btn-gold w-full gap-2 rounded-lg py-3.5 text-sm font-bold uppercase tracking-wider"
                            >
                                Создать героя
                                <ChevronRight className="h-4 w-4" />
                            </Link>
                        </div>

                        <div className="card-glow rounded-2xl border border-[oklch(0.75_0.18_75/20%)] bg-[oklch(0.13_0.01_260)] p-8 text-left" id="login">
                            <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-[oklch(0.75_0.18_75/20%)] bg-[oklch(0.75_0.18_75/10%)]">
                                <LogIn className="h-6 w-6 text-[oklch(0.6_0.01_80)]" />
                            </div>
                            <h3 className="mb-2 font-body text-xl font-black text-[oklch(0.95_0.01_80)]">
                                Войти в игру
                            </h3>
                            <p className="mb-6 font-body text-sm leading-relaxed text-[oklch(0.6_0.01_80)]">
                                Уже есть аккаунт? Продолжи своё приключение с того места, где остановился
                            </p>
                            <Link
                                href={route('login')}
                                className="btn-outline-gold w-full gap-2 rounded-lg py-3.5 text-sm font-bold uppercase tracking-wider"
                            >
                                Войти
                                <LogIn className="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                )}

                <p className="font-body text-xs text-muted-foreground">
                    Бесплатно · Только браузер · Без установки
                </p>
            </div>
        </section>
    );
}
