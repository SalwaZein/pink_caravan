import Alpine from 'alpinejs';

/**
 * Reusable date field (see resources/views/components/date-field.blade.php).
 *
 * Business feedback: navigating a native month-by-month calendar was slow for
 * admins and nurses, so the field offers two fast paths instead:
 *   - "pick"  — day / month / year dropdowns (no calendar paging at all);
 *   - "type"  — a numeric text box (inputmode="numeric" opens the phone keypad)
 *               where the date is keyed in as DD/MM/YYYY.
 * Both modes keep one hidden input in sync with an ISO (YYYY-MM-DD) value, so
 * the server contract is unchanged from the old <input type="date">.
 */
Alpine.data('pcDateField', (initial = '', minYear = 1900, maxYear = 2100) => ({
    mode: 'pick',
    d: '',
    m: '',
    y: '',
    typed: '',
    minYear,
    maxYear,

    init() {
        this.setFromIso(initial);
        this.typed = this.formatTyped();
    },

    /**
     * Last valid day of the chosen month (31 until a month is picked).
     * The day/year <option> lists are rendered server-side — an x-for list is
     * built after x-model applies, so a preset value would find no option to
     * select and the field would come up blank on an existing record.
     */
    get maxDay() {
        const m = parseInt(this.m, 10);
        if (!m) return 31;

        return new Date(parseInt(this.y, 10) || 2024, m, 0).getDate(); // leap-year aware
    },

    /** The value actually posted: YYYY-MM-DD, or empty while incomplete. */
    get iso() {
        if (!this.y || !this.m || !this.d) return '';
        return `${this.y}-${this.pad(this.m)}-${this.pad(this.d)}`;
    },

    pad(n) {
        return String(n).padStart(2, '0');
    },

    setFromIso(iso) {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec((iso || '').trim());
        if (!match) return;
        this.y = match[1];
        this.m = String(parseInt(match[2], 10));
        this.d = String(parseInt(match[3], 10));
    },

    /** Drop an out-of-range day when the month changes (e.g. 31 → February). */
    clampDay() {
        const max = this.maxDay;
        if (this.d && parseInt(this.d, 10) > max) this.d = String(max);
    },

    formatTyped() {
        return this.iso ? `${this.pad(this.d)}/${this.pad(this.m)}/${this.y}` : '';
    },

    /** Keypad entry: digits are auto-separated as DD/MM/YYYY while typing. */
    onTyped() {
        const digits = this.typed.replace(/\D/g, '').slice(0, 8);
        let out = digits.slice(0, 2);
        if (digits.length > 2) out += '/' + digits.slice(2, 4);
        if (digits.length > 4) out += '/' + digits.slice(4, 8);
        this.typed = out;

        if (digits.length === 8) {
            const d = parseInt(digits.slice(0, 2), 10);
            const m = parseInt(digits.slice(2, 4), 10);
            const y = parseInt(digits.slice(4, 8), 10);
            const valid = m >= 1 && m <= 12
                && d >= 1 && d <= new Date(y, m, 0).getDate()
                && y >= this.minYear && y <= this.maxYear;
            if (valid) {
                this.y = String(y);
                this.m = String(m);
                this.d = String(d);
                return;
            }
        }
        // Incomplete or impossible input posts nothing rather than a bad date.
        this.y = this.m = this.d = '';
    },

    toggle() {
        if (this.mode === 'pick') {
            this.typed = this.formatTyped();
            this.mode = 'type';
            this.$nextTick(() => this.$refs.typedInput && this.$refs.typedInput.focus());
        } else {
            this.mode = 'pick';
        }
    },

    clear() {
        this.y = this.m = this.d = '';
        this.typed = '';
    },
}));

window.Alpine = Alpine;
Alpine.start();
