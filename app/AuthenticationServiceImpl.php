<?php

namespace App;

use App\Services\UserService;
use App\Services\UserLdapService;
use Exception;
use Illuminate\Support\Facades\Cache;

readonly class AuthenticationServiceImpl implements \App\Services\AuthenticationService
{
    public function __construct(
        private UserService $userService,
        private UserLdapService $userLdapService
    ) {}

    /**
     * Étape 1: Authentification LDAP
     */
    public function login(string $cuid, string $password): array
    {
        try {
            // 1. Vérifier les credentials LDAP
            $ldapUser = $this->userLdapService->authenticate($cuid, $password);

            // 2. Stocker les données LDAP dans le cache (10 minutes)
            Cache::put("ldap_user_{$cuid}", $ldapUser, now()->addMinutes(10));

            // 3. Générer et envoyer l'OTP au numéro de téléphone
            $this->userLdapService->generateOtp($cuid);

            // 4. Préparer la réponse
            $response = [
                'status' => 'otp_sent',
                'cuid' => $cuid,
                'message' => 'Un code de vérification a été envoyé à votre numéro de téléphone.',
                'has_phone' => !empty($ldapUser['phone']),
                'has_email' => !empty($ldapUser['email']),
                'phone_masked' => $this->maskPhoneForDisplay($ldapUser['phone'] ?? ''),
            ];

            // 5. Journaliser la tentative de connexion
            Log::info('Login initiated', [
                'cuid' => $cuid,
                'status' => 'otp_sent',
                'phone' => $this->maskPhoneNumber($ldapUser['phone'] ?? '')
            ]);

            return $response;

        } catch (Exception $e) {
            // Journaliser l'échec
            Log::warning('Login failed', [
                'cuid' => $cuid,
                'error' => $e->getMessage()
            ]);

            // Messages d'erreur utilisateur-friendly
            $userMessage = match(true) {
                str_contains($e->getMessage(), 'numéro de téléphone') => 'Aucun numéro de téléphone valide trouvé pour votre compte.',
                str_contains($e->getMessage(), 'Identifiants incorrects') => 'Identifiants incorrects.',
                str_contains($e->getMessage(), 'compte bloqué') => 'Votre compte est temporairement bloqué.',
                default => 'Échec de l\'authentification. Veuillez réessayer.'
            };

            throw new Exception($userMessage);
        }
    }

    /**
     * Étape 2: Vérification OTP
     */
    public function verifyOtp(string $cuid, string $otp): array
    {
        try {
            // 1. Vérifier l'OTP via l'API avec le numéro de téléphone
            if (!$this->userLdapService->verifyOtp($cuid, $otp)) {
                // Incrémenter le compteur de tentatives échouées
                $failedAttempts = Cache::get("otp_failed_{$cuid}", 0) + 1;
                Cache::put("otp_failed_{$cuid}", $failedAttempts, now()->addMinutes(5));

                Log::warning('OTP verification failed', [
                    'cuid' => $cuid,
                    'attempt' => $failedAttempts
                ]);

                if ($failedAttempts >= 3) {
                    throw new Exception("Trop de tentatives échouées. Veuillez réessayer plus tard.");
                }

                throw new Exception("Code OTP incorrect. Il vous reste " . (3 - $failedAttempts) . " tentative(s).");
            }

            // 2. Récupérer les données LDAP depuis le cache
            $ldapUser = Cache::get("ldap_user_{$cuid}");

            if (!$ldapUser) {
                throw new Exception("Session expirée. Veuillez vous reconnecter.");
            }

            // 3. Créer ou mettre à jour l'utilisateur local
            $user = $this->userService->findOrCreateFromLdap($cuid, $ldapUser);

            // 4. Créer le token d'authentification
            $token = $user->createToken('auth-token', ['*'], now()->addDays(7))->plainTextToken;

            // 5. Nettoyer les données temporaires
            Cache::forget("ldap_user_{$cuid}");
            Cache::forget("otp_failed_{$cuid}");

            // 6. Journaliser la connexion réussie
            Log::info('User authenticated successfully', [
                'user_id' => $user->id,
                'cuid' => $cuid,
                'phone' => $this->maskPhoneNumber($ldapUser['phone'] ?? '')
            ]);

            return [
                'status' => 'authenticated',
                'user' => [
                    'id' => $user->id,
                    'cuid' => $user->cuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $ldapUser['phone'] ?? null,
                    'department' => $ldapUser['department'] ?? null,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 604800, // 7 jours en secondes
                'message' => 'Connexion réussie'
            ];

        } catch (Exception $e) {
            Log::error('OTP verification exception', [
                'cuid' => $cuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Déconnexion
     */
    public function logout(): void
    {
        try {
            $user = auth()->user();
            if ($user) {
                // Supprimer le token courant
                $user->currentAccessToken()->delete();

                // Nettoyer le cache
                Cache::forget("ldap_user_{$user->cuid}");

                Log::info('User logged out', ['user_id' => $user->id]);
            }
        } catch (Exception $e) {
            Log::error('Logout exception', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            throw new Exception("Erreur lors de la déconnexion.");
        }
    }

    /**
     * Masquer le numéro pour l'affichage
     */
    private function maskPhoneForDisplay(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return '***' . substr($phone, -1);
        }

        // Afficher seulement les 4 derniers chiffres
        $visible = substr($phone, -4);
        return '*** **** ' . $visible;
    }

    /**
     * Masquer partiellement le numéro de téléphone
     */
    private function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return '***' . substr($phone, -1);
        }

        $firstPart = substr($phone, 0, 3);
        $lastPart = substr($phone, -2);
        $masked = str_repeat('*', strlen($phone) - 5);

        return $firstPart . $masked . $lastPart;
    }

    // Ajouter dans l'interface AuthenticationService
public function resendOtp(string $cuid): void;

// Implémenter dans AuthenticationServiceImpl
public function resendOtp(string $cuid): void
{
    try {
        // Vérifier le délai minimum entre les envois (60 secondes)
        $lastSent = Cache::get("otp_last_sent_{$cuid}");

        if ($lastSent && now()->diffInSeconds($lastSent) < 60) {
            $remaining = 60 - now()->diffInSeconds($lastSent);
            throw new Exception("Veuillez attendre {$remaining} secondes avant de redemander un code.");
        }

        // Envoyer l'OTP
        $this->userLdapService->generateOtp($cuid);

        // Enregistrer l'heure d'envoi
        Cache::put("otp_last_sent_{$cuid}", now(), now()->addMinutes(2));

        Log::info('OTP resent', ['cuid' => $cuid]);

    } catch (Exception $e) {
        Log::error('OTP resend failed', [
            'cuid' => $cuid,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
}
