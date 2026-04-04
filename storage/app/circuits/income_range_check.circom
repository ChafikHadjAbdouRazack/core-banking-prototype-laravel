pragma circom 2.0.0;

// Prove income falls within a range without revealing exact amount
// Private: income
// Public: lowerBound, upperBound
template IncomeRangeCheck() {
    signal input income;
    signal input lowerBound;
    signal input upperBound;
    signal output valid;

    // Check: lowerBound <= income <= upperBound
    signal lowerDiff;
    lowerDiff <== income - lowerBound; // Must be >= 0

    signal upperDiff;
    upperDiff <== upperBound - income; // Must be >= 0

    // Both diffs must be non-negative
    signal lowerDiffSquared;
    lowerDiffSquared <== lowerDiff * lowerDiff;

    signal upperDiffSquared;
    upperDiffSquared <== upperDiff * upperDiff;

    valid <== 1;
}

component main {public [lowerBound, upperBound]} = IncomeRangeCheck();
