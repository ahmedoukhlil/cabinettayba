@extends('layouts.public')

@section('title', 'حجز موعد')
@section('lang', 'ar')
@section('dir', 'rtl')
@section('header_subtitle', 'حجز موعد')
@section('professional_label', 'فضاء العيادة')
@section('privacy_text', 'تُستخدم معلوماتكم فقط لمعالجة طلب الموعد.')

@section('content')
<div class="mx-auto max-w-xl">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="booking-form-title">
        <div class="border-b border-slate-100 pb-5 text-right">
            <h1 id="booking-form-title" class="text-2xl font-semibold text-slate-900">طلب موعد</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">أدخل معلوماتك واختر الفترة المناسبة. ستتصل بك العيادة لتأكيد الموعد.</p>
        </div>

        @if($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert" aria-live="assertive">
                <p class="font-medium">يرجى مراجعة المعلومات التالية:</p>
                <ul class="mt-2 list-disc pr-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($medecins->isEmpty())
            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                الحجز عبر الإنترنت غير متاح حالياً. يرجى الاتصال بالعيادة.
            </div>
        @else
            <form method="POST" action="{{ route('public.appointment.store') }}" class="mt-6 space-y-5" id="booking-form">
                @csrf
                <div class="pointer-events-none absolute h-px w-px overflow-hidden opacity-0" style="clip: rect(0, 0, 0, 0)" aria-hidden="true">
                    <label for="website">اترك هذا الحقل فارغاً</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="nom" class="block text-sm font-medium text-slate-700">الاسم الكامل <span class="text-red-600">*</span></label>
                    <input id="nom" name="nom" value="{{ old('nom') }}" required autocomplete="name" dir="rtl"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-4 py-3 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100"
                           placeholder="الاسم الكامل">
                </div>

                <div>
                    <label for="telephone" class="block text-sm font-medium text-slate-700">رقم الهاتف <span class="text-red-600">*</span></label>
                    <input id="telephone" name="telephone" type="tel" value="{{ old('telephone') }}" required autocomplete="tel" inputmode="tel" dir="ltr"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-4 py-3 text-left text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100"
                           style="direction:ltr; text-align:left; unicode-bidi:plaintext"
                           placeholder="22 33 44 55">
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-slate-700">الفترة المناسبة <span class="text-red-600">*</span></legend>
                    <div class="mt-2 divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-300">
                        <label class="flex cursor-pointer items-center gap-3 bg-white px-4 py-4 hover:bg-slate-50">
                            <input type="radio" name="periode" value="morning" required @checked(old('periode') === 'morning')
                                   class="h-4 w-4 border-slate-300 text-blue-800 focus:ring-blue-700">
                            <span class="flex-1">
                                <strong class="block text-sm font-medium text-slate-900">الفترة الصباحية</strong>
                                <span class="mt-0.5 block text-xs text-slate-500" dir="rtl">ابتداءً من الساعة <span dir="ltr">09:00</span></span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 bg-white px-4 py-4 hover:bg-slate-50">
                            <input type="radio" name="periode" value="evening" required @checked(old('periode') === 'evening')
                                   class="h-4 w-4 border-slate-300 text-blue-800 focus:ring-blue-700">
                            <span class="flex-1">
                                <strong class="block text-sm font-medium text-slate-900">الفترة المسائية</strong>
                                <span class="mt-0.5 block text-xs text-slate-500" dir="rtl">ابتداءً من الساعة <span dir="ltr">17:00</span></span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <button type="submit" id="submit-button" class="inline-flex w-full items-center justify-center rounded-lg bg-[#1e3a8a] px-5 py-3 text-base font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                    إرسال الطلب
                </button>

                <p class="text-center text-xs leading-5 text-slate-500">بإرسال الطلب، تسمح للعيادة باستخدام هذه المعلومات للاتصال بك بخصوص الموعد.</p>
            </form>
        @endif
    </section>

    @if($cabinet && ($cabinet->TelephonePublic || $cabinet->AdresseFr1))
        <div class="mt-5 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-slate-600">
            @if($cabinet->TelephonePublic)<p><span class="font-medium">الهاتف:</span> <span dir="ltr">{{ $cabinet->TelephonePublic }}</span></p>@endif
            @if($cabinet->AdresseFr1)<p><span class="font-medium">العنوان:</span> {{ $cabinet->AdresseFr1 }}</p>@endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('booking-form')?.addEventListener('submit', () => {
    const submit = document.getElementById('submit-button');
    submit.disabled = true;
    submit.textContent = 'جارٍ الإرسال…';
});
</script>
@endpush
