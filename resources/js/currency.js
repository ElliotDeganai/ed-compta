const formatter = new Intl.NumberFormat('fr-CH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})

export function amount(value) {
    return formatter.format(Number(value || 0))
}

export function money(value, currency = 'CHF') {
    return `${amount(value)} ${currency}`
}

export function signed(value, type, currency = 'CHF') {
    const sign = type === 'income' ? '+' : '\u2212'

    return `${sign}${amount(value)} ${currency}`
}

export function shortDate(value) {
    if (!value) {
        return ''
    }

    return new Date(value).toLocaleDateString('fr-CH', { day: '2-digit', month: 'short' })
}

export function fullDate(value) {
    if (!value) {
        return ''
    }

    return new Date(value).toLocaleDateString('fr-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
