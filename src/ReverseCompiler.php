<?php

require_once __DIR__ . '/Translator.php';

/**
 * APH Reverse Compiler v2.7
 * -------------------------
 * NEW:
 * - Proper HTML support ("<br>")
 * - Expression-aware echo parsing: "x" . y . PHP_EOL
 * - Constants handling (PHP_EOL)
 * - Cleaner function + IF/ELSE stack
 * - Block-based indentation
 */

class APH_ReverseCompiler {

    private array $stack = [];
    private int $indent = 0;

    /**
     * MAIN ENTRY: Convert PHP/mixed code → APH (.IMAM)
     */
    public function toAPH(string $code): string {
        $lines = preg_split("/\r\n|\n|\r/", $code);
        $out = [];

        foreach ($lines as $raw) {

            $trim = trim($raw);

            if ($trim === "") {
                $out[] = "";
                continue;
            }

            // PHP-style comments
            if (str_starts_with($trim, "//")) {
                $out[] = $this->indentLine("COMMENT " . substr($trim, 2));
                continue;
            }

            if (preg_match('#^/\*(.*)\*/$#', $trim, $m)) {
                $out[] = $this->indentLine("COMMENT " . trim($m[1]));
                continue;
            }

            // Already an APH keyword → preserve it
            if ($this->isAPH($trim)) {
                $this->updateStackForAPH($trim);
                $out[] = $this->indentLine($trim);
                continue;
            }

            // ============================================================
            // VARIABLE ASSIGNMENT
            // ============================================================
            if (preg_match('/^\$(\w+)\s*=\s*(.+);$/', $trim, $m)) {
                $var = $m[1];
                $val = trim($m[2]);

                // If raw var → strip $
                if (str_starts_with($val, '$')) {
                    $val = aph_strip($val);
                }

                $out[] = $this->indentLine("SET {$var} TO {$val}");
                continue;
            }

            // ============================================================
            // ECHO STATEMENTS (HTML + concatenation supported)
            // ============================================================
            if (preg_match('/^echo\s+(.+);$/i', $trim, $m)) {

                $expr = trim($m[1]);

                // Split on concatenation operators
                $parts = array_map('trim', explode('.', $expr));

                $aphParts = [];

                foreach ($parts as $p) {

                    // quoted string: preserve HTML
                    if ((str_starts_with($p, '"') && str_ends_with($p, '"')) ||
                        (str_starts_with($p, "'") && str_ends_with($p, "'"))) {
                        $aphParts[] = $p;
                        continue;
                    }

                    // constants like PHP_EOL
                    if (preg_match('/^[A-Z_]+$/', $p)) {
                        $aphParts[] = $p;
                        continue;
                    }

                    // variables → strip $
                    if (str_starts_with($p, '$')) {
                        $aphParts[] = aph_strip($p);
                        continue;
                    }

                    // fallback → keep raw PHP expression
                    $aphParts[] = $p;
                }

                $line = "DISPLAY " . implode(" AND ", $aphParts);

                $out[] = $this->indentLine($line);
                continue;
            }

            // ============================================================
            // IF ( ... ) {
            // ============================================================
            if (preg_match('/^if\s*\((.+)\)\s*\{?$/', $trim, $m)) {
                $cond = aph_normalize_php_condition($m[1]);
                $out[] = $this->indentLine("IF {$cond} THEN");
                $this->pushBlock("IF");
                continue;
            }

            // ============================================================
            // ELSEIF
            // ============================================================
            if (preg_match('/^elseif\s*\((.+)\)\s*\{?$/', $trim, $m)) {
                $this->popUntil(["IF", "ELSE"]);
                $cond = aph_normalize_php_condition($m[1]);
                $out[] = $this->indentLine("ELSE IF {$cond} THEN");
                $this->pushBlock("IF");
                continue;
            }

            // ============================================================
            // ELSE { }
            // ============================================================
            if (preg_match('/^else\s*\{?$/', $trim)) {
                $this->popUntil(["IF"]);
                $out[] = $this->indentLine("ELSE");
                $this->pushBlock("ELSE");
                continue;
            }

            // ============================================================
            // CLOSING BRACE }
            // ============================================================
            if ($trim === "}") {

                $blk = $this->popBlock();

                if ($blk === "IF" || $blk === "ELSE") {
                    $out[] = $this->indentLine("END IF");
                }
                else if ($blk === "REPEAT") {
                    $out[] = $this->indentLine("END REPEAT");
                }
                else if ($blk === "FUNCTION") {
                    $out[] = $this->indentLine("END FUNCTION");
                }
                else {
                    $out[] = $this->indentLine("COMMENT UNKNOWN: }");
                }

                continue;
            }

            // ============================================================
            // FOR LOOP (simple numeric)
            // ============================================================
            if (preg_match('/^for\s*\(\s*\$(\w+)\s*=\s*(\d+);\s*\$\1\s*<\s*(\d+);/', $trim, $m)) {
                $times = intval($m[3]);
                $out[] = $this->indentLine("REPEAT {$times} TIMES");
                $this->pushBlock("REPEAT");
                continue;
            }

            // ============================================================
            // FOREACH
            // ============================================================
            if (preg_match('/^foreach\s*\((.+)\)\s*\{?$/', $trim, $m)) {
                $inside = preg_replace('/\s+as\s+/i', ' AS ', $m[1]);
                $out[] = $this->indentLine("REPEAT OVER {$inside}");
                $this->pushBlock("REPEAT");
                continue;
            }

            // ============================================================
            // FUNCTION fn(args)
            // ============================================================
            if (preg_match('/^function\s+(\w+)\s*\((.*)\)\s*\{?$/', $trim, $m)) {

                $name = $m[1];
                $argsRaw = trim($m[2]);

                if ($argsRaw === "") {
                    $args = "";
                } else {
                    $args = implode(", ", array_map(function($a) {
                        return aph_strip(trim($a));
                    }, explode(",", $argsRaw)));
                }

                $out[] = $this->indentLine("FUNCTION {$name}({$args})");
                $this->pushBlock("FUNCTION");
                continue;
            }

            // ============================================================
            // RETURN
            // ============================================================
            if (preg_match('/^return\s+(.+);$/i', $trim, $m)) {
                $val = trim($m[1]);
                if (str_starts_with($val, '$')) $val = aph_strip($val);
                $out[] = $this->indentLine("RETURN {$val}");
                continue;
            }

            // ============================================================
            // INCLUDE / REQUIRE
            // ============================================================
            if (preg_match('/^(require|include)(_once)?\s+[\'"](.+)[\'"]\s*;$/i', $trim, $m)) {
                $out[] = $this->indentLine("IMPORT FILE \"{$m[3]}\"");
                continue;
            }

            // ============================================================
            // FALLBACK → COMMENT UNKNOWN
            // ============================================================
            $out[] = $this->indentLine("COMMENT UNKNOWN: {$trim}");
        }

        // Close any remaining open blocks
        while (($blk = $this->popBlock()) !== null) {
            if ($blk === "IF" || $blk === "ELSE") $out[] = "END IF";
            else if ($blk === "REPEAT") $out[] = "END REPEAT";
            else if ($blk === "FUNCTION") $out[] = "END FUNCTION";
        }

        return implode("\n", $out);
    }



