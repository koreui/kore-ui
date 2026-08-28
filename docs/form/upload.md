# Upload

File upload with drag & drop, real-time progress via Livewire, client-side validation (type, size, count), image previews, clipboard paste, and a non-Livewire fallback. Two variants: dropzone (default) and button.

## Basic Usage

```html
{{-- With Livewire --}}
<x-kore::upload wire:model="avatar" label="Avatar" accept="image/*" :max-size="5" />

{{-- Without Livewire (standard file input) --}}
<x-kore::upload name="document" label="Document" accept=".pdf" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `hint` | string | null | Help text below (hidden when error shown) |
| `name` | string | null | Input name (also used for error detection) |
| `error` | string | null | Manual error message |
| `variant` | string | `'dropzone'` | Visual variant: `dropzone`, `button` |
| `multiple` | bool | false | Allow multiple file selection |
| `accept` | string | null | Accepted file types (e.g. `image/*,.pdf`) |
| `maxSize` | int | null | Max file size in MB per file |
| `maxFiles` | int | null | Max number of files (multiple mode) |
| `preview` | bool | true | Show image thumbnails for image files |
| `deletable` | bool | false | Show delete button on each file |
| `deleteMethod` | string | `'deleteUpload'` | Livewire method to call on delete |
| `clearable` | bool | false | Show "Clear all" button |
| `pasteable` | bool | true | Allow pasting files from clipboard |
| `static` | bool | false | Display pre-existing files (no upload zone) |
| `staticFiles` | array | null | Array of existing file objects for static mode |
| `disabled` | bool | false | Disabled state |
| `readonly` | bool | false | Oculta la zona de selección y el borrado; la lista se ve |
| `required` | bool | false | Required indicator |
| `showError` | bool | true | Auto-detect errors from `$errors` bag |
| `invalidSizeMessage` | string | null | Custom message for size validation errors |
| `invalidTypeMessage` | string | null | Custom message for type validation errors |
| `autoUpload` | bool | true | Auto-upload on file selection. When false, files are staged until `uploadPending()` |
| `retryable` | bool | false | Enable automatic retry on upload error |
| `maxRetries` | int | 3 | Maximum retry attempts per file |
| `retryDelay` | int | 2000 | Delay in ms between retries |
| `capture` | string | null | Camera capture mode: `'user'` (front) or `'environment'` (rear) |

## Configuration

Global defaults in `config/kore-ui.php`:

```php
'form' => [
    'upload' => [
        'max_size' => null,              // MB per file (null = no client-side limit)
        'delete_method' => 'deleteUpload',
        'auto_upload' => true,           // false = staging mode
        'retryable' => false,            // auto-retry on error
        'max_retries' => 3,
        'retry_delay' => 2000,           // ms
    ],
],
```

## Variants

### Dropzone (default)

A dashed-border area that accepts click, drag & drop, and keyboard activation. Shows an upload icon and "Drag & drop or browse" text.

```html
<x-kore::upload wire:model="file" label="Attachment" />
```

### Button

A compact button trigger. No drag & drop zone — just a button that opens the file picker.

```html
<x-kore::upload wire:model="file" label="Attachment" variant="button" />
```

## Multiple Files

```html
<x-kore::upload wire:model="photos" label="Photos"
    multiple :max-files="5" :max-size="3"
    accept="image/png,image/jpeg,.webp" />
```

Each upload batch syncs to Livewire individually. The Alpine `files` array is UI-only and persists across batches to show a unified file list.

## Client-side Validation

Files are validated **before** upload starts. Invalid files are rejected with visible error messages. Three checks:

- **Type**: Matches against the `accept` string (extensions like `.pdf`, MIME prefixes like `image/*`, or exact MIME types)
- **Size**: Compares against `maxSize` in MB
- **Count**: Rejects files when `maxFiles` limit is reached

```html
<x-kore::upload wire:model="docs" label="Documents"
    accept=".pdf,.docx" :max-size="10" :max-files="3" multiple />
```

Constraint text is auto-generated below the dropzone: "Max 10 MB per file. PDF, DOCX. Up to 3 files."

Duplicate files (same name + size + type) are silently ignored.

### Custom Validation Messages

Use `{name}`, `{size}`, and `{maxSize}` placeholders:

```html
<x-kore::upload wire:model="file" label="File" :max-size="5"
    invalid-size-message="{name} is too large ({size}). Limit: {maxSize}."
    invalid-type-message="{name} is not allowed." />
```

## Image Preview

Enabled by default (`preview`). Images (`image/*`) show a thumbnail in the file list. Non-images show a generic file icon.

Previews use `URL.createObjectURL()` and are revoked on destroy, clear, or remove to prevent memory leaks.

```html
{{-- Disable preview --}}
<x-kore::upload wire:model="file" label="File" :preview="false" />
```

## Clipboard Paste

Enabled by default (`pasteable`). Press `Ctrl+V` / `Cmd+V` while the upload component is focused to paste images from the clipboard.

```html
{{-- Disable paste --}}
<x-kore::upload wire:model="file" label="File" :pasteable="false" />
```

## Delete & Clear

### Per-file Delete

Show a trash icon on each file. When clicked, calls the Livewire method specified by `deleteMethod`.

```html
<x-kore::upload wire:model="file" label="File" deletable />

{{-- Custom delete method --}}
<x-kore::upload wire:model="file" label="File"
    deletable delete-method="removeAttachment" />
```

In your Livewire component:

```php
public function deleteUpload($data)
{
    // $data['name'] contains the file name
    // Handle deletion logic
}
```

### Clear All

Show a "Clear all" button below the file list.

```html
<x-kore::upload wire:model="files" label="Files"
    multiple clearable />
```

Clears the Alpine file list, revokes all preview URLs, resets the file input, and calls `$wire.$set()` to clear the Livewire property.

## Static Mode

Display already-uploaded files without an upload zone. Useful for edit forms showing existing attachments.

```html
<x-kore::upload label="Attachments" static
    :static-files="[
        ['name' => 'report.pdf', 'size' => 245760, 'type' => 'application/pdf'],
        ['name' => 'photo.jpg', 'size' => 1048576, 'type' => 'image/jpeg', 'url' => '/storage/photo.jpg'],
    ]"
    deletable />
```

Each static file object supports:

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `name` | string | yes | File name displayed in the list |
| `size` | int | yes | File size in bytes |
| `type` | string | no | MIME type (used for preview detection) |
| `url` | string | no | URL for image thumbnail preview |

## Auto Upload

By default, files are uploaded immediately when selected (`autoUpload=true`). When disabled, files are staged locally and displayed in the file list with a "pending" status. The user must click the **Upload** button to start the actual upload.

```html
<x-kore::upload wire:model="files" label="Files"
    :auto-upload="false" multiple deletable clearable />
```

**How it works**: When `autoUpload=false` and `wire:model` is present, two `<input type="file">` elements are rendered:
- A visible input (without `wire:model`) for file selection
- A hidden input (with `wire:model`) used only when `uploadPending()` is triggered

This prevents Livewire from auto-uploading on the `change` event.

## Retry

When `retryable` is enabled, failed uploads automatically retry up to `maxRetries` times with a `retryDelay` between attempts. During retry, the file shows a spinning loader with the retry count.

```html
<x-kore::upload wire:model="file" label="File"
    retryable :max-retries="3" :retry-delay="2000" />
```

If all retries are exhausted, the file shows an error status with a manual retry button. Clicking the retry button resets the retry counter and tries again.

## Upload Speed & ETA

During Livewire uploads, the component automatically calculates and displays:
- **Upload speed** (e.g., "1.2 MB/s", "450 KB/s")
- **Estimated time remaining** (e.g., "~12s", "~2m 30s")

Speed is smoothed using an Exponential Moving Average (alpha=0.3) to avoid jittery numbers. Both values appear below the progress bar and disappear when the upload completes. No props needed — this is enabled automatically.

## Camera Capture

On mobile devices, the `capture` prop opens the device camera directly instead of a file picker.

```html
{{-- Front camera (selfie) --}}
<x-kore::upload wire:model="selfie" label="Selfie"
    capture="user" accept="image/*" />

{{-- Rear camera (document scan) --}}
<x-kore::upload wire:model="scan" label="Scan"
    capture="environment" accept="image/*" />
```

The `capture` attribute is a standard HTML attribute. On desktop browsers it's ignored and the regular file picker opens.

## Progress Tracking

With Livewire, upload progress is tracked automatically via native Livewire events on the file input:

- `livewire-upload-start` — marks file as uploading
- `livewire-upload-progress` — updates progress bar (0-100%)
- `livewire-upload-finish` — marks file as complete with check icon
- `livewire-upload-error` — marks file as error with alert icon

No additional setup needed — `wire:model` on a file input is all Livewire requires.

## Without Livewire

When no `wire:model` is present, the component works as an enhanced `<input type="file">`:

- File picker, preview, and validation work identically
- Files get `status: 'ready'` instead of going through the upload lifecycle
- Use a standard `name` attribute for form submission

```html
<form action="/upload" method="POST" enctype="multipart/form-data">
    <x-kore::upload name="attachment" label="Attachment"
        accept="image/*" :max-size="2" />
    <button type="submit">Upload</button>
</form>
```

## Drag & Drop

The dropzone variant handles drag & drop with proper visual feedback:

- `dragenter` highlights the border (blue)
- `dragleave` reverts (uses a counter to handle child element events)
- `drop` assigns files to the hidden input via `DataTransfer` and dispatches a `change` event so Livewire intercepts the upload

## States

```html
<x-kore::upload label="Disabled" disabled />
<x-kore::upload label="With error" error="Please upload a file." />
<x-kore::upload label="Required" required />
<x-kore::upload label="With hint" hint="Max 5 MB, PNG or JPG" />
```

## Accessibility

- Dropzone has `role="button"` and `tabindex="0"`
- `Enter` and `Space` keys open the file picker
- File list has `role="list"`
- Validation errors use `role="alert"` and `aria-live="polite"`
- Dropzone indicates upload status via `aria-busy`

## Wire:model

The component places `wire:model` directly on the `<input type="file">`. Livewire 4 automatically intercepts `change` events on file inputs to initiate uploads. No hidden input or manual sync needed.

- **Single file**: `wire:model="avatar"` — property receives a `TemporaryUploadedFile`
- **Multiple files**: `wire:model="photos"` with `multiple` — property receives an array

Your Livewire component needs the `WithFileUploads` trait:

```php
use Livewire\WithFileUploads;

class MyForm extends Component
{
    use WithFileUploads;

    public $avatar;
    public $photos = [];
}
```
