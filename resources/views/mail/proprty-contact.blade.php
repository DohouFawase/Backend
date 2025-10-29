<x-mail::message>
# 🔔 Nouveau Contact pour Votre Annonce !

Un visiteur intéressé vient de vous envoyer un message concernant l'annonce : **{{ $propertyTitle }}**.

---

## Informations sur l'Annonce

| Détail | Valeur |
| :------------- | :------------- |
| **Type de Bien** | {{ $propertyType }} |
| **Ville** | {{ $propertyCity }} |
| **Prix Estimé** | {{ $propertyPrice }} |
| **Lien de l'Annonce** | [Voir l'annonce]({{ $adUrl }}) |

---

## Détails du Contact

| Champ | Valeur |
| :------------- | :------------- |
| **Nom du Visiteur** | {{ $visitorName }} |
| **Email** | {{ $visitorEmail }} |
| **Téléphone** | {{ $visitorPhone }} |

---

## Message du Visiteur

<x-mail::panel>
{{ $message }}
</x-mail::panel>

---

Pour répondre au message ou marquer ce contact comme traité, accédez directement à votre tableau de bord.

<x-mail::button :url="$dashboardUrl">
Voir ce Contact dans le Dashboard
</x-mail::button>

<x-mail::subcopy>
Vous recevez cet email car votre annonce est publiée sur {{ config('app.name') }}.
</x-mail::subcopy>

</x-mail::message>