    // ============================================================
    // HELPERS
    // ============================================================

    private function isAPH(string $line): bool {
        $aph = [
            "DISPLAY", "SET", "IF", "ELSE IF", "ELSE",
            "END IF", "REPEAT", "END REPEAT",
            "FUNCTION", "END FUNCTION", "RETURN",
            "COMMENT", "IMPORT FILE"
        ];
        foreach ($aph as $k) {
            if (str_starts_with($line, $k)) return true;
        }
        return false;
    }

    private function updateStackForAPH(string $line): void {
        if (str_starts_with($line, "IF")) $this->pushBlock("IF");
        if (str_starts_with($line, "ELSE")) $this->pushBlock("ELSE");
        if (str_starts_with($line, "REPEAT")) $this->pushBlock("REPEAT");
        if (str_starts_with($line, "FUNCTION")) $this->pushBlock("FUNCTION");

        if (str_starts_with($line, "END IF")) $this->popBlock();
        if (str_starts_with($line, "END REPEAT")) $this->popBlock();
        if (str_starts_with($line, "END FUNCTION")) $this->popBlock();
    }

    private function indentLine(string $line): string {
        return str_repeat("    ", $this->indent) . $line;
    }

    private function pushBlock(string $blk): void {
        $this->stack[] = $blk;
        $this->indent = count($this->stack);
    }

    private function popBlock(): ?string {
        if (empty($this->stack)) return null;
        $blk = array_pop($this->stack);
        $this->indent = count($this->stack);
        return $blk;
    }

    private function popUntil(array $types): void {
        while (!empty($this->stack)) {
            $top = end($this->stack);
            if (in_array($top, $types, true)) {
                array_pop($this->stack);
                break;
            }
            array_pop($this->stack);
        }
        $this->indent = count($this->stack);
    }
}
