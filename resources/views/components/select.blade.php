@props([
    'name',
    'label' => null,
    'options' => [],
    'required' => false,
    'placeholder' => null,
    'help' => null,
])
@php($id = $attributes->get('id', $name))
@php($selected = old($name, $attributes->get('value', '')))

<div>
    @if($label)
        <label for="{{ $id }}" class="block font-mono text-[10px] text-haze uppercase tracking-wider mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-stamp-600" aria-label="обязательное поле">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if($required) required @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        @if($help && ! $errors->has($name)) aria-describedby="{{ $id }}-help" @endif
        {{ $attributes->except(['id', 'value'])->merge([
            'class' => 'block w-full bg-document border-2 border-edge px-3 py-2.5 text-steel
                        focus:border-blueprint-600 focus:outline-none transition'
                        . ($errors->has($name) ? ' border-stamp-600' : ''),
        ]) }}
    >
        @if($placeholder)
            <option value="" disabled @selected($selected === '')>{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected((string)$selected === (string)$value)>{{ $label }}</option>
        @endforeach
    </select>

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 font-mono text-[10px] uppercase tracking-wider text-stamp-700">⊘ {{ $message }}</p>
    @else
        @if($help)
            <p id="{{ $id }}-help" class="mt-1.5 font-mono text-[10px] text-haze uppercase tracking-wider">{{ $help }}</p>
        @endif
    @enderror
</div>
