# Date Picker

Full-featured date selection: single, range, multiple modes with calendar navigation, time picker, presets, keyboard support, and locale-aware formatting via `Intl.DateTimeFormat` (no external date libraries).

## Basic Usage

```html
<x-kore::datepicker wire:model="date" label="Date"
    placeholder="Select a date" clearable />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `hint` | string | null | Help text below (hidden when error shown) |
| `name` | string | null | Name for error detection |
| `error` | string | null | Manual error message |
| `size` | string | `'md'` | Size variant: `sm`, `md`, `lg` |
| `placeholder` | string | null | Placeholder text |
| `clearable` | bool | false | Show clear button |
| `disabled` | bool | false | Disabled state |
| `required` | bool | false | Required indicator |
| `showError` | bool | true | Auto-detect errors from `$errors` bag |
| `mode` | string | `'single'` | Selection mode: `single`, `range`, `multiple` |
| `multipleMax` | int | null | Max selections in `multiple` mode |
| `minDate` | string | null | Minimum selectable date (`YYYY-MM-DD`) |
| `maxDate` | string | null | Maximum selectable date (`YYYY-MM-DD`) |
| `disabledDates` | array | null | Array of disabled dates and/or date ranges (`['YYYY-MM-DD', ['start', 'end'], ...]`) |
| `disabledWeekdays` | array | null | Disabled days of week (`[0, 6]` for Sun, Sat) |
| `weekdaysOnly` | bool | false | Only weekdays selectable |
| `weekendsOnly` | bool | false | Only weekends selectable |
| `locale` | string | null | Locale for `Intl.DateTimeFormat` (null = app locale) |
| `startOfWeek` | int | 1 | First day of week (0=Sun, 1=Mon) |
| `inline` | bool | false | Always-visible calendar (no dropdown) |
| `months` | int | 1 | Number of months to show side-by-side |
| `responsive` | bool | true | Collapse multi-month to 1 on mobile (<768px) |
| `showWeekNumbers` | bool | false | Show ISO week numbers column |
| `withTime` | bool | false | Enable time picker below calendar |
| `timeFormat` | string | `'24'` | Time format: `'24'` or `'12'` (AM/PM) |
| `presets` | bool\|array | false | Show preset ranges sidebar (range mode only). `true` for defaults, or array of custom presets |
| `helpers` | bool | false | Show Yesterday/Today/Tomorrow buttons (single mode) |
| `manualInput` | bool | false | Allow typing date directly in `YYYY-MM-DD` format |
| `requiresConfirmation` | bool | false | Apply/Cancel buttons, selection doesn't commit until confirmed |

## Configuration

Global defaults in `config/kore-ui.php`:

```php
'form' => [
    'datepicker' => [
        'locale' => null,        // null = app.locale
        'start_of_week' => 1,   // 0=Sun, 1=Mon
        'format' => null,        // null = Intl defaults
    ],
],
```

## Range Selection

Select a start and end date. Hover previews the range highlight.

```html
<x-kore::datepicker wire:model="dateRange" label="Period"
    mode="range" clearable />
```

The `wire:model` property should be an `array`. Values sync as `['YYYY-MM-DD', 'YYYY-MM-DD']`.

## Multiple Dates

Select individual dates up to an optional maximum.

```html
<x-kore::datepicker wire:model="dates" label="Dates"
    mode="multiple" :multiple-max="5" clearable />
```

Values sync as an array of `YYYY-MM-DD` strings.

## Date & Time

Time spinners appear below the calendar. Hold buttons to auto-increment. The calendar stays open after selecting a date so the user can adjust the time. The value syncs as `YYYY-MM-DD HH:mm`.

```html
<x-kore::datepicker wire:model="dateTime" label="Appointment"
    with-time clearable />

{{-- 12-hour format with AM/PM toggle --}}
<x-kore::datepicker wire:model="dateTime" label="Event"
    with-time time-format="12" />
