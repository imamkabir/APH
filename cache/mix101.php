<?php

require __DIR__ . '/../src/Runtime.php';

//  Testing APH + PHP mixed mode
echo "Starting MIX test...";
$name = "Imam";
echo "APH Name: " . $name;
echo "\nInside RAW PHP block\n";
$phpAge = 25;
echo "PHP age is: $phpAge\n";
echo "Back to APH. Adding APH values and PHP values:";
echo $name . " is " . $phpAge . " years old";
function hello_php() {
    return "Hello from normal PHP function!";
}
echo hello_php();
echo "Ending MIX test.";
