<?php

declare(strict_types=1);

namespace App\Domain\Mobile\Services;

use App\Domain\Mobile\Exceptions\BiometricBlockedException;
use App\Domain\Mobile\Models\BiometricChallenge;
use App\Domain\Mobile\Models\BiometricFailure;
use App\Domain\Mobile\Models\MobileDevice;
use App\Domain\Mobile\Models\MobileDeviceSession;
use App\Traits\HasApiScopes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Service for WebAuthn/Passkey authentication.
 *
 * Follows the same security patterns as BiometricAuthenticationService:
 * challenge/response flow, rate limiting, IP validation, session creation.
 */
class PasskeyAuthenticationService
{
    use HasApiScopes;

    private const SESSION_DURATION_MINUTES = 30;

    private const TRUSTED_SESSION_DURATION_MINUTES = 120;

    /**
     * Generate a WebAuthn challenge for passkey authentication.
     */
    public function generateChallenge(MobileDevice $device, ?string $ipAddress = null): BiometricChallenge
    {
        // Invalidate any existing pending challenges
        BiometricChallenge::where('mobile_device_id', $device->id)
            ->pending()
            ->update(['status' => BiometricChallenge::STATUS_EXPIRED]);

        return BiometricChallenge::createForDevice($device, $ipAddress);
    }

