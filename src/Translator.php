<?php

/**
 * APH Translator Helpers
 * Converts English APH conditions and identifiers into proper PHP syntax.
 *
 * This file is used by Keywords.php to produce accurate PHP output.
 */

function aph_translate_condition($text) {

    // STEP 1: Convert APH operators into PHP operators
    $map = [
        " IS GREATER THAN " => " > ",
        " IS LESS THAN " => " < ",
        " IS EQUAL TO " => " == ",
        " IS NOT EQUAL TO " => " != ",
        " IS GREATER OR EQUAL TO " => " >= ",
        " IS LESS OR EQUAL TO " => " <= ",
        " AND ALSO " => " && ",
        " OR ELSE " => " || ",
    ];

    foreach ($map as $aph => $php) {
        $text = str_replace($aph, $php, $text);
    }

    // STEP 2: Break into tokens for variable recognition
    $parts = preg_split('/\s+/', trim($text));

    foreach ($parts as &$p) {
        if ($p === "") continue;

        // Skip numbers
        if (is_numeric($p)) continue;

        // Skip true/false
        if (strtolower($p) === "true" || strtolower($p) === "false") continue;

        // Skip quoted strings ("hello world")
        if ((str_starts_with($p, '"') && str_ends_with($p, '"')) ||
            (str_starts_with($p, "'") && str_ends_with($p, "'"))) {
            continue;
        }

        // Skip PHP operators
        if (in_array($p, ["==","!=",">","<",">=","<=","&&","||"])) continue;

        // Skip parentheses
        if ($p === "(" || $p === ")") continue;

        // Otherwise → VARIABLE → must begin with $
        if ($p[0] !== '$') {
            $p = "$" . $p;
        }
    }

    return implode(" ", $parts);
}


/**
 * Translate identifiers (future use)
 * For variables, macros, CamelCase, etc.
 */
function aph_identifier($word) {
    // If already $variable → keep it
    if (str_starts_with($word, '$')) {
        return $word;
    }

    // Numbers untouched
    if (is_numeric($word)) {
        return $word;
    }

    // Strings untouched
    if ((str_starts_with($word, '"') && str_ends_with($word, '"')) ||
        (str_starts_with($word, "'") && str_ends_with($word, "'"))) {
        return $word;
    }

    // Everything else becomes $variable
    return '$' . $word;
}
