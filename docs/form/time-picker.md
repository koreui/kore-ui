# Time Picker

Standalone time selection with spinner controls, 12/24 hour format, minute step, and full `wire:model` support.

## Basic Usage

```html
<x-kore::time-picker wire:model="time" label="Time"
    placeholder="Select time" clearable />
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
| `timeFormat` | string | `'24'` | Time format: `'24'` or `'12'` (AM/PM) |
| `minuteStep` | int | 1 | Minute increment step |

## 12-hour Format

```html
<x-kore::time-picker wire:model="time" label="Time"
    time-format="12" />
```

Shows an AM/PM toggle button next to the minute spinner.

## Minute Step

```html
<x-kore::time-picker wire:model="time" label="Time"
    :minute-step="5" />
```

Increments/decrements minutes by the specified step.

## Wire:model

The value always syncs as `HH:mm` in 24-hour format, regardless of the display format. For example, 2:30 PM syncs as `14:30`.

## Keyboard Navigation

| Key | Action |
|-----|--------|
| `Enter` / `Space` / `ArrowDown` | Open dropdown |
| `Escape` | Close dropdown |

## Spinner Controls

- Click the up/down chevrons to increment/decrement
- Hold (mousedown/touchstart) for continuous auto-increment (400ms delay, then 75ms interval)

## Dropdown Positioning

Teleported to `<body>` via `x-teleport`, `position: fixed`, repositions on scroll/resize, flips above when insufficient space below.
