<?php
require_once 'config/config.php';
require_once 'includes/icons.php';
require_once 'includes/language-icons.php';
requireLogin();

$pageTitle = 'Code Playground - ' . APP_NAME;

// Templates stored as PHP arrays to avoid JS escaping issues
$templates = [
    'hello-world' => '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hello World</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        h1 {
            color: white;
            font-size: 3rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <h1>Hello World!</h1>
</body>
</html>',

    'flexbox' => '<!DOCTYPE html>
<html>
<head>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; padding: 20px; background: #f0f0f0; }
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }
        .box {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            border-radius: 8px;
            transition: transform 0.3s;
        }
        .box:hover { transform: scale(1.1); }
        .box:nth-child(1) { background: #e74c3c; }
        .box:nth-child(2) { background: #3498db; }
        .box:nth-child(3) { background: #2ecc71; }
        .box:nth-child(4) { background: #f39c12; }
        .box:nth-child(5) { background: #9b59b6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">Box 1</div>
        <div class="box">Box 2</div>
        <div class="box">Box 3</div>
        <div class="box">Box 4</div>
        <div class="box">Box 5</div>
    </div>
</body>
</html>',

    'form' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; padding: 30px; background: #1a1a2e; }
        .form-container {
            max-width: 400px;
            margin: 0 auto;
            background: #16213e;
            padding: 30px;
            border-radius: 12px;
        }
        h2 { color: #e94560; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; color: #fff; margin-bottom: 5px; }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #0f3460;
            border-radius: 6px;
            background: #0f3460;
            color: white;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #e94560;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .error { color: #ff6b6b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Login Form</h2>
        <form id="loginForm" onsubmit="return validateForm()">
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="email" required>
                <span class="error" id="emailError"></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="password" required>
                <span class="error" id="passError"></span>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
    <script>
        function validateForm() {
            var email = document.getElementById("email").value;
            var pass = document.getElementById("password").value;
            var valid = true;
            
            document.getElementById("emailError").textContent = "";
            document.getElementById("passError").textContent = "";
            
            if (email.indexOf("@") === -1) {
                document.getElementById("emailError").textContent = "Invalid email";
                valid = false;
            }
            if (pass.length < 6) {
                document.getElementById("passError").textContent = "Min 6 chars";
                valid = false;
            }
            if (valid) alert("Login successful!");
            return false;
        }
    </script>
</body>
</html>',

    'animation' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #0f0f23;
        }
        .loader {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 3px solid transparent;
            border-radius: 50%;
        }
        .circle:nth-child(1) {
            border-top-color: #ff6b6b;
            animation: spin 1s linear infinite;
        }
        .circle:nth-child(2) {
            border-right-color: #4ecdc4;
            animation: spin 1.5s linear infinite;
        }
        .circle:nth-child(3) {
            border-bottom-color: #ffe66d;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .text {
            color: white;
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            font-family: sans-serif;
        }
    </style>
</head>
<body>
    <div class="loader">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <span class="text">Loading...</span>
    </div>
</body>
</html>',

    'calculator' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
        }
        .calculator {
            background: #0f3460;
            padding: 20px;
            border-radius: 15px;
        }
        .display {
            background: #1a1a2e;
            color: #e94560;
            font-size: 2rem;
            padding: 15px;
            text-align: right;
            border-radius: 8px;
            margin-bottom: 15px;
            min-height: 60px;
            font-family: monospace;
        }
        .buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        button {
            padding: 18px;
            font-size: 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .num { background: #16213e; color: white; }
        .op { background: #e94560; color: white; }
        .clear { background: #533483; color: white; grid-column: span 2; }
        .equals { background: #2ecc71; color: white; }
    </style>
</head>
<body>
    <div class="calculator">
        <div class="display" id="display">0</div>
        <div class="buttons">
            <button class="clear" onclick="clearD()">C</button>
            <button class="op" onclick="addOp(\'/\')">÷</button>
            <button class="op" onclick="addOp(\'*\')">×</button>
            <button class="num" onclick="addNum(\'7\')">7</button>
            <button class="num" onclick="addNum(\'8\')">8</button>
            <button class="num" onclick="addNum(\'9\')">9</button>
            <button class="op" onclick="addOp(\'-\')">-</button>
            <button class="num" onclick="addNum(\'4\')">4</button>
            <button class="num" onclick="addNum(\'5\')">5</button>
            <button class="num" onclick="addNum(\'6\')">6</button>
            <button class="op" onclick="addOp(\'+\')">+</button>
            <button class="num" onclick="addNum(\'1\')">1</button>
            <button class="num" onclick="addNum(\'2\')">2</button>
            <button class="num" onclick="addNum(\'3\')">3</button>
            <button class="equals" onclick="calc()">=</button>
            <button class="num" onclick="addNum(\'0\')">0</button>
            <button class="num" onclick="addNum(\'.\')">.</button>
        </div>
    </div>
    <script>
        var current = "0";
        function show() { document.getElementById("display").textContent = current; }
        function addNum(n) {
            if (current === "0" && n !== ".") current = n;
            else current += n;
            show();
        }
        function addOp(op) { current += " " + op + " "; show(); }
        function clearD() { current = "0"; show(); }
        function calc() {
            try { current = String(eval(current)); show(); }
            catch(e) { current = "Error"; show(); }
        }
    </script>
</body>
</html>',

    'js-array' => 'javascript',
    'js-fetch' => 'javascript',
    'py-basics' => 'python'
];

// JavaScript templates (run in browser)
$jsTemplates = [
    'js-array' => '// Array Methods Demo
var fruits = ["Apple", "Banana", "Orange", "Mango"];

console.log("Original array:", fruits);

// Add item
fruits.push("Grape");
console.log("After push:", fruits);

// Remove last item  
var removed = fruits.pop();
console.log("Removed:", removed);
console.log("After pop:", fruits);

// Filter
var longNames = fruits.filter(function(f) { 
    return f.length > 5; 
});
console.log("Long names (>5 chars):", longNames);

// Map
var upperFruits = fruits.map(function(f) { 
    return f.toUpperCase(); 
});
console.log("Uppercase:", upperFruits);

// Find
var found = fruits.find(function(f) { 
    return f.startsWith("O"); 
});
console.log("Starts with O:", found);',

    'js-fetch' => '// Fetch API Demo (simulated)
console.log("Fetching data...");

// Simulate API response
var users = [
    { id: 1, name: "John Doe", email: "john@example.com" },
    { id: 2, name: "Jane Smith", email: "jane@example.com" },
    { id: 3, name: "Bob Wilson", email: "bob@example.com" }
];

console.log("Users fetched:");
users.forEach(function(user) {
    console.log("- " + user.name + " (" + user.email + ")");
});

// Object manipulation
var firstUser = users[0];
console.log("\\nFirst user details:");
console.log("  ID:", firstUser.id);
console.log("  Name:", firstUser.name);
console.log("  Email:", firstUser.email);

// Array reduce
var emailList = users.map(function(u) { return u.email; });
console.log("\\nAll emails:", emailList);'
];

// Python templates
$pyTemplates = [
    'py-basics' => '# Python Basics Demo
print("=== Python Basics ===")

# Variables
name = "Python"
version = 3.11
is_awesome = True

print(f"Language: {name}")
print(f"Version: {version}")
print(f"Is awesome: {is_awesome}")

# List operations
print("\\n=== List Operations ===")
numbers = [1, 2, 3, 4, 5]
print(f"Numbers: {numbers}")
print(f"Sum: {sum(numbers)}")
print(f"Max: {max(numbers)}")
print(f"Min: {min(numbers)}")

# List comprehension
squares = [x**2 for x in numbers]
print(f"Squares: {squares}")

# Dictionary
print("\\n=== Dictionary ===")
person = {
    "name": "Alice",
    "age": 25,
    "city": "Jakarta"
}
for key, value in person.items():
    print(f"  {key}: {value}")'
];

// Merge templates
$templates = array_merge($templates, $jsTemplates, $pyTemplates);

// Default code for each language
$defaultCodes = [
    'html' => $templates['hello-world'],
    'javascript' => '// JavaScript Playground
// Tulis kode JavaScript kamu di sini

function greet(name) {
    return "Hello, " + name + "!";
}

console.log(greet("World"));
console.log("2 + 2 =", 2 + 2);

// Array example
var numbers = [1, 2, 3, 4, 5];
var doubled = numbers.map(function(n) { return n * 2; });
console.log("Doubled:", doubled);',
    'python' => '# Python Playground
# Tulis kode Python kamu di sini

def greet(name):
    return f"Hello, {name}!"

print(greet("World"))
print("2 + 2 =", 2 + 2)

# List example
numbers = [1, 2, 3, 4, 5]
doubled = [n * 2 for n in numbers]
print("Doubled:", doubled)',
    'php' => '<?php
// PHP Playground
// Tulis kode PHP kamu di sini

function greet($name) {
    return "Hello, " . $name . "!";
}

echo greet("World") . "\n";
echo "2 + 2 = " . (2 + 2) . "\n";

// Array example
$numbers = [1, 2, 3, 4, 5];
$doubled = array_map(fn($n) => $n * 2, $numbers);
print_r($doubled);
?>',
    'java' => '// Java Playground
public class Main {
    public static void main(String[] args) {
        System.out.println("Hello, World!");
        System.out.println("2 + 2 = " + (2 + 2));
        
        // Array example
        int[] numbers = {1, 2, 3, 4, 5};
        System.out.print("Numbers: ");
        for (int n : numbers) {
            System.out.print(n + " ");
        }
        System.out.println();
    }
}',
    'cpp' => '// C++ Playground
#include <iostream>
#include <vector>
using namespace std;

int main() {
    cout << "Hello, World!" << endl;
    cout << "2 + 2 = " << (2 + 2) << endl;
    
    // Vector example
    vector<int> numbers = {1, 2, 3, 4, 5};
    cout << "Numbers: ";
    for (int n : numbers) {
        cout << n << " ";
    }
    cout << endl;
    
    return 0;
}'
];

// PHP Templates
$phpTemplates = [
    'php-hello' => '<?php
// Hello World PHP
echo "Hello, World!\n";
echo "Selamat belajar PHP!\n";

// Variabel
$nama = "Prozone";
$tahun = 2025;

echo "Nama: $nama\n";
echo "Tahun: $tahun\n";
?>',

    'php-array' => '<?php
// PHP Array Operations

// Indexed Array
$buah = ["Apel", "Jeruk", "Mangga", "Pisang"];
echo "=== Indexed Array ===\n";
foreach ($buah as $index => $item) {
    echo "[$index] $item\n";
}

// Associative Array
$mahasiswa = [
    "nama" => "Budi",
    "nim" => "12345",
    "jurusan" => "Informatika"
];

echo "\n=== Associative Array ===\n";
foreach ($mahasiswa as $key => $value) {
    echo "$key: $value\n";
}

// Array Functions
echo "\n=== Array Functions ===\n";
$numbers = [5, 2, 8, 1, 9];
echo "Original: " . implode(", ", $numbers) . "\n";

sort($numbers);
echo "Sorted: " . implode(", ", $numbers) . "\n";

echo "Sum: " . array_sum($numbers) . "\n";
echo "Count: " . count($numbers) . "\n";
?>',

    'php-function' => '<?php
// PHP Functions

// Basic function
function greet($name) {
    return "Hello, $name!";
}

echo greet("Developer") . "\n";

// Function with default parameter
function calculateArea($length, $width = 10) {
    return $length * $width;
}

echo "Area 1: " . calculateArea(5, 8) . "\n";
echo "Area 2: " . calculateArea(5) . "\n";

// Function with type hints
function add(int $a, int $b): int {
    return $a + $b;
}

echo "5 + 3 = " . add(5, 3) . "\n";

// Arrow function (PHP 7.4+)
$multiply = fn($a, $b) => $a * $b;
echo "4 x 6 = " . $multiply(4, 6) . "\n";

// Recursive function
function factorial($n) {
    if ($n <= 1) return 1;
    return $n * factorial($n - 1);
}

echo "5! = " . factorial(5) . "\n";
?>',

    'php-oop' => '<?php
// PHP Object-Oriented Programming

class Person {
    private $name;
    private $age;
    
    public function __construct($name, $age) {
        $this->name = $name;
        $this->age = $age;
    }
    
    public function greet() {
        return "Hi, I\'m {$this->name} and I\'m {$this->age} years old.";
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getAge() {
        return $this->age;
    }
}

class Student extends Person {
    private $major;
    
    public function __construct($name, $age, $major) {
        parent::__construct($name, $age);
        $this->major = $major;
    }
    
    public function study() {
        return "{$this->getName()} is studying {$this->major}";
    }
}

// Create objects
$person = new Person("John", 30);
echo $person->greet() . "\n";

$student = new Student("Jane", 20, "Computer Science");
echo $student->greet() . "\n";
echo $student->study() . "\n";
?>'
];

// Java Templates
$javaTemplates = [
    'java-hello' => '// Java Hello World
public class Main {
    public static void main(String[] args) {
        System.out.println("Hello, World!");
        System.out.println("Selamat belajar Java!");
        
        // Variables
        String nama = "Prozone";
        int tahun = 2025;
        
        System.out.println("Nama: " + nama);
        System.out.println("Tahun: " + tahun);
    }
}',

    'java-oop' => '// Java OOP Example
class Person {
    private String name;
    private int age;
    
    public Person(String name, int age) {
        this.name = name;
        this.age = age;
    }
    
    public void greet() {
        System.out.println("Hi, I\'m " + name + " and I\'m " + age + " years old.");
    }
    
    public String getName() { return name; }
    public int getAge() { return age; }
}

class Student extends Person {
    private String major;
    
    public Student(String name, int age, String major) {
        super(name, age);
        this.major = major;
    }
    
    public void study() {
        System.out.println(getName() + " is studying " + major);
    }
}

public class Main {
    public static void main(String[] args) {
        Person person = new Person("John", 30);
        person.greet();
        
        Student student = new Student("Jane", 20, "Computer Science");
        student.greet();
        student.study();
    }
}',

    'java-array' => '// Java Array Operations
import java.util.Arrays;
import java.util.ArrayList;

public class Main {
    public static void main(String[] args) {
        // Basic Array
        int[] numbers = {5, 2, 8, 1, 9};
        System.out.println("=== Basic Array ===");
        System.out.println("Array: " + Arrays.toString(numbers));
        
        Arrays.sort(numbers);
        System.out.println("Sorted: " + Arrays.toString(numbers));
        
        // ArrayList
        System.out.println("\n=== ArrayList ===");
        ArrayList<String> fruits = new ArrayList<>();
        fruits.add("Apple");
        fruits.add("Banana");
        fruits.add("Orange");
        
        System.out.println("Fruits: " + fruits);
        System.out.println("First: " + fruits.get(0));
        System.out.println("Size: " + fruits.size());
        
        // Loop through ArrayList
        System.out.println("\nLooping:");
        for (String fruit : fruits) {
            System.out.println("- " + fruit);
        }
    }
}'
];

// C++ Templates
$cppTemplates = [
    'cpp-hello' => '// C++ Hello World
#include <iostream>
#include <string>
using namespace std;

int main() {
    cout << "Hello, World!" << endl;
    cout << "Selamat belajar C++!" << endl;
    
    // Variables
    string nama = "Prozone";
    int tahun = 2025;
    
    cout << "Nama: " << nama << endl;
    cout << "Tahun: " << tahun << endl;
    
    return 0;
}',

    'cpp-array' => '// C++ Array and Vector
#include <iostream>
#include <vector>
#include <algorithm>
using namespace std;

int main() {
    // Basic Array
    int numbers[] = {5, 2, 8, 1, 9};
    int size = sizeof(numbers) / sizeof(numbers[0]);
    
    cout << "=== Basic Array ===" << endl;
    cout << "Array: ";
    for (int i = 0; i < size; i++) {
        cout << numbers[i] << " ";
    }
    cout << endl;
    
    // Vector
    cout << "\n=== Vector ===" << endl;
    vector<string> fruits = {"Apple", "Banana", "Orange"};
    
    cout << "Fruits: ";
    for (const string& fruit : fruits) {
        cout << fruit << " ";
    }
    cout << endl;
    
    // Add element
    fruits.push_back("Mango");
    cout << "After push: ";
    for (const string& fruit : fruits) {
        cout << fruit << " ";
    }
    cout << endl;
    
    // Vector of int with sort
    vector<int> nums = {5, 2, 8, 1, 9};
    sort(nums.begin(), nums.end());
    
    cout << "\nSorted numbers: ";
    for (int n : nums) {
        cout << n << " ";
    }
    cout << endl;
    
    return 0;
}',

    'cpp-oop' => '// C++ OOP Example
#include <iostream>
#include <string>
using namespace std;

class Person {
protected:
    string name;
    int age;
    
public:
    Person(string n, int a) : name(n), age(a) {}
    
    void greet() {
        cout << "Hi, I\'m " << name << " and I\'m " << age << " years old." << endl;
    }
    
    string getName() { return name; }
    int getAge() { return age; }
};

class Student : public Person {
private:
    string major;
    
public:
    Student(string n, int a, string m) : Person(n, a), major(m) {}
    
    void study() {
        cout << getName() << " is studying " << major << endl;
    }
};

int main() {
    Person person("John", 30);
    person.greet();
    
    Student student("Jane", 20, "Computer Science");
    student.greet();
    student.study();
    
    return 0;
}'
];

// Add new templates to main templates array
$templates = array_merge($templates, $phpTemplates, $javaTemplates, $cppTemplates);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Code Playground - ' . APP_NAME, 'Latihan menulis kode dengan code playground interaktif.', 'code playground, coding practice'); ?>
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/theme/dracula.min.css">
    <style>
        .playground-container { max-width: 1600px; margin: 0 auto; padding: 1.5rem; }
        .playground-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .playground-title { display: flex; align-items: center; gap: 0.75rem; }
        .playground-title h1 { font-size: 1.5rem; margin: 0; background: linear-gradient(135deg, #a78bfa, #8b5cf6); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        .playground-title .icon { font-size: 2rem; }
        .language-tabs { display: flex; gap: 0.5rem; background: rgba(15, 15, 35, 0.6); padding: 0.35rem; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.2); flex-wrap: wrap; }
        .lang-tab { padding: 0.5rem 1rem; border-radius: 6px; border: none; background: transparent; color: #94a3b8; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s ease; }
        .lang-tab .tab-logo { width: 18px; height: 18px; object-fit: contain; }
        .lang-tab:hover { background: rgba(139, 92, 246, 0.1); color: #e2e8f0; }
        .lang-tab.active { background: linear-gradient(135deg, #7c3aed, #8b5cf6); color: white; }
        .lang-tab.active .tab-logo { filter: brightness(1.2); }
        .playground-actions { display: flex; gap: 0.5rem; }
        .btn-run { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .btn-run:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
        .btn-run:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-clear, .btn-copy { padding: 0.6rem 1rem; background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.25); border-radius: 8px; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease; }
        .btn-clear:hover, .btn-copy:hover { background: rgba(139, 92, 246, 0.25); border-color: rgba(139, 92, 246, 0.4); }
        .playground-main { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; height: calc(100vh - 320px); min-height: 400px; }
        .editor-panel, .output-panel { background: #1e1e32; border: 1px solid rgba(139, 92, 246, 0.15); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background: rgba(15, 15, 35, 0.5); border-bottom: 1px solid rgba(139, 92, 246, 0.1); }
        .panel-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #e2e8f0; }
        .panel-title .dot { width: 8px; height: 8px; border-radius: 50%; }
        .panel-title .dot.editor { background: #a78bfa; }
        .panel-title .dot.output { background: #10b981; }
        .panel-body { flex: 1; overflow: auto; }
        .CodeMirror { height: 100% !important; font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 14px; }
        .output-iframe { width: 100%; height: 100%; border: none; background: white; }
        .console-output { padding: 1rem; font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #e2e8f0; height: 100%; overflow: auto; white-space: pre-wrap; }
        .console-line { padding: 0.25rem 0; }
        .console-line.error { color: #f87171; }
        .console-line.log { color: #10b981; }
        .templates-panel { margin-top: 1.5rem; }
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
        .template-card { background: rgba(30, 30, 50, 0.8); border: 1px solid rgba(139, 92, 246, 0.15); border-radius: 10px; padding: 1rem; cursor: pointer; transition: all 0.2s ease; }
        .template-card:hover { background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.3); transform: translateY(-2px); }
        .template-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .template-name { color: #e2e8f0; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem; }
        .template-desc { color: #94a3b8; font-size: 0.75rem; }
        .shortcuts-hint { display: flex; gap: 1rem; align-items: center; color: #64748b; font-size: 0.75rem; margin-top: 1rem; flex-wrap: wrap; }
        .shortcut { display: flex; align-items: center; gap: 0.25rem; }
        .shortcut kbd { background: rgba(139, 92, 246, 0.2); color: #a78bfa; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
        @media (max-width: 1024px) {
            .playground-main { grid-template-columns: 1fr; height: auto; }
            .editor-panel, .output-panel { min-height: 300px; }
        }
        @media (max-width: 640px) {
            .playground-header { flex-direction: column; align-items: flex-start; }
            .playground-actions { width: 100%; }
            .btn-run, .btn-clear, .btn-copy { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="dashboard-main-container">
        <div class="playground-container">
            <div class="playground-header">
                <div class="playground-title">
                    <span class="icon"><?php icon('code', 24); ?></span>
                    <h1>Code Playground</h1>
                </div>

                <div class="language-tabs">
                    <button class="lang-tab active" data-lang="html" onclick="switchLanguage('html')">
                        <img src="<?php echo getLanguageIcon('html'); ?>" alt="HTML" class="tab-logo"> HTML/CSS/JS
                    </button>
                    <button class="lang-tab" data-lang="javascript" onclick="switchLanguage('javascript')">
                        <img src="<?php echo getLanguageIcon('javascript'); ?>" alt="JS" class="tab-logo"> JavaScript
                    </button>
                    <button class="lang-tab" data-lang="python" onclick="switchLanguage('python')">
                        <img src="<?php echo getLanguageIcon('python'); ?>" alt="Python" class="tab-logo"> Python
                    </button>
                    <button class="lang-tab" data-lang="php" onclick="switchLanguage('php')">
                        <img src="<?php echo getLanguageIcon('php'); ?>" alt="PHP" class="tab-logo"> PHP
                    </button>
                    <button class="lang-tab" data-lang="java" onclick="switchLanguage('java')">
                        <img src="<?php echo getLanguageIcon('java'); ?>" alt="Java" class="tab-logo"> Java
                    </button>
                    <button class="lang-tab" data-lang="cpp" onclick="switchLanguage('cpp')">
                        <img src="<?php echo getLanguageIcon('c++'); ?>" alt="C++" class="tab-logo"> C++
                    </button>
                </div>

                <div class="playground-actions">
                    <button class="btn-run" id="runBtn" onclick="runCode()">
                        <span><?php icon('play', 16); ?></span> Run
                    </button>
                    <button class="btn-copy" onclick="copyCode()"><?php icon('copy', 14); ?> Copy</button>
                    <button class="btn-clear" onclick="clearCode()"><?php icon('trash', 14); ?> Clear</button>
                </div>
            </div>

            <div class="playground-main">
                <div class="editor-panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <span class="dot editor"></span>
                            <span id="editorTitle">Editor (HTML)</span>
                        </div>
                    </div>
                    <div class="panel-body">
                        <textarea id="codeEditor"></textarea>
                    </div>
                </div>

                <div class="output-panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <span class="dot output"></span>
                            <span>Output</span>
                        </div>
                        <button class="btn-clear" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="clearOutput()">Clear</button>
                    </div>
                    <div class="panel-body">
                        <iframe id="outputFrame" class="output-iframe"></iframe>
                        <div id="consoleOutput" class="console-output" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <div class="shortcuts-hint">
                <div class="shortcut"><kbd>Ctrl</kbd>+<kbd>Enter</kbd> Run</div>
                <div class="shortcut"><kbd>Ctrl</kbd>+<kbd>S</kbd> Save</div>
            </div>

            <div class="templates-panel">
                <h3 style="color: #e2e8f0; font-size: 1rem; margin-bottom: 1rem;"><?php icon('book', 16); ?> Templates HTML/CSS</h3>
                <div class="templates-grid">
                    <div class="template-card" onclick="loadTemplate('hello-world')">
                        <div class="template-icon"><?php icon('hand', 20); ?></div>
                        <div class="template-name">Hello World</div>
                        <div class="template-desc">Basic HTML starter</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('flexbox')">
                        <div class="template-icon"><?php icon('grid', 20); ?></div>
                        <div class="template-name">Flexbox Layout</div>
                        <div class="template-desc">CSS Flexbox example</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('form')">
                        <div class="template-icon"><?php icon('edit', 20); ?></div>
                        <div class="template-name">Form Validation</div>
                        <div class="template-desc">JS form handling</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('animation')">
                        <div class="template-icon"><?php icon('star', 20); ?></div>
                        <div class="template-name">CSS Animation</div>
                        <div class="template-desc">Animated elements</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('calculator')">
                        <div class="template-icon"><?php icon('calculator', 20); ?></div>
                        <div class="template-name">Calculator</div>
                        <div class="template-desc">Simple calculator</div>
                    </div>
                </div>
                
                <h3 style="color: #e2e8f0; font-size: 1rem; margin: 1.5rem 0 1rem;"><?php icon('lightning', 16); ?> Templates JavaScript</h3>
                <div class="templates-grid">
                    <div class="template-card" onclick="loadTemplate('js-array', 'javascript')">
                        <div class="template-icon"><?php icon('chart', 20); ?></div>
                        <div class="template-name">Array Methods</div>
                        <div class="template-desc">map, filter, find</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('js-fetch', 'javascript')">
                        <div class="template-icon"><?php icon('globe', 20); ?></div>
                        <div class="template-name">Data & Objects</div>
                        <div class="template-desc">Object manipulation</div>
                    </div>
                </div>
                
                <h3 style="color: #e2e8f0; font-size: 1rem; margin: 1.5rem 0 1rem;"><?php icon('terminal', 16); ?> Templates Python</h3>
                <div class="templates-grid">
                    <div class="template-card" onclick="loadTemplate('py-basics', 'python')">
                        <div class="template-icon"><?php icon('book-open', 20); ?></div>
                        <div class="template-name">Python Basics</div>
                        <div class="template-desc">Variables, lists, dict</div>
                    </div>
                </div>
                
                <h3 style="color: #e2e8f0; font-size: 1rem; margin: 1.5rem 0 1rem;"><?php icon('server', 16); ?> Templates PHP</h3>
                <div class="templates-grid">
                    <div class="template-card" onclick="loadTemplate('php-hello', 'php')">
                        <div class="template-icon"><?php icon('hand', 20); ?></div>
                        <div class="template-name">PHP Hello World</div>
                        <div class="template-desc">Dasar PHP & variabel</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('php-array', 'php')">
                        <div class="template-icon"><?php icon('chart', 20); ?></div>
                        <div class="template-name">PHP Array</div>
                        <div class="template-desc">Array operations</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('php-function', 'php')">
                        <div class="template-icon"><?php icon('lightning', 20); ?></div>
                        <div class="template-name">PHP Functions</div>
                        <div class="template-desc">Function & callbacks</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('php-oop', 'php')">
                        <div class="template-icon"><?php icon('box', 20); ?></div>
                        <div class="template-name">PHP OOP</div>
                        <div class="template-desc">Class & inheritance</div>
                    </div>
                </div>
                
                <h3 style="color: #e2e8f0; font-size: 1rem; margin: 1.5rem 0 1rem;"><?php icon('code', 16); ?> Templates Java</h3>
                <div class="templates-grid">
                    <div class="template-card" onclick="loadTemplate('java-hello', 'java')">
                        <div class="template-icon"><?php icon('hand', 20); ?></div>
                        <div class="template-name">Java Hello World</div>
                        <div class="template-desc">Basic Java syntax</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('java-array', 'java')">
                        <div class="template-icon"><?php icon('chart', 20); ?></div>
                        <div class="template-name">Java Array</div>
                        <div class="template-desc">Array & ArrayList</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('java-oop', 'java')">
                        <div class="template-icon"><?php icon('box', 20); ?></div>
                        <div class="template-name">Java OOP</div>
                        <div class="template-desc">Class & inheritance</div>
                    </div>
                </div>
                
                <h3 style="color: #e2e8f0; font-size: 1rem; margin: 1.5rem 0 1rem;"><?php icon('cpu', 16); ?> Templates C++</h3>
                <div class="templates-grid">
                    <div class="template-card" onclick="loadTemplate('cpp-hello', 'cpp')">
                        <div class="template-icon"><?php icon('hand', 20); ?></div>
                        <div class="template-name">C++ Hello World</div>
                        <div class="template-desc">Basic C++ syntax</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('cpp-array', 'cpp')">
                        <div class="template-icon"><?php icon('chart', 20); ?></div>
                        <div class="template-name">C++ Array & Vector</div>
                        <div class="template-desc">STL containers</div>
                    </div>
                    <div class="template-card" onclick="loadTemplate('cpp-oop', 'cpp')">
                        <div class="template-icon"><?php icon('box', 20); ?></div>
                        <div class="template-name">C++ OOP</div>
                        <div class="template-desc">Class & inheritance</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/edit/closetag.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/edit/matchbrackets.min.js"></script>
    <script src="assets/js/navbar.js"></script>

    <script>
        var currentLang = 'html';
        var editor;

        // Templates from PHP
        var templates = <?= json_encode($templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        var defaultCode = <?= json_encode($defaultCodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        document.addEventListener('DOMContentLoaded', function() {
            editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
                mode: 'htmlmixed',
                theme: 'dracula',
                lineNumbers: true,
                autoCloseTags: true,
                matchBrackets: true,
                indentUnit: 4,
                tabSize: 4,
                lineWrapping: true
            });

            editor.setValue(defaultCode.html);

            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    runCode();
                }
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    saveCode();
                }
            });
        });

        function switchLanguage(lang) {
            currentLang = lang;
            
            document.querySelectorAll('.lang-tab').forEach(function(tab) {
                tab.classList.remove('active');
            });
            document.querySelector('[data-lang="' + lang + '"]').classList.add('active');
            
            var modes = { 
                html: 'htmlmixed', 
                javascript: 'javascript', 
                python: 'python', 
                php: 'php',
                java: 'text/x-java',
                cpp: 'text/x-c++src'
            };
            editor.setOption('mode', modes[lang]);
            
            var titles = { 
                html: 'Editor (HTML/CSS/JS)', 
                javascript: 'Editor (JavaScript)', 
                python: 'Editor (Python)', 
                php: 'Editor (PHP)',
                java: 'Editor (Java)',
                cpp: 'Editor (C++)'
            };
            document.getElementById('editorTitle').textContent = titles[lang];
            
            var iframe = document.getElementById('outputFrame');
            var consoleEl = document.getElementById('consoleOutput');
            
            if (lang === 'html') {
                iframe.style.display = 'block';
                consoleEl.style.display = 'none';
            } else {
                iframe.style.display = 'none';
                consoleEl.style.display = 'block';
            }
            
            editor.setValue(defaultCode[lang]);
        }

        function runCode() {
            var code = editor.getValue();
            var btn = document.getElementById('runBtn');
            var consoleEl = document.getElementById('consoleOutput');
            btn.disabled = true;
            btn.innerHTML = '<span>⏳</span> Running...';

            if (currentLang === 'html') {
                // Run HTML in iframe
                var iframe = document.getElementById('outputFrame');
                var doc = iframe.contentDocument || iframe.contentWindow.document;
                doc.open();
                doc.write(code);
                doc.close();
                btn.disabled = false;
                btn.innerHTML = '<span>▶</span> Run';
            } else if (currentLang === 'javascript') {
                // Run JavaScript in browser with captured console
                consoleEl.innerHTML = '';
                var logs = [];
                
                // Create sandboxed iframe for JS execution
                var sandbox = document.createElement('iframe');
                sandbox.style.display = 'none';
                document.body.appendChild(sandbox);
                
                var sandboxWindow = sandbox.contentWindow;
                
                // Override console methods
                sandboxWindow.console = {
                    log: function() {
                        var args = Array.prototype.slice.call(arguments);
                        logs.push({ type: 'log', msg: args.map(formatValue).join(' ') });
                    },
                    error: function() {
                        var args = Array.prototype.slice.call(arguments);
                        logs.push({ type: 'error', msg: args.map(formatValue).join(' ') });
                    },
                    warn: function() {
                        var args = Array.prototype.slice.call(arguments);
                        logs.push({ type: 'warn', msg: args.map(formatValue).join(' ') });
                    },
                    info: function() {
                        var args = Array.prototype.slice.call(arguments);
                        logs.push({ type: 'log', msg: args.map(formatValue).join(' ') });
                    }
                };
                
                try {
                    sandboxWindow.eval(code);
                    
                    if (logs.length === 0) {
                        logs.push({ type: 'log', msg: '(No output)' });
                    }
                    
                    var html = logs.map(function(l) {
                        return '<div class="console-line ' + l.type + '">' + escapeHtml(l.msg) + '</div>';
                    }).join('');
                    consoleEl.innerHTML = html;
                } catch (e) {
                    consoleEl.innerHTML = '<div class="console-line error">Error: ' + escapeHtml(e.message) + '</div>';
                }
                
                document.body.removeChild(sandbox);
                btn.disabled = false;
                btn.innerHTML = '<span>▶</span> Run';
            } else {
                // Run Python/PHP/Java/C++ via API
                fetch('api/run-code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ language: currentLang, code: code })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var output = data.output || data.error || 'No output';
                    var isCompilerMissing = output.includes('belum terinstall') || output.includes('not found');
                    
                    var onlineButtons = '';
                    if (isCompilerMissing) {
                        if (currentLang === 'java') {
                            onlineButtons = '<div style="margin-top:10px; padding-top:10px; border-top:1px solid #333;">' +
                                '<span style="color:#10b981; font-weight:bold;">🚀 Run Online: </span>' +
                                '<a href="https://www.jdoodle.com/online-java-compiler/" target="_blank" style="color:#8b5cf6; margin-right:10px;">JDoodle</a>' +
                                '<a href="https://www.programiz.com/java-programming/online-compiler/" target="_blank" style="color:#3b82f6; margin-right:10px;">Programiz</a>' +
                                '<a href="https://www.onlinegdb.com/online_java_compiler" target="_blank" style="color:#10b981;">OnlineGDB</a>' +
                                '</div>';
                        } else if (currentLang === 'cpp') {
                            onlineButtons = '<div style="margin-top:10px; padding-top:10px; border-top:1px solid #333;">' +
                                '<span style="color:#10b981; font-weight:bold;">🚀 Run Online: </span>' +
                                '<a href="https://www.onlinegdb.com/online_c++_compiler" target="_blank" style="color:#8b5cf6; margin-right:10px;">OnlineGDB</a>' +
                                '<a href="https://www.programiz.com/cpp-programming/online-compiler/" target="_blank" style="color:#3b82f6; margin-right:10px;">Programiz</a>' +
                                '<a href="https://cpp.sh/" target="_blank" style="color:#10b981;">cpp.sh</a>' +
                                '</div>';
                        }
                    }
                    
                    consoleEl.innerHTML = '<div class="console-line log">' + escapeHtml(output) + '</div>' + onlineButtons;
                })
                .catch(function(err) {
                    consoleEl.innerHTML = '<div class="console-line error">Error: ' + err.message + '</div>';
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<span>▶</span> Run';
                });
            }
        }
        
        function formatValue(val) {
            if (val === null) return 'null';
            if (val === undefined) return 'undefined';
            if (typeof val === 'object') {
                try {
                    return JSON.stringify(val, null, 2);
                } catch (e) {
                    return String(val);
                }
            }
            return String(val);
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        }

        function copyCode() {
            var code = editor.getValue();
            navigator.clipboard.writeText(code).then(function() {
                if (typeof window.showToast === 'function') {
                    window.showToast('Code copied!', 'success');
                } else {
                    alert('Code copied!');
                }
            });
        }

        function clearCode() {
            editor.setValue('');
            clearOutput();
        }

        function clearOutput() {
            var iframe = document.getElementById('outputFrame');
            iframe.srcdoc = '';
            document.getElementById('consoleOutput').innerHTML = '';
        }

        function saveCode() {
            var code = editor.getValue();
            localStorage.setItem('playground_' + currentLang, code);
            if (typeof window.showToast === 'function') {
                window.showToast('Code saved!', 'success');
            } else {
                alert('Code saved!');
            }
        }

        function loadTemplate(name, lang) {
            if (templates[name]) {
                var targetLang = lang || 'html';
                switchLanguage(targetLang);
                editor.setValue(templates[name]);
                runCode();
            }
        }
    </script>
</body>
</html>
