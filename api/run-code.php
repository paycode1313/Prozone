<?php
// api/run-code.php
header('Content-Type: application/json');
// Disable error display to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Buffer output to catch any stray echoes
ob_start();

// Use __DIR__ to ensure correct path
require_once __DIR__ . '/../config/config.php';

// Clear buffer
ob_clean();

// ============================================
// CONFIGURATION FOR PRODUCTION
// ============================================
// Set to 'local' for localhost, 'api' for production/hosting
// Or 'auto' to try local first, then fallback to API
define('CODE_RUNNER_MODE', 'auto');

// Piston API (Free, no API key needed)
define('PISTON_API_URL', 'https://emkc.org/api/v2/piston/execute');

// Language mapping for Piston API
$pistonLanguages = [
    'python' => ['language' => 'python', 'version' => '3.10'],
    'python3' => ['language' => 'python', 'version' => '3.10'],
    'java' => ['language' => 'java', 'version' => '15.0.2'],
    'text/x-java' => ['language' => 'java', 'version' => '15.0.2'],
    'cpp' => ['language' => 'cpp', 'version' => '10.2.0'],
    'c++' => ['language' => 'cpp', 'version' => '10.2.0'],
    'text/x-c++src' => ['language' => 'cpp', 'version' => '10.2.0'],
    'javascript' => ['language' => 'javascript', 'version' => '18.15.0'],
    'js' => ['language' => 'javascript', 'version' => '18.15.0'],
    'php' => ['language' => 'php', 'version' => '8.2.3'],
];

// Function to run code via Piston API (Free online compiler)
function runViaPistonAPI($language, $code, $pistonLanguages) {
    if (!isset($pistonLanguages[$language])) {
        return ['success' => false, 'output' => "Language '$language' not supported by online compiler."];
    }
    
    $langConfig = $pistonLanguages[$language];
    
    $payload = [
        'language' => $langConfig['language'],
        'version' => $langConfig['version'],
        'files' => [
            ['content' => $code]
        ]
    ];
    
    $ch = curl_init(PISTON_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'output' => "API Error: $error"];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'output' => "API returned HTTP $httpCode"];
    }
    
    $data = json_decode($response, true);
    if (isset($data['run'])) {
        $output = $data['run']['stdout'] ?? '';
        $stderr = $data['run']['stderr'] ?? '';
        if ($stderr) {
            $output .= ($output ? "\n" : "") . $stderr;
        }
        return ['success' => true, 'output' => $output ?: '(No output)'];
    }
    
    return ['success' => false, 'output' => 'Unexpected API response'];
}

