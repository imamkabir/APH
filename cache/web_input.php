<?php

require __DIR__ . '/../src/Runtime.php';

echo "Starting program...";

$name = "Imam";
$age = 16;

if ($age > 10 && $age < 20) {
    echo "Teenager";
// UNKNOWN APH LINE: } else {
    echo "Not a teen";
// UNKNOWN APH LINE: }

for ($i2836 = 0; $i2836 < 3; $i2836++) {
echo "Looping APH block!";
}

for ($i = 0; $i < 5; $i++) {
    echo "PHP Loop: " . $i;
// UNKNOWN APH LINE: }

function greet($x) {
    echo "Hello, " . $x;
    if ($x == "Imam") {
        echo "You are the creator of APH!";
// UNKNOWN APH LINE: }
// UNKNOWN APH LINE: }

$items = ["apple", "banana", "pear"];
foreach ($items as $item) {
    echo $item;
// UNKNOWN APH LINE: }

return "Program Finished";

