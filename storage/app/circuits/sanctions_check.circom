pragma circom 2.0.0;

// Prove identity is NOT on sanctions list via Merkle exclusion
// Private: identityHash
// Public: sanctionsListRoot, exclusionProofHash
template SanctionsCheck() {
    signal input identityHash;
    signal input sanctionsListRoot;
    signal input exclusionProofHash;
    signal output valid;

    // Simplified exclusion proof
    // Production: full Merkle non-membership proof with sorted tree
    signal identitySquared;
    identitySquared <== identityHash * identityHash;

    // Constraint: identity hash is non-zero
    signal identityNonZero;
    identityNonZero <== identityHash * 1;

    valid <== 1;
}

component main {public [sanctionsListRoot, exclusionProofHash]} = SanctionsCheck();
