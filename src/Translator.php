<?php

/**
 * APH Translator Helpers
 * Supports BOTH directions:
 *  - APH → PHP  (compiler)
 *  - PHP → APH  (reverse compiler helper)
 */


/**
 * ================================================================
 *  APH → PHP CONDITION TRANSLATION
 * ================================================================
 */
function aph_translate_condition($text) {

    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', $text);

    // STEP 1: Convert APH operators into PHP operators
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

    foreach ($map as $aph => $php) {
        $text = str_replace($aph, $php, $text);
    }

    // STEP 2: Tokenize for variable detection
    $parts = preg_split('/\s+/', trim($text));

    foreach ($parts as &$p) {

        if ($p === "") continue;

        // skip numbers
        if (is_numeric($p)) continue;

        // skip booleans
        if (in_array(strtolower($p), ["true","false"])) continue;

        // skip quoted strings
        if ((str_starts_with($p, '"') && str_ends_with($p, '"')) ||
            (str_starts_with($p, "'") && str_ends_with($p, "'"))) {
            continue;
        }

        // skip operators
        if (in_array($p, ["==","!=",">","<",">=","<=","&&","||"])) continue;

        // skip parentheses
        if ($p === "(" || $p === ")") continue;

        // otherwise → VARIABLE → add $
        if ($p[0] !== '$') {
            $p = "$" . $p;
        }
    }

    return implode(" ", $parts);
}


/**
 * ================================================================
 *  APH IDENTIFIER (for compiler)
 * ================================================================
 */
function aph_identifier($word) {

    $word = trim($word);

    // Already $variable → keep it
    if (str_starts_with($word, '$')) return $word;

    // Numbers untouched
    if (is_numeric($word)) return $word;

    // Quoted strings untouched
    if ((str_starts_with($word, '"') && str_ends_with($word, '"')) ||
        (str_starts_with($word, "'") && str_ends_with($word, "'"))) {
        return $word;
    }

    // Everything else becomes $variable
    return '$' . $word;
}


/**
 * ================================================================
 *  REMOVE `$` FOR APH NORMALIZATION (ReverseCompiler uses this)
 * ================================================================
 */
function aph_strip($word) {

    $word = trim($word);

    // Remove starting $
    if (str_starts_with($word, '$')) {
        return substr($word, 1);
    }

    return $word;
}


/**
 * ================================================================
 *  CLEAN / NORMALIZE RAW PHP CONDITION FOR REVERSE COMPILER
 * ================================================================
 */
function aph_normalize_php_condition($cond) {

    // Remove parentheses
    $cond = trim($cond, "() ");

    // Fix spacing around operators
    $cond = preg_replace('/([<>!=]=?)/', ' $1 ', $cond);

    // Remove extra spaces
    $cond = preg_replace('/\s+/', ' ', $cond);

    // Remove $
    $cond = str_replace("$", "", $cond);

    return trim($cond);
}
