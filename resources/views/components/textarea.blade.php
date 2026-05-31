@props([
    'name',
    'label' => null,
    'required' => false,
    'rows' => 4,
    'help' => null,
])
@php($id = $attributes->get('id', $name))

<div>
    @if($label)
        <label for="{{ $id }}" class="block font-mono text-[10px] text-haze uppercase tracking-wider mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-stamp-600" aria-label="обязательное поле">*</span>
            @endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if($required) required @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        @if($help && ! $errors->has($name)) aria-describedby="{{ $id }}-help" @endif
        {{ $attributes->except('id')->merge([
            'class' => 'block w-full bg-document border-2 border-edge px-3 py-2.5 text-steel
                        placeholder:text-haze focus:border-blueprint-600 focus:outline-none transition'
                        . ($errors->has($name) ? ' border-stamp-600' : ''),
        ]) }}
    >{{ old($name, $slot) }}</textarea>

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 font-mono text-[10px] uppercase tracking-wider text-stamp-700">⊘ {{ $message }}</p>
    @else
        @if($help)
            <p id="{{ $id }}-help" class="mt-1.5 font-mono text-[10px] text-haze uppercase tracking-wider">{{ $help }}</p>
        @endif
    @enderror
</div>
