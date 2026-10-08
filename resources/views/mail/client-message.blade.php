<x-mail::message>
{!! nl2br(e($body)) !!}

<x-mail::subcopy>
{{ setting('firm_name', config('app.name')) }} · {{ setting('firm_phone') }} · {{ setting('firm_email') }}
Enviado por {{ $sender->name }} ({{ $sender->email }}).
</x-mail::subcopy>
</x-mail::message>
