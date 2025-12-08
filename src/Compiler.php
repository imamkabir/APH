<?php

require_once __DIR__ . "/Translator.php";

class APH_Compiler {

    protected $keywords = [];

    public function __construct() {
        $this->keywords = require __DIR__ . "/Keywords.php";
    }

    public function compile($code) {

        $lines = explode("\n", $code);

        // Bootstrap runtime
        $php = "<?php\n\nrequire __DIR__ . '/../src/Runtime.php';\n\n";

        $inside_php_block = false;
        $inside_comment_block = false;

        foreach ($lines as $line) {

            $trim = trim($line);

            // ----------------------------------------------------
            // Empty line – preserve spacing
            // ----------------------------------------------------
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

            // If inside raw PHP area → pass everything through
            if ($inside_php_block) {
                $php .= $line . "\n";
                continue;
            }

            // ----------------------------------------------------
            // REAL PHP COMMENTS → KEEP EXACTLY
            // ----------------------------------------------------
            if (str_starts_with($trim, "//")) {
                $php .= $trim . "\n";
                continue;
            }

            if (str_starts_with($trim, "/*")) {
                $inside_comment_block = true;
                $php .= $line . "\n";
                continue;
            }

            if ($inside_comment_block) {
                $php .= $line . "\n";
                if (str_ends_with($trim, "*/")) {
                    $inside_comment_block = false;
                }
                continue;
            }

            // ----------------------------------------------------
            // PHP TAGS
            // ----------------------------------------------------
            if (str_starts_with($trim, "<?php") || str_starts_with($trim, "?>")) {
                $php .= $trim . "\n";
                continue;
            }

            // ----------------------------------------------------
            // APH COMMENT
            // ----------------------------------------------------
            if (str_starts_with($trim, "COMMENT")) {
                $php .= "// " . substr($trim, 7) . "\n";
                continue;
            }

            // ----------------------------------------------------
            // PRIORITIZE LONGEST APH KEYWORD MATCH FIRST
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
            // IF LINE IS VALID PHP → PASS THROUGH
            // ----------------------------------------------------
            if ($this->isPHPLine($trim)) {
                $php .= $line . "\n";
                continue;
            }

            // ----------------------------------------------------
            // LAST RESORT: treat as PHP echo-safe text literal
            // ----------------------------------------------------
            if (preg_match('/<[^>]+>/', $trim)) {
                // Line contains HTML → wrap safely
                $escaped = addslashes($trim);
                $php .= "echo \"$escaped\";\n";
                continue;
            }

            // ----------------------------------------------------
            // UNKNOWN APH → COMMENT INSTEAD OF FAILING
            // ----------------------------------------------------
            $php .= "// UNKNOWN APH LINE: $trim\n";
        }

        return $php;
    }



    // ==========================================================
    // Detect Real PHP
    // Extremely important.
    // ==========================================================
    private function isPHPLine(string $line): bool {

        // Variable assignment
        if (preg_match('/^\$\w+\s*=/', $line)) return true;

        // echo statements (HTML-safe)
        if (preg_match('/^echo\b/', $line)) return true;

        // if / elseif / else / switch
        if (preg_match('/^(if|elseif|else|switch)/', $line)) return true;

        // for / foreach / while / do
        if (preg_match('/^(for|foreach|while|do)/', $line)) return true;

        // function definitions
        if (preg_match('/^function\b/', $line)) return true;

        // return keyword
        if (preg_match('/^return\b/', $line)) return true;

        // require/include
        if (preg_match('/^(require|include)/', $line)) return true;

        // Array declarations
        if (preg_match('/^\$[\w]+\s*=\s*\[.*\];$/', $line)) return true;

        // HTML echo lines → safe
        if (preg_match('/echo\s*".*<.*>.*"/', $line)) return true;

        return false;
    }
}
