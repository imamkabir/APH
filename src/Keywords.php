<?php

/**
 * APH v2.7 — Keyword Dictionary (HTML-aware, Expression-aware)
 * ------------------------------------------------------------
 * Upgraded for correct handling of:
 * - HTML inside strings ("<br>")
 * - PHP_EOL inside DISPLAY
 * - Expressions: $x + 5, func($y), constant calls, etc.
 * - Complex echo concatenations
 */

return [

    // ----------------------------------------------------------
    // DISPLAY — now supports HTML, PHP_EOL, expressions, functions
    // ----------------------------------------------------------
    "DISPLAY" => function($content) {

        // Split on " AND " for multi-part display
        $parts = explode(" AND ", $content);
        $phpParts = [];

        foreach ($parts as $p) {
            $p = trim($p);

            // 1) Quoted string (HTML allowed)
            if ((str_starts_with($p, '"') && str_ends_with($p, '"')) ||
                (str_starts_with($p, "'") && str_ends_with($p, "'"))) {
                $phpParts[] = $p;
                continue;
            }

            // 2) PHP constants (e.g., PHP_EOL)
            if (preg_match('/^[A-Z_]+$/', $p)) {
                $phpParts[] = $p;
                continue;
            }

            // 3) Function call detection
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(.*\)$/', $p)) {
                $phpParts[] = $p;
                continue;
            }

            // 4) Raw PHP expression — contains operators
            if (preg_match('/[\+\-\*\/\.\%\>\<=]/', $p)) {
                // Example: $i + 1, $x . "<br>"
                $phpParts[] = $p;
                continue;
            }

            // 5) Pure variable
            if (str_starts_with($p, '$')) {
                $phpParts[] = $p;
                continue;
            }

            // 6) Fallback: APH variable → PHP variable
            $phpParts[] = aph_identifier($p);
        }

        return "echo " . implode(" . ", $phpParts) . ";";
    },


    // ----------------------------------------------------------
    // SET — now supports HTML, functions, expressions, constants
    // ----------------------------------------------------------
    "SET" => function($content) {

        $parts = explode(" TO ", $content);
        $var = trim($parts[0]);
        $value = trim($parts[1] ?? "");

        // Convert var name → PHP variable
        $var = aph_identifier($var);

        // 1) Numeric
        if (is_numeric($value)) {
            // NO CHANGE
        }
        // 2) Quoted string
        else if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            // NO CHANGE
        }
        // 3) PHP constants (PHP_EOL, etc.)
        else if (preg_match('/^[A-Z_]+$/', $value)) {
            // NO CHANGE
        }
        // 4) Function call
        else if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(.*\)$/', $value)) {
            // NO CHANGE
        }
        // 5) Expression
        else if (preg_match('/[\+\-\*\/\.\%\>\<=]/', $value)) {
            // NO CHANGE
        }
        // 6) Fallback: variable
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
        return "if (" . aph_translate_condition($condition) . ") {";
    },

    "ELSE IF" => function($content) {
        $condition = str_replace(" THEN", "", trim($content));
        return "} elseif (" . aph_translate_condition($condition) . ") {";
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
        // Example: REPEAT 3 TIMES
        $num = intval(str_replace(" TIMES", "", trim($content)));
        $i = "i" . rand(1000, 9999);
        return "for (\$$i = 0; \$$i < $num; \$$i++) {";
    },

    "END REPEAT" => function() {
        return "}";
    },


    // ----------------------------------------------------------
    // FUNCTION definition
    // ----------------------------------------------------------
    "FUNCTION" => function($content) {

        $content = trim($content);

        if (str_contains($content, "(")) {

            $name = trim(substr($content, 0, strpos($content, "(")));
            $args = substr($content, strpos($content, "(") + 1);
            $args = rtrim($args, ")");

            if ($args === "") {
                $phpArgs = "";
            } else {
                $phpArgs = implode(", ", array_map(function($a) {
                    return aph_identifier(trim($a));
                }, explode(",", $args)));
            }

            return "function {$name}($phpArgs) {";
        }

        return "function {$content} {";
    },

    "END FUNCTION" => function() {
        return "}";
    },


    // ----------------------------------------------------------
    // RETURN — expression-safe
    // ----------------------------------------------------------
    "RETURN" => function($content) {

        $content = trim($content);

        // numeric or string
        if (is_numeric($content) ||
            (str_starts_with($content, '"') && str_ends_with($content, '"')) ||
            (str_starts_with($content, "'") && str_ends_with($content, "'"))) {
            return "return $content;";
        }

        // function call
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*\(.*\)$/', $content)) {
            return "return $content;";
        }

        // expression
        if (preg_match('/[\+\-\*\/\.\%\>\<=]/', $content)) {
            return "return $content;";
        }

        // variable
        return "return " . aph_identifier($content) . ";";
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
    // BOOLEAN CONSTANTS
    // ----------------------------------------------------------
    "TRUE" => function() { return "true"; },
    "FALSE" => function() { return "false"; },
];
