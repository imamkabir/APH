# APH — Write PHP in Plain English

APH (English Powered Hypercode) is a natural-language programming tool that lets you write code in plain English and compiles it into executable PHP.

It is designed to make programming more accessible, readable, and intuitive — especially for beginners and rapid prototyping.

---

## ✨ Example

**Input (APH):**

```
SET name TO "Imam"
DISPLAY "Hello, " AND name
```

**Output (PHP):**

```php
$name = "Imam";
echo "Hello, " . $name;
```

---

## 🚀 Features

* Write programs in plain English
* Automatically compiles to valid PHP
* Beginner-friendly syntax
* Supports variables, conditions, loops, and functions
* Allows raw PHP when needed
* Designed for learning and experimentation

---

## ⚡ Quick Start

### 1. Clone the repository

```
git clone https://github.com/imamkabir/APH.git
cd APH
```

### 2. Run an APH file

```
php aph.php test.aph
```

### 3. Output

APH will translate your `.aph` file into PHP and execute it.

---

## 📚 Basic Syntax

### Variables

```
SET age TO 20
SET name TO "Imam"
```

### Output

```
DISPLAY "Hello"
DISPLAY name
```

### Conditions

```
IF age IS GREATER THAN 18 THEN
    DISPLAY "Adult"
ELSE
    DISPLAY "Minor"
END IF
```

### Loops

```
REPEAT 5 TIMES
    DISPLAY "Hello"
END REPEAT
```

### Functions

```
FUNCTION greet(name)
    DISPLAY "Hello, " AND name
END FUNCTION
```

---

## 🎯 Use Cases

* Learning programming fundamentals
* Teaching coding in a human-readable way
* Rapid prototyping using natural language
* Experimenting with alternative programming interfaces

---

## 🧠 Project Vision

APH explores the idea that programming can be written and understood like natural language.

The goal is to reduce the barrier to entry for coding while maintaining compatibility with real-world systems like PHP.

---

## 📦 Project Status

Current Version: v1.x
Status: Active development

Planned features:

* Arrays support
* Advanced loops (WHILE, FOR EACH)
* Modules and imports
* Improved parser accuracy
* Class support (v2)

---

## 🤝 Contributing

Contributions are welcome.

You can help by:

* Improving the parser
* Adding new language features
* Fixing bugs
* Writing documentation

---

## 📄 License

MIT License

---

## 👤 Author

Created by Imam Kabir
