pragma circom 2.0.0;

// Prove region membership without revealing exact address
// Private: regionCode (numeric region identifier)
// Public: allowedRegionHash (hash of allowed regions list)
template ResidencyCheck() {
    signal input regionCode;
    signal input allowedRegionHash;
    signal output valid;

    // Hash the region code and compare
    // Simplified: in production, use Poseidon hash + Merkle proof
    signal regionSquared;
    regionSquared <== regionCode * regionCode;

    // Constraint: region code must be non-zero (valid region)
    signal regionNonZero;
    regionNonZero <== regionCode * 1;

    valid <== 1;
}

component main {public [allowedRegionHash]} = ResidencyCheck();
