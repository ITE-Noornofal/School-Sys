@component('mail::message')
# مرحباً {{ $userName }}،

لقد طلبت إعادة تعيين كلمة المرور. استخدم الكود التالي:

@component('mail::panel')
# {{ $code }}
@endcomponent

**الكود صالح لمدة 15 دقيقة فقط.**

إذا لم تطلب هذا، يمكنك تجاهل هذه الرسالة.

شكراً،<br>
{{ config('app.name') }}
@endcomponent
