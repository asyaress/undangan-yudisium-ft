@php
    $invitationBoot = [
        'rsvpTutorialSteps' => $rsvpTutorialSteps ?? [],
        'showRsvpTutorialOnOpen' => $showRsvpGuide ?? false,
        'shouldAutoOpen' => $autoOpenInvitation ?? false,
        'logoCandidates' => [
            asset('Unmul.png'),
            asset('unmul.png'),
            asset('UNMUL.png'),
        ],
    ];
@endphp
<script type="application/json" id="invitation-boot">@json($invitationBoot)</script>
<script src="{{ asset('js/invitation.js') }}?v=1" defer></script>
