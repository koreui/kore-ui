@props([
    'variant' => 'dropzone',
    'multiple' => false,
    'accept' => null,
    'maxSize' => null,
    'maxFiles' => null,
    'preview' => true,
    'deletable' => false,
    'deleteMethod' => null,
    'clearable' => false,
    'pasteable' => true,
    'static' => false,
    'staticFiles' => null,
    'disabled' => false,
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'showError' => true,
    'required' => false,
    'invalidSizeMessage' => null,
    'invalidTypeMessage' => null,
])

@php
    $deleteMethod = $deleteMethod ?? config('kore-ui.form.upload.delete_method', 'deleteUpload');
    $maxSize = $maxSize ?? config('kore-ui.form.upload.max_size');

    $name = $name ?? $attributes->whereStartsWith('wire:model')->first();

    $hasError = false;
    $errorMessage = null;

    if ($showError) {
        if ($error) {
            $hasError = true;
            $errorMessage = $error;
        } elseif ($name && isset($errors) && $errors->has($name)) {
            $hasError = true;
            $errorMessage = $errors->first($name);
        }
    }

    $fieldId = $attributes->get('id', $name ? 'kore-' . str_replace('.', '-', $name) : 'kore-' . uniqid());

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $jsConfig = json_encode(array_filter([
        'multiple' => $multiple ?: null,
        'accept' => $accept,
        'maxSize' => $maxSize,
        'maxFiles' => $maxFiles,
        'preview' => $preview ?: null,
        'deletable' => $deletable ?: null,
        'deleteMethod' => $deletable ? $deleteMethod : null,
        'pasteable' => $pasteable ?: null,
        'disabled' => $disabled ?: null,
        'static' => $static ?: null,
        'staticFiles' => $static && $staticFiles ? $staticFiles : null,
        'invalidSizeMessage' => $invalidSizeMessage,
        'invalidTypeMessage' => $invalidTypeMessage,
    ], fn ($v) => $v !== null));
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    <div
        x-data="KoreUpload({{ $jsConfig }})"
        x-on:paste.prevent="onPaste($event)"
        class="relative"
    >
        {{-- Hidden file input --}}
        <input
            type="file"
            x-ref="fileInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
            @if($accept) accept="{{ $accept }}" @endif
            @if($multiple) multiple @endif
            @if($disabled) disabled @endif
            x-on:change="onFileSelected($event)"
            class="sr-only"
            id="{{ $fieldId }}"
        />

        @if(!$static)
            @if($variant === 'button')
                {{-- Button variant --}}
                <button
                    type="button"
                    x-on:click="openFilePicker()"
                    class="inline-flex items-center gap-2 rounded-kore-md border border-kore-input
                           bg-kore-bg text-kore-fg text-sm py-2 px-3
                           hover:bg-kore-muted transition-colors cursor-pointer
                           disabled:opacity-50 disabled:cursor-not-allowed"
                    @if($disabled) disabled @endif
                >
                    <x-lucide-upload class="size-4" />
                    <span>Choose file</span>
                </button>
            @else
                {{-- Dropzone variant (default) --}}
                <div
                    x-on:click="openFilePicker()"
                    x-on:dragenter.prevent="onDragEnter($event)"
                    x-on:dragover.prevent="onDragOver($event)"
                    x-on:dragleave="onDragLeave($event)"
                    x-on:drop.prevent="onDrop($event)"
                    x-bind:class="{ 'border-kore-primary bg-kore-primary/5': dragging }"
                    class="border-2 border-dashed rounded-kore-lg p-6 text-center cursor-pointer
                           hover:border-kore-primary/50 hover:bg-kore-primary/5 transition-colors duration-150
                           {{ $hasError ? 'border-kore-destructive' : 'border-kore-input' }}
                           {{ $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                    role="button"
                    tabindex="0"
                    x-on:keydown.enter.prevent="openFilePicker()"
                    x-on:keydown.space.prevent="openFilePicker()"
                    x-bind:aria-busy="uploading"
                >
                    <x-lucide-upload-cloud class="size-8 mx-auto text-kore-muted-fg mb-2" />
                    <p class="text-sm text-kore-fg font-medium">
                        Drag & drop or <span class="text-kore-primary underline">browse</span>
                    </p>
                    <template x-if="constraintsText">
                        <p x-text="constraintsText" class="text-xs text-kore-muted-fg mt-1"></p>
                    </template>
                </div>
            @endif
        @endif

        {{-- File list --}}
        <template x-if="hasFiles">
            <ul class="mt-3 space-y-2" role="list">
                <template x-for="file in files" :key="file.id">
                    <li class="flex items-center gap-3 rounded-kore-md border border-kore-border bg-kore-surface p-3">
                        {{-- File icon / preview --}}
                        <template x-if="file.previewUrl">
                            <img x-bind:src="file.previewUrl" class="size-10 rounded-kore-sm object-cover shrink-0" alt="" />
                        </template>
                        <template x-if="!file.previewUrl">
                            <div class="flex items-center justify-center size-10 rounded-kore-sm bg-kore-muted shrink-0">
                                <x-lucide-file class="size-5 text-kore-muted-fg" />
                            </div>
                        </template>

                        <div class="flex-1 min-w-0">
                            <p x-text="file.name" class="text-sm text-kore-fg truncate"></p>
                            <p x-text="_formatSize(file.size)" class="text-xs text-kore-muted-fg"></p>

                            {{-- Progress bar --}}
                            <div x-show="file.status === 'uploading'" x-cloak class="mt-1.5">
                                <div class="h-1.5 rounded-full bg-kore-muted overflow-hidden">
                                    <div class="h-full rounded-full bg-kore-primary transition-all duration-300"
                                         x-bind:style="'width: ' + file.progress + '%'"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Status indicators --}}
                        <template x-if="file.status === 'complete' || file.status === 'static' || file.status === 'ready'">
                            <x-lucide-check-circle-2 class="size-5 text-kore-success shrink-0" />
                        </template>
                        <template x-if="file.status === 'error'">
                            <x-lucide-alert-circle class="size-5 text-kore-destructive shrink-0" />
                        </template>

                        {{-- Delete button --}}
                        @if($deletable)
                            <button type="button" x-on:click="removeFile(file.id)"
                                class="text-kore-muted-fg hover:text-kore-destructive transition-colors shrink-0">
                                <x-lucide-trash-2 class="size-4" />
                            </button>
                        @endif
                    </li>
                </template>
            </ul>
        </template>

        {{-- Validation errors --}}
        <template x-if="validationErrors.length > 0">
            <ul class="mt-2 space-y-1" role="alert" aria-live="polite">
                <template x-for="(err, ei) in validationErrors" :key="ei">
                    <li class="flex items-center gap-2 text-sm text-kore-destructive">
                        <x-lucide-alert-triangle class="size-4 shrink-0" />
                        <span x-text="err.message" class="flex-1"></span>
                        <button type="button" x-on:click="removeValidationError(ei)"
                            class="text-kore-muted-fg hover:text-kore-fg transition-colors shrink-0">
                            <x-lucide-x class="size-3.5" />
                        </button>
                    </li>
                </template>
            </ul>
        </template>

        {{-- Clear all button --}}
        @if($clearable)
            <template x-if="hasFiles && !uploading">
                <button type="button" x-on:click="clear()"
                    class="mt-2 text-sm text-kore-muted-fg hover:text-kore-fg transition-colors inline-flex items-center gap-1">
                    <x-lucide-x class="size-3.5" />
                    Clear all
                </button>
            </template>
        @endif
    </div>
</x-kore::field>
