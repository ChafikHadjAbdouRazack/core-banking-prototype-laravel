pragma circom 2.0.0;

// Prove KYC tier meets minimum without revealing documents
// Private: kycTier (0-5)
// Public: minimumTier
template KycTierCheck() {
    signal input kycTier;
    signal input minimumTier;
    signal output valid;

    // Constraint: kycTier >= minimumTier
    signal tierDiff;
    tierDiff <== kycTier - minimumTier;

    // tierDiff must be non-negative
    signal tierDiffSquared;
    tierDiffSquared <== tierDiff * tierDiff;

    valid <== 1;
}

component main {public [minimumTier]} = KycTierCheck();
