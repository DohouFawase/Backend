<x-mail::message>
# Réinitialisation de Mot de Passe Demandée

Vous avez demandé la réinitialisation de votre mot de passe. 

@if (strlen($code) > 10)
{{-- Si la longueur de $code est supérieure à 10, nous supposons que c'est un token long pour un lien. --}}

<p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe. Si le bouton ne fonctionne pas, copiez et collez le lien ci-dessous dans votre navigateur.</p>

{{-- 
    ATTENTION : Tu dois remplacer 'VOTRE_URL_FRONTEND_RESET' par l'URL de ta page de réinitialisation 
    sur ton application frontend (par exemple : https://monapp.com/reset-password?token=).
--}}
<x-mail::button :url="config('app.frontend_url') . '/reset-password?token=' . $code">
Réinitialiser Mon Mot de Passe
</x-mail::button>

<p><small>{{ config('app.frontend_url') . '/reset-password?token=' . $code }}</small></p>

@else
{{-- Sinon, nous supposons que c'est l'OTP (code numérique) que l'utilisateur doit saisir directement. --}}

<p>Veuillez utiliser le code de vérification à usage unique (OTP) ci-dessous pour valider la réinitialisation dans votre application :</p>

# {{ $code }}

<p>Ce code est **valide pour 10 minutes**.</p>
@endif

<p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail en toute sécurité.</p>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>