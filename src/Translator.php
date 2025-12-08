<?php

/**
 * APH Translator v2.8
 * -------------------------------
 * Shared engine used by:
 *   - APH → PHP compiler
 *   - PHP → APH reverse compiler
 *
 * Handles:
 *   ✔ Condition parsing
 *   ✔ Identifier normalization
 *   ✔ Expression parsing
 *   ✔ HTML-safe translation
 *   ✔ Constant support (PHP_EOL, TRUE, FALSE)
 *   ✔ Variable detection
 */


/**
 * ================================================================
 *  APH → PHP CONDITION TRANSLATION (IF, ELSE IF)
 * ================================================================
 */
function aph_translate_condition($text) {

    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', trim($text));

    // APH → PHP operator map
    $map = [
        " IS GREATER THAN "        => " > ",
        " IS LESS THAN "           => " < ",
        " IS EQUAL TO "            => " == ",
        " IS NOT EQUAL TO "        => " != ",
        " IS GREATER OR EQUAL TO " => " >= ",
        " IS LESS OR EQUAL TO "    => " <= ",
        " AND ALSO "               => " && ",
        " OR ELSE "                => " || ",
    ];

    // Replace APH keywords with PHP equivalents
    foreach ($map as $aph => $php) {
        $text = str_replace($aph, $php, $text);
    }

    // Tokenize the result
    $parts = preg_split('/\s+/', $text);

    foreach ($parts as &$p) {

        if ($p === "") continue;

        // Skip numbers
        if (is_numeric($p)) continue;

        // Skip booleans
        if (in_array(strtolower($p), ["true", "false"])) continue;

        // Strings → unchanged
        if (is_quoted_literal($p)) continue;

        // Operators → unchanged
        if (in_array($p, ["==","!=",">","<",">=","<=","&&","||"])) continue;

        // Parentheses → unchanged
        if ($p === "(" || $p === ")") continue;

        // Otherwise → it's a variable, must start with $
        if (!str_starts_with($p, '$')) {
            $p = '$' . $p;
        }
    }

    return implode(" ", $parts);
}



/**
 * ================================================================
 *  IDENTIFIER NORMALIZATION (APH compiler uses this)
 * ================================================================
 */
function aph_identifier($word) {

    $word = trim($word);

    // Already a PHP variable → keep
    if (str_starts_with($word, '$')) return $word;

    // Numbers → keep
    if (is_numeric($word)) return $word;

    // Quoted strings → keep
    if (is_quoted_literal($word)) return $word;

    // Function call detection: name(...)
    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(.*\)$/', $word)) {
        return $word; // don't add $
    }

    // Constants like PHP_EOL stay raw
    if (preg_match('/^[A-Z_]+$/', $word)) {
        return $word;
    }

    // Otherwise treat as variable
    return '$' . $word;
}



/**
 * ================================================================
 *  REMOVE `$` FOR REVERSE COMPILER
 * ================================================================
 */
function aph_strip($word) {
    $word = trim($word);
    if (str_starts_with($word, '$')) return substr($word, 1);
    return $word;
}



/**
 * ================================================================
 *  CLEAN / NORMALIZE RAW PHP CONDITION FOR APH OUTPUT
 * ================================================================
 */
function aph_normalize_php_condition($cond) {

    $cond = trim($cond, "() ");

    // Space operators
    $cond = preg_replace('/([<>!=]=?)/', ' $1 ', $cond);

    // Collapse multiple spaces
    $cond = preg_replace('/\s+/', ' ', $cond);

    // Remove $
    $cond = str_replace('$', '', $cond);

    return trim($cond);
}



/**
 * ================================================================
 *  HELPER: detect quoted literal
 * ================================================================
 */
function is_quoted_literal($val): bool {
    return (
        (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
        (str_starts_with($val, "'") && str_ends_with($val, "'"))
    );
}
