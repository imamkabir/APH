<?php

/**
 * APH Runtime (v2.7)
 * ------------------------------------
 * Loaded inside every compiled APH program.
 * Provides:
 *  - internal error helpers
 *  - macro storage (future)
 *  - safe variable handling
 *  - HTML helpers
 *  - small printing helpers to normalize PHP_EOL and arrays
 */

/**
 * APH Version
 */
function aph_version() {
    return "APH v2.7";
}

/**
 * APH Error Helper – Pretty error output
 */
function aph_error($message) {
    echo "\n[APH ERROR] $message\n";
    exit(1);
}

/**
 * Macro storage for APH v2 (CamelCase rules)
 */
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

/**
 * Identifier processor for variables
 * (Used in Keywords.php)
 */
function aph_identifier($word) {

    // If it's already a PHP variable → OK
    if (is_string($word) && str_starts_with($word, '$')) {
        return $word;
    }

    // Numbers are allowed as-is
    if (is_numeric($word)) {
        return $word;
    }

    // Strings remain untouched
    if (
        (is_string($word) && str_starts_with($word, '"') && str_ends_with($word, '"')) ||
        (is_string($word) && str_starts_with($word, "'") && str_ends_with($word, "'"))
    ) {
        return $word;
    }

    // Otherwise → treat as variable
    return '$' . $word;
}

/* ------------------------------------------------------------------
 * HTML helpers
 * ------------------------------------------------------------------ */

/**
 * Escape a value for safe HTML output.
 * Accepts strings or arrays (arrays will be JSON-encoded then escaped).
 */
function aph_escape_html($val) {
    if (is_array($val) || is_object($val)) {
        $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Unescape HTML entities back to plain text.
 */
function aph_unescape_html($val) {
    return html_entity_decode((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ------------------------------------------------------------------
 * Printing helpers
 * ------------------------------------------------------------------ */

/**
 * aph_safe_echo
 *
 * Echo a single value safely. Arrays/objects are JSON-encoded.
 */
function aph_safe_echo($val) {
    if (is_array($val) || is_object($val)) {
        echo json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return;
    }

    echo (string)$val;
}

/**
 * aph_print
 *
 * Accepts multiple parts and prints them concatenated.
 * Special rule: the bare token PHP_EOL (without quotes) will be interpreted
 * as the actual PHP_EOL constant if passed as the literal string "PHP_EOL".
 *
 * Example calls:
 *  aph_print("Hello<br>", PHP_EOL);
 *  aph_print("Count: ", $i);
 */
function aph_print(...$parts) {
    $out = '';

    foreach ($parts as $p) {
        // If caller passed the literal string "PHP_EOL" (unquoted token), map to PHP_EOL
        if (is_string($p) && $p === 'PHP_EOL') {
            $out .= PHP_EOL;
            continue;
        }

        // Arrays/objects → JSON encode
        if (is_array($p) || is_object($p)) {
            $out .= json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            continue;
        }

        $out .= (string)$p;
    }

    echo $out;
}

/* ------------------------------------------------------------------
 * Utility: convert a token (e.g. $x or "text") to its string for debug
 * ------------------------------------------------------------------ */

function aph_to_string($val) {
    if (is_null($val)) return 'null';
    if (is_bool($val)) return $val ? 'true' : 'false';
    if (is_array($val) || is_object($val)) return json_encode($val, JSON_UNESCAPED_UNICODE);
    return (string)$val;
}
