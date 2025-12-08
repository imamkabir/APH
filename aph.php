<?php

require_once __DIR__ . "/src/Compiler.php";

$compiler = new APH_Compiler();

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

// Load APH source code
$aphCode = file_get_contents($input);

// Compile APH -> PHP
$phpCode = $compiler->compile($aphCode);

// OUTPUT FILENAME = same as .aph but in /cache
$base = basename($input, ".aph");
$outputFile = __DIR__ . "/cache/" . $base . ".php";

// Save compiled PHP
file_put_contents($outputFile, $phpCode);

// Execute compiled PHP
echo "Running: $outputFile\n\n";
system("php $outputFile");
