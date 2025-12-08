<?php

require __DIR__ . '/../src/Runtime.php';

//  Basic APH v1 language test

echo "=== APH TEST START ===";

$age = 20;
echo "Age is: " . $age;

if ($age > 18) {
echo "Person is an adult";
} else {
echo "Person is a minor";
}

function greet($name) {
echo "Hello, " . $name;
}

echo "\nFrom RAW PHP: ";
echo aph_version();

echo "Calling greet: ";
echo greet("Imam");

echo "=== APH TEST END ===";

