import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Menu, Sword, X } from 'lucide-react';

const navLinks = [
    { label: 'Геймплей', href: '#gameplay' },
    { label: 'Мир', href: '#world' },
    { label: 'Снаряжение', href: '#equipment' },
    { label: 'Персонаж', href: '#character' },
];

export default function Navbar({ auth }) {
    const [scrolled, setScrolled] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 40);
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const isAuthed = Boolean(auth?.user);

    return (
        <header
            className={`fixed left-0 right-0 top-0 z-50 transition-all duration-500 ${
                scrolled
                    ? 'border-b border-[oklch(0.75_0.18_75/15%)] bg-[oklch(0.08_0.005_260/95%)] backdrop-blur-md'
                    : 'bg-transparent'
            }`}
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex h-16 items-center justify-between lg:h-20">
                    <a href="#" className="group flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-sm border border-[oklch(0.75_0.18_75/60%)] transition-colors group-hover:border-[oklch(0.75_0.18_75)]">
                            <Sword className="h-4 w-4 text-[oklch(0.75_0.18_75)]" />
                        </div>
                        <span className="font-display text-xl font-black tracking-widest text-[oklch(0.75_0.18_75)]">
                            giga<span className="text-foreground">IRL</span>
                        </span>
                    </a>

                    <nav className="hidden items-center gap-8 md:flex">
                        {navLinks.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                className="font-body text-sm uppercase tracking-wider text-muted-foreground transition-colors duration-200 hover:text-[oklch(0.75_0.18_75)]"
                            >
                                {link.label}
                            </a>
                        ))}
                    </nav>

                    <div className="hidden items-center gap-3 md:flex">
                        {isAuthed ? (
                            <Link href={route('dashboard')} className="btn-gold cursor-pointer rounded px-5 py-2 text-sm">
                                Играть
                            </Link>
                        ) : (
                            <>
                                <Link href={route('login')} className="btn-outline-gold cursor-pointer rounded px-5 py-2 text-sm">
                                    Войти
                                </Link>
                                <Link href={route('register')} className="btn-gold cursor-pointer rounded px-5 py-2 text-sm">
                                    Начать играть
                                </Link>
                            </>
                        )}
                    </div>

                    <button
                        className="text-muted-foreground hover:text-foreground md:hidden"
                        onClick={() => setMobileOpen(!mobileOpen)}
                        aria-label="Меню"
                    >
                        {mobileOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                    </button>
                </div>

                {mobileOpen && (
                    <div className="flex flex-col gap-4 border-t border-[oklch(0.75_0.18_75/15%)] bg-[oklch(0.08_0.005_260/98%)] py-4 md:hidden">
                        {navLinks.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                onClick={() => setMobileOpen(false)}
                                className="px-2 font-body text-sm uppercase tracking-wider text-muted-foreground transition-colors hover:text-[oklch(0.75_0.18_75)]"
                            >
                                {link.label}
                            </a>
                        ))}
                        <div className="flex flex-col gap-2 border-t border-[oklch(0.75_0.18_75/15%)] pt-2">
                            {isAuthed ? (
                                <Link href={route('dashboard')} className="btn-gold rounded px-5 py-2 text-center text-sm">
                                    Играть
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')} className="btn-outline-gold rounded px-5 py-2 text-center text-sm">
                                        Войти
                                    </Link>
                                    <Link href={route('register')} className="btn-gold rounded px-5 py-2 text-center text-sm">
                                        Начать играть
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </header>
    );
}
