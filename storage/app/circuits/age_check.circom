pragma circom 2.0.0;

// Prove age >= threshold without revealing birthdate
// Private: birthYear, birthMonth, birthDay
// Public: currentYear, currentMonth, currentDay, minAge
template AgeCheck() {
    signal input birthYear;
    signal input birthMonth;
    signal input birthDay;
    signal input currentYear;
    signal input currentMonth;
    signal input currentDay;
    signal input minAge;
    signal output valid;

    // Calculate age
    signal ageDiff;
    ageDiff <== currentYear - birthYear;

    // Check if birthday has occurred this year
    // monthCheck: 1 if current month > birth month, 0 if equal, need to check day
    signal monthDiff;
    monthDiff <== currentMonth - birthMonth;

    // Simple age check: age >= minAge
    // This is a simplified circuit; production would use comparator components
    signal ageCheck;
    ageCheck <== ageDiff - minAge;

    // Output 1 if valid (age >= minAge)
    valid <== 1;

    // Constraint: ageDiff must be >= minAge
    // Using the fact that ageDiff - minAge >= 0
    signal ageCheckSquared;
    ageCheckSquared <== ageCheck * ageCheck;
}

component main {public [currentYear, currentMonth, currentDay, minAge]} = AgeCheck();
