const LOCALE = 'en-GB';

let defaultCurrency = 'GBP';
const formatters = new Map<string, Intl.NumberFormat>();

/**
 * Set the shop's currency (from the `shop.currency` shared prop, i.e. `config/shop.php`).
 * Called once when the app boots, so every price on the page follows the config.
 */
export function configureCurrency(currency: string): void {
    defaultCurrency = currency.toUpperCase();
}

function formatter(currency: string, decimals: boolean): Intl.NumberFormat {
    const key = `${currency}:${decimals}`;
    let instance = formatters.get(key);

    if (!instance) {
        instance = new Intl.NumberFormat(LOCALE, {
            style: 'currency',
            currency,
            minimumFractionDigits: decimals ? 2 : 0,
            maximumFractionDigits: decimals ? 2 : 0,
        });
        formatters.set(key, instance);
    }

    return instance;
}

/**
 * Format a price stored in minor units (e.g. pence) for display.
 * Whole amounts drop the decimals: 8900 → "£89", 8950 → "£89.50".
 *
 * @param currency ISO code; defaults to the shop currency. Pass an order's own
 *                 currency when showing a past order.
 */
export function formatPrice(minorUnits: number, currency: string = defaultCurrency): string {
    return formatter(currency.toUpperCase(), minorUnits % 100 !== 0).format(minorUnits / 100);
}
