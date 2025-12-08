⭐ APH — English Powered Hypercode
Programming in real English.

APH is a human-friendly programming language that compiles English instructions into real PHP code.
You write in English → APH converts it → PHP runs it.

This document explains every APH v1 keyword and what it does.

⭐ 1. DISPLAY

Purpose: Print text, variables, or function calls.

Syntax:
DISPLAY "Hello"
DISPLAY name
DISPLAY "Age: " AND age
DISPLAY greet("Imam")

What PHP does:
echo "Hello";
echo $name;
echo "Age: " . $age;
echo greet("Imam");

⭐ 2. SET

Purpose: Assign values to variables.

Syntax:
SET age TO 20
SET name TO "Imam"
SET result TO add(2, 3)

PHP equivalent:
$age = 20;
$name = "Imam";
$result = add(2, 3);


Variables in APH automatically become $variables in PHP.

⭐ 3. IF / ELSE IF / ELSE

Purpose: Conditional logic using English comparisons.

Syntax:
IF age IS GREATER THAN 18 THEN
    DISPLAY "Adult"
ELSE IF age IS LESS THAN 18 THEN
    DISPLAY "Minor"
ELSE
    DISPLAY "Unknown"
END IF

Supported comparisons:
APH English	PHP Operator
IS GREATER THAN	>
IS LESS THAN	<
IS EQUAL TO	==
IS NOT EQUAL TO	!=
IS GREATER OR EQUAL TO	>=
IS LESS OR EQUAL TO	<=
Logical operators:
APH	PHP
AND ALSO	&&
OR ELSE	||
PHP output:
if ($age > 18) {
    echo "Adult";
} elseif ($age < 18) {
    echo "Minor";
} else {
    echo "Unknown";
}

⭐ 4. REPEAT

Purpose: Run a block of code multiple times.

Syntax:
REPEAT 5 TIMES
    DISPLAY "Hello"
END REPEAT

PHP:
for ($i1234 = 0; $i1234 < 5; $i1234++) {
    echo "Hello";
}


Random loop variable names ($i1234) ensure no conflicts.

⭐ 5. FUNCTION

Purpose: Define reusable code blocks.

Syntax:
FUNCTION greet(name)
    DISPLAY "Hello, " AND name
END FUNCTION

PHP:
function greet($name) {
    echo "Hello, " . $name;
}


Functions can take parameters (they become $variables inside PHP).

⭐ 6. RETURN

Purpose: Return a value from a function.

Syntax:
RETURN name
RETURN 10
RETURN add(3, 5)

PHP:
return $name;
return 10;
return add(3, 5);

⭐ 7. IMPORT FILE

Purpose: Load another PHP or APH-generated file.

Syntax:
IMPORT FILE "utils.php"

PHP:
require "utils.php";

⭐ 8. COMMENT

Purpose: Add notes for developers (not executed).

Syntax:
COMMENT This is a test

PHP:
// This is a test

⭐ 9. TRUE / FALSE

Boolean constants.

Syntax:
SET isAdult TO TRUE
SET isHuman TO FALSE

PHP:
$isAdult = true;
$isHuman = false;

⭐ 10. RAW PHP BLOCK

Purpose: Write normal PHP inside APH.

Syntax:
PHP:
echo "Hello from PHP!";
END PHP


Everything between PHP: and END PHP is passed to PHP untouched.

⭐ APH SUMMARY TABLE
APH Keyword	Meaning	PHP Equivalent
DISPLAY	Print something	echo
SET	Assign variable	=
IF … THEN	Condition	if () {
ELSE IF … THEN	Condition	} elseif () {
ELSE	Else	} else {
END IF	End block	}
REPEAT N TIMES	Loop	for (…)
END REPEAT	End loop	}
FUNCTION name(args)	Define function	function name($arg)
END FUNCTION	End function	}
RETURN	Return value	return
IMPORT FILE	Include file	require
COMMENT	Comment	//
TRUE/FALSE	Booleans	true/false
PHP: … END PHP	Raw PHP block	untouched
⭐ APH Philosophy
✔ Readable by humans
✔ Zero symbols in English layer
✔ Beginner-friendly
✔ Converts cleanly to PHP
✔ Fully interoperable with PHP
✔ Safe and predictable

APH is designed so that even a child can read your code, but a professional system can run it at full speed.

⭐ What’s Next

APH v1 already works. Coming soon in v1.2+:

Function calls inside DISPLAY (done!)

Array syntax

WHILE loops

FOR EACH loops

Modules (IMPORT MODULE "account")

Classes (APH v2)

Custom macros (CamelCase rules)