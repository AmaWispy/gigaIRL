export function monsterNameClass(tier) {
    if (tier === 'rare') {
        return 'text-blue-700 font-medium';
    }

    if (tier === 'boss') {
        return 'text-red-700 font-semibold';
    }

    return 'text-gray-700';
}

export function monsterTierPrefix(tier) {
    if (tier === 'rare') {
        return '🔵 ';
    }

    if (tier === 'boss') {
        return '🔴 ';
    }

    return '';
}
