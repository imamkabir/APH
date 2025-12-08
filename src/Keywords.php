<?php

/**
 * APH v1 — Full Keyword Dictionary (Updated & Stable)
 * ----------------------------------
 * EVERY keyword now uses translator helpers,
 * and APH fully supports strings, variables, function calls, AND logic.
 */

return [

    // ----------------------------------------------------------
    // DISPLAY (print text, variables, or function calls)
    // ----------------------------------------------------------
    "DISPLAY" => function($content) {
        $parts = explode(" AND ", $content);
        $phpParts = [];

        foreach ($parts as $p) {
            $p = trim($p);

            // 1) Quoted text → leave as is
            if ((str_starts_with($p, '"') && str_ends_with($p, '"')) ||
                (str_starts_with($p, "'") && str_ends_with($p, "'"))) {

                $phpParts[] = $p;
                continue;
            }

            // 2) Function call (detect simple function(...) pattern)
            //    - starts with a letter/underscore, then letters/numbers/underscores, then parentheses
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(.*\)$/', $p)) {
                // use as-is (do NOT add $)
                $phpParts[] = $p;
                continue;
            }

            // 3) Raw PHP expression (starts with $ or contains operators) — keep as-is if it starts with $
            if (str_starts_with($p, '$')) {
                $phpParts[] = $p;
                continue;
            }

            // 4) Fallback: treat as variable via aph_identifier()
            $phpParts[] = aph_identifier($p);
        }

        return "echo " . implode(" . ", $phpParts) . ";";
    },

    // ----------------------------------------------------------
    // SET variable TO value
    // ----------------------------------------------------------
    "SET" => function($content) {

        // Example: SET age TO 20
        $parts = explode(" TO ", $content);
        $var = trim($parts[0]);
        $value = trim($parts[1] ?? "");

        // Turn "age" → "$age"
        $var = aph_identifier($var);

        // If value is a quoted string or numeric → keep as-is
        if (is_numeric($value) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {

            // nothing to do
        }
        // If value looks like a function call → leave as-is (no $)
        else if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(.*\)$/', $value)) {
            // keep $value as function call text
        }
        // Otherwise treat as variable
        else {
            $value = aph_identifier($value);
        }

        return "$var = $value;";
    },

    // ----------------------------------------------------------
    // IF / ELSE IF / ELSE
    // ----------------------------------------------------------
    "IF" => function($content) {
        $condition = str_replace(" THEN", "", trim($content));
        $condition = aph_translate_condition($condition);
        return "if ($condition) {";
    },

    "ELSE IF" => function($content) {
        $condition = str_replace(" THEN", "", trim($content));
        $condition = aph_translate_condition($condition);
        return "} elseif ($condition) {";
    },

    "ELSE" => function() {
        return "} else {";
    },

    "END IF" => function() {
        return "}";
    },

    // ----------------------------------------------------------
    // LOOPS
    // ----------------------------------------------------------
    "REPEAT" => function($content) {
        $num = intval(str_replace(" TIMES", "", trim($content)));
        $i = "i" . rand(1000,9999);
        return "for (\$$i = 0; \$$i < $num; \$$i++) {";
    },

    "END REPEAT" => function() {
        return "}";
    },

    // ----------------------------------------------------------
    // FUNCTION definition
    // Example: FUNCTION greet(name)
    // ----------------------------------------------------------
    "FUNCTION" => function($content) {

        $content = trim($content);

        // Convert parameters to PHP variables
        if (str_contains($content, "(")) {

            // Split function name and arguments
            $name = trim(substr($content, 0, strpos($content, "(")));
            $args = substr($content, strpos($content, "(") + 1);
            $args = rtrim($args, ")");

            if ($args === "") {
                $phpArgs = "";
            } else {
                $argList = explode(",", $args);
                $phpArgs = implode(", ", array_map(function($a) {
                    return aph_identifier(trim($a));
                }, $argList));
            }

            return "function {$name}($phpArgs) {";
        }

        // Fallback (no params)
        return "function {$content} {";
    },

    "END FUNCTION" => function() {
        return "}";
    },

    // ----------------------------------------------------------
    // RETURN
    // ----------------------------------------------------------
    "RETURN" => function($content) {

        $content = trim($content);

        // If numeric or quoted string, keep as-is
        if (is_numeric($content) ||
            (str_starts_with($content, '"') && str_ends_with($content, '"')) ||
            (str_starts_with($content, "'") && str_ends_with($content, "'"))) {
            // no change
        }
        // If it's a function call, keep as-is
        else if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(.*\)$/', $content)) {
            // no change
        }
        // Otherwise treat as variable
        else {
            $content = aph_identifier($content);
        }

        return "return $content;";
    },

    // ----------------------------------------------------------
    // IMPORT FILE
    // ----------------------------------------------------------
    "IMPORT FILE" => function($content) {
        $file = trim($content, " \"'");
        return "require \"$file\";";
    },

    // ----------------------------------------------------------
    // COMMENT
    // ----------------------------------------------------------
    "COMMENT" => function($content) {
        return "// " . trim($content);
    },

    // ----------------------------------------------------------
    // BOOL CONSTANTS
    // ----------------------------------------------------------
    "TRUE" => function() { return "true"; },
    "FALSE" => function() { return "false"; },

];
