<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Mail\ContactFormMail;
use App\Models\ContactSubmission;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * /contacts is a hybrid — it's a content Page (admin-editable) AND it
 * carries the lead form. Wrapping it in a dedicated controller keeps
 * the form-handling code out of the generic PageController and lets
 * us pre-fill product context from the catalog «Запросить цену» CTA.
 */
final class ContactController extends Controller
{
    public function show(Request $request): View
    {
        $page = Page::query()->where('slug', 'contacts')->first();
        $product = $request->filled('product')
            ? Product::query()->published()->find((int) $request->query('product'))
            : null;

        return view('contacts', [
            'page' => $page,
            'product' => $product,
            'settings' => Setting::current(),
        ]);
    }

    public function store(ContactFormRequest $request): RedirectResponse
    {
        $submission = ContactSubmission::create([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'message' => $request->validated('message'),
            'product_id' => $request->validated('product_id'),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        // Notification email is best-effort — failure here must not block
        // the lead from being persisted (we already have the submission in
        // the DB and the admin sees it in Filament). Logged for follow-up.
        try {
            Mail::to(Setting::current()->email_recipient ?? config('triad.inquiry_email'))
                ->send(new ContactFormMail($submission));
        } catch (\Throwable $e) {
            Log::warning('Contact form email failed', [
                'submission_id' => $submission->id,
                'exception' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('contacts.show')
            ->with('contact.sent', 'Заявка отправлена. Мы свяжемся с вами в течение рабочего дня.');
    }
}
