<?php

require_once __DIR__ . "/Translator.php";

class APH_Compiler {

    protected $keywords = [];

    public function __construct() {
        $this->keywords = require __DIR__ . "/Keywords.php";
    }

    public function compile($code) {

        $lines = explode("\n", $code);

        // Load correct Runtime.php
        $php = "<?php\n\nrequire __DIR__ . '/../src/Runtime.php';\n\n";

        $inside_php_block = false;

        foreach ($lines as $line) {

            $trim = trim($line);
            if ($trim === "") {
                $php .= "\n";
                continue;
            }

            // ----------------------------------------------------
            // RAW PHP BLOCK START
            // ----------------------------------------------------
            if ($trim === "PHP:") {
                $inside_php_block = true;
                continue;
            }

            // RAW PHP BLOCK END
            if ($trim === "END PHP") {
                $inside_php_block = false;
                continue;
            }

            // PASS RAW PHP THROUGH
            if ($inside_php_block) {
                $php .= $line . "\n";
                continue;
            }

            // ----------------------------------------------------
            // PHP TAGS
            // ----------------------------------------------------
            if (str_starts_with($trim, "<?php") || str_starts_with($trim, "?>")) {
                $php .= "$trim\n";
                continue;
            }

            // ----------------------------------------------------
            // APH COMMENTS
            // ----------------------------------------------------
            if (str_starts_with($trim, "COMMENT")) {
                $php .= "// " . substr($trim, 7) . "\n";
                continue;
            }

            if (str_starts_with($trim, "//")) {
                $php .= "$trim\n";
                continue;
            }

            // ----------------------------------------------------
            // PRIORITIZE LONGEST APH KEYWORD MATCH
            // ----------------------------------------------------
            uksort($this->keywords, function($a, $b) {
                return strlen($b) - strlen($a);
            });

            foreach ($this->keywords as $key => $func) {

                if (str_starts_with($trim, $key)) {

                    $rest = trim(substr($trim, strlen($key)));

                    // Convert APH → PHP
                    $php .= $func($rest) . "\n";
                    continue 2;
                }
            }

            // ----------------------------------------------------
            // NEW FEATURE:
            // If line looks like REAL PHP → pass it through
            // ----------------------------------------------------
            if ($this->isPHPLine($trim)) {
                $php .= $line . "\n";
                continue;
            }

            // ----------------------------------------------------
            // Otherwise, log unknown APH line
            // ----------------------------------------------------
            $php .= "// UNKNOWN APH LINE: $trim\n";
        }

        return $php;
    }



    // ==========================================================
    // Detect PHP automatically
    // ==========================================================
    private function isPHPLine(string $line): bool {

        // Variable assignment
        if (preg_match('/^\$\w+\s*=/', $line)) return true;

        // echo statements
        if (preg_match('/^echo\b/', $line)) return true;

        // if / elseif / else
        if (preg_match('/^(if|elseif|else\b)/', $line)) return true;

        // for / foreach / while
        if (preg_match('/^(for|foreach|while)\b/', $line)) return true;

        // function definitions
        if (preg_match('/^function\b/', $line)) return true;

        // return statements
        if (preg_match('/^return\b/', $line)) return true;

        // require/include
        if (preg_match('/^(require|include)/', $line)) return true;

        return false;
    }
}