    /**
     * Verify a WebAuthn assertion and create an authenticated session.
     *
     * @param  string  $credentialId  The credential ID from the authenticator (base64url)
     * @param  string  $authenticatorData  The authenticator data (base64url)
     * @param  string  $clientDataJSON  The client data JSON (base64url)
     * @param  string  $signature  The signature from the authenticator (base64url)
     * @return array{access_token: string, refresh_token: string, expires_at: \Carbon\Carbon, session_id: string}|null
     *
     * @throws BiometricBlockedException If the device is temporarily blocked
     */
    public function verifyAndAuthenticate(
        MobileDevice $device,
        string $challenge,
        string $credentialId,
        string $authenticatorData,
        string $clientDataJSON,
        string $signature,
        ?string $ipAddress = null,
    ): ?array {
        // SECURITY CHECK 1: Is device blocked?
        if ($device->is_blocked) {
            Log::warning('Passkey auth: device is blocked', [
                'device_id' => $device->id,
                'reason'    => $device->blocked_reason,
            ]);

            return null;
        }

        // SECURITY CHECK 2: Is biometric/passkey temporarily blocked due to failures?
        if ($device->isBiometricBlocked()) {
            /** @var \Carbon\Carbon $blockedUntil */
            $blockedUntil = $device->biometric_blocked_until;

            throw new BiometricBlockedException($blockedUntil);
        }

        // SECURITY CHECK 3: Rate limit check
        $maxFailures = (int) config('mobile.security.max_biometric_failures', 3);
        $recentFailures = BiometricFailure::countRecentForDevice($device->id, 10);

        if ($recentFailures >= $maxFailures) {
            $this->blockDevicePasskey($device);
            $device->refresh();

            /** @var \Carbon\Carbon $blockedUntil */
            $blockedUntil = $device->biometric_blocked_until;

            throw new BiometricBlockedException($blockedUntil);
        }

        // Check if device has passkey enabled and credentials stored
        if (! $device->passkey_enabled || $device->passkey_credential_id === null || $device->passkey_public_key === null) {
            Log::warning('Passkey auth: passkey not enabled or no credentials', [
                'device_id'       => $device->id,
                'passkey_enabled' => $device->passkey_enabled,
            ]);

            return null;
        }

        // Verify credential ID matches
        if ($credentialId !== $device->passkey_credential_id) {
            Log::warning('Passkey auth: credential ID mismatch', [
                'device_id' => $device->id,
            ]);
            $this->recordFailure($device, 'passkey_credential_mismatch', $ipAddress);

            return null;
        }

        // Find the pending challenge
        $biometricChallenge = BiometricChallenge::where('mobile_device_id', $device->id)
            ->where('challenge', $challenge)
            ->pending()
            ->first();

        if (! $biometricChallenge) {
            Log::warning('Passkey auth: challenge not found or expired', [
                'device_id' => $device->id,
            ]);
            $this->recordFailure($device, 'passkey_challenge_not_found', $ipAddress);

            return null;
        }

        // SECURITY CHECK 4: Validate client data contains the correct challenge
        if (! $this->validateClientData($clientDataJSON, $challenge)) {
            $biometricChallenge->markAsFailed();
            $this->recordFailure($device, 'passkey_client_data_invalid', $ipAddress);

            return null;
        }

        // Verify the WebAuthn signature
        /** @var string $publicKey */
        $publicKey = $device->passkey_public_key;
        if (! $this->verifyWebAuthnSignature($authenticatorData, $clientDataJSON, $signature, $publicKey)) {
            $biometricChallenge->markAsFailed();
            $this->recordFailure($device, 'passkey_signature_invalid', $ipAddress);

            Log::warning('Passkey signature verification failed', [
                'device_id'    => $device->id,
                'challenge_id' => $biometricChallenge->id,
            ]);

            return null;
        }

        // Create session on success
        try {
            return DB::transaction(function () use ($device, $biometricChallenge, $ipAddress) {
                $biometricChallenge->markAsVerified();
                $device->resetBiometricFailures();

                $sessionDuration = $device->is_trusted
                    ? self::TRUSTED_SESSION_DURATION_MINUTES
                    : self::SESSION_DURATION_MINUTES;

                $session = MobileDeviceSession::create([
                    'mobile_device_id'     => $device->id,
                    'user_id'              => $device->user_id,
                    'session_token'        => MobileDeviceSession::generateToken(),
                    'ip_address'           => $ipAddress,
                    'last_activity_at'     => now(),
                    'expires_at'           => now()->addMinutes($sessionDuration),
                    'is_biometric_session' => true,
                ]);

                $user = $device->user;
                if (! $user) {
                    throw new RuntimeException('User not found for device');
                }
                $tokenPair = $this->createTokenPair($user, 'mobile-passkey');

                $device->update(['last_active_at' => now()]);

                Log::info('Passkey authentication successful', [
                    'user_id'    => $device->user_id,
                    'device_id'  => $device->device_id,
                    'session_id' => $session->id,
                ]);

                return [
                    'access_token'  => $tokenPair['access_token'],
                    'refresh_token' => $tokenPair['refresh_token'],
                    'expires_at'    => $session->expires_at,
                    'session_id'    => $session->id,
                ];
            });
        } catch (BiometricBlockedException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Passkey session creation failed', [
                'device_id' => $device->id,
                'error'     => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Register a passkey credential for a device.
     *
     * @return array{credential_id: string, registered_at: string}
     */
    public function registerPasskey(MobileDevice $device, string $credentialId, string $publicKey): array
    {
        $device->update([
            'passkey_enabled'       => true,
            'passkey_credential_id' => $credentialId,
            'passkey_public_key'    => $publicKey,
            'passkey_enabled_at'    => now(),
        ]);

        Log::info('Passkey registered for device', [
            'device_id' => $device->device_id,
            'user_id'   => $device->user_id,
        ]);

        return [
            'credential_id' => $credentialId,
            'registered_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate client data JSON contains the expected challenge.
     */
    private function validateClientData(string $clientDataJSON, string $challenge): bool
    {
        $decoded = base64_decode($clientDataJSON, true);
        if ($decoded === false) {
            return false;
        }

        $clientData = json_decode($decoded, true);
        if (! is_array($clientData)) {
            return false;
        }

        // WebAuthn spec: type must be "webauthn.get" for assertion
        if (($clientData['type'] ?? '') !== 'webauthn.get') {
            return false;
        }

        // Challenge in clientDataJSON must match the server-issued challenge
        $clientChallenge = $clientData['challenge'] ?? '';

        return hash_equals($challenge, $clientChallenge);
    }

    /**
     * Verify WebAuthn assertion signature.
     *
     * Verifies that: signature = sign(authenticatorData + SHA256(clientDataJSON), privateKey)
     */
    private function verifyWebAuthnSignature(
        string $authenticatorData,
        string $clientDataJSON,
        string $signature,
        string $publicKey,
    ): bool {
        $authData = base64_decode($authenticatorData, true);
        $clientData = base64_decode($clientDataJSON, true);
        $sig = base64_decode($signature, true);

        if ($authData === false || $clientData === false || $sig === false) {
            return false;
        }

        // WebAuthn: signed data = authenticatorData || SHA256(clientDataJSON)
        $clientDataHash = hash('sha256', $clientData, true);
        $signedData = $authData . $clientDataHash;

        // Verify with the stored public key (PEM format expected)
        $pubKeyResource = openssl_pkey_get_public($publicKey);
        if ($pubKeyResource === false) {
            Log::warning('Passkey: invalid public key format');

            return false;
        }

        $result = openssl_verify($signedData, $sig, $pubKeyResource, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    private function recordFailure(MobileDevice $device, string $reason, ?string $ipAddress): void
    {
        BiometricFailure::create([
            'mobile_device_id' => $device->id,
            'failure_reason'   => $reason,
            'ip_address'       => $ipAddress,
        ]);

        $device->incrementBiometricFailures();
    }

    private function blockDevicePasskey(MobileDevice $device): void
    {
        $blockMinutes = (int) config('mobile.security.biometric_block_minutes', 15);
        $device->blockBiometric($blockMinutes);

        Log::warning('Passkey blocked for device due to failures', [
            'device_id'     => $device->id,
            'block_minutes' => $blockMinutes,
        ]);
    }
}
