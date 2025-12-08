<?php

require __DIR__ . '/../src/Runtime.php';

<?php

require __DIR__ . '/../src/Runtime.php';

$a = 10;
$b = 20;

echo $a . " + " . $b . " = " . ($a + $b);

if ($a < $b) {
    echo "a is smaller";
// UNKNOWN APH LINE: } else {
    echo "a is NOT smaller";
// UNKNOWN APH LINE: }

$x = 5;
$y = 3;
echo $x + $y;
echo "\n";

function add($val1, $val2) {
    return $val1 + $val2;
// UNKNOWN APH LINE: }

echo add(7, 8);

echo "Line1" . PHP_EOL . "Line2";



