<?php

require_once __DIR__ . '/Translator.php';

/**
 * Reverse Compiler — Beautified, stack-based version
 * Converts PHP / mixed source into CLEAN APH (.IMAM) output.
 */

class APH_ReverseCompiler {

    private array $stack = [];   // track blocks: 'IF','REPEAT','FUNCTION','ELSE'...
    private int $indent = 0;

    /**
     * Convert any source into APH (clean, indented)
     */
    public function toAPH(string $code): string {
        $lines = preg_split("/\r\n|\n|\r/", $code);
        $output = [];

        foreach ($lines as $raw) {
            $trim = trim($raw);

            // preserve blank lines
            if ($trim === "") {
                $output[] = "";
                continue;
            }

            // If already APH syntax → format and update stack accordingly
            if ($this->isAPH($trim)) {
                $line = $this->formatAPHLine($trim);
                $output[] = $line;
                // update internal stack if it's an opening/closing APH token
                $this->updateStackForAPH($trim);
                continue;
            }

            // VARIABLE ASSIGNMENT: $name = value;
            if (preg_match('/^\$(\w+)\s*=\s*(.+);$/', $trim, $m)) {
                $var = $m[1];
                $val = trim($m[2]);

                // normalize value: strip $ if variable, keep strings/numbers
                if (str_starts_with($val, '$')) {
                    $val = aph_strip($val);
                }
                $line = "SET {$var} TO {$val}";
                $output[] = $this->indentLine($line);
                continue;
            }

            // ECHO -> DISPLAY
            if (preg_match('/^echo\s+(.+);$/', $trim, $m)) {
                $expr = trim($m[1]);
                $line = "DISPLAY {$expr}";
                $output[] = $this->indentLine($line);
                continue;
            }

            // IF statement: if ( ... ) {
            if (preg_match('/^if\s*\((.+)\)\s*\{?$/', $trim, $m)) {
                $condRaw = $m[1];
                $cond = aph_normalize_php_condition($condRaw);
                $line = "IF {$cond} THEN";
                $output[] = $this->indentLine($line);
                // push IF block
                $this->pushBlock('IF');
                continue;
            }

            // ELSEIF: } elseif (...) {
            if (preg_match('/^\}?\s*elseif\s*\((.+)\)\s*\{?$/', $trim, $m)) {
                // Pop previous IF/ELSE block if present (we're replacing it with ELSE IF)
                $this->popUntil(['IF','ELSE','ELSE_IF','ELSEIF','ELSE IF']);
                $condRaw = $m[1];
                $cond = aph_normalize_php_condition($condRaw);
                $line = "ELSE IF {$cond} THEN";
                $output[] = $this->indentLine($line);
                $this->pushBlock('IF'); // continue an IF-style block
                continue;
            }

            // ELSE: } else {
            if (preg_match('/^\}?\s*else\s*\{?$/', $trim)) {
                // close any previous IF block context for formatting
                $this->popUntil(['IF','ELSE']);
                $line = "ELSE";
                $output[] = $this->indentLine($line);
                $this->pushBlock('ELSE');
                continue;
            }

            // CLOSING BRACE: }  -> end the topmost block
            if ($trim === "}" || $trim === "});") {
                $top = $this->popBlock();
                if ($top === null) {
                    // nothing to close: emit a generic comment
                    $output[] = $this->indentLine("COMMENT UNKNOWN: }");
                    continue;
                }

                // produce the correct END token
                switch ($top) {
                    case 'IF':
                    case 'ELSE':
                        $output[] = $this->indentLine("END IF");
                        break;
                    case 'REPEAT':
                        $output[] = $this->indentLine("END REPEAT");
                        break;
                    case 'FUNCTION':
                        $output[] = $this->indentLine("END FUNCTION");
                        break;
                    default:
                        $output[] = $this->indentLine("COMMENT END {$top}");
                }
                continue;
            }

            // FOR loop (simple numeric form): for ($i=0; $i < N; $i++)
            if (preg_match('/^for\s*\(\s*\$(\w+)\s*=\s*(\d+)\s*;\s*\$\1\s*<\s*(\d+)\s*;.*\)\s*\{?$/', $trim, $m)) {
                $times = intval($m[3]);
                $line = "REPEAT {$times} TIMES";
                $output[] = $this->indentLine($line);
                $this->pushBlock('REPEAT');
                continue;
            }

            // FOREACH (basic): foreach ($arr as $v) {
            if (preg_match('/^foreach\s*\((.+)\)\s*\{?$/', $trim, $m)) {
                // We output a generic REPEAT over items (simple)
                $inside = $m[1];
                $inside = preg_replace('/\s+as\s+/', ' AS ', $inside); // cosmetic
                $line = "REPEAT OVER {$inside}";
                $output[] = $this->indentLine($line);
                $this->pushBlock('REPEAT');
                continue;
            }

            // FUNCTION definition: function name($a, $b) {
            if (preg_match('/^function\s+(\w+)\s*\((.*)\)\s*\{?$/', $trim, $m)) {
                $name = $m[1];
                $args = trim($m[2]);
                // strip $ from args
                $args = $args === "" ? "" : implode(", ", array_map(function($a){
                    return aph_strip(trim($a));
                }, array_map('trim', explode(",", $args))));
                $line = "FUNCTION {$name}(" . $args . ")";
                $output[] = $this->indentLine($line);
                $this->pushBlock('FUNCTION');
                continue;
            }

            // RETURN statement
            if (preg_match('/^return\s+(.+);$/', $trim, $m)) {
                $val = trim($m[1]);
                if (str_starts_with($val, '$')) {
                    $val = aph_strip($val);
                }
                $line = "RETURN {$val}";
                $output[] = $this->indentLine($line);
                continue;
            }

            // require/include -> IMPORT FILE (best effort)
            if (preg_match('/^(require|include)(_once)?\s+[\'"](.+)[\'"]\s*;$/', $trim, $m)) {
                $file = $m[3];
                $line = "IMPORT FILE \"{$file}\"";
                $output[] = $this->indentLine($line);
                continue;
            }

            // Comments (// or /* */) -> convert to APH COMMENT
            if (str_starts_with($trim, "//")) {
                $text = trim(substr($trim, 2));
                $output[] = $this->indentLine("COMMENT " . $text);
                continue;
            }

            if (preg_match('#^/\*(.*)\*/$#', $trim, $m)) {
                $output[] = $this->indentLine("COMMENT " . trim($m[1]));
                continue;
            }

            // FALLBACK: unknown line -> comment it
            $output[] = $this->indentLine("COMMENT UNKNOWN: {$trim}");
        }

        // Ensure stack is closed cleanly at EOF
        while (($blk = $this->popBlock()) !== null) {
            switch ($blk) {
                case 'IF':
                case 'ELSE':
                    $output[] = $this->indentLine("END IF");
                    break;
                case 'REPEAT':
                    $output[] = $this->indentLine("END REPEAT");
                    break;
                case 'FUNCTION':
                    $output[] = $this->indentLine("END FUNCTION");
                    break;
                default:
                    $output[] = $this->indentLine("COMMENT END {$blk}");
            }
        }

        // Post-process output: ensure blank line separation between top-level blocks for readability
        $pretty = $this->padBlocks($output);

        return implode("\n", $pretty);
    }

