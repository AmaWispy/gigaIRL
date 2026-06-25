const QUALITY_PREFIX = /^(?:⚪|🟢|🔵|🟣|🔴)\s+/u;

export function itemDisplayName(name, { stripQualityPrefix = false } = {}) {
    if (!name) {
        return '';
    }

    return stripQualityPrefix ? name.replace(QUALITY_PREFIX, '') : name;
}
