function formatStats(stats) {
    if (!stats) {
        return '';
    }

    const parts = [];

    if (stats.strength > 0) {
        parts.push(`+${stats.strength} урон`);
    }
    if (stats.defense > 0) {
        parts.push(`+${stats.defense} броня`);
    }
    if (stats.max_hp > 0) {
        parts.push(`+${stats.max_hp} HP`);
    }

    return `БСП ${stats.bsp}${parts.length ? ` · ${parts.join(' · ')}` : ''}`;
}

export default function EquipmentStatsPreview({ preview, className = '' }) {
    if (!preview) {
        return null;
    }

    if (preview.variants?.length) {
        return (
            <div className={`space-y-1 text-xs text-gray-600 ${className}`}>
                {preview.variants.map((variant) => (
                    <p key={variant.quality ?? variant.quality_label}>
                        <span>{variant.quality_label}</span>
                        {' '}ур. {preview.level}: {formatStats(variant.stats)}
                        {variant.note && (
                            <span className="text-amber-700"> ({variant.note})</span>
                        )}
                    </p>
                ))}
            </div>
        );
    }

    return (
        <p className={`text-xs text-gray-600 ${className}`}>
            <span>{preview.quality_label}</span>
            {' '}ур. {preview.level}: {formatStats(preview.stats)}
        </p>
    );
}
