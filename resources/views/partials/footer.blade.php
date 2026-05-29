@php
    use App\Models\Setting;
    $settings ??= Setting::current();
    $phoneTel = $settings->phone ? preg_replace('/[^0-9+]/', '', $settings->phone) : null;
@endphp

<footer class="bg-slate-900 text-slate-300 mt-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

            <div>
                <h3 class="text-white font-semibold mb-4">{{ $settings->site_name }}</h3>
                @if($settings->site_tagline)
                    <p class="text-sm leading-relaxed">{{ $settings->site_tagline }}</p>
                @endif
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4">Контакты</h3>
                <ul class="space-y-2 text-sm">
                    @if($settings->phone)
                        <li>
                            <a href="tel:{{ $phoneTel }}" class="hover:text-white">{{ $settings->phone }}</a>
                        </li>
                    @endif
                    @if($settings->public_email)
                        <li>
                            <a href="mailto:{{ $settings->public_email }}" class="hover:text-white">
                                {{ $settings->public_email }}
                            </a>
                        </li>
                    @endif
                    @if($settings->address || $settings->city)
                        <li>
                            @if($settings->address)<div>{{ $settings->address }}</div>@endif
                            @if($settings->postal_code || $settings->city)
                                <div>{{ trim(($settings->postal_code ?? '').' '.($settings->city ?? '')) }}</div>
                            @endif
                        </li>
                    @endif
                    @php($scheduleLines = $settings->workingHoursLines())
                    @if(! empty($scheduleLines))
                        <li class="space-y-0.5">
                            @foreach($scheduleLines as $line)
                                <div>{{ $line }}</div>
                            @endforeach
                        </li>
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4">Каталог</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ url('/catalog') }}" class="hover:text-white">Все категории</a></li>
                    <li><a href="{{ url('/blog') }}" class="hover:text-white">Статьи</a></li>
                    <li><a href="{{ url('/gosts') }}" class="hover:text-white">ГОСТы и серии</a></li>
                    <li><a href="{{ url('/payment') }}" class="hover:text-white">Оплата и доставка</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4">Компания</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ url('/about') }}" class="hover:text-white">О компании</a></li>
                    <li><a href="{{ url('/contacts') }}" class="hover:text-white">Контакты</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800 mt-12 pt-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 text-xs text-slate-500">
            <p>© {{ date('Y') }} {{ $settings->site_name }}. Все права защищены.</p>
            @if($settings->company_bin)
                <p>БИН {{ $settings->company_bin }}</p>
            @endif
        </div>
    </div>
</footer>
