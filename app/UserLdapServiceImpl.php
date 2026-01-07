<?php

namespace App;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class UserLdapServiceImpl implements \App\Services\UserLdapService
{
    private string $ldapApiUrl;
    private string $otpApiUrl;
    private array $otpConfig;

    public function __construct()
    {
        $this->ldapApiUrl = config('ldap.ldap.api_url');
        $this->otpApiUrl = config('ldap.otp.api_url');
        $this->otpConfig = config('ldap.otp.config');
    }

    /**
     * Authentification LDAP
     */
    public function authenticate(string $cuid, string $password): array
    {
        try {
            // Formater la date
            $date = Carbon::now()->timezone('UTC')->format('Y-m-d H:i:s');

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<COMMANDE>
    <TYPE>AUTH_SVC</TYPE>
    <APPLINAME>Ubora</APPLINAME>
    <CUID>' . htmlspecialchars($cuid) . '</CUID>
    <PASSWORD>' . htmlspecialchars($password) . '</PASSWORD>
    <DATE>' . $date . '</DATE>
</COMMANDE>';

            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
            ])
            ->timeout(config('ldap.ldap.timeout', 10))
            ->retry(config('ldap.ldap.retry', 3))
            ->post($this->ldapApiUrl . config('ldap.ldap.endpoint', '/ldap'), $xml);

            if ($response->failed()) {
                Log::error('LDAP authentication failed', [
                    'cuid' => $cuid,
                    'status' => $response->status()
                ]);
                throw new Exception("Échec de connexion au serveur LDAP.");
            }

            // Parser la réponse et extraire le numéro de téléphone
            $userData = $this->parseLdapResponse($response->body());

            // Vérifier qu'on a un numéro de téléphone pour l'OTP
            if (empty($userData['phone'])) {
                throw new Exception("Aucun numéro de téléphone trouvé pour cet utilisateur.");
            }

            // Valider le format du numéro de téléphone
            $userData['phone'] = $this->formatPhoneNumber($userData['phone']);

