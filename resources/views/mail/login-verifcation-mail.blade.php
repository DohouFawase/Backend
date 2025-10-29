git reset<x-mail::message>
# Vérification de votre compte

Bonjour,

Merci de vous être inscrit ! Pour activer votre compte, veuillez utiliser le code de vérification à usage unique (OTP) ci-dessous.

<div style="text-align: center; margin: 25px 0; padding: 15px; background-color: #f4f4f4; border-radius: 8px;">
   <h1 style="color: #1a1a1a; font-size: 28px; margin: 0;">{{ $code }}</h1>
</div>

**Attention : Ce code n'est valide que pour les 10 prochaines minutes et ne peut être utilisé qu'une seule fois.**

Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet e-mail.

<x-mail::button :url="config('app.url') . '/verify-otp'">
Vérifier mon compte
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>