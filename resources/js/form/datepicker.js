import { startFloating, stopFloating } from '../utils/floating.js';

export default (config) => ({
    open: false,
    currentView: 'date',
    viewDate: null,
    selected: null,
    focusedDate: null,
    calendarDays: [],

    // Range mode
    rangeStart: null,
    rangeEnd: null,
    hoveredDate: null,

    // Multiple mode
    selectedMultiple: [],

    // Time picker
    hours: 0,
    minutes: 0,
    ampm: 'AM',
    holdInterval: null,
    holdTimeout: null,

    // Confirmation mode
    pendingSelected: null,
    pendingRangeStart: null,
    pendingRangeEnd: null,

    // Sin declarar, Alpine las escribiría en el x-data ancestro, no aquí — y `_floatingCleanup`
    // lo comparten seis componentes. Ver tests/js/alpine-scope.test.js.
    _floatingCleanup: null,
    _boundMousedown: null,
    _boundResponsive: null,

    init() {
        const input = this.$refs.hiddenInput;
        this._syncFromInput(input);

        if (!this.viewDate) {
            this.viewDate = new Date();
        }
        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth(), 1);
        this.focusedDate = this.selected ? new Date(this.selected) : new Date();

        // Init time from selected
        if (config.withTime && this.selected) {
            this.hours = this.selected.getHours();
            this.minutes = this.selected.getMinutes();
            if (config.timeFormat === '12') {
                this.ampm = this.hours >= 12 ? 'PM' : 'AM';
                this.hours = this.hours % 12 || 12;
            }
        }

        this.buildCalendar();

        // Livewire re-sync for range/multiple
        if ((config.mode === 'range' || config.mode === 'multiple') && input) {
            const wireModel = input.getAttribute('wire:model.live')
                || input.getAttribute('wire:model.blur')
                || input.getAttribute('wire:model.defer')
                || input.getAttribute('wire:model');
            if (wireModel && this.$wire) {
                this.$wire.$watch(wireModel, (val) => {
                    if (config.mode === 'range' && Array.isArray(val)) {
                        this.rangeStart = val[0] ? this._parseDate(val[0]) : null;
                        this.rangeEnd = val[1] ? this._parseDate(val[1]) : null;
                        this.buildCalendar();
                    } else if (config.mode === 'multiple' && Array.isArray(val)) {
                        this.selectedMultiple = val.map(d => this._parseDate(d)).filter(Boolean);
                        this.buildCalendar();
                    }
                });
            }
        }

        if (input && !input.value) {
            this.$nextTick(() => this._syncFromInput(input));
        }

        // Responsive check for multi-month
        if (config.responsive && config.months > 1) {
            this._checkResponsive();
            this._boundResponsive = () => this._checkResponsive();
            window.addEventListener('resize', this._boundResponsive, { passive: true });
        }
    },

    destroy() {
        this._cleanup();
        if (this._boundResponsive) {
            window.removeEventListener('resize', this._boundResponsive);
            this._boundResponsive = null;
        }
    },

    // ── Dropdown ─────────────────────────────────────────────────

    toggle() {
        this.open ? this.close() : this.openDropdown();
    },

    openDropdown() {
        this.open = true;
        this.currentView = 'date';
        this.buildCalendar();
        this.$nextTick(() => {
            this._floatingCleanup = startFloating(this.$refs.trigger, this.$refs.dropdown, {
                placement: 'bottom-start',
                offset: 4,
            });
            this._addClickAwayListener();
        });
    },

    close() {
        if (!this.open) return;
        this.open = false;
        this.currentView = 'date';
        this._cleanup();
        // When closing with withTime, ensure final value is synced
        if (config.withTime && this.selected) {
            this.syncModel();
        }
    },

    _cleanup() {
        stopFloating(this._floatingCleanup);
        this._floatingCleanup = null;
        this._removeClickAwayListener();
    },

    _onMousedown(e) {
        if (!this.open) return;
        if (this.$refs.trigger?.contains(e.target)) return;
        if (this.$refs.dropdown?.contains(e.target)) return;
        this.close();
    },

    _addClickAwayListener() {
        this._removeClickAwayListener();
        this._boundMousedown = (e) => this._onMousedown(e);
        document.addEventListener('mousedown', this._boundMousedown);
    },

    _removeClickAwayListener() {
        if (this._boundMousedown) {
            document.removeEventListener('mousedown', this._boundMousedown);
            this._boundMousedown = null;
        }
    },

    // ── Calendar math ────────────────────────────────────────────

    _daysInMonth(y, m) {
        return new Date(y, m + 1, 0).getDate();
    },

    _firstDayOfMonth(y, m) {
        return new Date(y, m, 1).getDay();
    },

    _isSameDay(d1, d2) {
        if (!d1 || !d2) return false;
        return d1.getFullYear() === d2.getFullYear()
            && d1.getMonth() === d2.getMonth()
            && d1.getDate() === d2.getDate();
    },

    _isToday(date) {
        return this._isSameDay(date, new Date());
    },

    _toISOString(date) {
        if (!date) return null;
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    },

    _toDateTimeString(date) {
        if (!date) return null;
        const iso = this._toISOString(date);
        let h = this.hours;
        let min = this.minutes;
        if (config.timeFormat === '12') {
            if (this.ampm === 'PM' && h !== 12) h += 12;
            if (this.ampm === 'AM' && h === 12) h = 0;
        }
        return `${iso} ${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
    },

    _parseDate(str) {
        if (!str) return null;
        if (str instanceof Date) return str;
        // Handle 'YYYY-MM-DD' or 'YYYY-MM-DD HH:mm'
        const parts = String(str).split(' ');
        const dateParts = parts[0].split('-');
        if (dateParts.length !== 3) return null;
        const y = parseInt(dateParts[0], 10);
        const m = parseInt(dateParts[1], 10) - 1;
        const d = parseInt(dateParts[2], 10);
        const date = new Date(y, m, d);
        if (parts[1]) {
            const timeParts = parts[1].split(':');
            date.setHours(parseInt(timeParts[0], 10) || 0);
            date.setMinutes(parseInt(timeParts[1], 10) || 0);
        }
        return date;
    },

    _subDays(date, n) {
        const d = new Date(date);
        d.setDate(d.getDate() - n);
        return d;
    },

    _addDays(date, n) {
        const d = new Date(date);
        d.setDate(d.getDate() + n);
        return d;
    },

    _startOfMonth(date) {
        return new Date(date.getFullYear(), date.getMonth(), 1);
    },

    _endOfMonth(date) {
        return new Date(date.getFullYear(), date.getMonth() + 1, 0);
    },

    _getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    },

    // ── Constraints ──────────────────────────────────────────────

    isDisabled(date) {
        if (!date) return true;
        const ts = date.getTime();

        if (config.minDate) {
            const min = this._parseDate(config.minDate);
            if (min && date < new Date(min.getFullYear(), min.getMonth(), min.getDate())) return true;
        }
        if (config.maxDate) {
            const max = this._parseDate(config.maxDate);
            if (max && date > new Date(max.getFullYear(), max.getMonth(), max.getDate(), 23, 59, 59)) return true;
        }

        const day = date.getDay();
        if (config.disabledWeekdays && config.disabledWeekdays.includes(day)) return true;
        if (config.weekdaysOnly && (day === 0 || day === 6)) return true;
        if (config.weekendsOnly && day !== 0 && day !== 6) return true;

        if (config.disabledDates) {
            if (Array.isArray(config.disabledDates)) {
                const isoStr = this._toISOString(date);
                for (const entry of config.disabledDates) {
                    if (Array.isArray(entry) && entry.length === 2) {
                        const rStart = this._parseDate(entry[0]);
                        const rEnd = this._parseDate(entry[1]);
                        if (rStart && rEnd) {
                            const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                            const s = new Date(rStart.getFullYear(), rStart.getMonth(), rStart.getDate());
                            const e = new Date(rEnd.getFullYear(), rEnd.getMonth(), rEnd.getDate());
                            if (d >= s && d <= e) return true;
                        }
                    } else if (typeof entry === 'string' && entry === isoStr) {
                        return true;
                    }
                }
            }
            if (typeof config.disabledDates === 'function') {
                if (config.disabledDates(date)) return true;
            }
        }

        return false;
    },

    // ── Grid generation ──────────────────────────────────────────

    buildCalendar() {
        const y = this.viewDate.getFullYear();
        const m = this.viewDate.getMonth();
        this.calendarDays = this._buildMonthGrid(y, m);
    },

    _buildMonthGrid(y, m) {
        const startOfWeek = config.startOfWeek ?? 1;
        const firstDay = this._firstDayOfMonth(y, m);
        const daysInMonth = this._daysInMonth(y, m);
        const daysInPrevMonth = this._daysInMonth(y, m - 1);

        let startOffset = firstDay - startOfWeek;
        if (startOffset < 0) startOffset += 7;

        const cells = [];

        // Previous month days
        for (let i = startOffset - 1; i >= 0; i--) {
            const date = new Date(y, m - 1, daysInPrevMonth - i);
            cells.push(this._makeCell(date, false));
        }

        // Current month days
        for (let d = 1; d <= daysInMonth; d++) {
            const date = new Date(y, m, d);
            cells.push(this._makeCell(date, true));
        }

        // Next month days (fill to 42)
        let nextDay = 1;
        while (cells.length < 42) {
            const date = new Date(y, m + 1, nextDay++);
            cells.push(this._makeCell(date, false));
        }

        return cells;
    },

    _makeCell(date, isCurrentMonth) {
        return {
            date,
            day: date.getDate(),
            isCurrentMonth,
            isToday: this._isToday(date),
            isSelected: this._isCellSelected(date),
            isDisabled: this.isDisabled(date),
        };
    },

    _isCellSelected(date) {
        if (config.mode === 'range') {
            if (config.requiresConfirmation) {
                return this._isSameDay(date, this.pendingRangeStart) || this._isSameDay(date, this.pendingRangeEnd);
            }
            return this._isSameDay(date, this.rangeStart) || this._isSameDay(date, this.rangeEnd);
        }
        if (config.mode === 'multiple') {
            return this.selectedMultiple.some(d => this._isSameDay(d, date));
        }
        if (config.requiresConfirmation && this.pendingSelected) {
            return this._isSameDay(date, this.pendingSelected);
        }
        return this._isSameDay(date, this.selected);
    },

    get weeks() {
        const rows = [];
        for (let i = 0; i < 42; i += 7) {
            rows.push(this.calendarDays.slice(i, i + 7));
        }
        return rows;
    },

    // ── Multi-month ──────────────────────────────────────────────

    _responsiveMonths: null,

    get effectiveMonths() {
        if (this._responsiveMonths !== null) return this._responsiveMonths;
        return config.months || 1;
    },

    _checkResponsive() {
        this._responsiveMonths = window.innerWidth < 768 ? 1 : (config.months || 1);
    },

    get calendars() {
        const count = this.effectiveMonths;
        const result = [];
        for (let i = 0; i < count; i++) {
            const y = this.viewDate.getFullYear();
            const m = this.viewDate.getMonth() + i;
            const d = new Date(y, m, 1);
            const locale = config.locale || undefined;
            const title = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(d);
            result.push({
                month: d.getMonth(),
                year: d.getFullYear(),
                title,
                weeks: this._buildWeeks(d.getFullYear(), d.getMonth()),
            });
        }
        return result;
    },

    _buildWeeks(y, m) {
        const cells = this._buildMonthGrid(y, m);
        const rows = [];
        for (let i = 0; i < 42; i += 7) {
            rows.push(cells.slice(i, i + 7));
        }
        return rows;
    },

    // ── Navigation ───────────────────────────────────────────────

    prevMonth() {
        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() - 1, 1);
        this.buildCalendar();
    },

    nextMonth() {
        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1);
        this.buildCalendar();
    },

    goToMonth(i) {
        this.viewDate = new Date(this.viewDate.getFullYear(), i, 1);
        this.currentView = 'date';
        this.buildCalendar();
    },

    goToYear(y) {
        this.viewDate = new Date(y, this.viewDate.getMonth(), 1);
        this.currentView = 'month';
    },

    showMonthView() {
        this.currentView = 'month';
    },

    showYearView() {
        this.currentView = 'year';
    },

    // ── 3-level view getters ─────────────────────────────────────

    get monthNames() {
        const locale = config.locale || undefined;
        const formatter = new Intl.DateTimeFormat(locale, { month: 'short' });
        return Array.from({ length: 12 }, (_, i) =>
            formatter.format(new Date(2026, i, 1))
        );
    },

    get yearRange() {
        const y = this.viewDate.getFullYear();
        const start = y - (y % 10) - 1;
        return Array.from({ length: 12 }, (_, i) => start + i);
    },

    get viewTitle() {
        const locale = config.locale || undefined;
        if (this.currentView === 'date') {
            return new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(this.viewDate);
        }
        if (this.currentView === 'month') {
            return String(this.viewDate.getFullYear());
        }
        // year view
        const range = this.yearRange;
        return `${range[0]} – ${range[range.length - 1]}`;
    },

    get weekdayHeaders() {
        const locale = config.locale || undefined;
        const formatter = new Intl.DateTimeFormat(locale, { weekday: 'narrow' });
        const startOfWeek = config.startOfWeek ?? 1;
        // Generate from a known Sunday (Jan 7, 2024 is a Sunday)
        const days = [];
        for (let i = 0; i < 7; i++) {
            const d = new Date(2024, 0, 7 + ((startOfWeek + i) % 7));
            days.push(formatter.format(d));
        }
        return days;
    },

    // ── Selection ────────────────────────────────────────────────

    selectDate(cell) {
        if (cell.isDisabled) return;

        if (!cell.isCurrentMonth) {
            // Navigate to the cell's month
            this.viewDate = new Date(cell.date.getFullYear(), cell.date.getMonth(), 1);
        }

        if (config.mode === 'range') {
            this._selectRange(cell);
        } else if (config.mode === 'multiple') {
            this._selectMultiple(cell);
        } else {
            this._selectSingle(cell);
        }

        this.buildCalendar();
    },

    _selectSingle(cell) {
        if (config.requiresConfirmation) {
            this.pendingSelected = new Date(cell.date);
            return;
        }
        this.selected = new Date(cell.date);
        if (config.withTime) {
            this._applyTimeTo(this.selected);
            // Don't close or sync — let user adjust time first
            // Sync happens on close() or on time change
            return;
        }
        this.syncModel();
        if (!config.inline) this.close();
    },

    _selectRange(cell) {
        const date = new Date(cell.date);
        if (config.requiresConfirmation) {
            if (!this.pendingRangeStart || this.pendingRangeEnd) {
                this.pendingRangeStart = date;
                this.pendingRangeEnd = null;
            } else {
                if (date >= this.pendingRangeStart) {
                    this.pendingRangeEnd = date;
                } else {
                    this.pendingRangeStart = date;
                    this.pendingRangeEnd = null;
                }
            }
            return;
        }
        if (!this.rangeStart || this.rangeEnd) {
            this.rangeStart = date;
            this.rangeEnd = null;
        } else {
            if (date >= this.rangeStart) {
                this.rangeEnd = date;
                this.syncModel();
                if (!config.inline) this.close();
            } else {
                this.rangeStart = date;
                this.rangeEnd = null;
            }
        }
    },

    _selectMultiple(cell) {
        const date = new Date(cell.date);
        const idx = this.selectedMultiple.findIndex(d => this._isSameDay(d, date));
        if (idx > -1) {
            this.selectedMultiple.splice(idx, 1);
        } else {
            if (config.multipleMax && this.selectedMultiple.length >= config.multipleMax) return;
            this.selectedMultiple.push(date);
        }
        this.syncModel();
    },

    isInRange(date) {
        if (config.mode !== 'range') return false;
        const start = config.requiresConfirmation ? this.pendingRangeStart : this.rangeStart;
        const end = config.requiresConfirmation
            ? (this.pendingRangeEnd || this.hoveredDate)
            : (this.rangeEnd || this.hoveredDate);
        if (!start || !end) return false;

        const ts = date.getTime();
        const startTs = start.getTime();
        const endTs = end.getTime();
        const lo = Math.min(startTs, endTs);
        const hi = Math.max(startTs, endTs);

        return ts > lo && ts < hi;
    },

    isRangeStart(date) {
        const start = config.requiresConfirmation ? this.pendingRangeStart : this.rangeStart;
        return this._isSameDay(date, start);
    },

    isRangeEnd(date) {
        const end = config.requiresConfirmation ? this.pendingRangeEnd : this.rangeEnd;
        return this._isSameDay(date, end);
    },

    onCellHover(cell) {
        if (config.mode === 'range') {
            this.hoveredDate = cell.date;
        }
    },

    clear() {
        this.selected = null;
        this.rangeStart = null;
        this.rangeEnd = null;
        this.selectedMultiple = [];
        this.hoveredDate = null;
        this.pendingSelected = null;
        this.pendingRangeStart = null;
        this.pendingRangeEnd = null;
        this.syncModel();
        this.buildCalendar();
    },

    // ── Confirmation ─────────────────────────────────────────────

    confirm() {
        if (config.mode === 'range') {
            this.rangeStart = this.pendingRangeStart;
            this.rangeEnd = this.pendingRangeEnd;
        } else {
            this.selected = this.pendingSelected;
            if (config.withTime) this._applyTimeTo(this.selected);
        }
        this.pendingSelected = null;
        this.pendingRangeStart = null;
        this.pendingRangeEnd = null;
        this.syncModel();
        this.buildCalendar();
        if (!config.inline) this.close();
    },

    cancel() {
        this.pendingSelected = null;
        this.pendingRangeStart = null;
        this.pendingRangeEnd = null;
        this.buildCalendar();
        if (!config.inline) this.close();
    },

    // ── Display value ────────────────────────────────────────────

    get displayValue() {
        const locale = config.locale || undefined;
        if (config.mode === 'range') {
            if (!this.rangeStart) return '';
            const fmt = new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'short', day: 'numeric' });
            if (!this.rangeEnd) return fmt.format(this.rangeStart) + ' – ...';
            return fmt.format(this.rangeStart) + ' – ' + fmt.format(this.rangeEnd);
        }
        if (config.mode === 'multiple') {
            const count = this.selectedMultiple.length;
            if (count === 0) return '';
            if (count === 1) {
                return new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'long', day: 'numeric' }).format(this.selectedMultiple[0]);
            }
            return `${count} dates selected`;
        }
        if (!this.selected) return '';
        const opts = { year: 'numeric', month: 'long', day: 'numeric' };
        if (config.withTime) {
            opts.hour = '2-digit';
            opts.minute = '2-digit';
            if (config.timeFormat === '12') opts.hour12 = true;
            else opts.hour12 = false;
        }
        return new Intl.DateTimeFormat(locale, opts).format(this.selected);
    },

    get hasValue() {
        if (config.mode === 'range') return !!this.rangeStart;
        if (config.mode === 'multiple') return this.selectedMultiple.length > 0;
        return this.selected !== null;
    },

    // ── Wire:model sync ──────────────────────────────────────────

    syncModel() {
        if (!this.$refs.hiddenInput) return;

        const isArrayMode = config.mode === 'range' || config.mode === 'multiple';
        const modelName = Array.from(this.$refs.hiddenInput.attributes)
            .find(a => a.name.startsWith('wire:model'))?.value ?? null;

        if (isArrayMode && this.$wire && modelName) {
            if (config.mode === 'range') {
                this.$wire.$set(modelName, [
                    this._toISOString(this.rangeStart),
                    this._toISOString(this.rangeEnd),
                ]);
            } else {
                this.$wire.$set(modelName, this.selectedMultiple.map(d => this._toISOString(d)));
            }
            return;
        }

        let val;
        if (config.mode === 'range') {
            val = [this._toISOString(this.rangeStart), this._toISOString(this.rangeEnd)].filter(Boolean).join(',') || null;
        } else if (config.mode === 'multiple') {
            val = this.selectedMultiple.map(d => this._toISOString(d)).join(',') || null;
        } else {
            val = config.withTime ? this._toDateTimeString(this.selected) : this._toISOString(this.selected);
        }

        if (val === null && this.$wire && modelName) {
            this.$wire.$set(modelName, null);
            return;
        }

        this.$refs.hiddenInput.value = val ?? '';
        this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    },

    _syncFromInput(input) {
        if (!input?.value) return;
        const val = input.value;

        if (config.mode === 'range') {
            try {
                const arr = JSON.parse(val);
                if (Array.isArray(arr)) {
                    this.rangeStart = arr[0] ? this._parseDate(arr[0]) : null;
                    this.rangeEnd = arr[1] ? this._parseDate(arr[1]) : null;
                    if (this.rangeStart) this.viewDate = new Date(this.rangeStart.getFullYear(), this.rangeStart.getMonth(), 1);
                }
            } catch {
                const parts = val.split(',');
                this.rangeStart = this._parseDate(parts[0]);
                this.rangeEnd = this._parseDate(parts[1]);
                if (this.rangeStart) this.viewDate = new Date(this.rangeStart.getFullYear(), this.rangeStart.getMonth(), 1);
            }
        } else if (config.mode === 'multiple') {
            try {
                const arr = JSON.parse(val);
                if (Array.isArray(arr)) {
                    this.selectedMultiple = arr.map(d => this._parseDate(d)).filter(Boolean);
                }
            } catch {
                this.selectedMultiple = val.split(',').map(d => this._parseDate(d.trim())).filter(Boolean);
            }
            if (this.selectedMultiple.length) {
                this.viewDate = new Date(this.selectedMultiple[0].getFullYear(), this.selectedMultiple[0].getMonth(), 1);
            }
        } else {
            const parsed = this._parseDate(val);
            if (parsed) {
                this.selected = parsed;
                this.viewDate = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
            }
        }
    },

    // ── Time picker ──────────────────────────────────────────────

    _applyTimeTo(date) {
        if (!date || !config.withTime) return;
        let h = this.hours;
        let min = this.minutes;
        if (config.timeFormat === '12') {
            if (this.ampm === 'PM' && h !== 12) h += 12;
            if (this.ampm === 'AM' && h === 12) h = 0;
        }
        date.setHours(h);
        date.setMinutes(min);
    },

    incrementHour() {
        const max = config.timeFormat === '12' ? 12 : 23;
        const min = config.timeFormat === '12' ? 1 : 0;
        this.hours = this.hours >= max ? min : this.hours + 1;
        this._onTimeChange();
    },

    decrementHour() {
        const max = config.timeFormat === '12' ? 12 : 23;
        const min = config.timeFormat === '12' ? 1 : 0;
        this.hours = this.hours <= min ? max : this.hours - 1;
        this._onTimeChange();
    },

    incrementMinute() {
        this.minutes = this.minutes >= 59 ? 0 : this.minutes + 1;
        this._onTimeChange();
    },

    decrementMinute() {
        this.minutes = this.minutes <= 0 ? 59 : this.minutes - 1;
        this._onTimeChange();
    },

    toggleAmPm() {
        this.ampm = this.ampm === 'AM' ? 'PM' : 'AM';
        this._onTimeChange();
    },

    _onTimeChange() {
        if (this.selected && !config.requiresConfirmation) {
            this._applyTimeTo(this.selected);
            // Force reactivity by reassigning
            this.selected = new Date(this.selected);
            this.syncModel();
        }
    },

    startHold(fn) {
        fn();
        this.holdTimeout = setTimeout(() => {
            this.holdInterval = setInterval(() => fn(), 75);
        }, 400);
    },

    stopHold() {
        clearTimeout(this.holdTimeout);
        clearInterval(this.holdInterval);
        this.holdTimeout = null;
        this.holdInterval = null;
    },

    // ── Presets & Helpers ────────────────────────────────────────

    get presetOptions() {
        if (!config.presets) return [];

        // Custom presets array from PHP
        if (Array.isArray(config.presets)) {
            return config.presets.map(p => ({
                label: p.label,
                start: this._parseDate(p.start),
                end: this._parseDate(p.end),
            })).filter(p => p.start && p.end);
        }

        // Default presets when presets === true
        const today = new Date();
        return [
            { label: 'Today', start: today, end: today },
            { label: 'Last 7 days', start: this._subDays(today, 6), end: today },
            { label: 'Last 30 days', start: this._subDays(today, 29), end: today },
            { label: 'This month', start: this._startOfMonth(today), end: this._endOfMonth(today) },
            {
                label: 'Last month',
                start: this._startOfMonth(new Date(today.getFullYear(), today.getMonth() - 1, 1)),
                end: this._endOfMonth(new Date(today.getFullYear(), today.getMonth() - 1, 1)),
            },
            { label: 'This year', start: new Date(today.getFullYear(), 0, 1), end: new Date(today.getFullYear(), 11, 31) },
        ];
    },

    get helperDates() {
        if (!config.helpers) return [];
        const today = new Date();
        const helpers = [
            { label: 'Yesterday', date: this._subDays(today, 1) },
            { label: 'Today', date: today },
            { label: 'Tomorrow', date: this._addDays(today, 1) },
        ];
        return helpers.filter(h => !this.isDisabled(h.date));
    },

    applyPreset(preset) {
        this.rangeStart = new Date(preset.start);
        this.rangeEnd = new Date(preset.end);
        this.viewDate = new Date(preset.start.getFullYear(), preset.start.getMonth(), 1);
        this.syncModel();
        this.buildCalendar();
        if (!config.inline) this.close();
    },

    applyHelper(helper) {
        this.selected = new Date(helper.date);
        if (config.withTime) this._applyTimeTo(this.selected);
        this.viewDate = new Date(helper.date.getFullYear(), helper.date.getMonth(), 1);
        this.syncModel();
        this.buildCalendar();
        if (!config.inline) this.close();
    },

    // ── Manual input ─────────────────────────────────────────────

    onManualInput(e) {
        const val = e.target.value;
        if (!val) return;
        const parsed = this._parseDate(val);
        if (parsed && !isNaN(parsed.getTime()) && !this.isDisabled(parsed)) {
            this.selected = parsed;
            this.viewDate = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
            this.buildCalendar();
            this.syncModel();
        }
    },

    // ── Keyboard ─────────────────────────────────────────────────

    onKeydown(e) {
        if (!this.open) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                this.openDropdown();
            }
            return;
        }

        if (this.currentView !== 'date') {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.currentView = 'date';
            }
            return;
        }

        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                this._moveFocus(-1);
                break;
            case 'ArrowRight':
                e.preventDefault();
                this._moveFocus(1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this._moveFocus(-7);
                break;
            case 'ArrowDown':
                e.preventDefault();
                this._moveFocus(7);
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                if (this.focusedDate) {
                    const cell = this._makeCell(this.focusedDate, true);
                    this.selectDate(cell);
                }
                break;
            case 'Escape':
                e.preventDefault();
                this.close();
                this.$refs.trigger?.focus();
                break;
            case 'PageUp':
                e.preventDefault();
                if (e.shiftKey) {
                    this.viewDate = new Date(this.viewDate.getFullYear() - 1, this.viewDate.getMonth(), 1);
                    this.focusedDate = new Date(this.focusedDate.getFullYear() - 1, this.focusedDate.getMonth(), this.focusedDate.getDate());
                } else {
                    this.prevMonth();
                    this.focusedDate = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth() - 1, this.focusedDate.getDate());
                }
                this.buildCalendar();
                break;
            case 'PageDown':
                e.preventDefault();
                if (e.shiftKey) {
                    this.viewDate = new Date(this.viewDate.getFullYear() + 1, this.viewDate.getMonth(), 1);
                    this.focusedDate = new Date(this.focusedDate.getFullYear() + 1, this.focusedDate.getMonth(), this.focusedDate.getDate());
                } else {
                    this.nextMonth();
                    this.focusedDate = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth() + 1, this.focusedDate.getDate());
                }
                this.buildCalendar();
                break;
        }
    },

    _moveFocus(days) {
        if (!this.focusedDate) {
            this.focusedDate = this.selected ? new Date(this.selected) : new Date();
            return;
        }
        const newDate = this._addDays(this.focusedDate, days);
        this.focusedDate = newDate;

        // If new focused date is outside current view month, navigate
        const viewMonth = this.viewDate.getMonth();
        const viewYear = this.viewDate.getFullYear();
        if (newDate.getMonth() !== viewMonth || newDate.getFullYear() !== viewYear) {
            this.viewDate = new Date(newDate.getFullYear(), newDate.getMonth(), 1);
            this.buildCalendar();
        }
    },
});