    /**
     * Is this line already APH?
     */
    private function isAPH(string $line): bool {
        $aphKeywords = [
            "DISPLAY", "SET", "IF", "ELSE IF", "ELSE", "END IF",
            "REPEAT", "END REPEAT", "FUNCTION", "END FUNCTION",
            "RETURN", "COMMENT", "IMPORT FILE"
        ];
        foreach ($aphKeywords as $k) {
            if (str_starts_with($line, $k)) return true;
        }
        return false;
    }

    /**
     * Format an APH line with current indentation, without modifying stack here.
     */
    private function formatAPHLine(string $line): string {
        return $this->indentLine($line);
    }

    /**
     * Update stack when encountering APH input lines (openers / closers).
     * Keeps behavior consistent if user already writes APH in the source.
     */
    private function updateStackForAPH(string $line): void {
        $trim = trim($line);
        if (preg_match('/^(IF|REPEAT|FUNCTION)\b/', $trim)) {
            // openers
            if (preg_match('/^IF\b/', $trim)) $this->pushBlock('IF');
            if (preg_match('/^REPEAT\b/', $trim)) $this->pushBlock('REPEAT');
            if (preg_match('/^FUNCTION\b/', $trim)) $this->pushBlock('FUNCTION');
        } elseif (preg_match('/^END (IF|REPEAT|FUNCTION)\b/', $trim)) {
            // closers
            $this->popBlock();
        } elseif (preg_match('/^ELSE\b/', $trim)) {
            // treat ELSE as a block replacement for IF
            $this->popUntil(['IF','ELSE']);
            $this->pushBlock('ELSE');
        }
    }

    /**
     * Push block and increase indent
     */
    private function pushBlock(string $name): void {
        $this->stack[] = $name;
        $this->indent = max(0, count($this->stack));
    }

    /**
     * Pop block and return its name (or null if empty)
     */
    private function popBlock(): ?string {
        if (empty($this->stack)) return null;
        $top = array_pop($this->stack);
        $this->indent = max(0, count($this->stack));
        return $top;
    }

    /**
     * Pop until any of the given types are removed (used for elseif/else transitions)
     */
    private function popUntil(array $types): void {
        while (!empty($this->stack)) {
            $top = end($this->stack);
            if (in_array($top, $types, true)) {
                array_pop($this->stack);
                break;
            }
            array_pop($this->stack);
        }
        $this->indent = max(0, count($this->stack));
    }

    /**
     * Indent a line according to current indent level
     */
    private function indentLine(string $line): string {
        return str_repeat("    ", max(0, $this->indent - 1)) . $line;
        // note: indent-1 so top-level (stack size 1) has no leading indent
    }

    /**
     * Ensure blank lines between major blocks for readability
     */
    private function padBlocks(array $lines): array {
        $out = [];
        $prevEmpty = false;

        foreach ($lines as $i => $l) {

            // If line is a block opener or a SET/DISPLAY at top level, ensure separation
            $trim = trim($l);

            if ($trim === "") {
                $out[] = "";
                $prevEmpty = true;
                continue;
            }

            // Insert blank line before top-level blocks (FUNCTION, SET, IF, REPEAT, IMPORT FILE)
            if (!$prevEmpty && preg_match('/^(FUNCTION|SET|DISPLAY|IF|REPEAT|IMPORT FILE)/', $trim)) {
                // Only add blank line if previous line exists
                if (!empty($out)) $out[] = "";
            }

            $out[] = $l;
            $prevEmpty = ($trim === "");
        }

        return $out;
    }
}
