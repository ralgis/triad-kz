@php
    use App\Models\Setting;
    $settings ??= Setting::current();
    $phoneTel = $settings->phone ? preg_replace('/[^0-9+]/', '', $settings->phone) : null;
    $wa = $settings->whatsappNumbers()[0] ?? null;
    $tg = $settings->telegramUrl();
    $scheduleLines = $settings->workingHoursLines();
@endphp

<footer class="bg-steel text-document mt-16 border-t-2 border-edge">
    {{-- Tech strip — like the bottom band of a tech document --}}
    <div class="border-b border-steel-soft">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">
                ━━━━━━━ КОНТАКТЫ И РЕКВИЗИТЫ
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">

            {{-- Column 1: brand + tagline --}}
            <div>
                <h3 class="font-display uppercase tracking-wider text-lg mb-4">{{ $settings->site_name }}</h3>
                @if($settings->site_tagline)
                    <p class="text-sm leading-relaxed text-haze">{{ $settings->site_tagline }}</p>
                @endif
            </div>

            {{-- Column 2: contacts --}}
            <div>
                <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-3">Контакты</p>
                <ul class="space-y-2 text-sm">
                    @if($settings->phone)
                        <li>
                            <a href="tel:{{ $phoneTel }}" class="font-mono spec-value text-document hover:text-blueprint-200 transition">
                                {{ $settings->phone }}
                            </a>
                        </li>
                    @endif
                    @if($wa)
                        <li>
                            <a href="{{ $wa['wa_url'] }}" class="inline-flex items-center gap-2 font-mono text-document hover:text-blueprint-200 transition">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                                WhatsApp
                            </a>
                        </li>
                    @endif
                    @if($tg)
                        <li>
                            <a href="{{ $tg }}" class="inline-flex items-center gap-2 font-mono text-document hover:text-blueprint-200 transition">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                                Telegram
                            </a>
                        </li>
                    @endif
                    @if($settings->public_email)
                        <li>
                            <a href="mailto:{{ $settings->public_email }}" class="font-mono text-document hover:text-blueprint-200 transition">
                                {{ $settings->public_email }}
                            </a>
                        </li>
                    @endif
                </ul>

                @if($settings->address || $settings->city)
                    <p class="font-mono text-[10px] text-haze uppercase tracking-wider mt-5 mb-2">Адрес</p>
                    <address class="not-italic text-sm leading-relaxed text-haze">
                        @if($settings->address){{ $settings->address }}<br>@endif
                        {{ trim(($settings->postal_code ?? '').' '.($settings->city ?? '')) }}
                    </address>
                @endif

                @if(! empty($scheduleLines))
                    <p class="font-mono text-[10px] text-haze uppercase tracking-wider mt-5 mb-2">Часы работы</p>
                    <ul class="font-mono text-xs space-y-0.5 text-haze">
                        @foreach($scheduleLines as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Column 3: catalog --}}
            <div>
                <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-3">Каталог</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ url('/catalog') }}" class="font-display uppercase tracking-wider text-document hover:text-blueprint-200 transition">Все категории</a></li>
                    <li><a href="{{ url('/blog') }}" class="font-display uppercase tracking-wider text-document hover:text-blueprint-200 transition">Статьи</a></li>
                    <li><a href="{{ url('/gosts') }}" class="font-display uppercase tracking-wider text-document hover:text-blueprint-200 transition">ГОСТы и серии</a></li>
                    <li><a href="{{ url('/payment') }}" class="font-display uppercase tracking-wider text-document hover:text-blueprint-200 transition">Оплата и доставка</a></li>
                </ul>
            </div>

            {{-- Column 4: company --}}
            <div>
                <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-3">Компания</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ url('/about') }}" class="font-display uppercase tracking-wider text-document hover:text-blueprint-200 transition">О компании</a></li>
                    <li><a href="{{ url('/contacts') }}" class="font-display uppercase tracking-wider text-document hover:text-blueprint-200 transition">Контакты</a></li>
                </ul>

                @if($settings->company_legal_name || $settings->company_bin)
                    <p class="font-mono text-[10px] text-haze uppercase tracking-wider mt-5 mb-2">Реквизиты</p>
                    <dl class="space-y-1 font-mono text-xs">
                        @if($settings->company_legal_name)
                            <div><dt class="text-haze inline">Юр. лицо: </dt><dd class="inline text-document">{{ $settings->company_legal_name }}</dd></div>
                        @endif
                        @if($settings->company_bin)
                            <div><dt class="text-haze inline">БИН: </dt><dd class="inline text-document spec-value">{{ $settings->company_bin }}</dd></div>
                        @endif
                    </dl>
                @endif
            </div>
        </div>

        <div class="border-t border-steel-soft mt-12 pt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 font-mono text-[10px] sm:text-xs uppercase tracking-wider text-haze">
            <p>© {{ date('Y') }} {{ $settings->site_name }} · ВСЕ ПРАВА ЗАЩИЩЕНЫ</p>
            <p>{{ $settings->city ? mb_strtoupper((string) $settings->city) : 'ALMATY' }} · KZ · ЖБИ</p>
        </div>
    </div>
</footer>
