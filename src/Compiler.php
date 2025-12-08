<?php

require_once __DIR__ . "/Translator.php";

class APH_Compiler {

    protected $keywords = [];

    public function __construct() {
        $this->keywords = require __DIR__ . "/Keywords.php";
    }

    public function compile($code) {

        $lines = explode("\n", $code);

        // FIXED: Runtime.php must load from src/ (NOT cache/)
        $php = "<?php\n\nrequire __DIR__ . '/../src/Runtime.php';\n\n";

        $inside_php_block = false;

        foreach ($lines as $line) {

            $trim = trim($line);
            if ($trim === "") continue;

            // RAW PHP BLOCK START
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

            // PHP TAGS
            if (str_starts_with($trim, "<?php") || str_starts_with($trim, "?>")) {
                $php .= "$trim\n";
                continue;
            }

            // COMMENTS
            if (str_starts_with($trim, "COMMENT")) {
                $php .= "// " . substr($trim, 7) . "\n";
                continue;
            }

            if (str_starts_with($trim, "//")) {
                $php .= "$trim\n";
                continue;
            }

            // IMPORTANT:
            // Sort keywords by length so "ELSE IF" matches before "ELSE"
            uksort($this->keywords, function($a, $b) {
                return strlen($b) - strlen($a);
            });

            // PROCESS APH KEYWORDS
            foreach ($this->keywords as $key => $func) {
                if (str_starts_with($trim, $key)) {
                    $rest = trim(substr($trim, strlen($key)));
                    $php .= $func($rest) . "\n";
                    continue 2;
                }
            }

            // UNKNOWN LINE — leave a trace for debugging
            $php .= "// UNKNOWN APH LINE: $trim\n";
        }

        return $php;
    }
}
