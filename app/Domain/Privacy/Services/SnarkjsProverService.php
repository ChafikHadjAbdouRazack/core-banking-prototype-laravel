<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Services;

use App\Domain\Privacy\Contracts\ZkProverInterface;
use App\Domain\Privacy\Enums\ProofType;
use App\Domain\Privacy\Exceptions\CircuitNotFoundException;
use App\Domain\Privacy\Exceptions\SnarkjsException;
use App\Domain\Privacy\ValueObjects\ZkProof;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Production ZK prover that wraps snarkjs CLI via Symfony Process.
 *
 * Maps ProofType to circuit name via config, then runs:
 *   snarkjs groth16 prove <circuit.zkey> <input.json> <proof.json> <public.json>
 *   snarkjs groth16 verify <verification_key.json> <public.json> <proof.json>
 */
class SnarkjsProverService implements ZkProverInterface
{
    private readonly string $snarkjsBinary;

    private readonly string $circuitDirectory;

    private readonly int $timeoutSeconds;

    /** @var array<string, string> */
    private readonly array $circuitMapping;

    public function __construct()
    {
        $this->snarkjsBinary = (string) config('privacy.zk.snarkjs_binary', 'snarkjs');
        $this->circuitDirectory = (string) config('privacy.zk.circuit_directory', storage_path('app/circuits'));
        $this->timeoutSeconds = (int) config('privacy.zk.snarkjs_timeout_seconds', 120);
        $this->circuitMapping = (array) config('privacy.zk.circuit_mapping', []);
    }

    public function generateProof(
        ProofType $type,
        array $privateInputs,
        array $publicInputs,
    ): ZkProof {
        $circuitName = $this->resolveCircuitName($type);
        $this->validateCircuitFiles($circuitName);

        Log::info('Starting snarkjs proof generation', [
            'proof_type' => $type->value,
            'circuit'    => $circuitName,
        ]);

        // Write input JSON to temp file
        $inputFile = tempnam(sys_get_temp_dir(), 'zk_input_');
        $proofFile = tempnam(sys_get_temp_dir(), 'zk_proof_');
        $publicFile = tempnam(sys_get_temp_dir(), 'zk_public_');

        try {
            $allInputs = array_merge($privateInputs, $publicInputs);
            file_put_contents($inputFile, json_encode($allInputs, JSON_THROW_ON_ERROR));

            $wasmPath = $this->getCircuitPath($circuitName, 'wasm');
            $zkeyPath = $this->getCircuitPath($circuitName, 'zkey');

            // Run snarkjs groth16 prove
            $process = new Process([
                $this->snarkjsBinary,
                'groth16', 'prove',
                $zkeyPath,
                $inputFile,
                $proofFile,
                $publicFile,
            ]);
            $process->setTimeout($this->timeoutSeconds);
            $process->run();

            if (! $process->isSuccessful()) {
                if ($process->isStarted() && ! $process->isTerminated()) {
                    throw SnarkjsException::processTimeout($circuitName, $this->timeoutSeconds);
                }
                throw SnarkjsException::processFailed($circuitName, $process->getErrorOutput());
            }

            // Read proof output
            $proofJson = file_get_contents($proofFile);
            if ($proofJson === false || $proofJson === '') {
                throw SnarkjsException::invalidOutput($circuitName, 'Empty proof output');
            }

            $proofData = base64_encode($proofJson);
            $createdAt = new DateTimeImmutable();
            $validityDays = (int) config('privacy.zk.proof_validity_days', 90);
            $expiresAt = $createdAt->modify("+{$validityDays} days");

            Log::info('snarkjs proof generation complete', [
                'proof_type' => $type->value,
                'circuit'    => $circuitName,
            ]);

            return new ZkProof(
                type: $type,
                proof: $proofData,
                publicInputs: $publicInputs,
                verifierAddress: $this->getVerifierAddress($type),
                createdAt: $createdAt,
                expiresAt: $expiresAt,
                metadata: [
                    'provider'        => $this->getProviderName(),
                    'circuit'         => $circuitName,
                    'circuit_version' => config("privacy.zk.circuit_versions.{$type->value}", '1.0.0'),
                ],
            );
        } finally {
            // Clean up temp files
            @unlink($inputFile);
            @unlink($proofFile);
            @unlink($publicFile);
        }
    }

    public function verifyProof(ZkProof $proof): bool
    {
        if ($proof->isExpired()) {
            return false;
        }

        $circuitName = $this->resolveCircuitName($proof->type);
        $vkeyPath = $this->getCircuitPath($circuitName, 'vkey.json');

        if (! file_exists($vkeyPath)) {
            Log::warning('Verification key not found, cannot verify', ['circuit' => $circuitName]);

            return false;
        }

        // Write proof and public inputs to temp files
        $proofFile = tempnam(sys_get_temp_dir(), 'zk_verify_proof_');
        $publicFile = tempnam(sys_get_temp_dir(), 'zk_verify_public_');

        try {
            $decodedProof = base64_decode($proof->proof, true);
            if ($decodedProof === false) {
                return false;
            }

            file_put_contents($proofFile, $decodedProof);
            file_put_contents($publicFile, json_encode($proof->publicInputs, JSON_THROW_ON_ERROR));

            // Run snarkjs groth16 verify
            $process = new Process([
                $this->snarkjsBinary,
                'groth16', 'verify',
                $vkeyPath,
                $publicFile,
                $proofFile,
            ]);
            $process->setTimeout($this->timeoutSeconds);
            $process->run();

            return $process->isSuccessful();
        } finally {
            @unlink($proofFile);
            @unlink($publicFile);
        }
    }

    public function getVerifierAddress(ProofType $type): string
    {
        return (string) config("privacy.zk.verifier_addresses.{$type->value}", '0x' . str_repeat('0', 40));
    }

    public function supportsProofType(ProofType $type): bool
    {
        return isset($this->circuitMapping[$type->value])
            || $type !== ProofType::CUSTOM;
    }

    public function getProviderName(): string
    {
        return 'snarkjs';
    }

    /**
     * Resolve the circuit name for a proof type.
     */
    private function resolveCircuitName(ProofType $type): string
    {
        $circuitName = $this->circuitMapping[$type->value] ?? null;

        if ($circuitName === null) {
            throw CircuitNotFoundException::unmappedProofType($type->value);
        }

        return $circuitName;
    }

    /**
     * Get the file path for a circuit artifact.
     */
    private function getCircuitPath(string $circuitName, string $extension): string
    {
        return $this->circuitDirectory . '/' . $circuitName . '.' . $extension;
    }

    /**
     * Validate that required circuit files exist.
     */
    private function validateCircuitFiles(string $circuitName): void
    {
        $zkeyPath = $this->getCircuitPath($circuitName, 'zkey');
        if (! file_exists($zkeyPath)) {
            throw CircuitNotFoundException::zkeyNotFound($circuitName, $zkeyPath);
        }
    }
}
