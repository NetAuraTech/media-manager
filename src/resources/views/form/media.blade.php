@php
    $label ??= null;
    $name ??= '';
    $value ??= '';
    $errorLocation ??= null;
    $displayError ??= true;
    $help ??= null;
@endphp

<div class="form-media form-group m-bottom-6" style="align-self:stretch;">
    <label for="{{ $name }}">{{ $label }}</label>
    <input type="text" id="{{ $name }}" name="{{ $name }}"
           is="input-media"
           data-endpoint="/api/media"
           overwrite="overwrite"
           class="form-control"
           value="{{ $value }}"
           style="display: none;"
    >
    @if($displayError)
        @error($name, $errorLocation)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    @endif
    @if($help)
        <div class="clr-neutral-600 margin-block-start-2">
            {{ $help }}
        </div>
    @endif
</div>
