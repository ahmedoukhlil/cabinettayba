@extends('layouts.public')

@section('title', 'حجز موعد')
@section('lang', 'ar')
@section('dir', 'rtl')
@section('header_subtitle', 'حجز موعد عبر الإنترنت')
@section('professional_label', 'فضاء العيادة')
@section('privacy_text', 'تُستخدم معلوماتكم فقط لمعالجة طلب الموعد.')

@section('content')
<div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-start" style="font-family: Tahoma, Arial, sans-serif">
    <section class="text-right">
        <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800">بسيط وآمن</span>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">احجز موعدك بسهولة</h1>
        <p class="mt-4 text-lg leading-8 text-slate-600">
            أدخل اسمك ورقم هاتفك، ثم اختر الفترة التي تناسبك. ستحدد العيادة أول موعد متاح وتتواصل معك للتأكيد.
        </p>

        <div class="mt-8 space-y-4 rounded-2xl border border-blue-100 bg-blue-50 p-5">
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">١</span>
                <p class="text-sm text-slate-700">أدخل الاسم ورقم الهاتف.</p>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">٢</span>
                <p class="text-sm text-slate-700">اختر الفترة الصباحية أو المسائية.</p>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">٣</span>
                <p class="text-sm text-slate-700">ستتواصل معك العيادة لتأكيد الموعد.</p>
            </div>
        </div>

        @if($cabinet && ($cabinet->TelephonePublic || $cabinet->AdresseFr1))
            <div class="mt-6 text-sm text-slate-600">
                @if($cabinet->TelephonePublic)<p><strong>الهاتف:</strong> {{ $cabinet->TelephonePublic }}</p>@endif
                @if($cabinet->AdresseFr1)<p class="mt-1"><strong>العنوان:</strong> {{ $cabinet->AdresseFr1 }}</p>@endif
            </div>
        @endif
    </section>

    <section class="rounded-2xl bg-white p-5 text-right shadow-xl ring-1 ring-slate-200 sm:p-8" aria-labelledby="booking-form-title">
        <h2 id="booking-form-title" class="text-xl font-bold text-slate-900">طلب موعد</h2>
        <p class="mt-1 text-sm text-slate-500">جميع الحقول مطلوبة.</p>

        @if($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert" aria-live="assertive">
                <p class="font-semibold">يرجى تصحيح المعلومات التالية:</p>
                <ul class="mt-2 list-disc pr-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($medecins->isEmpty())
            <div class="mt-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                الحجز عبر الإنترنت غير متاح مؤقتاً. يرجى الاتصال بالعيادة.
            </div>
        @else
            <form method="POST" action="{{ route('public.appointment.store') }}" class="mt-6 space-y-6" id="booking-form">
                @csrf
                <div class="pointer-events-none absolute h-px w-px overflow-hidden opacity-0" style="clip: rect(0, 0, 0, 0)" aria-hidden="true">
                    <label for="website">اترك هذا الحقل فارغاً</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="nom" class="block text-sm font-semibold text-slate-700">الاسم الكامل <span class="text-red-600">*</span></label>
                    <input id="nom" name="nom" value="{{ old('nom') }}" required autocomplete="name" dir="rtl"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200"
                           placeholder="اكتب الاسم الكامل">
                </div>

                <div>
                    <label for="telephone" class="block text-sm font-semibold text-slate-700">رقم الهاتف <span class="text-red-600">*</span></label>
                    <input id="telephone" name="telephone" type="tel" value="{{ old('telephone') }}" required autocomplete="tel" inputmode="tel" dir="ltr"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-4 py-3 text-right focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200"
                           placeholder="مثال: 22 33 44 55">
                </div>

                <fieldset>
                    <legend class="text-sm font-semibold text-slate-700">اختر الفترة المناسبة <span class="text-red-600">*</span></legend>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <label class="group relative cursor-pointer rounded-xl border-2 border-slate-200 p-4 transition hover:border-blue-400 hover:bg-blue-50">
                            <input type="radio" name="periode" value="morning" required @checked(old('periode') === 'morning') class="peer sr-only">
                            <span class="absolute inset-0 rounded-xl ring-2 ring-transparent peer-checked:border-blue-700 peer-checked:ring-blue-700"></span>
                            <span class="relative flex items-center gap-3">
                                <span class="text-2xl" aria-hidden="true">☀️</span>
                                <span><strong class="block text-slate-900">الفترة الصباحية</strong><small class="text-slate-500">من الصباح حتى الظهر</small></span>
                            </span>
                        </label>
                        <label class="group relative cursor-pointer rounded-xl border-2 border-slate-200 p-4 transition hover:border-blue-400 hover:bg-blue-50">
                            <input type="radio" name="periode" value="evening" required @checked(old('periode') === 'evening') class="peer sr-only">
                            <span class="absolute inset-0 rounded-xl ring-2 ring-transparent peer-checked:ring-blue-700"></span>
                            <span class="relative flex items-center gap-3">
                                <span class="text-2xl" aria-hidden="true">🌙</span>
                                <span><strong class="block text-slate-900">الفترة المسائية</strong><small class="text-slate-500">من بعد الظهر حتى المساء</small></span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <p class="rounded-lg bg-slate-50 p-3 text-xs leading-5 text-slate-600">
                    بإرسال الطلب، توافق على استخدام معلوماتك من طرف العيادة للاتصال بك وتأكيد الموعد.
                </p>

                <button type="submit" id="submit-button" class="inline-flex w-full items-center justify-center rounded-lg bg-[#1e3a8a] px-5 py-3 font-semibold text-white shadow-sm hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                    إرسال طلب الموعد
                </button>
            </form>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('booking-form')?.addEventListener('submit', () => {
    const submit = document.getElementById('submit-button');
    submit.disabled = true;
    submit.textContent = 'جارٍ إرسال الطلب…';
});
</script>
@endpush