// Function to check if local execution is available
function canRunLocally($language) {
    // PHP can always run locally via eval
    if (in_array($language, ['php', 'application/x-httpd-php'])) {
        return true;
    }
    
    // Check if shell_exec is available
    if (!function_exists('shell_exec')) {
        return false;
    }
    
    // Check disabled functions
    $disabled = explode(',', ini_get('disable_functions'));
    if (in_array('shell_exec', array_map('trim', $disabled))) {
        return false;
    }
    
    // Check for specific compilers/interpreters
    switch ($language) {
        case 'python':
        case 'python3':
            $check = @shell_exec("python --version 2>&1");
            return $check && stripos($check, 'python') !== false;
            
        case 'java':
        case 'text/x-java':
            $check = @shell_exec("javac -version 2>&1");
            return $check && stripos($check, 'javac') !== false;
            
        case 'cpp':
        case 'c++':
        case 'text/x-c++src':
            $paths = ['C:\\mingw64\\bin\\g++.exe', 'C:\\MinGW\\bin\\g++.exe'];
            foreach ($paths as $path) {
                if (file_exists($path)) return true;
            }
            $check = @shell_exec("g++ --version 2>&1");
            return $check && stripos($check, 'g++') !== false;
            
        default:
            return false;
    }
}
// ============================================

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['output' => 'Error: Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$language = $input['language'] ?? '';
$code = $input['code'] ?? '';

if (empty($code)) {
    echo json_encode(['output' => '']);
    exit;
}

// ============================================
// DECIDE: Local or API execution
// ============================================
$useAPI = false;
if (CODE_RUNNER_MODE === 'api') {
    $useAPI = true;
} elseif (CODE_RUNNER_MODE === 'auto') {
    // Try local first, use API as fallback
    if (!canRunLocally($language) && isset($pistonLanguages[$language])) {
        $useAPI = true;
    }
}

// If using API for supported languages
if ($useAPI && isset($pistonLanguages[$language])) {
    $result = runViaPistonAPI($language, $code, $pistonLanguages);
    echo json_encode(['success' => $result['success'], 'output' => $result['output']]);
    exit;
}
// ============================================

$output = '';

switch ($language) {
    case 'php':
    case 'application/x-httpd-php':
        // Use internal eval for PHP to avoid shell_exec dependency
        ob_start();
        try {
            // Security Check: Blacklist dangerous functions
            $dangerous_functions = [
                'exec', 'passthru', 'system', 'shell_exec', 'popen', 'proc_open', 
                'pcntl_exec', 'eval', 'assert', 'create_function', 'include', 
                'require', 'include_once', 'require_once', 'file_get_contents', 
                'file_put_contents', 'unlink', 'rmdir', 'mkdir', 'chmod', 'chown'
            ];
            
            foreach ($dangerous_functions as $func) {
                if (stripos($code, $func) !== false) {
                    throw new Exception("Security Violation: Function '$func' is not allowed.");
                }
            }

            // Heuristic: If code contains <?php, treat as mixed/full file.
            // Otherwise, treat as pure PHP code snippet.
            if (stripos($code, '<?php') !== false || stripos($code, '<?') !== false) {
                eval('?>' . $code);
            } else {
                eval($code);
            }
        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        $output = ob_get_clean();
        break;

    case 'java':
    case 'text/x-java':
        if (!function_exists('shell_exec')) {
            $output = "⚠️ Java tidak dapat dijalankan di server ini.\n\n" .
                      "💡 Alternatif: Gunakan online compiler:\n" .
                      "   • https://www.jdoodle.com/online-java-compiler/\n" .
                      "   • https://www.programiz.com/java-programming/online-compiler/\n\n" .
                      "📝 Copy kode di atas dan paste ke salah satu website tersebut.";
            break;
        }
        
        // Check if code has a main method
        $hasMain = preg_match('/public\s+static\s+void\s+main\s*\(/', $code);
        
        // Extract all class names
        preg_match_all('/class\s+(\w+)/', $code, $allClasses);
        $classNames = $allClasses[1] ?? [];
        
        if (empty($classNames)) {
            // No class at all - wrap everything in Main class
            $className = 'Main';
            $code = "public class Main {\n    public static void main(String[] args) {\n        $code\n    }\n}";
        } elseif (!$hasMain) {
            // Has class(es) but no main method
            // Check if there's code outside the class (like usage examples)
            $codeWithoutComments = preg_replace('/\/\/.*$/m', '', $code);
            $codeWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $codeWithoutComments);
            
            // Find the last closing brace of classes
            $lastBrace = strrpos($code, '}');
            $afterClasses = trim(substr($code, $lastBrace + 1));
            
            if (!empty($afterClasses)) {
                // There's code after the class definition - it's usage code
                $classCode = substr($code, 0, $lastBrace + 1);
                $usageCode = $afterClasses;
                
                // Add a Main class with main method containing the usage code
                $className = 'Main';
                $code = $classCode . "\n\npublic class Main {\n    public static void main(String[] args) {\n        " . 
                        str_replace("\n", "\n        ", $usageCode) . "\n    }\n}";
            } else {
                // No usage code, but no main - add a simple main to first class
                $className = $classNames[0];
                // Insert main method before the last closing brace of the first class
                $pattern = '/(class\s+' . $className . '\s*\{[^}]*)(}\s*)$/s';
                if (preg_match($pattern, $code)) {
                    $code = preg_replace($pattern, '$1    public static void main(String[] args) {\n        System.out.println("' . $className . ' class loaded successfully!");\n    }\n$2', $code);
                }
            }
        } else {
            // Has main method - find which class has main
            foreach ($classNames as $name) {
                if (preg_match('/class\s+' . $name . '\s*\{[^}]*public\s+static\s+void\s+main/s', $code)) {
                    $className = $name;
                    break;
                }
            }
            if (!isset($className)) {
                $className = $classNames[0];
            }
        }

        $tempDir = sys_get_temp_dir();
        $javaFile = $tempDir . DIRECTORY_SEPARATOR . $className . '.java';
        file_put_contents($javaFile, $code);

        // Check if javac exists
        $check = shell_exec("javac -version 2>&1");
        if ($check === null || stripos($check, 'not recognized') !== false || (stripos($check, 'javac') === false && stripos($check, 'release') === false)) {
             $output = "⚠️ Java Compiler (JDK) belum terinstall di server.\n\n" .
                       "🔧 Cara Install JDK:\n" .
                       "   1. Download JDK dari https://adoptium.net/ (gratis)\n" .
                       "   2. Install dan centang 'Set JAVA_HOME variable'\n" .
                       "   3. Restart Laragon/XAMPP\n\n" .
                       "💡 Alternatif: Gunakan online compiler:\n" .
                       "   • https://www.jdoodle.com/online-java-compiler/\n" .
                       "   • https://www.programiz.com/java-programming/online-compiler/\n\n" .
                       "📝 Copy kode di atas dan paste ke salah satu website tersebut.";
             if (file_exists($javaFile)) unlink($javaFile);
             break;
        }

        // Compile
        $compileCmd = "javac \"$javaFile\" 2>&1";
        $compileOutput = shell_exec($compileCmd);

        if ($compileOutput) {
            $output = "❌ Compilation Error:\n" . $compileOutput;
        } else {
            // Run
            $runCmd = "java -cp \"$tempDir\" $className 2>&1";
            $output = shell_exec($runCmd);
        }

        // Clean up
        if (file_exists($javaFile)) unlink($javaFile);
        // Clean up all generated class files
        foreach ($classNames as $cn) {
            $classFile = $tempDir . DIRECTORY_SEPARATOR . $cn . '.class';
            if (file_exists($classFile)) unlink($classFile);
        }
        $mainClassFile = $tempDir . DIRECTORY_SEPARATOR . 'Main.class';
        if (file_exists($mainClassFile)) unlink($mainClassFile);
        break;

    case 'cpp':
    case 'c++':
    case 'text/x-c++src':
        if (!function_exists('shell_exec')) {
            $output = "⚠️ C++ tidak dapat dijalankan di server ini.\n\n" .
                      "💡 Alternatif: Gunakan online compiler:\n" .
                      "   • https://www.onlinegdb.com/online_c++_compiler\n" .
                      "   • https://www.programiz.com/cpp-programming/online-compiler/\n\n" .
                      "📝 Copy kode di atas dan paste ke salah satu website tersebut.";
            break;
        }

        $tempDir = sys_get_temp_dir();
        $uniqueId = time() . '_' . mt_rand(1000, 9999);
        $cppFile = $tempDir . DIRECTORY_SEPARATOR . 'code_' . $uniqueId . '.cpp';
        $exeFile = $tempDir . DIRECTORY_SEPARATOR . 'code_' . $uniqueId . '.exe';
        file_put_contents($cppFile, $code);

        // Check for g++ compiler - try direct path first
        $gppPath = null;
        $possiblePaths = [
            'C:\\mingw64\\bin\\g++.exe',
            'C:\\MinGW\\bin\\g++.exe',
            'C:\\msys64\\mingw64\\bin\\g++.exe',
            'C:\\Program Files\\mingw64\\bin\\g++.exe',
            'C:\\TDM-GCC-64\\bin\\g++.exe'
        ];
        
        // Check direct paths first (more reliable)
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $gppPath = $path;
                break;
            }
        }
        
        // If not found, try PATH
        if ($gppPath === null) {
            $check = shell_exec("where g++ 2>&1");
            if ($check && stripos($check, 'g++') !== false && stripos($check, 'not find') === false) {
                $gppPath = trim(explode("\n", $check)[0]);
            }
        }
        
        if ($gppPath === null) {
            $output = "⚠️ C++ Compiler (g++) belum terinstall di server.\n\n" .
                      "🔧 Cara Install MinGW (g++):\n" .
                      "   1. Download MinGW dari https://winlibs.com/\n" .
                      "   2. Extract ke C:\\mingw64\n" .
                      "   3. Tambahkan C:\\mingw64\\bin ke System PATH\n" .
                      "   4. Restart Laragon/XAMPP\n\n" .
                      "💡 Alternatif: Gunakan online compiler:\n" .
                      "   • https://www.onlinegdb.com/online_c++_compiler\n" .
                      "   • https://www.programiz.com/cpp-programming/online-compiler/\n\n" .
                      "📝 Copy kode di atas dan paste ke salah satu website tersebut.";
            if (file_exists($cppFile)) unlink($cppFile);
            break;
        }

        // Compile using full path
        $compileCmd = "\"$gppPath\" \"$cppFile\" -o \"$exeFile\" 2>&1";
        $compileOutput = shell_exec($compileCmd);

        if ($compileOutput) {
            $output = "❌ Compilation Error:\n" . $compileOutput;
        } else {
            // Run - need to add MinGW bin to PATH for runtime DLLs
            $mingwBin = dirname($gppPath);
            $runCmd = "set PATH=$mingwBin;%PATH% && \"$exeFile\" 2>&1";
            $output = shell_exec($runCmd);
            if ($output === null) {
                $output = "(Program executed with no output)";
            }
        }

        // Clean up
        if (file_exists($cppFile)) unlink($cppFile);
        if (file_exists($exeFile)) unlink($exeFile);
        break;

    case 'python':
    case 'python3':
        $tempFile = tempnam(sys_get_temp_dir(), 'py_code_');
        file_put_contents($tempFile, $code);

        // 1. Define standard commands
        $commands = ['python', 'py', 'python3', 'C:\Windows\py.exe'];

        // 2. Auto-discover Python paths
        $possiblePaths = [
            'C:\Python312\python.exe',
            'C:\Python311\python.exe',
            'C:\Python310\python.exe',
            'C:\Python39\python.exe'
        ];
        
        $userProfile = getenv('USERPROFILE');
        if ($userProfile) {
            $appData = $userProfile . '\AppData\Local\Programs\Python';
            if (is_dir($appData)) {
                $dirs = glob($appData . '\Python3*', GLOB_ONLYDIR);
                if ($dirs) {
                    foreach ($dirs as $dir) {
                        $possiblePaths[] = $dir . '\python.exe';
                    }
                }
            }
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $commands[] = $path;
            }
        }

        $success = false;
        $debugInfo = [];

        foreach ($commands as $cmdName) {
            // Use proc_open for better control and error capture
            $descriptors = [
                0 => ["pipe", "r"],  // stdin
                1 => ["pipe", "w"],  // stdout
                2 => ["pipe", "w"]   // stderr
            ];
            
            // Wrap in cmd /c to ensure shell environment on Windows
            $commandLine = (strpos($cmdName, ' ') !== false) ? "\"$cmdName\"" : $cmdName;
            $commandLine .= " \"$tempFile\"";
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                 $commandLine = "cmd /c " . $commandLine;
            }

            $process = proc_open($commandLine, $descriptors, $pipes);

            if (is_resource($process)) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $return_value = proc_close($process);

                $currentOutput = $stdout . $stderr;
                
                // Check for specific failure strings
                $isFailure = false;
                if (strpos($currentOutput, 'is not recognized') !== false) $isFailure = true;
                if (strpos($currentOutput, 'Python was not found') !== false) $isFailure = true;
                if (strpos($currentOutput, 'Microsoft Store') !== false) $isFailure = true;
                if (strpos($currentOutput, 'The system cannot find the file specified') !== false) $isFailure = true;
                
                // If return code is non-zero and we have "not found" in stderr, it's a system error
                if ($return_value != 0 && (empty($stdout) || $isFailure)) {
                     $debugInfo[] = "Cmd: $cmdName | Ret: $return_value | Out: " . trim(substr($currentOutput, 0, 100));
                     continue;
                }

                // If we got here, it likely ran (even if the python code itself had an error)
                $output = $currentOutput;
                $success = true;
                break;
            } else {
                $debugInfo[] = "Cmd: $cmdName | Failed to open process";
            }
        }

        if (!$success) {
             $output = "System Error: Python not found.\n" .
                      "Debug Info:\n" . implode("\n", $debugInfo) . "\n\n" .
                      "Troubleshooting:\n" .
                      "1. Install Python from python.org\n" .
                      "2. Check 'Add Python to PATH' during installation\n" .
                      "3. Restart XAMPP (Stop/Start Apache)";
        }

        unlink($tempFile);
        break;

    case 'javascript':
    case 'js':
        // JavaScript should run in browser, but we can use Node.js if available
        $tempFile = tempnam(sys_get_temp_dir(), 'js_code_');
        file_put_contents($tempFile, $code);
        
        $commands = ['node', 'nodejs', 'C:\Program Files\nodejs\node.exe'];
        $success = false;
        
        foreach ($commands as $cmdName) {
            $descriptors = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];
            
            $commandLine = (strpos($cmdName, ' ') !== false) ? "\"$cmdName\"" : $cmdName;
            $commandLine .= " \"$tempFile\"";
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $commandLine = "cmd /c " . $commandLine;
            }
            
            $process = proc_open($commandLine, $descriptors, $pipes);
            
            if (is_resource($process)) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $return_value = proc_close($process);
                
                if (strpos($stdout . $stderr, 'is not recognized') === false &&
                    strpos($stdout . $stderr, 'not found') === false) {
                    $output = $stdout . $stderr;
                    $success = true;
                    break;
                }
            }
        }
        
        if (!$success) {
            $output = "Node.js tidak tersedia. JavaScript akan dijalankan di browser.\nInstall Node.js dari https://nodejs.org untuk menjalankan JavaScript di server.";
        }
        
        unlink($tempFile);
        break;

    default:
        $output = "Error: Unsupported language '$language'";
}

echo json_encode(['success' => true, 'output' => $output]);
