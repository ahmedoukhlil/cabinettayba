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
            <p class="mt-2 text-sm leading-6 text-slate-600">تحقق من ملفك، ثم اختر الطبيب والتاريخ والساعة المناسبة.</p>
        </div>

        <ol class="mt-5 grid grid-cols-4 gap-1 text-center text-xs font-semibold text-slate-400 sm:gap-2" aria-label="مراحل حجز الموعد">
            <li data-booking-step="1" class="booking-step is-active"><span>1</span><small>الهاتف</small></li>
            <li data-booking-step="2" class="booking-step"><span>2</span><small>المريض</small></li>
            <li data-booking-step="3" class="booking-step"><span>3</span><small>الطبيب</small></li>
            <li data-booking-step="4" class="booking-step"><span>4</span><small>الموعد</small></li>
        </ol>

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
                    <label for="telephone" class="block text-sm font-medium text-slate-700">رقم الهاتف <span class="text-red-600">*</span></label>
                    <div class="mt-1.5 flex flex-col gap-2 sm:flex-row-reverse">
                        <input id="telephone" name="telephone" type="tel" value="{{ old('telephone') }}" required autocomplete="tel" inputmode="tel" dir="ltr"
                               class="block w-full rounded-lg border border-slate-300 px-4 py-3 text-left text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100"
                               style="direction:ltr; text-align:left; unicode-bidi:plaintext"
                               placeholder="22 33 44 55">
                        <button type="button" id="verify-patient-button" class="shrink-0 rounded-lg border border-blue-800 px-5 py-3 font-semibold text-blue-800 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                            التحقق من الملف
                        </button>
                    </div>
                    <p id="patient-lookup-status" class="mt-2 hidden rounded-lg p-3 text-sm" role="status" aria-live="polite"></p>
                </div>

                <div id="patient-name-section" class="hidden">
                    <label for="nom" class="block text-sm font-medium text-slate-700">الاسم الكامل <span class="text-red-600">*</span></label>
                    <input id="nom" name="nom" value="{{ old('nom') }}" autocomplete="name" dir="rtl"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-4 py-3 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100"
                           placeholder="الاسم الكامل">
                    <p id="patient-name-help" class="mt-1.5 text-xs text-slate-500"></p>
                </div>

                <div id="reservation-section" class="hidden space-y-5">
                    <div>
                        <label for="medecin_id" class="block text-sm font-medium text-slate-700">الطبيب <span class="text-red-600">*</span></label>
                        <select id="medecin_id" name="medecin_id" required
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">اختر الطبيب</option>
                            @foreach($medecins as $medecin)
                                <option value="{{ $medecin->idMedecin }}"
                                        dir="auto"
                                        @if((string) old('medecin_id') === (string) $medecin->idMedecin) selected @endif>
                                    د. {{ $medecin->Nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <fieldset>
                        <legend class="text-sm font-medium text-slate-700">الفترة المناسبة <span class="text-red-600">*</span></legend>
                        <div class="mt-2 divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-300">
                            <label class="flex cursor-pointer items-center gap-3 bg-white px-4 py-4 hover:bg-slate-50">
                                <input type="radio" name="periode" value="morning" required @checked(old('periode') === 'morning')
                                       class="h-4 w-4 border-slate-300 text-blue-800 focus:ring-blue-700">
                                <span class="flex-1">
                                    <strong class="block text-sm font-medium text-slate-900">الفترة الصباحية</strong>
                                    <span class="mt-0.5 block text-xs text-slate-500" dir="rtl">ابتداءً من الساعة <span dir="ltr">{{ substr($morningStartTime, 0, 5) }}</span></span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 bg-white px-4 py-4 hover:bg-slate-50">
                                <input type="radio" name="periode" value="evening" required @checked(old('periode') === 'evening')
                                       class="h-4 w-4 border-slate-300 text-blue-800 focus:ring-blue-700">
                                <span class="flex-1">
                                    <strong class="block text-sm font-medium text-slate-900">الفترة المسائية</strong>
                                    <span class="mt-0.5 block text-xs text-slate-500" dir="rtl">ابتداءً من الساعة <span dir="ltr">{{ substr($eveningStartTime, 0, 5) }}</span></span>
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <div>
                        <label for="date_souhaitee" class="block text-sm font-medium text-slate-700">تاريخ الموعد <span class="text-red-600">*</span></label>
                        <input id="date_souhaitee" name="date_souhaitee" type="date" value="{{ old('date_souhaitee') }}" required
                               min="{{ $minimumBookingDate }}" max="{{ $maximumBookingDate }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>

                    <fieldset id="slots-section" class="hidden">
                        <legend class="text-sm font-medium text-slate-700">الساعة المتاحة <span class="text-red-600">*</span></legend>
                        <div id="slots-status" class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-4 text-center text-sm text-slate-600" role="status" aria-live="polite">
                            اختر الطبيب والفترة والتاريخ لعرض الساعات المتاحة.
                        </div>
                        <div id="slots-list" class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4" dir="ltr"></div>
                    </fieldset>

                    <button type="submit" id="submit-button" disabled class="inline-flex w-full items-center justify-center rounded-lg px-5 py-3 text-base font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" style="background:{{ $cabinetBrand['primary_color'] }}">
                        إرسال الطلب
                    </button>

                    <p class="text-center text-xs leading-5 text-slate-500">بإرسال الطلب، تسمح للعيادة باستخدام هذه المعلومات للاتصال بك بخصوص الموعد.</p>
                </div>
            </form>
        @endif
    </section>

    @if($cabinetBrand['phone'] || $cabinetBrand['address'])
        <div class="mt-5 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-slate-600">
            @if($cabinetBrand['phone'])<p><span class="font-medium">الهاتف:</span> <span dir="ltr">{{ $cabinetBrand['phone'] }}</span></p>@endif
            @if($cabinetBrand['address'])<p><span class="font-medium">العنوان:</span> {{ $cabinetBrand['address'] }}</p>@endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('booking-form');
    if (!form) return;

    const telephone = document.getElementById('telephone');
    const verifyButton = document.getElementById('verify-patient-button');
    const status = document.getElementById('patient-lookup-status');
    const nameSection = document.getElementById('patient-name-section');
    const nameInput = document.getElementById('nom');
    const nameHelp = document.getElementById('patient-name-help');
    const reservationSection = document.getElementById('reservation-section');
    const submit = document.getElementById('submit-button');
    const doctor = document.getElementById('medecin_id');
    const periods = [...form.querySelectorAll('input[name="periode"]')];
    const bookingDate = document.getElementById('date_souhaitee');
    const slotsSection = document.getElementById('slots-section');
    const slotsStatus = document.getElementById('slots-status');
    const slotsList = document.getElementById('slots-list');
    const steps = [...document.querySelectorAll('[data-booking-step]')];
    let verifiedTelephone = null;
    let verificationTimer = null;
    let patientRequest = null;
    let slotsRequest = null;
    let preferredSlot = @json(old('heure_souhaitee'));

    const normalizedTelephone = () => telephone.value.replace(/\D/g, '');

    const setStep = current => steps.forEach(step => {
        const number = Number(step.dataset.bookingStep);
        step.classList.toggle('is-active', number === current);
        step.classList.toggle('is-complete', number < current);
    });

    const selectedPeriod = () => periods.find(input => input.checked)?.value || '';

    const resetSlots = (message = 'اختر الطبيب والفترة والتاريخ لعرض الساعات المتاحة.') => {
        slotsRequest?.abort();
        slotsRequest = null;
        slotsList.innerHTML = '';
        slotsStatus.textContent = message;
        slotsStatus.className = 'mt-2 rounded-lg border border-slate-200 bg-slate-50 p-4 text-center text-sm text-slate-600';
        slotsSection.classList.toggle('hidden', !doctor.value && !selectedPeriod() && !bookingDate.value);
        submit.disabled = true;
    };

    const resetVerification = () => {
        patientRequest?.abort();
        patientRequest = null;
        verifyButton.disabled = false;
        verifyButton.textContent = 'التحقق من الملف';
        verifiedTelephone = null;
        status.className = 'mt-2 hidden rounded-lg p-3 text-sm';
        status.textContent = '';
        nameSection.classList.add('hidden');
        reservationSection.classList.add('hidden');
        resetSlots();
        setStep(1);
        if (nameInput.readOnly) nameInput.value = '';
        nameInput.readOnly = false;
        nameInput.required = false;
        nameInput.classList.remove('bg-slate-100');
        nameHelp.textContent = '';
    };

    const loadSlots = async () => {
        const period = selectedPeriod();
        if (!doctor.value || !period || !bookingDate.value) {
            resetSlots();
            setStep(doctor.value ? 4 : 3);
            return;
        }

        slotsRequest?.abort();
        slotsRequest = new AbortController();
        slotsSection.classList.remove('hidden');
        slotsList.innerHTML = '';
        slotsStatus.textContent = 'جارٍ تحميل الساعات المتاحة…';
        slotsStatus.className = 'mt-2 rounded-lg border border-blue-100 bg-blue-50 p-4 text-center text-sm text-blue-800';
        submit.disabled = true;
        setStep(4);

        const query = new URLSearchParams({
            medecin_id: doctor.value,
            date: bookingDate.value,
            periode: period,
        });

        try {
            const response = await fetch(`${@json(route('public.appointment.availability'))}?${query}`, {
                headers: { 'Accept': 'application/json' },
                signal: slotsRequest.signal,
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'تعذر تحميل المواعيد.');

            if (!data.slots.length) {
                resetSlots('لا توجد ساعات متاحة في هذا التاريخ والفترة. اختر تاريخاً أو فترة أخرى.');
                slotsSection.classList.remove('hidden');
                slotsStatus.className = 'mt-2 rounded-lg border border-amber-200 bg-amber-50 p-4 text-center text-sm text-amber-900';
                return;
            }

            slotsStatus.classList.add('hidden');
            data.slots.forEach(slot => {
                const label = document.createElement('label');
                label.className = 'slot-option';
                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'heure_souhaitee';
                input.value = slot;
                input.required = true;
                input.className = 'sr-only';
                input.checked = preferredSlot && preferredSlot.substring(0, 5) === slot;
                input.addEventListener('change', () => {
                    slotsList.querySelectorAll('.slot-option').forEach(item => item.classList.remove('is-selected'));
                    label.classList.add('is-selected');
                    submit.disabled = false;
                });
                const text = document.createElement('span');
                text.textContent = slot;
                label.append(input, text);
                slotsList.appendChild(label);
                if (input.checked) {
                    label.classList.add('is-selected');
                    submit.disabled = false;
                }
            });
            preferredSlot = null;
        } catch (error) {
            if (error.name === 'AbortError') return;
            resetSlots(error.message || 'تعذر تحميل المواعيد الآن. يرجى المحاولة مرة أخرى.');
            slotsSection.classList.remove('hidden');
            slotsStatus.className = 'mt-2 rounded-lg border border-red-200 bg-red-50 p-4 text-center text-sm text-red-800';
        }
    };

    const showStatus = (message, type) => {
        status.textContent = message;
        status.className = type === 'success'
            ? 'mt-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800'
            : 'mt-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800';
    };

    const verifyPatient = async () => {
        window.clearTimeout(verificationTimer);
        verificationTimer = null;
        const phone = normalizedTelephone();
        if (phone.length < 8 || phone.length > 15) {
            showStatus('يرجى إدخال رقم هاتف صحيح يحتوي على 8 إلى 15 رقماً.', 'error');
            telephone.focus();
            return;
        }

        verifyButton.disabled = true;
        verifyButton.textContent = 'جارٍ التحقق…';
        patientRequest?.abort();
        const requestController = new AbortController();
        patientRequest = requestController;

        try {
            const response = await fetch(@json(route('public.appointment.patient.lookup')), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({ telephone: telephone.value }),
                signal: requestController.signal,
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'تعذر التحقق من رقم الهاتف.');
            }

            verifiedTelephone = phone;
            nameSection.classList.remove('hidden');
            reservationSection.classList.remove('hidden');
            setStep(2);

            if (data.found) {
                nameInput.value = data.name;
                nameInput.readOnly = true;
                nameInput.required = false;
                nameInput.classList.add('bg-slate-100');
                nameHelp.textContent = 'تم استرجاع الاسم من ملف المريض المسجل.';
                showStatus('تم العثور على ملفك. يمكنك متابعة طلب الموعد.', 'success');
                setStep(3);
            } else {
                nameInput.readOnly = false;
                nameInput.required = true;
                nameInput.classList.remove('bg-slate-100');
                nameHelp.textContent = 'لم يتم العثور على ملف. أدخل اسمك، وسيتم استكمال بقية البيانات عند حضورك إلى العيادة.';
                showStatus('لم يتم العثور على ملف بهذا الرقم. يرجى إدخال اسمك للمتابعة.', 'success');
                nameInput.focus();
            }

            if (doctor.value || selectedPeriod() || bookingDate.value) loadSlots();
        } catch (error) {
            if (error.name === 'AbortError') return;
            resetVerification();
            showStatus(error.message || 'تعذر التحقق الآن. يرجى المحاولة مرة أخرى.', 'error');
        } finally {
            if (patientRequest === requestController) {
                patientRequest = null;
                verifyButton.disabled = false;
                verifyButton.textContent = 'التحقق من الملف';
            }
        }
    };

    telephone.addEventListener('input', () => {
        window.clearTimeout(verificationTimer);
        resetVerification();
        const length = normalizedTelephone().length;
        if (length >= 8 && length <= 15) {
            verificationTimer = window.setTimeout(verifyPatient, 500);
        }
    });
    verifyButton.addEventListener('click', verifyPatient);
    nameInput.addEventListener('input', () => setStep(nameInput.value.trim().length >= 2 ? 3 : 2));
    doctor.addEventListener('change', () => {
        setStep(doctor.value ? 4 : 3);
        loadSlots();
    });
    periods.forEach(input => input.addEventListener('change', loadSlots));
    bookingDate.addEventListener('change', loadSlots);
    form.addEventListener('submit', (event) => {
        if (verifiedTelephone !== normalizedTelephone() || !form.querySelector('input[name="heure_souhaitee"]:checked')) {
            event.preventDefault();
            if (verifiedTelephone !== normalizedTelephone()) verifyPatient();
            else loadSlots();
            return;
        }

        submit.disabled = true;
        submit.textContent = 'جارٍ الإرسال…';
    });

    if (normalizedTelephone().length >= 8) {
        verifyPatient();
    }
})();
</script>
@endpush