            return $userData;

        } catch (Exception $e) {
            Log::error('LDAP authentication exception', [
                'cuid' => $cuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Génération OTP avec le numéro de téléphone de l'utilisateur
     */
    public function generateOtp(string $cuid): void
    {
        try {
            // Récupérer les données utilisateur depuis le cache
            $userData = cache()->get("ldap_user_{$cuid}");

            if (!$userData || empty($userData['phone'])) {
                throw new Exception("Données utilisateur non trouvées ou numéro de téléphone manquant.");
            }

            $phoneNumber = $userData['phone'];

            // Message en français avec placeholder pour le code OTP
            $message = $this->otpConfig['customerMessage'];

            $payload = [
                'reference' => $phoneNumber, // Numéro de téléphone comme référence
                'origin' => $this->otpConfig['origin'],
                'otpOveroutLine' => $this->otpConfig['otpOveroutLine'],
                'customerMessage' => $message,
                'senderName' => $this->otpConfig['senderName'],
                'ignoreOrangeNumber' => $this->otpConfig['ignoreOrangeNumber'],
            ];

            Log::info('OTP generation request', [
                'cuid' => $cuid,
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'origin' => $this->otpConfig['origin']
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(config('ldap.otp.timeout', 10))
            ->retry(config('ldap.otp.retry', 2))
            ->post($this->otpApiUrl . config('ldap.otp.endpoints.generate', '/generate'), $payload);

            $responseData = $response->json();

            Log::info('OTP generation response', [
                'status' => $response->status(),
                'success' => $responseData['success'] ?? false
            ]);

            if ($response->failed() || !($responseData['success'] ?? false)) {
                $errorMessage = $responseData['message'] ?? "Échec de l'envoi du code OTP.";

                // Journaliser les détails de l'erreur
                Log::error('OTP generation failed', [
                    'cuid' => $cuid,
                    'phone' => $this->maskPhoneNumber($phoneNumber),
                    'error' => $errorMessage,
                    'response' => $responseData
                ]);

                throw new Exception("Impossible d'envoyer le code OTP. Vérifiez votre numéro de téléphone.");
            }

            Log::info('OTP sent successfully', [
                'cuid' => $cuid,
                'phone' => $this->maskPhoneNumber($phoneNumber)
            ]);

        } catch (Exception $e) {
            Log::error('OTP generation exception', [
                'cuid' => $cuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception("Erreur lors de l'envoi du code OTP : " . $e->getMessage());
        }
    }

    /**
     * Vérification OTP avec le numéro de téléphone comme référence
     */
    public function verifyOtp(string $cuid, string $otp): bool
    {
        try {
            // Récupérer les données utilisateur depuis le cache
            $userData = cache()->get("ldap_user_{$cuid}");

            if (!$userData || empty($userData['phone'])) {
                throw new Exception("Session expirée. Veuillez vous reconnecter.");
            }

            $phoneNumber = $userData['phone'];

            $payload = [
                'reference' => $phoneNumber, // Numéro de téléphone comme référence
                'origin' => $this->otpConfig['origin'],
                'receivedOtp' => $otp,
            ];

            Log::info('OTP verification request', [
                'cuid' => $cuid,
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'otp_length' => strlen($otp)
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(config('ldap.otp.timeout', 10))
            ->post($this->otpApiUrl . config('ldap.otp.endpoints.verify', '/check'), $payload);

            $responseData = $response->json();

            Log::info('OTP verification response', [
                'status' => $response->status(),
                'verified' => $responseData['verified'] ?? false,
                'success' => $responseData['success'] ?? false
            ]);

            if ($response->failed()) {
                return false;
            }

            // Différentes façons dont l'API peut indiquer le succès
            if (isset($responseData['verified']) && $responseData['verified'] === true) {
                return true;
            }

            if (isset($responseData['success']) && $responseData['success'] === true) {
                return true;
            }

            if (isset($responseData['valid']) && $responseData['valid'] === true) {
                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('OTP verification exception', [
                'cuid' => $cuid,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Erreur lors de la vérification du code OTP.");
        }
    }

    /**
     * Formater le numéro de téléphone
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Si le numéro commence par 0, le convertir en format international (Congo)
        if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '243' . substr($phone, 1); // Format RDC
        }

        // Si le numéro a 9 chiffres (sans le 0), ajouter l'indicatif
        if (strlen($phone) === 9) {
            $phone = '243' . $phone;
        }

        return $phone;
    }

    /**
     * Masquer partiellement le numéro de téléphone pour les logs
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

    /**
     * Parser la réponse XML du LDAP
     */
    private function parseLdapResponse(string $xmlResponse): array
    {
        try {
            $xmlResponse = trim($xmlResponse);

            // Vérifier si c'est une erreur
            if (str_contains($xmlResponse, '<ERROR>') || str_contains($xmlResponse, '<error>')) {
                $error = $this->extractXmlValue($xmlResponse, ['ERROR', 'error', 'message', 'MESSAGE']);
                throw new Exception($error ?? "Authentification échouée.");
            }

            // Extraire les informations utilisateur
            $userData = [
                'cuid' => $this->extractXmlValue($xmlResponse, ['CUID', 'cuid', 'LOGIN', 'login']),
                'name' => $this->extractXmlValue($xmlResponse, ['NAME', 'name', 'NOM', 'nom', 'FULLNAME']),
                'email' => $this->extractXmlValue($xmlResponse, ['EMAIL', 'email', 'MAIL', 'mail']),
                'phone' => $this->extractXmlValue($xmlResponse, ['PHONE', 'phone', 'TELEPHONE', 'TELEPHONE', 'TEL', 'MOBILE', 'mobile']),
                'department' => $this->extractXmlValue($xmlResponse, ['DEPARTMENT', 'department', 'SERVICE', 'service']),
                'status' => $this->extractXmlValue($xmlResponse, ['STATUS', 'status']) ?? 'active',
            ];

            // Nettoyer et formater les données
            foreach ($userData as $key => &$value) {
                if (is_string($value)) {
                    $value = trim($value);

                    // Convertir les valeurs vides en null
                    if ($value === '') {
                        $value = null;
                    }
                }
            }

            Log::info('LDAP user data parsed', [
                'cuid' => $userData['cuid'],
                'name' => $userData['name'],
                'has_phone' => !empty($userData['phone']),
                'has_email' => !empty($userData['email'])
            ]);

            return $userData;

        } catch (Exception $e) {
            Log::error('Failed to parse LDAP response', [
                'error' => $e->getMessage(),
                'xml_preview' => substr($xmlResponse, 0, 300)
            ]);
            throw $e;
        }
    }

    /**
     * Extraire une valeur d'un XML
     */
    private function extractXmlValue(string $xml, array $tagNames): ?string
    {
        foreach ($tagNames as $tag) {
            $patterns = [
                "/<{$tag}>(.*?)<\/{$tag}>/si",
                "/<{$tag}[^>]*>(.*?)<\/{$tag}>/si",
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $xml, $matches)) {
                    return trim($matches[1] ?? '');
                }
            }

            // Essayer en majuscule
            $upperTag = strtoupper($tag);
            if ($upperTag !== $tag) {
                $patterns = [
                    "/<{$upperTag}>(.*?)<\/{$upperTag}>/si",
                    "/<{$upperTag}[^>]*>(.*?)<\/{$upperTag}>/si",
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $xml, $matches)) {
                        return trim($matches[1] ?? '');
                    }
                }
            }
        }

        return null;
    }
}
