export default function SetNameTooltip({ set, children }) {
    if (!set?.name) {
        return children;
    }

    return (
        <span className="relative inline group cursor-help">
            <span className="border-b border-dotted border-current">{children}</span>
            <span
                role="tooltip"
                className="pointer-events-none absolute left-0 top-full z-20 mt-1.5 hidden w-60 rounded-lg border border-gray-200 bg-white p-3 text-left text-xs font-normal normal-case tracking-normal text-gray-700 shadow-lg group-hover:block"
            >
                <span className="mb-2 block text-sm font-semibold text-gray-900">
                    {set.name}
                    {set.item_level ? ` (ур. ${set.item_level})` : ''}
                </span>
                <ul className="space-y-1">
                    {set.items.map((item) => (
                        <li key={`${item.slot}-${item.name}`}>{item.name}</li>
                    ))}
                </ul>
            </span>
        </span>
    );
}

/**
 * Wraps «set name» in description with a hover tooltip when set data is available.
 */
export function DescriptionWithSetTooltip({ description, set }) {
    if (!description) {
        return null;
    }

    if (!set?.name || !description.includes(`«${set.name}»`)) {
        return <>{description}</>;
    }

    const marker = `«${set.name}»`;
    const [before, after] = description.split(marker);

    return (
        <>
            {before}
            <SetNameTooltip set={set}>«{set.name}»</SetNameTooltip>
            {after}
        </>
    );
}
