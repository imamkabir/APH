<?php

require_once __DIR__ . "/src/Compiler.php";
require_once __DIR__ . "/src/ReverseCompiler.php";

$compiler = new APH_Compiler();
$reverse  = new APH_ReverseCompiler();

$input = $argv[1] ?? null;

// Check if filename is provided
if (!$input) {
    echo "Usage: php aph.php file.aph\n";
    exit;
}

// Check if file exists
if (!file_exists($input)) {
    echo "Error: File not found: $input\n";
    exit;
}

// Load APH/PHP/mixed source code
$aphCode = file_get_contents($input);

// ----------------------------------------
// 1️⃣ COMPILE → PHP
// ----------------------------------------
$phpCode = $compiler->compile($aphCode);

// Name base (without extension)
$base = pathinfo($input, PATHINFO_FILENAME);

// Save compiled PHP
$phpFile = __DIR__ . "/cache/" . $base . ".php";
file_put_contents($phpFile, $phpCode);

// ----------------------------------------
// 2️⃣ REVERSE COMPILE → PURE APH
// ----------------------------------------
$imamCode = $reverse->toAPH($aphCode);

$imamFile = __DIR__ . "/cache/" . $base . ".IMAM";
file_put_contents($imamFile, $imamCode);

// ----------------------------------------
// RUN THE PHP FILE
// ----------------------------------------
echo "Generated Files:\n";
echo " - PHP OUT:   $phpFile\n";
echo " - APH OUT:   $imamFile\n\n";

echo "Running PHP Output:\n";
system("php $phpFile");
