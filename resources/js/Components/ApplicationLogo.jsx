import { Sword } from 'lucide-react';

export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <span
            {...props}
            className={`inline-flex items-center justify-center rounded-sm border border-[oklch(0.75_0.18_75/60%)] text-[oklch(0.75_0.18_75)] transition-colors hover:border-[oklch(0.75_0.18_75)] ${className}`}
        >
            <Sword className="h-1/2 w-1/2" />
        </span>
    );
}