```

When `with-time` is enabled, `wire:ignore` is applied automatically to prevent Livewire morphs from closing the dropdown while the user adjusts the time.

## Presets

Quick-select sidebar for common date ranges. Only available in `range` mode.

```html
{{-- Default presets --}}
<x-kore::datepicker wire:model="period" label="Report Period"
    mode="range" :presets="true" clearable />
```

Built-in presets: Today, Last 7 days, Last 30 days, This month, Last month, This year.

### Custom Presets

Pass an array of presets with `label`, `start`, and `end` keys:

```html
<x-kore::datepicker wire:model="period" label="Report Period"
    mode="range" :presets="[
        ['label' => 'This week', 'start' => '2026-03-16', 'end' => '2026-03-22'],
        ['label' => 'Next week', 'start' => '2026-03-23', 'end' => '2026-03-29'],
        ['label' => 'Q1 2026', 'start' => '2026-01-01', 'end' => '2026-03-31'],
    ]" clearable />

## Helpers

Yesterday/Today/Tomorrow quick-select buttons. Only available in `single` mode.

```html
<x-kore::datepicker wire:model="date" label="Date"
    :helpers="true" />
```

## Multi-month

Show multiple months side-by-side. Responsive by default — collapses to 1 month on screens < 768px.

```html
<x-kore::datepicker wire:model="dates" label="Travel Dates"
    mode="range" :months="2" clearable />
```

## Inline

Always-visible calendar without dropdown trigger.

```html
<x-kore::datepicker wire:model="date" label="Calendar" inline />
```

## Constraints

```html
{{-- Date range --}}
<x-kore::datepicker label="This year only"
    min-date="2026-01-01" max-date="2026-12-31" />

{{-- Weekdays only --}}
<x-kore::datepicker label="Business days" :weekdays-only="true" />

{{-- Specific dates disabled --}}
<x-kore::datepicker label="Availability"
    :disabled-dates="['2026-03-25', '2026-12-25']" />

{{-- Date ranges disabled --}}
<x-kore::datepicker label="Availability"
    :disabled-dates="[
        '2026-12-25',
        ['2026-04-01', '2026-04-10'],
        ['2026-07-20', '2026-07-31'],
    ]" />

{{-- Disabled weekdays by number --}}
<x-kore::datepicker label="No weekends"
    :disabled-weekdays="[0, 6]" />
```

## Week Numbers

Show ISO 8601 week numbers in a left column.

```html
<x-kore::datepicker label="Date" :show-week-numbers="true" />
```

## Manual Input

Allow typing a date directly. The input validates against `YYYY-MM-DD` format and constraints.

```html
<x-kore::datepicker label="Type or Pick"
    :manual-input="true" clearable />
```

## Requires Confirmation

Selection doesn't commit until the user clicks Apply. Cancel reverts.

```html
<x-kore::datepicker label="Confirm Date"
    :requires-confirmation="true" />
```

## 3-Level Navigation

Click the month/year title to navigate between views:

- **Date view** → click title → **Month view** (pick month)
- **Month view** → click title → **Year view** (pick year from decade)

## Keyboard Navigation

| Key | Action |
|-----|--------|
| `ArrowLeft/Right` | Move focus ±1 day |
| `ArrowUp/Down` | Move focus ±7 days |
| `Enter` / `Space` | Select focused date |
| `Escape` | Close dropdown |
| `PageUp` | Previous month |
| `PageDown` | Next month |
| `Shift+PageUp` | Previous year |
| `Shift+PageDown` | Next year |

## Dropdown Positioning

Same pattern as Select — teleported to `<body>` via `x-teleport`, `position: fixed`, repositions on scroll/resize, flips above when insufficient space below. Unlike Select, the dropdown has its own fixed width (~280px) and clamps to the viewport edge.

## Wire:model

- **Single mode**: syncs as `YYYY-MM-DD` string (or `YYYY-MM-DD HH:mm` with time)
- **Range mode**: syncs as array `['start', 'end']` via `$wire.$set()`
- **Multiple mode**: syncs as array `['date1', 'date2', ...]` via `$wire.$set()`

Range, multiple, and `with-time` modes use `wire:ignore` to prevent Livewire morphs from interfering with the Alpine dropdown state.
