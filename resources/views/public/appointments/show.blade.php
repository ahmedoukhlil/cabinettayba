@extends('layouts.public')

@section('title', 'متابعة الطلب')
@section('lang', 'ar')
@section('dir', 'rtl')
@section('robots', 'noindex,nofollow')
@section('header_subtitle', 'حجز موعد عبر الإنترنت')
@section('professional_label', 'فضاء العيادة')
@section('privacy_text', 'تُستخدم معلوماتكم فقط لمعالجة طلب الموعد.')
@if(!$queueState['expired'])
    @section('pwa_subscribe_url', route('public.appointment.notifications.store', [
        'reference' => $appointmentRequest->reference,
        'token' => request()->route('token'),
    ]))
    @section('pwa_subscription_id', 'appointment:' . $appointmentRequest->getKey())
@endif

@section('content')
<div class="mx-auto max-w-2xl rounded-2xl bg-white p-6 text-center shadow-xl ring-1 ring-slate-200 sm:p-10" style="font-family: Tahoma, Arial, sans-serif">
    @php
        $states = [
            'pending' => ['label' => 'في انتظار التأكيد', 'title' => 'تم تسجيل طلبك', 'class' => 'bg-amber-100 text-amber-900', 'text' => 'ستراجع العيادة طلبك وتتصل بك لتأكيد الموعد.'],
            'confirmed' => ['label' => 'تم تأكيد الموعد', 'title' => 'موعدك مؤكد', 'class' => 'bg-green-100 text-green-900', 'text' => 'تمت إضافة موعدك إلى برنامج العيادة.'],
            'arrived' => ['label' => 'تم تسجيل الوصول', 'title' => 'أنت في طابور العيادة', 'class' => 'bg-blue-100 text-blue-900', 'text' => 'تابع عدد المرضى أمامك، وسيتم إشعارك عند اقتراب دورك.'],
            'in_consultation' => ['label' => 'حان دورك', 'title' => 'الطبيب بانتظارك', 'class' => 'bg-green-100 text-green-900', 'text' => 'يرجى التوجه إلى غرفة الطبيب.'],
            'rejected' => ['label' => 'تعذر تأكيد الموعد', 'title' => 'تعذر تأكيد طلبك', 'class' => 'bg-red-100 text-red-900', 'text' => 'يمكنك إرسال طلب جديد أو الاتصال بالعيادة.'],
        ];
        $state = $states[$appointmentRequest->status] ?? $states['pending'];
        $requestedTime = substr($appointmentRequest->heure_souhaitee, 0, 5);
        $period = $requestedTime < config('appointments.evening_start_time') ? 'الفترة الصباحية' : 'الفترة المسائية';
        $trackingStatusUrl = route('public.appointment.status', [
            $appointmentRequest->reference,
            request()->route('token'),
        ]);
    @endphp

    @if($queueState['expired'])
        <span class="inline-flex rounded-full bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">انتهت صلاحية الرابط</span>
        <h1 class="mt-6 text-3xl font-bold text-slate-900">اطلب موعداً جديداً</h1>
        <p class="mt-3 text-slate-600">
            {{ match($queueState['closed_status']) {
                'cancelled' => 'تم إلغاء هذا الموعد.',
                'completed' => 'تم إنهاء هذا الموعد بنجاح.',
                'no_show' => 'تم تسجيل عدم الحضور لهذا الموعد.',
                default => 'انتهى وقت هذا الموعد ولم تعد تفاصيله متاحة عبر هذا الرابط.',
            } }}
        </p>
        <div class="mt-8 rounded-xl border border-blue-200 bg-blue-50 p-5 text-blue-950">
            للحجز أو المتابعة، يرجى إرسال طلب موعد جديد.
        </div>
        <a href="{{ route('public.appointment.create') }}" class="mt-6 inline-flex rounded-lg bg-[#1e3a8a] px-6 py-3 font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">طلب موعد جديد</a>
    @else
    <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $state['class'] }}">{{ $state['label'] }}</span>
    <h1 class="mt-6 text-3xl font-bold text-slate-900">{{ $state['title'] }}</h1>
    <p class="mt-3 text-slate-600">{{ $state['text'] }}</p>

    @if(in_array($appointmentRequest->status, ['confirmed', 'arrived', 'in_consultation'], true))
        <section class="mt-8 grid gap-4 sm:grid-cols-2" aria-label="معلومات الدور">
            <div class="rounded-2xl bg-[#1e3a8a] p-6 text-white">
                <p class="text-sm text-blue-100">رقم موعدك</p>
                <p id="appointment-number" class="mt-2 text-5xl font-black" dir="ltr">{{ $queueState['appointment_number'] }}</p>
            </div>
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-6 text-blue-950">
                <p class="text-sm font-semibold">عدد المرضى أمامك</p>
                <p id="people-ahead" class="mt-2 text-5xl font-black" dir="ltr">{{ $queueState['people_ahead'] }}</p>
                <p id="queue-message" class="mt-2 text-sm">{{ $queueState['people_ahead'] === 0 ? 'أنت التالي، يرجى الاستعداد.' : 'سيتم تحديث الطابور تلقائياً.' }}</p>
            </div>
        </section>
        <p class="mt-3 text-xs text-slate-500">الطابور تقديري حسب برنامج العيادة وقد يتغير عند الحالات المستعجلة.</p>
    @endif

    <dl class="mt-8 grid gap-4 rounded-xl bg-slate-50 p-5 text-right sm:grid-cols-2">
        <div><dt class="text-xs font-semibold text-slate-500">رقم الطلب</dt><dd class="mt-1 font-bold text-slate-900" dir="ltr">{{ $appointmentRequest->reference }}</dd></div>
        <div><dt class="text-xs font-semibold text-slate-500">الفترة المطلوبة</dt><dd class="mt-1 text-slate-900">{{ $period }}</dd></div>
        @if($appointmentRequest->status !== 'rejected')
            <div><dt class="text-xs font-semibold text-slate-500">تاريخ الموعد</dt><dd class="mt-1 text-slate-900">{{ $appointmentRequest->date_souhaitee->format('d/m/Y') }} — {{ $requestedTime }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">الطبيب</dt><dd class="mt-1 text-slate-900">د. {{ optional($appointmentRequest->medecin)->Nom }}</dd></div>
        @endif
    </dl>

    <p class="mt-6 text-sm text-slate-500">احتفظ بهذا الرابط الخاص لمتابعة الحجز. رقم الطلب وحده لا يسمح بالدخول إلى تفاصيلك.</p>
    <p class="mt-2 text-sm text-slate-500">فعّل الإشعارات لتصلك نتيجة التأكيد حتى عند إغلاق التطبيق.</p>
    @if($appointmentRequest->status === 'rejected')
        <a href="{{ route('public.appointment.create') }}" class="mt-6 inline-flex min-h-11 items-center rounded-lg bg-[#1e3a8a] px-5 py-3 font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">إرسال طلب جديد</a>
    @endif
    @endif
</div>
@endsection

@if(!$queueState['expired'])
@push('scripts')
<script>
(() => {
    const statusUrl = {{ Illuminate\Support\Js::from($trackingStatusUrl) }};
    let currentPeopleAhead = @json($queueState['people_ahead']);
    let currentStatus = @json($appointmentRequest->status);

    const refreshStatus = async () => {
        try {
            const response = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data.expired || data.cancelled || data.status !== currentStatus) {
                window.location.reload();
                return;
            }

            const number = document.getElementById('appointment-number');
            const ahead = document.getElementById('people-ahead');
            const message = document.getElementById('queue-message');
            if (number && data.appointmentNumber !== null) number.textContent = data.appointmentNumber;
            if (ahead && data.peopleAhead !== null) ahead.textContent = data.peopleAhead;
            if (message && data.peopleAhead !== null) {
                message.textContent = data.peopleAhead === 0
                    ? 'أنت التالي، يرجى الاستعداد.'
                    : 'سيتم تحديث الطابور تلقائياً.';
            }
            if (currentPeopleAhead !== null && data.peopleAhead < currentPeopleAhead && window.showToast) {
                window.showToast(data.peopleAhead === 0 ? 'أنت التالي في الطابور.' : `بقي أمامك ${data.peopleAhead}.`, 'info');
            }
            currentPeopleAhead = data.peopleAhead;
            currentStatus = data.status;
        } catch (_) {
            // La page conserve le dernier état connu si le réseau est momentanément indisponible.
        }
    };

    window.setInterval(refreshStatus, 60000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshStatus();
    });
})();
</script>
@endpush
@endif
