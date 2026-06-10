<?php
/**
 * API untuk validasi kode practice lesson
 * Membandingkan output user dengan expected output
 */

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../models/Lesson.php';

$database = new Database();
$db = $database->getConnection();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$lesson_id = $input['lesson_id'] ?? 0;
$user_output = $input['user_output'] ?? '';
$user_code = $input['user_code'] ?? '';
$language = $input['language'] ?? '';

if (!$lesson_id) {
    echo json_encode(['success' => false, 'message' => 'Lesson ID required']);
    exit;
}

// Get lesson data
$lesson = new Lesson($db);
$lesson->id = $lesson_id;
$lesson_data = $lesson->readOne();

if (!$lesson_data) {
    echo json_encode(['success' => false, 'message' => 'Lesson not found']);
    exit;
}

// Get expected solution and output
$kode_solusi = $lesson_data['kode_solusi'] ?? '';
$expected_output = $lesson_data['expected_output'] ?? ''; // From database if available

// Validation result
$validation = [
    'is_valid' => false,
    'message' => '',
    'hints' => [],
    'score' => 0
];

// If no solution defined, auto-validate as correct
if (empty(trim($kode_solusi)) && empty(trim($expected_output))) {
    $validation['is_valid'] = true;
    $validation['message'] = 'Kode berhasil dijalankan!';
    $validation['score'] = 100;
    echo json_encode(['success' => true, 'validation' => $validation]);
    exit;
}

// Normalize strings for comparison (remove extra whitespace, trim)
function normalizeOutput($str) {
    $str = preg_replace('/\s+/', ' ', $str);
    $str = trim($str);
    $str = strtolower($str);
    return $str;
}

function normalizeCode($code) {
    // Remove comments
    $code = preg_replace('/\/\/.*$/m', '', $code);
    $code = preg_replace('/\/\*[\s\S]*?\*\//', '', $code);
    $code = preg_replace('/#.*$/m', '', $code);
    
    // Normalize whitespace
    $code = preg_replace('/\s+/', ' ', $code);
    $code = trim($code);
    $code = strtolower($code);
    return $code;
}

// Check validation strategy based on language
$isHTMLCSS = in_array($language, ['htmlmixed', 'html', 'css', '']);

if ($isHTMLCSS) {
    // For HTML/CSS, we compare structural elements
    $validation = validateHTMLCSS($user_code, $kode_solusi, $lesson_data);
} else {
    // For programming languages, compare output
    $validation = validateProgramOutput($user_output, $kode_solusi, $user_code, $lesson_data);
}

echo json_encode(['success' => true, 'validation' => $validation]);

/**
 * Validate HTML/CSS code
 */
