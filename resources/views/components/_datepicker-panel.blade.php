{{-- DatePicker panel — included by datepicker.blade.php --}}
<div class="flex">
    {{-- Presets sidebar --}}
    @if($presets && $mode === 'range')
        <div class="border-r border-kore-border pr-3 mr-3 min-w-[130px]">
            <template x-for="(preset, pi) in presetOptions" :key="pi">
                <button
                    type="button"
                    x-on:click="applyPreset(preset)"
                    x-text="preset.label"
                    class="block w-full text-left text-sm px-2 py-1.5 rounded-kore-sm hover:bg-kore-muted text-kore-fg transition-colors"
                ></button>
            </template>
        </div>
    @endif

    <div class="flex-1">
        {{-- Multi-month or single-month --}}
        @if($months > 1)
            <div class="flex gap-4">
                <template x-for="(cal, ci) in calendars" :key="ci">
                    <div class="min-w-[252px]">
                        {{-- Header --}}
                        <div class="flex items-center justify-between mb-2">
                            <button
                                type="button"
                                x-show="ci === 0"
                                x-on:click="prevMonth()"
                                class="p-1 rounded-kore-sm hover:bg-kore-muted text-kore-muted-fg hover:text-kore-fg transition-colors"
                            >
                                <x-lucide-chevron-left class="size-4" />
                            </button>
                            <span x-show="ci !== 0" class="w-6"></span>

                            <span
                                x-text="cal.title"
                                class="text-sm font-medium text-kore-fg"
                            ></span>

                            <button
                                type="button"
                                x-show="ci === calendars.length - 1"
                                x-on:click="nextMonth()"
                                class="p-1 rounded-kore-sm hover:bg-kore-muted text-kore-muted-fg hover:text-kore-fg transition-colors"
                            >
                                <x-lucide-chevron-right class="size-4" />
                            </button>
                            <span x-show="ci !== calendars.length - 1" class="w-6"></span>
                        </div>

                        {{-- Day grid --}}
                        <table class="w-full border-collapse" role="grid">
                            <thead>
                                <tr>
                                    @if($showWeekNumbers)
                                        <th class="w-8 text-[10px] font-normal text-kore-muted-fg text-center pb-1">#</th>
                                    @endif
                                    <template x-for="(wd, wi) in weekdayHeaders" :key="wi">
                                        <th class="w-9 h-9 text-xs font-normal text-kore-muted-fg text-center" x-text="wd"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(week, ri) in cal.weeks" :key="ri">
                                    <tr>
                                        @if($showWeekNumbers)
                                            <td class="text-[10px] text-kore-muted-fg text-center" x-text="_getWeekNumber(week[0].date)"></td>
                                        @endif
                                        <template x-for="(cell, di) in week" :key="di">
                                            <td class="p-0">
                                                <button
                                                    type="button"
                                                    x-on:click="selectDate(cell)"
                                                    x-on:mouseenter="onCellHover(cell)"
                                                    x-text="cell.day"
                                                    class="w-9 h-9 text-sm rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring"
                                                    x-bind:class="{
                                                        'bg-kore-primary text-kore-primary-fg font-medium': _isCellSelected(cell.date),
                                                        'ring-1 ring-kore-primary': cell.isToday && !_isCellSelected(cell.date),
                                                        'text-kore-muted-fg/50': !cell.isCurrentMonth,
                                                        'opacity-30 cursor-not-allowed': cell.isDisabled,
                                                        'hover:bg-kore-muted cursor-pointer': !cell.isDisabled && !_isCellSelected(cell.date),
                                                        'bg-kore-primary/10': isInRange(cell.date) && !isRangeStart(cell.date) && !isRangeEnd(cell.date),
                                                        'rounded-l-full': isRangeStart(cell.date),
                                                        'rounded-r-full': isRangeEnd(cell.date),
                                                    }"
                                                    x-bind:disabled="cell.isDisabled"
                                                    x-bind:tabindex="focusedDate && _isSameDay(cell.date, focusedDate) ? 0 : -1"
                                                ></button>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        @else
            {{-- Single month view --}}
            <div class="min-w-[252px]">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-2">
                    <button
                        type="button"
                        x-on:click="currentView === 'year' ? viewDate = new Date(viewDate.getFullYear() - 10, viewDate.getMonth(), 1) : prevMonth()"
                        class="p-1 rounded-kore-sm hover:bg-kore-muted text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        <x-lucide-chevron-left class="size-4" />
                    </button>

                    <button
                        type="button"
                        x-on:click="currentView === 'date' ? showMonthView() : (currentView === 'month' ? showYearView() : null)"
                        x-text="viewTitle"
                        class="text-sm font-medium text-kore-fg hover:bg-kore-muted px-2 py-1 rounded-kore-sm transition-colors"
                    ></button>

                    <button
                        type="button"
                        x-on:click="currentView === 'year' ? viewDate = new Date(viewDate.getFullYear() + 10, viewDate.getMonth(), 1) : nextMonth()"
                        class="p-1 rounded-kore-sm hover:bg-kore-muted text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        <x-lucide-chevron-right class="size-4" />
                    </button>
                </div>

                {{-- DATE VIEW --}}
                <div x-show="currentView === 'date'">
                    <table class="w-full border-collapse" role="grid">
                        <thead>
                            <tr>
                                @if($showWeekNumbers)
                                    <th class="w-8 text-[10px] font-normal text-kore-muted-fg text-center pb-1">#</th>
                                @endif
                                <template x-for="(wd, wi) in weekdayHeaders" :key="wi">
                                    <th class="w-9 h-9 text-xs font-normal text-kore-muted-fg text-center" x-text="wd"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(week, ri) in weeks" :key="ri">
                                <tr>
                                    @if($showWeekNumbers)
                                        <td class="text-[10px] text-kore-muted-fg text-center" x-text="_getWeekNumber(week[0].date)"></td>
                                    @endif
                                    <template x-for="(cell, di) in week" :key="di">
                                        <td class="p-0">
                                            <button
                                                type="button"
                                                x-on:click="selectDate(cell)"
                                                x-on:mouseenter="onCellHover(cell)"
                                                x-text="cell.day"
                                                class="w-9 h-9 text-sm rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring"
                                                x-bind:class="{
                                                    'bg-kore-primary text-kore-primary-fg font-medium': _isCellSelected(cell.date),
                                                    'ring-1 ring-kore-primary': cell.isToday && !_isCellSelected(cell.date),
                                                    'text-kore-muted-fg/50': !cell.isCurrentMonth,
                                                    'opacity-30 cursor-not-allowed': cell.isDisabled,
                                                    'hover:bg-kore-muted cursor-pointer': !cell.isDisabled && !_isCellSelected(cell.date),
                                                    'bg-kore-primary/10': isInRange(cell.date) && !isRangeStart(cell.date) && !isRangeEnd(cell.date),
                                                    'rounded-l-full': isRangeStart(cell.date),
                                                    'rounded-r-full': isRangeEnd(cell.date),
                                                }"
                                                x-bind:disabled="cell.isDisabled"
                                                x-bind:tabindex="focusedDate && _isSameDay(cell.date, focusedDate) ? 0 : -1"
                                            ></button>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- MONTH VIEW --}}
                <div x-show="currentView === 'month'" x-cloak>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="(month, mi) in monthNames" :key="mi">
                            <button
                                type="button"
                                x-on:click="goToMonth(mi)"
                                x-text="month"
                                class="py-2 text-sm rounded-kore-sm transition-colors hover:bg-kore-muted text-kore-fg"
                                x-bind:class="{ 'bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90': viewDate.getMonth() === mi }"
                            ></button>
                        </template>
                    </div>
                </div>

                {{-- YEAR VIEW --}}
                <div x-show="currentView === 'year'" x-cloak>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="(year, yi) in yearRange" :key="yi">
                            <button
                                type="button"
                                x-on:click="goToYear(year)"
                                x-text="year"
                                class="py-2 text-sm rounded-kore-sm transition-colors hover:bg-kore-muted text-kore-fg"
                                x-bind:class="{ 'bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90': viewDate.getFullYear() === year }"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>
        @endif

        {{-- Time picker --}}
        @if($withTime)
            <div class="flex items-center justify-center gap-2 mt-3 pt-3 border-t border-kore-border">
                {{-- Hours --}}
                <div class="flex flex-col items-center">
                    <button
                        type="button"
                        x-on:mousedown="startHold(() => incrementHour())"
                        x-on:mouseup="stopHold()"
                        x-on:mouseleave="stopHold()"
                        x-on:touchstart.prevent="startHold(() => incrementHour())"
                        x-on:touchend="stopHold()"
                        class="p-0.5 text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        <x-lucide-chevron-up class="size-4" />
                    </button>
                    <span
                        x-text="String(hours).padStart(2, '0')"
                        class="text-lg font-medium text-kore-fg tabular-nums w-8 text-center"
                    ></span>
                    <button
                        type="button"
                        x-on:mousedown="startHold(() => decrementHour())"
                        x-on:mouseup="stopHold()"
                        x-on:mouseleave="stopHold()"
                        x-on:touchstart.prevent="startHold(() => decrementHour())"
                        x-on:touchend="stopHold()"
                        class="p-0.5 text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        <x-lucide-chevron-down class="size-4" />
                    </button>
                </div>

                <span class="text-lg font-medium text-kore-fg">:</span>

                {{-- Minutes --}}
                <div class="flex flex-col items-center">
                    <button
                        type="button"
                        x-on:mousedown="startHold(() => incrementMinute())"
                        x-on:mouseup="stopHold()"
                        x-on:mouseleave="stopHold()"
                        x-on:touchstart.prevent="startHold(() => incrementMinute())"
                        x-on:touchend="stopHold()"
                        class="p-0.5 text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        <x-lucide-chevron-up class="size-4" />
                    </button>
                    <span
                        x-text="String(minutes).padStart(2, '0')"
                        class="text-lg font-medium text-kore-fg tabular-nums w-8 text-center"
                    ></span>
                    <button
                        type="button"
                        x-on:mousedown="startHold(() => decrementMinute())"
                        x-on:mouseup="stopHold()"
                        x-on:mouseleave="stopHold()"
                        x-on:touchstart.prevent="startHold(() => decrementMinute())"
                        x-on:touchend="stopHold()"
                        class="p-0.5 text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        <x-lucide-chevron-down class="size-4" />
                    </button>
                </div>

                {{-- AM/PM toggle --}}
                @if($timeFormat === '12')
                    <button
                        type="button"
                        x-on:click="toggleAmPm()"
                        x-text="ampm"
                        class="ml-1 px-2 py-1 text-sm font-medium rounded-kore-sm border border-kore-input bg-kore-bg text-kore-fg hover:bg-kore-muted transition-colors"
                    ></button>
                @endif
            </div>
        @endif

        {{-- Helpers (single mode) --}}
        @if($helpers && $mode === 'single')
            <div class="flex items-center justify-center gap-2 mt-3 pt-3 border-t border-kore-border">
                <template x-for="(helper, hi) in helperDates" :key="hi">
                    <button
                        type="button"
                        x-on:click="applyHelper(helper)"
                        x-text="helper.label"
                        class="px-2.5 py-1 text-xs rounded-kore-sm border border-kore-input text-kore-fg hover:bg-kore-muted transition-colors"
                    ></button>
                </template>
            </div>
        @endif

        {{-- Confirmation footer --}}
        @if($requiresConfirmation)
            <div class="flex items-center justify-end gap-2 mt-3 pt-3 border-t border-kore-border">
                <button
                    type="button"
                    x-on:click="cancel()"
                    class="px-3 py-1.5 text-sm rounded-kore-md border border-kore-input text-kore-fg hover:bg-kore-muted transition-colors"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    x-on:click="confirm()"
                    class="px-3 py-1.5 text-sm rounded-kore-md bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90 transition-colors"
                >
                    Apply
                </button>
            </div>
        @endif
    </div>
</div>
