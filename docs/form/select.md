# Select

The most feature-rich component: native/custom, searchable, multi-select with chips, grouped, async-ready, keyboard navigation.

## Basic Usage

```html
{{-- Custom select (default) --}}
<x-kore::select wire:model="country" label="Country"
    :options="$countries" option-label="label" option-value="value"
    placeholder="Select a country" />

{{-- Native HTML select --}}
<x-kore::select wire:model="status" label="Status"
    :options="['active' => 'Active', 'inactive' => 'Inactive']"
    native />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `hint` | string | null | Help text |
| `name` | string | null | Name for error detection |
| `error` | string | null | Manual error |
| `size` | string | `'md'` | Size: `sm`, `md`, `lg` |
| `options` | array | `[]` | Options array |
| `optionLabel` | string | `'label'` | Key for option display text |
| `optionValue` | string | `'value'` | Key for option value |
| `optionDescription` | string | null | Key for secondary text |
| `optionImage` | string | null | Key for option image URL |
| `placeholder` | string | null | Placeholder text |
| `searchable` | bool | false | Enable search/filter |
| `clearable` | bool | false | Show clear button |
| `multiple` | bool | false | Multi-select with chips |
| `max` | int | null | Max selections (multi only) |
| `native` | bool | false | Use native `<select>` element |
| `grouped` | bool | false | Grouped options (native only) |
| `async` | string | null | URL for remote search |
| `debounce` | int | 300 | Debounce for async search (ms) |
| `minSearch` | int | 2 | Min chars before async fires |
| `disabled` | bool | false | Disabled state |
| `required` | bool | false | Required indicator |
| `showError` | bool | true | Auto-detect errors |

## Options Format

```php
// Simple key => value array
:options="['mx' => 'Mexico', 'us' => 'USA']"

// Array of objects
:options="[
    ['value' => 'mx', 'label' => 'Mexico'],
    ['value' => 'us', 'label' => 'USA'],
]"

// Eloquent collection
:options="$users" option-label="name" option-value="id"

// With description and image
:options="$users" option-label="name" option-value="id"
    option-description="email" option-image="avatar_url"
```

## Searchable

```html
<x-kore::select wire:model="user" label="User"
    :options="$users" option-label="name" option-value="id"
    searchable clearable />
```

Client-side filtering by option label. For server-side search, use `async`.

## Multi-select

Selected values shown as chips in the trigger. Remove by clicking the X on each chip.

```html
<x-kore::select wire:model="tags" label="Tags"
    :options="$tags" option-label="label" option-value="value"
    multiple :max="5" searchable />
```

Multi-select uses `wire:ignore` to prevent Livewire morphs from interfering with the dropdown state. Values sync via `$wire.$set()` in real-time. Server-to-client updates (e.g., `$this->reset()`) are handled via `$wire.$watch()`.

## Grouped (Native)

```html
<x-kore::select wire:model="city" label="City" grouped native
    :options="[
        'North' => ['mty' => 'Monterrey', 'chih' => 'Chihuahua'],
        'Center' => ['cdmx' => 'CDMX', 'gdl' => 'Guadalajara'],
    ]" />
```

## Keyboard Navigation

The custom select supports full keyboard navigation:

| Key | Action |
|-----|--------|
| `ArrowDown` | Highlight next option (opens dropdown if closed) |
| `ArrowUp` | Highlight previous option (opens dropdown if closed) |
| `Enter` | Select highlighted option |
| `Escape` | Close dropdown, return focus to trigger |
| `Tab` | Close dropdown |

## Dropdown Positioning

The dropdown is teleported to `<body>` via Alpine's `x-teleport` to escape `overflow: hidden` containers. It uses `position: fixed` and repositions on scroll/resize. Automatically flips above the trigger when there's not enough space below.

## Async (Remote Search)

```html
<x-kore::select wire:model="client" label="Client"
    async="/api/clients" option-label="name" option-value="id"
    searchable :debounce="300" :min-search="2" />
```

The endpoint receives `?search=term` and should return a JSON array of options. Requests are debounced and use `AbortController` to cancel previous in-flight requests.
