<?php

header("Content-Type: application/json");

// 1. Get POST data
$code = $_POST['code'] ?? '';
$lang = $_POST['lang'] ?? 'aph';

// 2. Temp APH file location
$tmpBase = "web_input";
$tmpFile = "../cache/$tmpBase.aph";

// 3. Save user's code as APH file
// If lang = PHP → treat it as hybrid and still save as .aph
file_put_contents($tmpFile, $code);

// 4. Run APH compiler
// Using `php ../aph.php`
ob_start();
system("php ../aph.php $tmpFile 2>&1");
$cliOutput = ob_get_clean();

// 5. Paths to generated outputs
$phpFile  = "../cache/$tmpBase.php";
$imamFile = "../cache/$tmpBase.IMAM";

// 6. Load outputs
$phpOut  = file_exists($phpFile)  ? file_get_contents($phpFile)  : "No PHP output generated.";
$aphOut  = file_exists($imamFile) ? file_get_contents($imamFile) : "No APH(.IMAM) output generated.";

// 7. Send result back as JSON
echo json_encode([
    "php" => $phpOut,
    "aph" => $aphOut,
    "cli" => $cliOutput
]);
