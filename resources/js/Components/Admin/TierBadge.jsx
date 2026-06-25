const TIERS = {
    white: { label: 'Белый', dot: 'bg-gray-300', text: 'text-gray-600' },
    green: { label: 'Зелёный', dot: 'bg-green-500', text: 'text-green-700' },
    blue: { label: 'Синий', dot: 'bg-blue-500', text: 'text-blue-700' },
    purple: { label: 'Фиолетовый', dot: 'bg-purple-500', text: 'text-purple-700' },
    red: { label: 'Красный', dot: 'bg-red-500', text: 'text-red-700' },
    boss: { label: 'Босс', dot: 'bg-amber-500', text: 'text-amber-700' },
    normal: { label: 'Обычный', dot: 'bg-gray-400', text: 'text-gray-600' },
    rare: { label: 'Редкий', dot: 'bg-blue-500', text: 'text-blue-700' },
};

export default function TierBadge({ value }) {
    if (value === null || value === undefined || value === '') {
        return <span className="text-slate-400">—</span>;
    }

    const tier = TIERS[value] ?? { label: value, dot: 'bg-slate-300', text: 'text-slate-600' };

    return (
        <span className={`inline-flex items-center gap-1.5 ${tier.text}`}>
            <span className={`h-2.5 w-2.5 rounded-full ${tier.dot}`} />
            <span className="font-medium">{tier.label}</span>
        </span>
    );
}
