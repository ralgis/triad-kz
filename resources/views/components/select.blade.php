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
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-600" aria-label="обязательное поле">*</span>
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
            'class' => 'block w-full rounded border-slate-300 px-3 py-2 text-slate-900
                        focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none'
                        . ($errors->has($name) ? ' border-red-500' : ''),
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
        <p id="{{ $id }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @else
        @if($help)
            <p id="{{ $id }}-help" class="mt-1 text-sm text-slate-500">{{ $help }}</p>
        @endif
    @enderror
</div>