function validateHTMLCSS($user_code, $solution_code, $lesson_data) {
    $result = [
        'is_valid' => false,
        'message' => '',
        'hints' => [],
        'score' => 0,
        'checks' => []
    ];
    
    $total_checks = 0;
    $passed_checks = 0;
    
    // Parse solution to find required elements
    $required_elements = [];
    
    // Check for required HTML tags
    $common_tags = ['html', 'head', 'body', 'div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 
                    'span', 'a', 'img', 'ul', 'ol', 'li', 'table', 'tr', 'td', 'th', 
                    'form', 'input', 'button', 'header', 'footer', 'nav', 'section', 
                    'article', 'main', 'aside'];
    
    // Find tags in solution
    foreach ($common_tags as $tag) {
        $pattern = '/<' . $tag . '[^>]*>/i';
        if (preg_match($pattern, $solution_code)) {
            $required_elements[] = $tag;
        }
    }
    
    // Validate required elements in user code
    foreach ($required_elements as $tag) {
        $total_checks++;
        $check = ['element' => '<' . $tag . '>', 'passed' => false];
        
        $pattern = '/<' . $tag . '[^>]*>/i';
        if (preg_match($pattern, $user_code)) {
            $passed_checks++;
            $check['passed'] = true;
            } else {
                $result['hints'][] = 'Tambahkan elemen <' . $tag . '> ke dalam kode Anda. Pastikan tag dibuka dan ditutup dengan benar.';
            }
        $result['checks'][] = $check;
    }
    
    // Check for CSS properties if solution has them
    if (preg_match('/<style[^>]*>([\s\S]*?)<\/style>/i', $solution_code, $styleMatch)) {
        $solution_css = $styleMatch[1] ?? '';
        
        // Extract CSS properties
        preg_match_all('/([\w-]+)\s*:/i', $solution_css, $cssProps);
        $required_css = array_unique($cssProps[1] ?? []);
        
        // Check user's CSS
        $user_css = '';
        if (preg_match('/<style[^>]*>([\s\S]*?)<\/style>/i', $user_code, $userStyleMatch)) {
            $user_css = $userStyleMatch[1] ?? '';
        }
        
        foreach ($required_css as $prop) {
            if (in_array($prop, ['webkit', 'moz', 'ms', 'o'])) continue; // Skip vendor prefixes
            
            $total_checks++;
            $check = ['element' => "CSS: $prop", 'passed' => false];
            
            if (stripos($user_css, $prop) !== false) {
                $passed_checks++;
                $check['passed'] = true;
            } else {
                $result['hints'][] = "Tambahkan properti CSS '$prop' di dalam tag <style> atau file CSS Anda.";
            }
            $result['checks'][] = $check;
        }
    }
    
    // Calculate score
    if ($total_checks > 0) {
        $result['score'] = round(($passed_checks / $total_checks) * 100);
    } else {
        $result['score'] = 100; // No specific checks, assume correct
    }
    
    // Determine validation result
    if ($result['score'] >= 80) {
        $result['is_valid'] = true;
        $result['message'] = 'Bagus! Kode Anda sudah benar! (' . $result['score'] . '%)';
    } elseif ($result['score'] >= 50) {
        $result['message'] = 'Hampir benar! Masih ada beberapa elemen yang kurang. (' . $result['score'] . '%)';
        if (empty($result['hints'])) {
            $result['hints'][] = 'Periksa kembali semua elemen yang diminta dalam instruksi';
            $result['hints'][] = 'Bandingkan hasil Anda dengan tab "Target" di preview';
        }
    } else {
        $result['message'] = 'Kode perlu diperbaiki. Lihat detail pengecekan dan hints di bawah. (' . $result['score'] . '%)';
        if (empty($result['hints'])) {
            $result['hints'][] = 'Baca kembali instruksi dengan teliti';
            $result['hints'][] = 'Pastikan semua elemen yang diminta sudah ada di kode Anda';
            $result['hints'][] = 'Bandingkan dengan tab "Target" untuk melihat contoh yang benar';
        }
    }
    
    // Limit hints to 3
    $result['hints'] = array_slice($result['hints'], 0, 3);
    
    return $result;
}

/**
 * Validate program output (Python, Java, PHP, etc)
 */
function validateProgramOutput($user_output, $solution_code, $user_code, $lesson_data) {
    $result = [
        'is_valid' => false,
        'message' => '',
        'hints' => [],
        'score' => 0
    ];
    
    // First, check if expected_output is set in the database
    $expected_output = $lesson_data['expected_output'] ?? '';
    
    // If not in database, try to extract from solution comments
    if (empty($expected_output)) {
        // Look for comments like // Expected: xxx or # Output: xxx
        if (preg_match('/(?:\/\/|#|\/\*)\s*(?:Expected|Output|Result|Hasil)[\s:]*(.+?)(?:\*\/|$)/im', $solution_code, $match)) {
            $expected_output = trim($match[1]);
        }
    }
    
    // If no expected output comment, try running solution (for simple cases)
    if (empty($expected_output)) {
        // For PHP, we can execute the solution
        if (stripos($solution_code, '<?php') !== false || stripos($solution_code, 'echo') !== false) {
            ob_start();
            try {
                // Security: only run if solution is simple
                $dangerous = ['exec', 'system', 'shell_exec', 'file_get_contents', 'file_put_contents'];
                $isSafe = true;
                foreach ($dangerous as $func) {
                    if (stripos($solution_code, $func) !== false) {
                        $isSafe = false;
                        break;
                    }
                }
                if ($isSafe && strlen($solution_code) < 2000) {
                    if (stripos($solution_code, '<?php') !== false) {
                        eval('?>' . $solution_code);
                    } else {
                        eval($solution_code);
                    }
                    $expected_output = ob_get_contents();
                }
            } catch (Throwable $e) {
                // Ignore errors
            }
            ob_end_clean();
        }
    }
    
    // Normalize outputs for comparison
    $normalized_user = normalizeOutput($user_output);
    $normalized_expected = normalizeOutput($expected_output);
    
    // Calculate similarity
    if (!empty($expected_output)) {
        // Exact match
        if ($normalized_user === $normalized_expected) {
            $result['is_valid'] = true;
            $result['score'] = 100;
            $result['message'] = 'Sempurna! Output sudah sesuai!';
        } 
        // Partial match (contains expected)
        elseif (strpos($normalized_user, $normalized_expected) !== false) {
            $result['is_valid'] = true;
            $result['score'] = 90;
            $result['message'] = 'Bagus! Output sudah benar!';
        }
        // Check similarity
        else {
            similar_text($normalized_user, $normalized_expected, $percent);
            $result['score'] = round($percent);
            
            if ($percent >= 80) {
                $result['is_valid'] = true;
                $result['message'] = 'Hampir sempurna! Output hampir sesuai. (' . round($percent) . '%)';
            } elseif ($percent >= 50) {
                $result['message'] = 'Perlu sedikit perbaikan. (' . round($percent) . '%)';
                $result['hints'][] = 'Periksa output program Anda';
                $result['hints'][] = 'Expected: ' . substr($expected_output, 0, 100) . (strlen($expected_output) > 100 ? '...' : '');
            } else {
                $result['message'] = 'Output belum sesuai. (' . round($percent) . '%)';
                $result['hints'][] = 'Periksa logika program Anda';
                if (!empty($expected_output)) {
                    $result['hints'][] = 'Expected output: ' . substr($expected_output, 0, 100) . (strlen($expected_output) > 100 ? '...' : '');
                }
            }
        }
    } else {
        // No expected output - validate based on code structure
        $result = validateCodeStructure($user_code, $solution_code, $lesson_data);
    }
    
    return $result;
}

