@extends('layouts.public')

@section('title', 'متابعة الطلب')
@section('lang', 'ar')
@section('dir', 'rtl')
@section('robots', 'noindex,nofollow')
@section('header_subtitle', 'حجز موعد عبر الإنترنت')
@section('professional_label', 'فضاء العيادة')
@section('privacy_text', 'تُستخدم معلوماتكم فقط لمعالجة طلب الموعد.')
@section('pwa_subscribe_url', route('public.appointment.notifications.store', [
    'reference' => $appointmentRequest->reference,
    'token' => request()->route('token'),
]))
@section('pwa_subscription_id', 'appointment:' . $appointmentRequest->getKey())

@section('content')
<div class="mx-auto max-w-2xl rounded-2xl bg-white p-6 text-center shadow-xl ring-1 ring-slate-200 sm:p-10" style="font-family: Tahoma, Arial, sans-serif">
    @php
        $states = [
            'pending' => ['label' => 'في انتظار التأكيد', 'class' => 'bg-amber-100 text-amber-900', 'text' => 'ستراجع العيادة طلبك وتتصل بك لتأكيد الموعد.'],
            'confirmed' => ['label' => 'تم تأكيد الموعد', 'class' => 'bg-green-100 text-green-900', 'text' => 'تمت إضافة موعدك إلى برنامج العيادة.'],
            'rejected' => ['label' => 'تعذر تأكيد الموعد', 'class' => 'bg-red-100 text-red-900', 'text' => 'يمكنك إرسال طلب جديد أو الاتصال بالعيادة.'],
        ];
        $state = $states[$appointmentRequest->status] ?? $states['pending'];
        $requestedTime = substr($appointmentRequest->heure_souhaitee, 0, 5);
        $period = $requestedTime < config('appointments.evening_start_time') ? 'الفترة الصباحية' : 'الفترة المسائية';
    @endphp

    <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $state['class'] }}">{{ $state['label'] }}</span>
    <h1 class="mt-6 text-3xl font-bold text-slate-900">تم تسجيل طلبك</h1>
    <p class="mt-3 text-slate-600">{{ $state['text'] }}</p>

    <dl class="mt-8 grid gap-4 rounded-xl bg-slate-50 p-5 text-right sm:grid-cols-2">
        <div><dt class="text-xs font-semibold text-slate-500">رقم الطلب</dt><dd class="mt-1 font-bold text-slate-900" dir="ltr">{{ $appointmentRequest->reference }}</dd></div>
        <div><dt class="text-xs font-semibold text-slate-500">الفترة المطلوبة</dt><dd class="mt-1 text-slate-900">{{ $period }}</dd></div>
        @if($appointmentRequest->status === 'confirmed')
            <div><dt class="text-xs font-semibold text-slate-500">تاريخ الموعد</dt><dd class="mt-1 text-slate-900">{{ $appointmentRequest->date_souhaitee->format('d/m/Y') }} — {{ $requestedTime }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">الطبيب</dt><dd class="mt-1 text-slate-900">د. {{ optional($appointmentRequest->medecin)->Nom }}</dd></div>
        @endif
    </dl>

    <p class="mt-6 text-sm text-slate-500">احتفظ برقم الطلب لمتابعة الحجز.</p>
    <p class="mt-2 text-sm text-slate-500">فعّل الإشعارات لتصلك نتيجة التأكيد حتى عند إغلاق التطبيق.</p>
    <a href="{{ route('public.appointment.create') }}" class="mt-6 inline-flex rounded-lg bg-[#1e3a8a] px-5 py-3 font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">إرسال طلب جديد</a>
</div>
@endsection
