@component('mail::message')
# Bonjour {{ $user->name }},

Vous avez été sélectionné pour évaluer les candidats.

@component('mail::button', ['url' => $url])
Accéder à la page de l'évaluation
@endcomponent

Merci de votre collaboration.

@endcomponent