/**
 * Validate code structure when no expected output
 */
function validateCodeStructure($user_code, $solution_code, $lesson_data) {
    $result = [
        'is_valid' => false,
        'message' => '',
        'hints' => [],
        'score' => 0,
        'checks' => []
    ];
    
    $total_checks = 0;
    $passed_checks = 0;
    
    // Find key patterns in solution
    $patterns = [
        'function' => '/function\s+(\w+)/i',
        'class' => '/class\s+(\w+)/i',
        'variable' => '/(\$\w+|let\s+\w+|var\s+\w+|const\s+\w+)\s*=/i',
        'loop' => '/(for|while|foreach)\s*\(/i',
        'condition' => '/if\s*\(/i',
        'print' => '/(print|echo|console\.log|System\.out|println)/i'
    ];
    
    foreach ($patterns as $name => $pattern) {
        if (preg_match($pattern, $solution_code)) {
            $total_checks++;
            $check = ['element' => ucfirst($name), 'passed' => false];
            
            if (preg_match($pattern, $user_code)) {
                $passed_checks++;
                $check['passed'] = true;
            } else {
                $hintMessage = "Kode Anda memerlukan: " . $name;
                if ($name === 'function') {
                    $hintMessage .= ". Pastikan Anda membuat function sesuai instruksi.";
                } elseif ($name === 'loop') {
                    $hintMessage .= ". Gunakan for, while, atau foreach sesuai kebutuhan.";
                } elseif ($name === 'condition') {
                    $hintMessage .= ". Gunakan if statement untuk kondisi tertentu.";
                } elseif ($name === 'print') {
                    $hintMessage .= ". Gunakan print/echo untuk menampilkan output.";
                }
                $result['hints'][] = $hintMessage;
            }
            $result['checks'][] = $check;
        }
    }
    
    // Calculate score
    if ($total_checks > 0) {
        $result['score'] = round(($passed_checks / $total_checks) * 100);
    } else {
        // No patterns to check, use text similarity
        $normalized_user = normalizeCode($user_code);
        $normalized_solution = normalizeCode($solution_code);
        
        similar_text($normalized_user, $normalized_solution, $percent);
        $result['score'] = round($percent);
    }
    
    // Determine result
    if ($result['score'] >= 70) {
        $result['is_valid'] = true;
        $result['message'] = 'Bagus! Kode Anda sudah benar! (' . $result['score'] . '%)';
    } elseif ($result['score'] >= 40) {
        $result['message'] = 'Hampir benar! Ada beberapa bagian yang perlu diperbaiki. (' . $result['score'] . '%)';
    } else {
        $result['message'] = 'Kode perlu diperbaiki. (' . $result['score'] . '%)';
    }
    
    $result['hints'] = array_slice($result['hints'], 0, 3);
    
    return $result;
}
