<?php

/**
 * APH Runtime (v1)
 * ------------------------------------
 * This file loads automatically inside every compiled APH program.
 * It provides:
 *  - internal error helpers
 *  - macro storage (future feature)
 *  - safe variable handling (future)
 *  - debugging helpers
 */

// -------------------------------------------
// APH Version
// -------------------------------------------
function aph_version() {
    return "APH v1.0";
}

// -------------------------------------------
// APH Error Helper – Pretty error output
// -------------------------------------------
function aph_error($message) {
    echo "\n[APH ERROR] $message\n";
    exit(1);
}

// -------------------------------------------
// Macro storage for APH v2 (CamelCase rules)
// -------------------------------------------
$GLOBALS['APH_MACROS'] = [];

/**
 * Register a macro (future APH feature)
 */
function aph_define_macro($key, $value) {
    $GLOBALS['APH_MACROS'][$key] = $value;
}

/**
 * Get macro value (future)
 */
function aph_get_macro($key) {
    return $GLOBALS['APH_MACROS'][$key] ?? null;
}

// -------------------------------------------
// Identifier processor for variables
// (Used in Keywords.php)
// -------------------------------------------
function aph_identifier($word) {

    // If it's already a PHP variable → OK
    if (str_starts_with($word, '$')) {
        return $word;
    }

    // Numbers are allowed as-is
    if (is_numeric($word)) {
        return $word;
    }

    // Strings remain untouched
    if ((str_starts_with($word, '"') && str_ends_with($word, '"')) ||
        (str_starts_with($word, "'") && str_ends_with($word, "'"))) {
        return $word;
    }

    // Otherwise → treat as variable
    return '$' . $word;
}
