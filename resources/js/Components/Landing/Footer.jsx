import { Sword } from 'lucide-react';

export default function Footer() {
    return (
        <footer className="relative border-t border-[oklch(0.75_0.18_75/10%)] px-4 py-10">
            <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 sm:flex-row">
                <div className="flex items-center gap-2">
                    <div className="flex h-6 w-6 items-center justify-center rounded-sm border border-[oklch(0.75_0.18_75/40%)]">
                        <Sword className="h-3.5 w-3.5 text-[oklch(0.75_0.18_75)]" />
                    </div>
                    <span className="font-display text-base font-black tracking-widest text-[oklch(0.75_0.18_75)]">
                        giga<span className="text-muted-foreground">IRL</span>
                    </span>
                </div>

                <p className="text-center font-body text-xs text-muted-foreground">
                    Живи свою жизнь продуктивно — и твой герой будет становиться сильнее.
                </p>

                <div className="flex flex-col items-center gap-1 font-body text-xs text-muted-foreground sm:items-end">
                    <span>© 2026 gigaIRL</span>
                    <span>
                        Разработка —{' '}
                        <a
                            href="https://ameliq.ru"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-[oklch(0.75_0.18_75)] transition-colors hover:text-[oklch(0.85_0.16_80)]"
                        >
                            ameliq.ru
                        </a>
                    </span>
                </div>
            </div>
        </footer>
    );
}
