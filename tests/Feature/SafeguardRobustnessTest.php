<?php

declare(strict_types=1);

it('handles rule execution errors gracefully', function () {
    // Ce test vérifie que les erreurs d'exécution des règles sont gérées correctement
    // En mode normal sans --fail-on-error, la commande devrait toujours retourner 0
    $this->artisan('safeguard:check')
        ->assertExitCode(0);
});

it('handles output visibility in programmatic contexts', function () {
    // Test que l'output est visible même dans des contextes programmatiques
    $this->artisan('safeguard:check --ci')
        ->expectsOutputToContain('[PASS]')
        ->assertExitCode(1); // CI mode retourne 1 s'il y a des erreurs
});
