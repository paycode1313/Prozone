<?php
require_once 'config/config.php';
requireLogin();
require_once 'includes/icons.php';
require_once 'includes/language-icons.php';

require_once 'models/Course.php';
require_once 'models/Lesson.php';
require_once 'models/Enrollment.php';
require_once 'models/UserProgress.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);
$lesson = new Lesson($db);
$enrollment = new Enrollment($db);
$user_progress = new UserProgress($db);

// Get course and lesson
$course_id = $_GET['course_id'] ?? 0;
$lesson_id = $_GET['lesson_id'] ?? 0;

$course->id = $course_id;
$course_data = $course->readOne();

$lesson->id = $lesson_id;
$lesson->course_id = $course_id;
$lesson_data = $lesson->readOne();

if (!$course_data || !$lesson_data) {
    header('Location: courses.php');
    exit();
}

// Check enrollment
$is_enrolled = $enrollment->isEnrolled($_SESSION['user_id'], $course_id);
if (!$is_enrolled) {
    header('Location: course.php?id=' . $course_id);
    exit();
}

// Get all lessons for navigation
$lessons_stmt = $lesson->readByCourse($course_id);
$all_lessons = [];
while ($row = $lessons_stmt->fetch(PDO::FETCH_ASSOC)) {
    $all_lessons[] = $row;
}

// Get current lesson index
$current_index = 0;
foreach ($all_lessons as $index => $l) {
    if ($l['id'] == $lesson_id) {
        $current_index = $index;
        break;
    }
}

$prev_lesson = $current_index > 0 ? $all_lessons[$current_index - 1] : null;
$next_lesson = $current_index < count($all_lessons) - 1 ? $all_lessons[$current_index + 1] : null;

// Get or create user progress
$progress = $user_progress->getProgress($_SESSION['user_id'], $lesson_id);
if (!$progress) {
    $user_progress->user_id = $_SESSION['user_id'];
    $user_progress->course_id = $course_id;
    $user_progress->lesson_id = $lesson_id;
    $user_progress->status = 'not_started';
    $user_progress->create();
    $progress = $user_progress->getProgress($_SESSION['user_id'], $lesson_id);
}

// Determine lesson type
$lesson_type = $lesson_data['tipe'] ?? 'theory';

// Determine file extension early (needed for instruction parsing)
$course_lower = strtolower($course_data['judul_course'] ?? '');
$main_file = 'main.html'; // default

if (strpos($course_lower, 'python') !== false) {
    $main_file = 'main.py';
} elseif (strpos($course_lower, 'php') !== false) {
    $main_file = 'main.php';
} elseif (strpos($course_lower, 'javascript') !== false || strpos($course_lower, 'js') !== false) {
    $main_file = 'main.js';
} elseif (strpos($course_lower, 'java') !== false && strpos($course_lower, 'javascript') === false) {
    $main_file = 'Main.java';
} elseif (strpos($course_lower, 'c++') !== false || strpos($course_lower, 'cpp') !== false) {
    $main_file = 'main.cpp';
} elseif (strpos($course_lower, 'c') !== false && strpos($course_lower, 'css') === false) {
    $main_file = 'main.c';
}

// Parse instructions - Enhanced parsing
$instructions = [];
if ($lesson_data['instruksi']) {
    $parsed = json_decode($lesson_data['instruksi'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
        $instructions = $parsed;
    } else {
        // Try to parse structured text format
        $text = $lesson_data['instruksi'];
        
        // Check if it contains structured format (like "index.html" markers)
        if (preg_match('/index\.html|stylesheet\.css/i', $text)) {
            // Split by file markers
            $parts = preg_split('/(index\.html|stylesheet\.css)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
            $current_file = $main_file;
            $current_text = '';
            
            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part)) continue;
                
                if (preg_match('/\.(html|css)$/i', $part)) {
                    if (!empty($current_text)) {
                        $instructions[] = ['text' => $current_text, 'code' => '', 'file' => $current_file];
                        $current_text = '';
                    }
                    $current_file = $part;
                } else {
                    $current_text .= ($current_text ? "\n" : '') . $part;
                }
            }
            
            if (!empty($current_text)) {
                $instructions[] = ['text' => $current_text, 'code' => '', 'file' => $current_file];
            }
        } else {
            // Simple line-by-line parsing
            $lines = explode("\n", $text);
            $current_step = '';
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    if (!empty($current_step)) {
                        $instructions[] = ['text' => $current_step, 'code' => '', 'file' => $main_file];
                        $current_step = '';
                    }
                } else {
                    $current_step .= ($current_step ? "\n" : '') . $line;
                }
            }
            if (!empty($current_step)) {
                $instructions[] = ['text' => $current_step, 'code' => '', 'file' => $main_file];
            }
        }
    }
}

// Get user code
$user_html = '';
$user_css = '';
$user_code = $progress['kode_user'] ?? $lesson_data['kode_contoh'] ?? '';

if (strpos($user_code, '<style>') !== false || strpos($user_code, '<link') !== false) {
    preg_match('/<html[\s\S]*?<\/html>/i', $user_code, $html_match);
    $user_html = $html_match[0] ?? $user_code;
    preg_match('/<style>([\s\S]*?)<\/style>/i', $user_code, $css_match);
    $user_css = $css_match[1] ?? '';
} else {
    $user_html = $user_code;
}

// Determine file extension based on course (MUST BE BEFORE HTML OUTPUT)
$course_lower = strtolower($course_data['judul_course'] ?? '');
$file_ext = '.html';
$file_icon = 'globe';
$editor_mode = 'htmlmixed';
$default_code = '<!DOCTYPE html>
<html>
<head>
    <title>Hello World</title>
</head>
<body>
    <h1>Hello World!</h1>
    <p>Ini adalah paragraf pertama saya.</p>
</body>
</html>';

if (strpos($course_lower, 'python') !== false) {
    $file_ext = '.py';
    $file_icon = 'terminal';
    $editor_mode = 'python';
    $default_code = '# Python Code
print("Hello, World!")

# Tulis kode Anda di sini
';
} elseif (strpos($course_lower, 'php') !== false) {
    $file_ext = '.php';
    $file_icon = 'server';
    $editor_mode = 'php';
    $default_code = '<?php
// PHP Code
echo "Hello, World!";

// Tulis kode Anda di sini
?>';
} elseif (strpos($course_lower, 'javascript') !== false || strpos($course_lower, 'js') !== false) {
    $file_ext = '.js';
    $file_icon = 'lightning';
    $editor_mode = 'javascript';
    $default_code = '// JavaScript Code
console.log("Hello, World!");

// Tulis kode Anda di sini
';
} elseif (strpos($course_lower, 'java') !== false && strpos($course_lower, 'javascript') === false) {
    $file_ext = '.java';
    $file_icon = 'code';
    $editor_mode = 'text/x-java';
    $default_code = 'public class Main {
    public static void main(String[] args) {
        System.out.println("Hello, World!");
        
        // Tulis kode Anda di sini
    }
}';
} elseif (strpos($course_lower, 'c++') !== false || strpos($course_lower, 'cpp') !== false) {
    $file_ext = '.cpp';
    $file_icon = 'cpu';
    $editor_mode = 'text/x-c++src';
    $default_code = '#include <iostream>
using namespace std;

int main() {
    cout << "Hello, World!" << endl;
    
    // Tulis kode Anda di sini
    
    return 0;
}';
} elseif (strpos($course_lower, 'c') !== false && strpos($course_lower, 'css') === false) {
    $file_ext = '.c';
    $file_icon = 'cpu';
    $editor_mode = 'text/x-csrc';
    $default_code = '#include <stdio.h>

int main() {
    printf("Hello, World!\\n");
    
    // Tulis kode Anda di sini
    
    return 0;
}';
}

$show_css_tab = strpos($course_lower, 'html') !== false || strpos($course_lower, 'css') !== false;

// Get solution code
$solution_html = '';
$solution_css = '';
$solution_code = $lesson_data['kode_solusi'] ?? '';

if (strpos($solution_code, '<style>') !== false) {
    preg_match('/<html[\s\S]*?<\/html>/i', $solution_code, $html_match);
    $solution_html = $html_match[0] ?? $solution_code;
    preg_match('/<style>([\s\S]*?)<\/style>/i', $solution_code, $css_match);
    $solution_css = $css_match[1] ?? '';
} else {
    $solution_html = $solution_code;
}

// Handle save progress
if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        // If it's an AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF Token']);
            exit;
        } else {
            die('Sesi tidak valid (CSRF Token Error). Silakan refresh halaman.');
        }
    }

    if (isset($_POST['save_progress'])) {
        $user_progress->user_id = $_SESSION['user_id'];
    $user_progress->course_id = $course_id;
    $user_progress->lesson_id = $lesson_id;
    $user_progress->status = $_POST['status'] ?? 'in_progress';
    
    $combined_code = $_POST['html_code'] ?? '';
    if (!empty($_POST['css_code'])) {
        $combined_code = str_replace('</head>', '<style>' . $_POST['css_code'] . '</style></head>', $combined_code);
        if (strpos($combined_code, '</head>') === false) {
            $combined_code = '<!DOCTYPE html><html><head><style>' . $_POST['css_code'] . '</style></head><body>' . $combined_code . '</body></html>';
        }
    }
    
    $user_progress->kode_user = $combined_code;
    $user_progress->skor = $_POST['skor'] ?? 0;
    
    // Check if already completed before updating
    $existing_progress = $user_progress->getProgress($_SESSION['user_id'], $lesson_id);
    $was_completed = $existing_progress && $existing_progress['status'] === 'completed';
    
    if ($user_progress->updateOrCreate()) {
        // Award XP if newly completed
        if ($user_progress->status === 'completed' && !$was_completed) {
             $xp_earned = $lesson_data['xp_reward'] ?? 10;
             $update_xp = "UPDATE users 
                          SET total_xp = total_xp + :xp_earned,
                              level = FLOOR((total_xp + :xp_earned) / 100) + 1
                          WHERE id = :user_id";
             $stmt_xp = $db->prepare($update_xp);
             $stmt_xp->bindParam(':xp_earned', $xp_earned);
             $stmt_xp->bindParam(':user_id', $_SESSION['user_id']);
             $stmt_xp->execute();
             
             $_SESSION['xp_earned'] = $xp_earned;
        }

        $enrollment->updateProgress($_SESSION['user_id'], $course_id);
        
        $_SESSION['success_message'] = $user_progress->status == 'completed' 
            ? 'Lesson berhasil diselesaikan! ' . (isset($_SESSION['xp_earned']) ? '+' . $_SESSION['xp_earned'] . ' XP' : '')
            : 'Progress berhasil disimpan!';
        unset($_SESSION['xp_earned']);
        
        if ($user_progress->status == 'completed' && $next_lesson) {
            header('Location: lesson.php?course_id=' . $course_id . '&lesson_id=' . $next_lesson['id']);
            exit();
        } else {
            header('Location: lesson.php?course_id=' . $course_id . '&lesson_id=' . $lesson_id);
            exit();
        }
    } else {
        $_SESSION['error_message'] = 'Gagal menyimpan progress. Silakan coba lagi.';
    }
    } elseif (isset($_POST['complete_lesson'])) {
        $user_progress->user_id = $_SESSION['user_id'];
        $user_progress->course_id = $course_id;
        $user_progress->lesson_id = $lesson_id;
        $user_progress->status = 'completed';
        $user_progress->kode_user = 'QUIZ COMPLETED';
        $user_progress->skor = 100;
        
        // Check if already completed before updating
        $existing_progress = $user_progress->getProgress($_SESSION['user_id'], $lesson_id);
        $was_completed = $existing_progress && $existing_progress['status'] === 'completed';
        
        if ($user_progress->updateOrCreate()) {
            // Award XP if newly completed
            if (!$was_completed) {
                 $xp_earned = $lesson_data['xp_reward'] ?? 10;
                 $update_xp = "UPDATE users 
                              SET total_xp = total_xp + :xp_earned,
                                  level = FLOOR((total_xp + :xp_earned) / 100) + 1
                              WHERE id = :user_id";
                 $stmt_xp = $db->prepare($update_xp);
                 $stmt_xp->bindParam(':xp_earned', $xp_earned);
                 $stmt_xp->bindParam(':user_id', $_SESSION['user_id']);
                 $stmt_xp->execute();
                 
                 $_SESSION['xp_earned'] = $xp_earned;
            }

            $enrollment->updateProgress($_SESSION['user_id'], $course_id);
            
            $_SESSION['success_message'] = '🎉 Quiz berhasil diselesaikan! ' . (isset($_SESSION['xp_earned']) ? '+' . $_SESSION['xp_earned'] . ' XP' : '');
            unset($_SESSION['xp_earned']);
            
            if ($next_lesson) {
                header('Location: lesson.php?course_id=' . $course_id . '&lesson_id=' . $next_lesson['id']);
                exit();
            } else {
                header('Location: course.php?id=' . $course_id);
                exit();
            }
        } else {
            $_SESSION['error_message'] = 'Gagal menyimpan progress quiz.';
        }
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta(htmlspecialchars($lesson_data['judul_lesson']) . ' - ' . APP_NAME, 'Pelajari ' . htmlspecialchars($lesson_data['judul_lesson']), 'lesson, tutorial, coding'); ?>
    <title><?php echo htmlspecialchars($lesson_data['judul_lesson']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/lesson-enhanced.css">
    <!-- Parsedown for Markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    
    <!-- CodeMirror JS - Load in head for all lessons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closetag.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    <style>
        /* ============================================
           MODERN LESSON UI - PREMIUM DESIGN
           Glass morphism, smooth animations, 
           beautiful gradients
           ============================================ */
        :root {
            /* Enhanced Color Palette - Vibrant & Modern */
            --primary-purple: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-light: #a78bfa;
            --primary-glow: rgba(139, 92, 246, 0.5);
            --accent-cyan: #06b6d4;
            --accent-pink: #ec4899;
            --accent-green: #10b981;
            --accent-orange: #f97316;
            --accent-yellow: #eab308;
            
            /* Background Layers - Deeper & Richer */
            --bg-dark: #050508;
            --bg-secondary: #0c0c14;
            --bg-tertiary: #141420;
            --bg-card: #1a1a28;
            --bg-elevated: #222233;
            --bg-glass: rgba(26, 26, 40, 0.85);
            --bg-glass-light: rgba(40, 40, 60, 0.6);
            
            /* Text Colors - Better Contrast */
            --text-primary: #f8fafc;
            --text-secondary: #e2e8f0;
            --text-muted: #94a3b8;
            --text-accent: #c4b5fd;
            
            /* Borders & Effects */
            --border-subtle: rgba(139, 92, 246, 0.15);
            --border-glow: rgba(139, 92, 246, 0.3);
            --border-dark: rgba(255, 255, 255, 0.06);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 4px 24px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 12px 48px rgba(0, 0, 0, 0.6);
            --shadow-glow: 0 0 40px rgba(139, 92, 246, 0.2);
            --shadow-glow-cyan: 0 0 30px rgba(6, 182, 212, 0.15);
            
            /* 8-point spacing scale */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            
            /* Typography - Refined */
            --text-xs: 0.6875rem;
            --text-sm: 0.75rem;
            --text-base: 0.8125rem;
            --text-md: 0.875rem;
            --text-lg: 1rem;
            --text-xl: 1.125rem;
            --text-2xl: 1.375rem;
            --text-3xl: 1.75rem;
            
            /* Animation - Smoother */
            --transition-fast: 0.12s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
            scroll-behavior: smooth;
        }

        body {
            background: var(--bg-dark);
            background-image: 
                radial-gradient(ellipse at 0% 0%, rgba(139, 92, 246, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 100%, rgba(6, 182, 212, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(236, 72, 153, 0.03) 0%, transparent 70%);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: var(--text-base);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Custom Scrollbar - Refined */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(10, 10, 20, 0.8);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, 
                rgba(139, 92, 246, 0.6) 0%, 
                rgba(109, 40, 217, 0.5) 100%);
            border-radius: 3px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, 
                rgba(167, 139, 250, 0.7) 0%, 
                rgba(139, 92, 246, 0.6) 100%);
        }

        ::-webkit-scrollbar-corner {
            background: rgba(10, 10, 20, 0.8);
        }

        /* Selection styling */
        ::selection {
            background: rgba(139, 92, 246, 0.4);
            color: #fff;
        }

        .dark-mode-toggle {
            display: none;
        }

        /* ============================================
           LESSON HEADER - SLEEK & MODERN
           ============================================ */
        .lesson-header {
            background: linear-gradient(135deg, 
                rgba(109, 40, 217, 0.98) 0%, 
                rgba(124, 58, 237, 0.95) 35%,
                rgba(139, 92, 246, 0.92) 100%);
            backdrop-filter: blur(20px);
            color: white;
            padding: var(--space-3) var(--space-5);
            margin-top: 56px;
            box-shadow: 
                0 4px 30px rgba(109, 40, 217, 0.4),
                0 1px 0 rgba(255, 255, 255, 0.15) inset,
                0 -1px 0 rgba(0, 0, 0, 0.1) inset;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .lesson-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.03) 50%, transparent 100%),
                url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='M20 20.5V19h-.5v.5h.5zm0-1h.5v-.5h-.5v.5z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }

        .lesson-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            animation: headerShimmer 8s ease-in-out infinite;
        }

        @keyframes headerShimmer {
            0%, 100% { transform: translateX(-50%); }
            50% { transform: translateX(50%); }
        }

        .lesson-header-content {
            max-width: 1800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--space-3);
            position: relative;
            z-index: 1;
        }

        .lesson-title-section h1 {
            margin: 0;
            font-size: var(--text-lg);
            font-weight: 600;
            letter-spacing: -0.01em;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }
        
        .lesson-title-with-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .lesson-language-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3)) brightness(1.1);
            transition: transform var(--transition-normal);
        }

        .lesson-language-logo:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .lesson-title-section p {
            margin: var(--space-1) 0 0 0;
            opacity: 0.9;
            font-size: var(--text-sm);
            font-weight: 400;
        }

        .lesson-navigation {
            display: flex;
            gap: var(--space-2);
        }

        .nav-btn {
            padding: var(--space-2) var(--space-4);
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            font-size: var(--text-sm);
            transition: all var(--transition-normal);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
        }

        .nav-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* ============================================
           MAIN LAYOUT - 3 PANEL (FIT SCREEN)
           ============================================ */
        .lesson-wrapper {
            display: grid;
            grid-template-columns: 420px 1fr 350px;
            height: calc(100vh - 106px);
            overflow: hidden;
            gap: 0;
            background: var(--bg-dark);
        }

        @media (max-width: 1400px) {
            .lesson-wrapper {
                grid-template-columns: 380px 1fr 320px;
            }
        }

        @media (max-width: 1200px) {
            .lesson-wrapper {
                grid-template-columns: 350px 1fr 280px;
            }
        }

        @media (max-width: 1024px) {
            .lesson-wrapper {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr 1fr;
                height: auto;
                min-height: calc(100vh - 56px);
            }
        }

        .lesson-wrapper.mode-theory {
            grid-template-columns: 1fr;
            height: auto;
            min-height: calc(100vh - 56px);
            padding: var(--space-6);
            overflow-x: hidden;
            background: radial-gradient(ellipse at center top, rgba(139, 92, 246, 0.06) 0%, transparent 60%);
        }

        /* ============================================
           SLIDE CONTAINER - THEORY MODE
           ============================================ */
        .slide-container {
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
        }

        .slide-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-5);
            padding: var(--space-4);
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border-radius: 1rem;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-md), var(--shadow-glow);
        }

        .slide-nav-btn {
            padding: var(--space-3) var(--space-5);
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 0.625rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            font-size: var(--text-sm);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.35);
        }

        .slide-nav-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.45);
        }

        .slide-nav-btn:active:not(:disabled) {
            transform: translateY(-1px);
        }

        .slide-nav-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            box-shadow: none;
        }

        .slide-indicator {
            font-size: var(--text-lg);
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-light), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .slide-progress {
            width: 100%;
            height: 6px;
            background: var(--bg-tertiary);
            border-radius: 3px;
            margin-bottom: var(--space-5);
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .slide-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, 
                var(--primary-purple) 0%, 
                var(--accent-cyan) 50%, 
                var(--accent-green) 100%);
            border-radius: 3px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            width: 0%;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.5);
        }

        .slides-wrapper {
            position: relative;
            min-height: 500px;
        }

        .slide-card {
            display: none;
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border-radius: 1.25rem;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-lg), var(--shadow-glow);
            padding: var(--space-8);
            animation: slideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 500px;
        }

        .slide-card.active {
            display: block;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        .slide-header {
            margin-bottom: var(--space-6);
            padding-bottom: var(--space-4);
            border-bottom: 2px solid;
            border-image: linear-gradient(90deg, var(--primary-purple), var(--accent-cyan), transparent) 1;
        }

        .slide-title {
            font-size: var(--text-2xl);
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .slide-body {
            min-height: 350px;
        }

        .slide-content {
            font-size: var(--text-md);
            line-height: 1.8;
            color: var(--text-secondary);
        }

        /* Box Model Visualization - Compact */
        .visual-box-model {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-6);
            align-items: start;
        }

        @media (max-width: 968px) {
            .visual-box-model {
                grid-template-columns: 1fr;
            }
        }

        .box-model-container {
            position: relative;
            padding: var(--space-4);
        }

        .box-margin {
            background: rgba(239, 68, 68, 0.1);
            border: 2px dashed #ef4444;
            padding: var(--space-4);
            border-radius: 0.375rem;
            position: relative;
        }

        .box-border {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid #f59e0b;
            padding: var(--space-4);
            border-radius: 0.375rem;
            position: relative;
        }

        .box-padding {
            background: rgba(59, 130, 246, 0.1);
            border: 2px dashed #3b82f6;
            padding: var(--space-4);
            border-radius: 0.375rem;
            position: relative;
        }

        .box-content {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid #10b981;
            padding: var(--space-4);
            border-radius: 0.375rem;
            text-align: center;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .box-label {
            position: absolute;
            top: -10px;
            left: 8px;
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: var(--text-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .box-content .box-label {
            position: static;
            margin-bottom: var(--space-1);
        }

        .box-content-text {
            font-size: var(--text-lg);
            font-weight: 600;
            color: var(--primary-light);
        }

        .box-model-explanation {
            display: flex;
            flex-direction: column;
            gap: var(--space-3);
        }

        .explanation-item {
            display: flex;
            gap: var(--space-3);
            align-items: start;
            padding: var(--space-3);
            background: var(--bg-tertiary);
            border-radius: 0.375rem;
            border-left: 3px solid;
        }

        .color-indicator {
            width: 28px;
            height: 28px;
            border-radius: 0.375rem;
            flex-shrink: 0;
            border: 2px solid;
        }

        .color-indicator.margin {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
        }

        .color-indicator.border {
            background: rgba(245, 158, 11, 0.2);
            border-color: #f59e0b;
        }

        .color-indicator.padding {
            background: rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
        }

        .color-indicator.content {
            background: rgba(16, 185, 129, 0.2);
            border-color: #10b981;
        }

        .explanation-item strong {
            display: block;
            font-size: var(--text-md);
            margin-bottom: var(--space-1);
            color: var(--text-primary);
        }

        .explanation-item p {
            margin: 0;
            color: var(--text-muted);
            font-size: var(--text-sm);
        }

        /* Shorthand Visualization - Compact */
        .visual-shorthand {
            display: flex;
            flex-direction: column;
            gap: var(--space-6);
        }

        .shorthand-comparison {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            padding: var(--space-4);
            background: var(--bg-tertiary);
            border-radius: 0.75rem;
            border: 1px solid var(--border-dark);
        }

        .shorthand-long, .shorthand-short {
            flex: 1;
        }

        .shorthand-arrow {
            font-size: var(--text-2xl);
            color: var(--primary-light);
            font-weight: 700;
        }

        .shorthand-comparison h3 {
            margin: 0 0 var(--space-3) 0;
            font-size: var(--text-md);
            color: var(--text-primary);
        }

        .code-example {
            background: #0f0f1a;
            color: #e2e8f0;
            padding: var(--space-3);
            border-radius: 0.375rem;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: var(--text-sm);
            line-height: 1.6;
            border: 1px solid var(--border-dark);
        }

        .code-example.highlight {
            border-color: var(--primary-purple);
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.2);
        }

        .shorthand-rules {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-3);
        }

        @media (max-width: 768px) {
            .shorthand-rules {
                grid-template-columns: 1fr;
            }
        }

        .shorthand-rules h4 {
            grid-column: 1 / -1;
            margin: 0 0 var(--space-2) 0;
            font-size: var(--text-lg);
            color: var(--text-primary);
        }

        .rule-item {
            padding: var(--space-3);
            background: var(--bg-card);
            border-radius: 0.5rem;
            border-left: 3px solid var(--primary-purple);
            box-shadow: 0 1px 4px rgba(139, 92, 246, 0.1);
            transition: all 0.2s ease;
        }

        .rule-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.15);
        }

        .rule-item strong {
            display: block;
            margin-bottom: var(--space-1);
            color: var(--primary-light);
            font-size: var(--text-md);
        }

        .rule-item code {
            background: var(--bg-tertiary);
            color: var(--primary-light);
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: var(--text-sm);
        }

        /* ============================================
           LEFT PANEL - INSTRUCTIONS (BEAUTIFIED)
           ============================================ */
        .instructions-panel {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-dark) 100%);
            border-right: 1px solid var(--border-dark);
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-width: 0;
            position: relative;
        }

        .instructions-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(180deg, rgba(139, 92, 246, 0.06) 0%, transparent 100%);
            pointer-events: none;
            z-index: 0;
        }

        .instructions-header {
            padding: var(--space-3) var(--space-4);
            border-bottom: 1px solid var(--border-subtle);
            background: linear-gradient(180deg, var(--bg-card) 0%, var(--bg-tertiary) 100%);
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        .instructions-panel h2 {
            color: var(--text-primary);
            font-size: var(--text-md);
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .instructions-panel h2 svg {
            color: var(--primary-light);
        }

        .instructions-subtitle {
            color: var(--text-muted);
            font-size: var(--text-xs);
            margin-top: 2px;
            opacity: 0.8;
        }

        .instructions-content {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: var(--space-3) var(--space-4);
            position: relative;
            z-index: 1;
        }

        /* Instruction intro text */
        .instruction-intro {
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.6;
            margin-bottom: var(--space-3);
        }

        /* Instruction highlight box - Enhanced */
        .instruction-box {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.12) 0%, 
                rgba(109, 40, 217, 0.08) 50%,
                rgba(6, 182, 212, 0.04) 100%);
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-left: 3px solid var(--primary-purple);
            padding: var(--space-3) var(--space-4);
            border-radius: 0 0.625rem 0.625rem 0;
            margin-bottom: var(--space-3);
            position: relative;
            backdrop-filter: blur(4px);
            transition: all var(--transition-normal);
        }

        .instruction-number {
            position: absolute;
            top: -6px;
            left: -8px;
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            box-shadow: 0 2px 10px rgba(139, 92, 246, 0.5);
            border: 2px solid var(--bg-secondary);
        }

        .instruction-box:hover {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.18) 0%, 
                rgba(109, 40, 217, 0.12) 50%,
                rgba(6, 182, 212, 0.06) 100%);
            border-color: rgba(139, 92, 246, 0.25);
            transform: translateX(2px);
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.15);
        }

        .instruction-box p {
            color: var(--text-secondary);
            line-height: 1.65;
            margin: 0;
            font-size: var(--text-sm);
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: pre-wrap;
        }

        .instruction-box h2 {
            color: var(--primary-light);
            font-size: var(--text-lg);
            font-weight: 600;
            margin: 0 0 var(--space-2) 0;
        }

        .instruction-box h3 {
            color: var(--text-accent);
            font-size: var(--text-md);
            font-weight: 600;
            margin: var(--space-2) 0;
        }

        .instruction-box ol,
        .instruction-box ul {
            margin: var(--space-2) 0;
            padding-left: var(--space-5);
            color: var(--text-secondary);
            font-size: var(--text-sm);
        }

        .instruction-box li {
            margin: var(--space-2) 0;
            line-height: 1.6;
        }

        .instruction-box strong {
            color: #a78bfa;
            font-weight: 600;
        }

        .instruction-box code {
            background: rgba(139, 92, 246, 0.15);
            color: #c4b5fd;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .instruction-box pre {
            background: rgba(15, 15, 35, 0.8);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 0.5rem;
            padding: var(--space-3);
            overflow-x: auto;
            margin: var(--space-3) 0;
        }

        .instruction-box pre code {
            background: none;
            padding: 0;
            color: #e0e7ff;
            display: block;
            line-height: 1.5;
        }

        .instruction-box .tips {
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid #10b981;
            padding: var(--space-3);
            margin: var(--space-3) 0;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .instruction-box .tips strong {
            color: #10b981;
        }

        .file-list {
            margin: var(--space-3) 0;
            padding: var(--space-3);
            background: var(--bg-tertiary);
            border-radius: 0.375rem;
            border: 1px solid var(--border-dark);
        }

        .file-list-title {
            font-size: var(--text-xs);
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: var(--space-2);
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2);
            margin-bottom: var(--space-1);
            background: var(--bg-card);
            border-radius: 0.375rem;
            border: 1px solid var(--border-dark);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .file-item:hover {
            background: var(--bg-tertiary);
            border-color: var(--primary-purple);
            transform: translateX(3px);
        }

        .file-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border-radius: 0.25rem;
            font-size: var(--text-xs);
            font-weight: 600;
            flex-shrink: 0;
        }

        .file-name {
            font-size: var(--text-sm);
            font-weight: 500;
            color: var(--text-primary);
            font-family: 'Fira Code', 'Courier New', monospace;
        }

        .file-label {
            display: inline-flex;
            align-items: center;
            gap: var(--space-1);
            margin-top: var(--space-3);
            padding: var(--space-1) var(--space-3);
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            color: white;
            border-radius: 0.375rem;
            font-size: var(--text-xs);
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3);
        }

        .code-snippet {
            background: #0f0f1a;
            color: #e2e8f0;
            padding: 0;
            border-radius: 0.375rem;
            margin: var(--space-2) 0;
            font-family: 'Courier New', monospace;
            font-size: var(--text-sm);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-dark);
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-2) var(--space-3);
            background: rgba(139, 92, 246, 0.15);
            border-bottom: 1px solid var(--border-dark);
        }

        .code-label {
            color: #a78bfa;
            font-size: var(--text-xs);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .code-snippet code {
            display: block;
            padding: var(--space-3);
            overflow-x: auto;
            white-space: pre;
        }

        .copy-btn {
            background: #8b5cf6;
            color: white;
            border: none;
            padding: var(--space-1) var(--space-2);
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: var(--text-xs);
            font-weight: 500;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: #7c3aed;
        }

        /* Instruction Section Styling */
        .instruction-section {
            margin-top: var(--space-4);
        }

        .instruction-section-title {
            color: var(--primary-light);
            font-size: var(--text-lg);
            font-weight: 600;
            margin: 0 0 var(--space-3) 0;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .instruction-intro {
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.7;
            margin-bottom: var(--space-4);
            padding: var(--space-3);
            background: rgba(139, 92, 246, 0.08);
            border-left: 3px solid var(--primary-purple);
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .instruction-step-title {
            color: var(--text-accent);
            font-size: var(--text-md);
            font-weight: 600;
            margin: var(--space-2) 0 var(--space-2) 0;
            line-height: 1.4;
        }

        .instruction-text {
            color: var(--text-secondary);
            line-height: 1.7;
            margin: var(--space-2) 0;
            font-size: var(--text-sm);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .file-info {
            margin-top: var(--space-3);
            padding-top: var(--space-2);
            border-top: 1px solid rgba(139, 92, 246, 0.2);
        }

        .file-info .file-label {
            margin-top: 0;
        }

        .instruction-tip {
            margin-top: var(--space-4);
            padding: var(--space-3) var(--space-4);
            background: linear-gradient(135deg, 
                rgba(16, 185, 129, 0.12) 0%, 
                rgba(5, 150, 105, 0.08) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-left: 3px solid #10b981;
            border-radius: 0 0.5rem 0.5rem 0;
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.6;
        }

        .instruction-tip strong {
            color: #10b981;
            font-weight: 600;
        }

        .tip-list {
            margin: 0.75rem 0 0 0;
            padding-left: 1.5rem;
            list-style: none;
        }

        .tip-list li {
            margin: 0.5rem 0;
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.6;
            position: relative;
        }

        .tip-list li::before {
            content: '✓';
            position: absolute;
            left: -1.5rem;
            color: #10b981;
            font-weight: 700;
        }

        /* How to Complete Section */
        .how-to-complete-section {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.12) 0%, 
                rgba(109, 40, 217, 0.08) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-left: 4px solid var(--primary-purple);
            border-radius: 0 0.75rem 0.75rem 0;
            padding: var(--space-4);
            margin-bottom: var(--space-4);
        }

        .how-to-title {
            color: var(--primary-light);
            font-size: var(--text-md);
            font-weight: 600;
            margin: 0 0 var(--space-3) 0;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .completion-steps {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .completion-steps li {
            margin: var(--space-2) 0;
            padding: var(--space-2) var(--space-3);
            background: rgba(139, 92, 246, 0.08);
            border-radius: 0.5rem;
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.6;
            position: relative;
            padding-left: 2.5rem;
        }

        .completion-steps li::before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            left: var(--space-2);
            top: var(--space-2);
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .completion-steps {
            counter-reset: step-counter;
        }

        .completion-steps li strong {
            color: var(--text-accent);
            font-weight: 600;
        }

        /* Visual Steps */
        .visual-steps {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .visual-step {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: rgba(139, 92, 246, 0.08);
            border-radius: 0.75rem;
            border-left: 4px solid var(--primary-purple);
            transition: all 0.3s ease;
        }

        .visual-step:hover {
            background: rgba(139, 92, 246, 0.12);
            transform: translateX(4px);
        }

        .visual-step-number {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .visual-step-content {
            flex: 1;
        }

        .visual-step-content strong {
            display: block;
            color: var(--primary-light);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .visual-step-content p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .visual-step-content strong {
            color: var(--text-accent);
        }

        /* Enhanced Instruction Intro */
        .instruction-intro-enhanced {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.15) 0%, 
                rgba(109, 40, 217, 0.1) 100%);
            border: 1px solid rgba(139, 92, 246, 0.25);
            border-left: 4px solid var(--primary-purple);
            border-radius: 0 0.75rem 0.75rem 0;
            margin-bottom: 1.5rem;
        }

        .intro-icon {
            font-size: 2rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .intro-content {
            flex: 1;
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .intro-content strong {
            color: var(--primary-light);
            font-weight: 600;
        }

        /* Instruction Action */
        .instruction-action {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-2);
            padding: var(--space-2);
            background: rgba(139, 92, 246, 0.1);
            border-radius: 0.5rem;
        }

        .action-icon {
            font-size: 1.2rem;
        }

        .action-text {
            color: var(--primary-light);
            font-weight: 600;
            font-size: var(--text-sm);
        }

        .instruction-content-text {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-top: var(--space-2);
        }

        .instruction-content-text p {
            margin: 0.5rem 0;
            color: var(--text-secondary);
        }

        .instruction-bullet {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .instruction-bullet::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--primary-purple);
            font-weight: 700;
        }

        /* Code Instruction */
        .code-instruction {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-top: var(--space-2);
            padding: var(--space-2) var(--space-3);
            background: rgba(139, 92, 246, 0.08);
            border-radius: 0.5rem;
            color: var(--text-secondary);
            font-size: var(--text-xs);
            border-left: 3px solid var(--primary-purple);
        }

        .code-instruction-icon {
            font-size: 1rem;
        }

        /* Completion Guide */
        .completion-guide {
            background: linear-gradient(135deg, 
                rgba(16, 185, 129, 0.12) 0%, 
                rgba(5, 150, 105, 0.08) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-left: 4px solid #10b981;
            border-radius: 0 0.75rem 0.75rem 0;
            padding: var(--space-4);
            margin-top: var(--space-4);
        }

        .guide-title {
            color: #10b981;
            font-size: var(--text-md);
            font-weight: 600;
            margin: 0 0 var(--space-3) 0;
        }

        .guide-steps {
            display: grid;
            gap: var(--space-2);
        }

        .guide-step {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-2);
            background: rgba(16, 185, 129, 0.08);
            border-radius: 0.5rem;
        }

        .guide-step-number {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .guide-step-content {
            flex: 1;
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.6;
        }

        .guide-step-content strong {
            color: #10b981;
            font-weight: 600;
        }

        /* Help Section */
        .help-section {
            margin-top: var(--space-4);
            padding: var(--space-4);
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.12) 0%, 
                rgba(37, 99, 235, 0.08) 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-left: 4px solid #3b82f6;
            border-radius: 0 0.75rem 0.75rem 0;
        }

        .help-title {
            color: #3b82f6;
            font-size: var(--text-md);
            font-weight: 600;
            margin: 0 0 var(--space-3) 0;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .help-content {
            display: grid;
            gap: var(--space-3);
        }

        .help-step {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-2);
            background: rgba(59, 130, 246, 0.08);
            border-radius: 0.5rem;
        }

        .help-step-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
            line-height: 1;
        }

        .help-step-text {
            flex: 1;
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.6;
        }

        .help-step-text strong {
            color: #60a5fa;
            font-weight: 600;
            display: block;
            margin-bottom: 0.25rem;
        }

        .instructions-footer {
            margin-top: auto;
            padding: var(--space-2) var(--space-3);
            border-top: 1px solid var(--border-dark);
            flex-shrink: 0;
            background: var(--bg-card);
        }

        .back-to-slide-btn {
            width: 100%;
            padding: var(--space-2) var(--space-3);
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: var(--text-xs);
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
        }

        .back-to-slide-btn:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        }

        /* ============================================
           TASK SUMMARY SECTION
           ============================================ */
        .task-summary {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
            border: 1px solid rgba(251, 191, 36, 0.25);
            border-left: 3px solid #fbbf24;
            border-radius: 0 0.5rem 0.5rem 0;
            padding: var(--space-3);
            margin: var(--space-3) 0;
            backdrop-filter: blur(10px);
        }

        .task-summary h4 {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            color: #fbbf24;
            font-size: var(--text-sm);
            font-weight: 700;
            margin: 0 0 var(--space-2) 0;
        }

        .task-summary h4 svg {
            color: #fbbf24;
        }

        .task-box {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .task-step {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: var(--text-xs);
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .task-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border-radius: 50%;
            font-size: 0.65rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .task-step strong {
            color: #fbbf24;
        }

        /* ============================================
           SUCCESS CRITERIA SECTION
           ============================================ */
        .success-criteria {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.03) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-left: 3px solid #10b981;
            border-radius: 0 0.5rem 0.5rem 0;
            padding: var(--space-3);
            margin: var(--space-3) 0;
            backdrop-filter: blur(10px);
        }

        .success-criteria h4 {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            color: #10b981;
            font-size: var(--text-sm);
            font-weight: 700;
            margin: 0 0 var(--space-2) 0;
        }

        .success-criteria h4 svg {
            color: #10b981;
        }

        .criteria-info p {
            color: var(--text-secondary);
            font-size: var(--text-xs);
            margin: 0 0 var(--space-2) 0;
        }

        .criteria-info ul {
            list-style: none;
            padding: 0;
            margin: 0 0 var(--space-2) 0;
        }

        .criteria-info ul li {
            display: flex;
            align-items: flex-start;
            gap: var(--space-2);
            color: var(--text-secondary);
            font-size: var(--text-xs);
            padding: var(--space-1) 0;
            line-height: 1.5;
        }

        .check-icon {
            color: #10b981;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .score-info {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding-top: var(--space-2);
            border-top: 1px dashed rgba(16, 185, 129, 0.2);
            color: var(--text-secondary);
            font-size: var(--text-xs);
        }

        .score-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .score-badge.success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        /* ============================================
           PRACTICE TIPS
           ============================================ */
        .practice-tips {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(34, 211, 238, 0.05) 100%);
            border: 1px solid var(--border-subtle);
            border-left: 3px solid var(--accent-cyan);
            padding: var(--space-2) var(--space-3);
            border-radius: 0 0.5rem 0.5rem 0;
            margin: var(--space-2) 0;
            backdrop-filter: blur(10px);
        }

        .practice-tips h4 {
            color: var(--accent-cyan);
            font-size: var(--text-sm);
            font-weight: 700;
            margin: 0 0 var(--space-2) 0;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .practice-tips ul {
            margin: 0;
            padding-left: var(--space-4);
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: var(--text-xs);
        }

        .practice-tips li {
            margin-bottom: var(--space-2);
        }

        .practice-tips strong {
            color: var(--primary-light);
            font-weight: 600;
        }

        /* ============================================
           EDITOR PANEL - MODERN IDE LOOK
           ============================================ */
        .editor-panel {
            background: linear-gradient(180deg, #0a0a14 0%, #08080f 100%);
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(139, 92, 246, 0.1);
            overflow: hidden;
            position: relative;
        }

        .editor-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: 
                radial-gradient(ellipse at 0% 50%, rgba(139, 92, 246, 0.03) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 80%, rgba(6, 182, 212, 0.02) 0%, transparent 40%);
            pointer-events: none;
            z-index: 0;
        }

        .editor-tabs {
            display: flex;
            background: linear-gradient(180deg, rgba(20, 20, 35, 0.95) 0%, rgba(15, 15, 25, 0.95) 100%);
            border-bottom: 1px solid rgba(139, 92, 246, 0.15);
            padding: 0;
            gap: 0;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .editor-tab {
            padding: var(--space-2) var(--space-4);
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-weight: 500;
            font-size: var(--text-xs);
            border-bottom: 2px solid transparent;
            transition: all var(--transition-normal);
            position: relative;
            letter-spacing: 0.3px;
        }

        .editor-tab.active {
            color: #ffffff;
            background: linear-gradient(180deg, rgba(139, 92, 246, 0.15) 0%, transparent 100%);
            border-bottom-color: transparent;
        }

        .editor-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-purple) 0%, var(--accent-cyan) 100%);
        }

        .editor-tab:hover:not(.active) {
            color: var(--text-secondary);
            background: rgba(139, 92, 246, 0.08);
        }

        .editor-content {
            flex: 1;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        /* ============================================
           CODE EDITOR - PREMIUM STYLING
           ============================================ */
        .CodeMirror {
            height: 100% !important;
            font-size: 13px;
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            line-height: 1.65;
            background: transparent !important;
            border-radius: 0;
        }

        .CodeMirror-lines {
            padding: var(--space-3) 0;
        }

        .CodeMirror-scroll {
            overflow-x: auto !important;
            overflow-y: auto !important;
        }

        .CodeMirror-gutters {
            background: linear-gradient(180deg, rgba(10, 10, 20, 0.8), rgba(5, 5, 12, 0.9)) !important;
            border-right: 1px solid rgba(139, 92, 246, 0.1) !important;
            padding-right: 10px;
        }

        .CodeMirror-linenumber {
            color: rgba(139, 92, 246, 0.4) !important;
            font-size: 11px;
            padding-right: 8px;
        }

        .CodeMirror-cursor {
            border-left: 2px solid var(--accent-cyan) !important;
            animation: cursorBlink 1s ease-in-out infinite;
        }

        @keyframes cursorBlink {
            0%, 45% { opacity: 1; }
            50%, 95% { opacity: 0; }
            100% { opacity: 1; }
        }

        .CodeMirror-selected {
            background: rgba(139, 92, 246, 0.2) !important;
        }

        .CodeMirror-activeline-background {
            background: rgba(139, 92, 246, 0.06) !important;
        }

        .CodeMirror-matchingbracket {
            color: var(--accent-cyan) !important;
            background: rgba(6, 182, 212, 0.15);
            border-radius: 2px;
            font-weight: 600;
        }

        /* ============================================
           ACTION BUTTONS - SIMPLIFIED
           ============================================ */
        .editor-actions {
            padding: var(--space-2) var(--space-3);
            background: linear-gradient(180deg, rgba(15, 15, 25, 0.98) 0%, rgba(10, 10, 18, 0.98) 100%);
            border-top: 1px solid rgba(139, 92, 246, 0.12);
            display: flex;
            gap: var(--space-3);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .btn-run {
            flex: 1;
            padding: var(--space-2) var(--space-4);
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 0.625rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            font-size: var(--text-sm);
            box-shadow: 
                0 4px 15px rgba(59, 130, 246, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
            position: relative;
            overflow: hidden;
        }

        .btn-run::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .btn-run:hover::before {
            left: 100%;
        }

        .btn-run:hover:not(:disabled) {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 50%, #2563eb 100%);
            transform: translateY(-2px);
            box-shadow: 
                0 8px 25px rgba(59, 130, 246, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .btn-run:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.3);
        }

        .btn-run:disabled {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            cursor: not-allowed;
            opacity: 0.6;
            box-shadow: none;
        }

        .btn-submit {
            flex: 1;
            padding: var(--space-2) var(--space-4);
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            border: none;
            border-radius: 0.625rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            font-size: var(--text-sm);
            box-shadow: 
                0 4px 15px rgba(16, 185, 129, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover:not(:disabled) {
            background: linear-gradient(135deg, #34d399 0%, #10b981 50%, #059669 100%);
            transform: translateY(-2px);
            box-shadow: 
                0 8px 25px rgba(16, 185, 129, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .btn-submit:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
        }

        .btn-submit:disabled {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            cursor: not-allowed;
            opacity: 0.6;
            box-shadow: none;
        }

        /* ============================================
           SUCCESS MODAL
           ============================================ */
        .success-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(5, 5, 15, 0.9);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: overlayFadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes overlayFadeIn {
            from { opacity: 0; backdrop-filter: blur(0px); }
            to { opacity: 1; backdrop-filter: blur(12px); }
        }

        .success-modal {
            background: linear-gradient(180deg, 
                rgba(20, 20, 35, 0.98) 0%, 
                rgba(12, 12, 22, 0.98) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 
                0 25px 80px rgba(0, 0, 0, 0.6),
                0 0 80px rgba(139, 92, 246, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: modalSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }

        .success-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, 
                var(--primary-purple) 0%, 
                var(--accent-cyan) 50%,
                var(--primary-purple) 100%);
            background-size: 200% 100%;
            animation: shimmerBar 2s linear infinite;
        }

        @keyframes shimmerBar {
            from { background-position: 200% 0; }
            to { background-position: -200% 0; }
        }

        @keyframes modalSlideIn {
            from { 
                opacity: 0; 
                transform: scale(0.85) translateY(30px); 
            }
            to { 
                opacity: 1; 
                transform: scale(1) translateY(0); 
            }
        }

        .success-modal-icon {
            font-size: 4rem;
            margin-bottom: 1.25rem;
            animation: celebrateBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: inline-block;
        }

        @keyframes celebrateBounce {
            0% { transform: scale(0) rotate(-10deg); }
            50% { transform: scale(1.2) rotate(5deg); }
            70% { transform: scale(0.9) rotate(-3deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        .success-modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.75rem 0;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .success-modal-xp {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.5rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            font-size: 1.125rem;
            font-weight: 700;
            border-radius: 2rem;
            margin-bottom: 1rem;
            box-shadow: 
                0 4px 20px rgba(16, 185, 129, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            animation: xpPulse 2s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        .success-modal-xp::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: xpShine 2s ease-in-out infinite;
        }

        @keyframes xpShine {
            0%, 100% { left: -100%; }
            50% { left: 100%; }
        }

        @keyframes xpPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4); }
            50% { transform: scale(1.03); box-shadow: 0 6px 30px rgba(16, 185, 129, 0.5); }
        }

        .success-modal-message {
            color: var(--text-secondary);
            font-size: 0.9375rem;
            margin: 0 0 1.75rem 0;
            line-height: 1.7;
        }

        .success-modal-actions {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }

        .success-modal-btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .success-modal-btn.primary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 50%, #6d28d9 100%);
            color: white;
            box-shadow: 
                0 4px 20px rgba(139, 92, 246, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .success-modal-btn.primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.5s ease;
        }

        .success-modal-btn.primary:hover::before {
            left: 100%;
        }

        .success-modal-btn.primary:hover {
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 50%, #7c3aed 100%);
            transform: translateY(-2px);
            box-shadow: 
                0 8px 30px rgba(139, 92, 246, 0.45),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .success-modal-btn.secondary {
            background: rgba(25, 25, 45, 0.9);
            color: var(--text-secondary);
            border: 1px solid rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(10px);
        }

        .success-modal-btn.secondary:hover {
            background: rgba(35, 35, 60, 0.95);
            color: var(--text-primary);
            border-color: rgba(139, 92, 246, 0.3);
            transform: translateY(-1px);
        }

        /* ============================================
           VALIDATION RESULT PANEL - ENHANCED
           ============================================ */
        .validation-result {
            position: absolute;
            bottom: 75px;
            left: 12px;
            right: 12px;
            background: linear-gradient(180deg, 
                rgba(15, 15, 28, 0.97) 0%, 
                rgba(10, 10, 20, 0.98) 100%);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 1rem;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                0 0 60px rgba(139, 92, 246, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.03);
            z-index: 100;
            overflow: hidden;
            animation: validationSlideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes validationSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .validation-header {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            background: linear-gradient(180deg, rgba(25, 25, 45, 0.8) 0%, rgba(15, 15, 30, 0.6) 100%);
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
        }

        .validation-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-right: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .validation-result.success .validation-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 15px rgba(16, 185, 129, 0.4);
        }

        .validation-result.warning .validation-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 2px 15px rgba(245, 158, 11, 0.4);
        }

        .validation-result.error .validation-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 15px rgba(239, 68, 68, 0.4);
        }

        .validation-title {
            flex: 1;
            font-weight: 600;
            color: var(--text-primary);
            font-size: var(--text-md);
            letter-spacing: 0.3px;
        }

        .validation-close {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 16px;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all var(--transition-normal);
        }

        .validation-close:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(139, 92, 246, 0.3);
            color: var(--text-primary);
            transform: scale(1.05);
        }

        .validation-body {
            padding: var(--space-4);
        }

        .validation-score {
            text-align: center;
            margin-bottom: var(--space-3);
        }

        .score-value {
            font-size: 3rem;
            font-weight: 800;
            text-shadow: 0 4px 25px rgba(0, 0, 0, 0.4);
            letter-spacing: -1px;
        }

        .validation-result.success .score-value {
            background: linear-gradient(135deg, #10b981 0%, #34d399 50%, #6ee7b7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 10px rgba(16, 185, 129, 0.3));
        }

        .validation-result.warning .score-value {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .validation-result.error .score-value {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .validation-message {
            text-align: center;
            color: var(--text-primary);
            margin-bottom: var(--space-3);
            font-size: var(--text-md);
            font-weight: 500;
            line-height: 1.6;
        }

        .validation-hints {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.25) 0%, rgba(0, 0, 0, 0.15) 100%);
            border-radius: 0.75rem;
            padding: var(--space-3);
            border: 1px solid rgba(139, 92, 246, 0.1);
        }

        .validation-hints ul {
            margin: 0;
            padding-left: var(--space-4);
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.75;
        }

        .validation-hints li {
            margin-bottom: var(--space-2);
            position: relative;
        }

        .validation-hints li::marker {
            color: var(--primary-light);
        }

        .validation-hints:empty {
            display: none;
        }

        /* ============================================
           ENHANCED VALIDATION MODAL
           ============================================ */
        .validation-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(5, 5, 15, 0.95);
            backdrop-filter: blur(20px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 1rem;
            animation: overlayFadeIn 0.3s ease;
        }

        .validation-modal-overlay.show {
            display: flex;
        }

        .validation-modal {
            background: linear-gradient(180deg, 
                rgba(20, 20, 35, 0.98) 0%, 
                rgba(12, 12, 22, 0.98) 100%);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 1rem;
            max-width: 600px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 
                0 25px 80px rgba(0, 0, 0, 0.7),
                0 0 80px rgba(139, 92, 246, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }

        .validation-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
            background: linear-gradient(180deg, rgba(25, 25, 45, 0.8) 0%, rgba(15, 15, 30, 0.6) 100%);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .validation-modal-title-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .validation-modal-icon {
            font-size: 1.75rem;
            line-height: 1;
        }

        .validation-modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            background: linear-gradient(135deg, #fff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .validation-modal-subtitle {
            margin: 0.25rem 0 0 0;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .modal-score-text {
            color: #f59e0b;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .validation-modal-close {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 1.5rem;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            flex-shrink: 0;
        }

        .validation-modal-close:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
            color: #f87171;
            transform: rotate(90deg) scale(1.1);
        }

        .validation-modal-close:active {
            transform: rotate(90deg) scale(0.95);
        }

        .validation-modal-body {
            padding: 1.25rem;
        }

        .validation-modal-message {
            text-align: center;
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
            padding: 0.75rem;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 0.5rem;
            border-left: 4px solid var(--primary-purple);
            font-weight: 500;
        }

        /* Comparison Section */
        .comparison-section {
            margin: 1.25rem 0;
        }

        .comparison-title {
            color: var(--primary-light);
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0 0.75rem 0;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .comparison-item {
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .comparison-label {
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .comparison-content {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .comparison-content.passed {
            background: rgba(16, 185, 129, 0.1);
        }

        .comparison-content.failed {
            background: rgba(239, 68, 68, 0.1);
        }

        .comparison-check-item {
            padding: 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .comparison-check-item.passed {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .comparison-check-item.failed {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        /* Action Items */
        .action-items-section {
            margin: 1.25rem 0;
            padding: 1rem;
            background: linear-gradient(135deg, 
                rgba(239, 68, 68, 0.12) 0%, 
                rgba(220, 38, 38, 0.08) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-left: 4px solid #ef4444;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .action-items-title {
            color: #f87171;
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
        }

        .action-items-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .action-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 0.5rem;
            border-left: 3px solid #ef4444;
        }

        .action-item-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .action-item-text {
            flex: 1;
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .action-item-text strong {
            color: #f87171;
            font-weight: 600;
        }

        /* Quick Fix Guide */
        .quick-fix-guide {
            margin: 1.25rem 0;
            padding: 1rem;
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.12) 0%, 
                rgba(37, 99, 235, 0.08) 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-left: 4px solid #3b82f6;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .quick-fix-title {
            color: #60a5fa;
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
        }

        .quick-fix-steps {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .quick-fix-step {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 0.5rem;
        }

        .quick-fix-number {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .quick-fix-step span:last-child {
            flex: 1;
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .quick-fix-step strong {
            color: #60a5fa;
        }

        /* Modal Footer */
        .validation-modal-footer {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(139, 92, 246, 0.2);
            background: linear-gradient(180deg, rgba(15, 15, 30, 0.6) 0%, rgba(10, 10, 20, 0.8) 100%);
            position: sticky;
            bottom: 0;
        }

        .modal-action-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
        }

        .modal-action-btn.primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .modal-action-btn.primary:hover {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .modal-action-btn.secondary {
            background: rgba(25, 25, 45, 0.9);
            color: var(--text-secondary);
            border: 1px solid rgba(139, 92, 246, 0.15);
        }

        .modal-action-btn.secondary:hover {
            background: rgba(35, 35, 60, 0.95);
            color: var(--text-primary);
            border-color: rgba(139, 92, 246, 0.3);
        }

        .modal-action-btn svg {
            width: 16px;
            height: 16px;
        }

        @media (max-width: 768px) {
            .validation-modal {
                max-width: 95%;
                max-height: 95vh;
            }

            .comparison-grid {
                grid-template-columns: 1fr;
            }

            .validation-modal-footer {
                flex-direction: column;
            }
        }

        /* Validation Checks */
        .validation-checks {
            margin: var(--space-4) 0;
            padding: var(--space-3);
            background: rgba(139, 92, 246, 0.08);
            border-radius: 0.75rem;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .checks-title {
            color: var(--primary-light);
            font-size: var(--text-sm);
            font-weight: 600;
            margin: 0 0 var(--space-3) 0;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .checks-list {
            display: grid;
            gap: var(--space-2);
        }

        .check-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2);
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0.5rem;
            font-size: var(--text-sm);
        }

        .check-item.passed {
            border-left: 3px solid #10b981;
        }

        .check-item.failed {
            border-left: 3px solid #ef4444;
        }

        .check-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .check-item.passed .check-icon {
            background: #10b981;
            color: white;
        }

        .check-item.failed .check-icon {
            background: #ef4444;
            color: white;
        }

        .check-element {
            flex: 1;
            color: var(--text-secondary);
        }

        .check-item.passed .check-element {
            color: #10b981;
        }

        .check-item.failed .check-element {
            color: #f87171;
        }

        /* Validation Hints Section */
        .validation-hints-section {
            margin-top: var(--space-4);
        }

        .hints-title {
            color: var(--primary-light);
            font-size: var(--text-sm);
            font-weight: 600;
            margin: 0 0 var(--space-2) 0;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        /* Troubleshooting Guide */
        .troubleshooting-guide {
            margin-top: var(--space-4);
            padding: var(--space-4);
            background: linear-gradient(135deg, 
                rgba(245, 158, 11, 0.12) 0%, 
                rgba(217, 119, 6, 0.08) 100%);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-left: 4px solid #f59e0b;
            border-radius: 0 0.75rem 0.75rem 0;
        }

        .troubleshooting-title {
            color: #f59e0b;
            font-size: var(--text-sm);
            font-weight: 600;
            margin: 0 0 var(--space-3) 0;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .troubleshooting-steps {
            margin: 0;
            padding-left: 0;
            list-style: none;
            counter-reset: troubleshooting-counter;
        }

        .troubleshooting-steps li {
            counter-increment: troubleshooting-counter;
            margin: var(--space-2) 0;
            padding: var(--space-2) var(--space-3);
            padding-left: 2.5rem;
            background: rgba(245, 158, 11, 0.08);
            border-radius: 0.5rem;
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: 1.6;
            position: relative;
        }

        .troubleshooting-steps li::before {
            content: counter(troubleshooting-counter);
            position: absolute;
            left: var(--space-2);
            top: var(--space-2);
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .quick-help-actions {
            display: flex;
            gap: var(--space-2);
            margin-top: var(--space-3);
            flex-wrap: wrap;
        }

        .quick-help-btn {
            flex: 1;
            min-width: 120px;
            padding: var(--space-2) var(--space-3);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.15) 100%);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 0.5rem;
            color: #60a5fa;
            font-weight: 600;
            font-size: var(--text-xs);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-1);
        }

        .quick-help-btn:hover {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.3) 0%, rgba(37, 99, 235, 0.25) 100%);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .quick-help-btn svg {
            width: 14px;
            height: 14px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================
           PREVIEW PANEL - ENHANCED
           ============================================ */
        .preview-panel {
            background: linear-gradient(180deg, #0a0a14 0%, #08080f 100%);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .preview-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: 
                radial-gradient(ellipse at 100% 0%, rgba(6, 182, 212, 0.04) 0%, transparent 50%),
                radial-gradient(ellipse at 0% 100%, rgba(139, 92, 246, 0.03) 0%, transparent 40%);
            pointer-events: none;
            z-index: 0;
        }

        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-2) var(--space-3);
            background: linear-gradient(180deg, rgba(20, 20, 35, 0.95) 0%, rgba(15, 15, 25, 0.95) 100%);
            border-bottom: 1px solid rgba(6, 182, 212, 0.15);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .preview-header h3 {
            margin: 0;
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: var(--space-2);
            letter-spacing: 0.3px;
        }

        .preview-header h3::before {
            content: '👁️';
            font-size: 0.875rem;
        }

        .preview-badge {
            padding: 3px 10px;
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: livePulse 2.5s ease-in-out infinite;
            box-shadow: 
                0 2px 12px rgba(16, 185, 129, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .preview-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: badgeShine 3s ease-in-out infinite;
        }

        @keyframes badgeShine {
            0%, 100% { left: -100%; }
            50% { left: 100%; }
        }

        @keyframes livePulse {
            0%, 100% { 
                opacity: 1; 
                box-shadow: 0 2px 12px rgba(16, 185, 129, 0.4);
                transform: scale(1);
            }
            50% { 
                opacity: 0.9;
                box-shadow: 0 4px 20px rgba(16, 185, 129, 0.6);
                transform: scale(1.02);
            }
        }

        /* Preview Tabs */
        .preview-tabs {
            display: flex;
            gap: 0.25rem;
            background: rgba(0, 0, 0, 0.2);
            padding: 3px;
            border-radius: 8px;
        }

        .preview-tab-btn {
            padding: 0.35rem 0.75rem;
            border: none;
            background: transparent;
            color: #94a3b8;
            font-size: 0.7rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .preview-tab-btn:hover {
            color: #e2e8f0;
            background: rgba(255, 255, 255, 0.05);
        }

        .preview-tab-btn.active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .preview-tab-btn.expected-active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .preview-body {
            flex: 1;
            padding: var(--space-2);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            overflow: hidden;
            position: relative;
            z-index: 1;
            border-radius: 0 0 0 8px;
        }

        .preview-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(90deg, rgba(0,0,0,0.015) 1px, transparent 1px),
                linear-gradient(rgba(0,0,0,0.015) 1px, transparent 1px);
            background-size: 16px 16px;
            pointer-events: none;
            opacity: 0.5;
        }

        .preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 1;
        }

        /* ============================================
           NOTIFICATIONS & TOASTS - PREMIUM
           ============================================ */
        .alert-toast {
            position: fixed;
            top: 85px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            max-width: 420px;
            padding: var(--space-3) var(--space-5);
            border-radius: 0.875rem;
            box-shadow: 
                0 15px 50px rgba(0, 0, 0, 0.4),
                0 0 40px rgba(0, 0, 0, 0.2);
            animation: toastSlideDown 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: var(--text-sm);
            font-weight: 600;
            backdrop-filter: blur(20px);
            letter-spacing: 0.3px;
        }

        @keyframes toastSlideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-40px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0) scale(1);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, 
                rgba(16, 185, 129, 0.97) 0%, 
                rgba(5, 150, 105, 0.97) 50%,
                rgba(4, 120, 87, 0.97) 100%);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 15px 50px rgba(0, 0, 0, 0.4),
                0 0 30px rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: linear-gradient(135deg, 
                rgba(239, 68, 68, 0.97) 0%, 
                rgba(220, 38, 38, 0.97) 50%,
                rgba(185, 28, 28, 0.97) 100%);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 15px 50px rgba(0, 0, 0, 0.4),
                0 0 30px rgba(239, 68, 68, 0.3);
        }

        /* Notification Toast */
        .notification-toast {
            position: fixed;
            top: 90px;
            right: 25px;
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            background: linear-gradient(180deg, 
                rgba(20, 20, 35, 0.97) 0%, 
                rgba(12, 12, 22, 0.97) 100%);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 0.875rem;
            box-shadow: 
                0 15px 50px rgba(0, 0, 0, 0.5),
                0 0 40px rgba(139, 92, 246, 0.08);
            z-index: 10001;
            animation: notificationSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: var(--text-sm);
            max-width: 380px;
        }

        .notification-success {
            border-left: 4px solid #10b981;
            background: linear-gradient(135deg, 
                rgba(16, 185, 129, 0.08) 0%, 
                rgba(15, 15, 28, 0.97) 100%);
        }

        .notification-error {
            border-left: 4px solid #ef4444;
            background: linear-gradient(135deg, 
                rgba(239, 68, 68, 0.08) 0%, 
                rgba(15, 15, 28, 0.97) 100%);
        }

        .notification-info {
            border-left: 4px solid #3b82f6;
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.08) 0%, 
                rgba(15, 15, 28, 0.97) 100%);
        }

        .notification-icon {
            font-size: var(--text-lg);
            flex-shrink: 0;
        }

        .notification-message {
            color: var(--text-primary);
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .notification-hide {
            animation: notificationSlideOut 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes notificationSlideIn {
            from {
                opacity: 0;
                transform: translateX(120%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes notificationSlideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        /* XP Reward Toast - Premium Celebration */
        .xp-reward-toast {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            gap: var(--space-6);
            padding: var(--space-8) var(--space-10);
            background: linear-gradient(135deg, 
                rgba(30, 27, 75, 0.98) 0%, 
                rgba(49, 46, 129, 0.95) 50%, 
                rgba(76, 29, 149, 0.98) 100%);
            border: 2px solid transparent;
            border-radius: 1.5rem;
            box-shadow: 
                0 0 0 1px rgba(139, 92, 246, 0.3),
                0 0 80px rgba(139, 92, 246, 0.6),
                0 0 120px rgba(251, 191, 36, 0.3),
                0 30px 80px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            z-index: 10002;
            animation: xpBounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            overflow: hidden;
        }

        .xp-reward-toast::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse at 30% 0%, rgba(251, 191, 36, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 100%, rgba(139, 92, 246, 0.2) 0%, transparent 50%);
            pointer-events: none;
        }

        .xp-reward-toast::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(
                from 0deg,
                transparent 0deg,
                rgba(251, 191, 36, 0.1) 60deg,
                transparent 120deg
            );
            animation: xpRotateGlow 4s linear infinite;
            pointer-events: none;
        }

        @keyframes xpRotateGlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .xp-icon {
            position: relative;
            z-index: 1;
            font-size: 56px;
            filter: drop-shadow(0 0 20px rgba(251, 191, 36, 0.6));
            animation: xpPulse 1s ease-in-out infinite, xpFloat 2s ease-in-out infinite;
        }

        @keyframes xpFloat {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-5px) scale(1.05); }
        }

        .xp-content {
            position: relative;
            z-index: 1;
            text-align: left;
        }

        .xp-title {
            color: #c4b5fd;
            font-size: var(--text-sm);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: var(--space-2);
            text-shadow: 0 2px 10px rgba(139, 92, 246, 0.5);
        }

        .xp-amount {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #fcd34d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 42px;
            font-weight: 900;
            letter-spacing: -1px;
            filter: drop-shadow(0 4px 20px rgba(251, 191, 36, 0.4));
            animation: xpAmountPulse 1.5s ease-in-out infinite;
        }

        @keyframes xpAmountPulse {
            0%, 100% { 
                filter: drop-shadow(0 4px 20px rgba(251, 191, 36, 0.4));
            }
            50% { 
                filter: drop-shadow(0 4px 30px rgba(251, 191, 36, 0.7));
            }
        }

        .xp-hide {
            animation: xpBounceOut 0.6s ease forwards;
        }

        @keyframes xpBounceIn {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.2) rotate(-10deg);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.15) rotate(2deg);
            }
            70% {
                transform: translate(-50%, -50%) scale(0.95) rotate(-1deg);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1) rotate(0deg);
            }
        }

        @keyframes xpBounceOut {
            0% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1) rotate(0deg);
            }
            30% {
                transform: translate(-50%, -50%) scale(1.1) rotate(3deg);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.3) translateY(-80px) rotate(-10deg);
            }
        }

        @keyframes xpPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.15);
            }
        }

        /* Confetti particles for XP celebration */
        .xp-confetti {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: xpConfettiFall 1s ease-out forwards;
        }

        @keyframes xpConfettiFall {
            0% {
                opacity: 1;
                transform: translateY(0) rotate(0deg) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateY(100px) rotate(720deg) scale(0);
            }
        }

        /* Theory Mode - Premium Content Styling */
        .theory-content {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, rgba(15, 15, 26, 0.98) 100%);
            padding: var(--space-10) var(--space-12);
            overflow-y: auto;
            max-width: 960px;
            margin: 0 auto;
            line-height: 1.85;
            position: relative;
        }

        .theory-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 250px;
            background: radial-gradient(ellipse at 50% 0%, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .theory-content h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: var(--space-6);
            line-height: 1.3;
            background: linear-gradient(135deg, #c4b5fd 0%, #a78bfa 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            letter-spacing: -0.5px;
        }

        .theory-content h2 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: var(--space-10);
            margin-bottom: var(--space-5);
            padding-bottom: var(--space-4);
            border-bottom: 2px solid rgba(139, 92, 246, 0.3);
            position: relative;
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .theory-content h2::before {
            content: '◆';
            color: var(--primary-purple);
            font-size: var(--text-md);
        }

        .theory-content h2::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #8b5cf6 0%, #22d3ee 50%, transparent 100%);
        }

        .theory-content h3 {
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: var(--space-8);
            margin-bottom: var(--space-4);
            padding-left: var(--space-5);
            border-left: 4px solid transparent;
            border-image: linear-gradient(180deg, #8b5cf6, #22d3ee) 1;
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .theory-content h3::before {
            content: '▸';
            color: var(--accent-cyan);
            font-size: var(--text-md);
        }

        .theory-content h4 {
            color: var(--text-secondary);
            font-size: 1.125rem;
            font-weight: 600;
            margin-top: var(--space-6);
            margin-bottom: var(--space-3);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .theory-content h4::before {
            content: '•';
            color: var(--accent-pink);
            font-size: 1.25em;
        }

        .theory-content p {
            color: var(--text-secondary);
            line-height: 1.9;
            font-size: 1.0625rem;
            margin-bottom: var(--space-5);
            text-align: justify;
        }

        .theory-content ul, .theory-content ol {
            color: var(--text-secondary);
            line-height: 1.9;
            font-size: 1.0625rem;
            margin-bottom: var(--space-6);
            padding-left: var(--space-8);
        }

        .theory-content ul li {
            margin-bottom: var(--space-4);
            position: relative;
            padding-left: var(--space-3);
        }

        .theory-content ul li::marker {
            color: var(--accent-cyan);
            font-weight: bold;
            content: '✦ ';
        }

        .theory-content ol li {
            margin-bottom: var(--space-4);
            padding-left: var(--space-2);
        }

        .theory-content li {
            margin-bottom: var(--space-3);
        }

        .theory-content blockquote {
            border-left: 5px solid transparent;
            border-image: linear-gradient(180deg, #8b5cf6, #f472b6) 1;
            padding: var(--space-5) var(--space-6);
            margin: var(--space-6) 0;
            color: var(--text-secondary);
            font-style: italic;
            font-size: 1.0625rem;
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.08) 0%, 
                rgba(244, 114, 182, 0.05) 100%);
            border-radius: 0 1rem 1rem 0;
            box-shadow: 
                0 4px 16px rgba(139, 92, 246, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.03);
            position: relative;
            backdrop-filter: blur(10px);
        }

        .theory-content blockquote::before {
            content: '"';
            position: absolute;
            top: -20px;
            left: 24px;
            font-size: 72px;
            color: var(--primary-purple);
            opacity: 0.2;
            font-family: Georgia, serif;
            line-height: 1;
        }

        .theory-content img {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            margin: var(--space-5) 0;
            box-shadow: 
                0 8px 32px rgba(139, 92, 246, 0.15),
                0 0 0 1px rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            transition: all var(--transition-normal);
        }

        .theory-content img:hover {
            transform: scale(1.02);
            box-shadow: 
                0 12px 40px rgba(139, 92, 246, 0.25),
                0 0 0 1px rgba(139, 92, 246, 0.3);
        }

        .theory-content pre {
            background: linear-gradient(135deg, #0a0a14 0%, #0f0f1a 100%);
            color: #e2e8f0;
            padding: var(--space-6);
            border-radius: 1rem;
            overflow-x: auto;
            margin: var(--space-6) 0;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.95rem;
            line-height: 1.8;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.03);
            position: relative;
        }

        .theory-content pre::before {
            content: '< / >';
            position: absolute;
            top: 14px;
            right: 18px;
            font-size: var(--text-sm);
            background: linear-gradient(135deg, #8b5cf6, #22d3ee);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .theory-content code {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(34, 211, 238, 0.1));
            color: var(--accent-cyan);
            padding: 0.25rem 0.6rem;
            border-radius: 0.4rem;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.95em;
            font-weight: 500;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .theory-content pre code {
            background: transparent;
            color: inherit;
            padding: 0;
            border: none;
            font-weight: normal;
        }

        .theory-content table {
            width: 100%;
            border-collapse: collapse;
            margin: var(--space-6) 0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .theory-content table th {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
            color: white;
            padding: var(--space-4) var(--space-5);
            text-align: left;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .theory-content table td {
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            background: var(--bg-card);
            color: var(--text-secondary);
            font-size: 1rem;
            transition: all var(--transition-fast);
        }

        .theory-content table tr:hover td {
            background: rgba(139, 92, 246, 0.05);
        }

        .theory-content table tr:last-child td {
            border-bottom: none;
        }

        .empty-content-message {
            text-align: center;
            padding: var(--space-12) var(--space-6);
            color: var(--text-muted);
        }

        .empty-content-message .empty-icon {
            font-size: 64px;
            margin-bottom: var(--space-4);
            filter: grayscale(0.3);
            animation: emptyFloat 3s ease-in-out infinite;
        }

        @keyframes emptyFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-content-message h3 {
            color: var(--text-primary);
            font-size: var(--text-xl);
            margin-bottom: var(--space-2);
            font-weight: 700;
        }

        .empty-content-message p {
            color: var(--text-muted);
            font-size: var(--text-md);
        }

        .theory-navigation {
            background: linear-gradient(180deg, transparent 0%, rgba(15, 15, 26, 0.95) 100%);
            padding: var(--space-5);
            border-top: 1px solid rgba(139, 92, 246, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1000px;
            margin: 0 auto;
            backdrop-filter: blur(20px);
            position: relative;
        }

        .theory-navigation::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(139, 92, 246, 0.5), transparent);
        }

        .theory-nav-btn {
            padding: var(--space-3) var(--space-5);
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            font-size: var(--text-sm);
            box-shadow: 
                0 4px 20px rgba(139, 92, 246, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .theory-nav-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, transparent 50%);
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .theory-nav-btn:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            transform: translateY(-2px);
            box-shadow: 
                0 8px 30px rgba(139, 92, 246, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .theory-nav-btn:hover::before {
            opacity: 1;
        }

        .theory-nav-btn.secondary {
            background: var(--bg-glass);
            color: var(--text-primary);
            border: 1px solid rgba(139, 92, 246, 0.3);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        .theory-nav-btn.secondary:hover {
            background: rgba(139, 92, 246, 0.15);
            border-color: var(--primary-purple);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.2);
        }

        @media (max-width: 1024px) {
            .lesson-wrapper {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr 1fr;
                height: auto;
                min-height: calc(100vh - 56px - 52px);
            }

            .lesson-wrapper.mode-theory {
                height: auto;
                min-height: calc(100vh - 56px - 52px);
            }

            .instructions-panel {
                max-height: 400px;
                padding: var(--space-5);
            }

            .instructions-panel h2 {
                font-size: var(--text-lg);
            }

            .instruction-box p,
            .instruction-box li {
                font-size: 1rem;
                line-height: 1.75;
            }

            .lesson-header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .theory-content {
                padding: var(--space-6) var(--space-5);
            }

            .theory-content h1 {
                font-size: 1.75rem;
            }

            .theory-content h2 {
                font-size: 1.35rem;
            }

            .theory-content h3 {
                font-size: 1.15rem;
            }

            .theory-content p,
            .theory-content li {
                font-size: 1rem;
                line-height: 1.8;
            }
        }

        /* Scrollbar styling - Premium */
        .instructions-panel::-webkit-scrollbar,
        .theory-content::-webkit-scrollbar,
        .preview-body::-webkit-scrollbar {
            width: 8px;
        }

        .instructions-panel::-webkit-scrollbar-track,
        .theory-content::-webkit-scrollbar-track,
        .preview-body::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 4px;
        }

        .instructions-panel::-webkit-scrollbar-thumb,
        .theory-content::-webkit-scrollbar-thumb,
        .preview-body::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #8b5cf6, #7c3aed);
            border-radius: 4px;
            border: 2px solid var(--bg-secondary);
        }

        .instructions-panel::-webkit-scrollbar-thumb:hover,
        .theory-content::-webkit-scrollbar-thumb:hover,
        .preview-body::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #a78bfa, #8b5cf6);
        }

        /* Quiz Styles - Premium */
        .quiz-container {
            background: linear-gradient(135deg, 
                rgba(30, 30, 53, 0.95) 0%, 
                rgba(26, 26, 46, 0.98) 100%) !important;
            padding: 2.5rem !important;
            border-radius: 1.5rem !important;
            border: 1px solid rgba(139, 92, 246, 0.2) !important;
            box-shadow: 
                0 8px 40px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.05) !important;
            position: relative;
            overflow: hidden;
        }

        .quiz-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: radial-gradient(ellipse at 50% 0%, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .quiz-container h2 {
            background: linear-gradient(135deg, #c4b5fd 0%, #a78bfa 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 1.75rem;
            position: relative;
            z-index: 1;
        }

        .quiz-question {
            background: rgba(139, 92, 246, 0.05);
            padding: 1.5rem !important;
            border-radius: 1rem;
            margin-bottom: 1.5rem !important;
            border: 1px solid rgba(139, 92, 246, 0.1);
            transition: all var(--transition-normal);
        }

        .quiz-question:hover {
            border-color: rgba(139, 92, 246, 0.3);
            background: rgba(139, 92, 246, 0.08);
        }

        .quiz-question p {
            color: var(--text-primary) !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
        }

        .quiz-options label {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 0.75rem !important;
            padding: 1rem !important;
            transition: all var(--transition-normal) !important;
        }

        .quiz-options label:hover {
            background: rgba(139, 92, 246, 0.1) !important;
            border-color: rgba(139, 92, 246, 0.4) !important;
            transform: translateX(5px);
        }

        .quiz-options input[type="radio"] {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(139, 92, 246, 0.5);
            border-radius: 50%;
            background: transparent;
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
        }

        .quiz-options input[type="radio"]:checked {
            border-color: #8b5cf6;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .quiz-options input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }

        #quizResult {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(34, 211, 238, 0.05));
            padding: 2rem;
            border-radius: 1.5rem;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        #scoreDisplay {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 4rem !important;
            font-weight: 900 !important;
            text-shadow: none;
            filter: drop-shadow(0 4px 20px rgba(251, 191, 36, 0.3));
        }

        #scoreDisplay.pass {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        #scoreDisplay.fail {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Loading Spinner - Premium */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(139, 92, 246, 0.2);
            border-top: 3px solid var(--primary-purple);
            border-radius: 50%;
            animation: spinnerRotate 0.8s linear infinite;
            margin: 0 auto;
        }

        .loading-spinner.small {
            width: 20px;
            height: 20px;
            border-width: 2px;
        }

        @keyframes spinnerRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Loading State for buttons */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top-color: white;
            border-radius: 50%;
            animation: spinnerRotate 0.6s linear infinite;
            margin-left: 8px;
        }

        /* Progress Indicator - Enhanced */
        .progress-indicator {
            width: 100%;
            height: 3px;
            background: rgba(139, 92, 246, 0.15);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }

        .progress-indicator::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(139, 92, 246, 0.1), 
                transparent);
            animation: progressShimmer 2s ease-in-out infinite;
        }

        @keyframes progressShimmer {
            0%, 100% { transform: translateX(-100%); }
            50% { transform: translateX(100%); }
        }

        .progress-indicator .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, 
                var(--primary-purple) 0%, 
                var(--accent-cyan) 50%, 
                var(--primary-light) 100%);
            background-size: 200% 100%;
            border-radius: 2px;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: progressGlow 3s ease-in-out infinite;
        }

        @keyframes progressGlow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Tooltip styles - Enhanced */
        .tooltip {
            position: relative;
        }

        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-8px);
            padding: 0.5rem 0.875rem;
            background: linear-gradient(180deg, 
                rgba(25, 25, 45, 0.98) 0%, 
                rgba(15, 15, 30, 0.98) 100%);
            color: var(--text-primary);
            font-size: 11px;
            font-weight: 500;
            border-radius: 0.5rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all var(--transition-normal);
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.4),
                0 0 20px rgba(139, 92, 246, 0.1);
            z-index: 1000;
            backdrop-filter: blur(10px);
            letter-spacing: 0.2px;
        }

        .tooltip:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(-4px);
        }

        /* Focus states for accessibility */
        button:focus-visible,
        input:focus-visible,
        a:focus-visible {
            outline: 2px solid var(--primary-purple);
            outline-offset: 2px;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        }

        /* Smooth page transitions */
        .fade-enter {
            opacity: 0;
            transform: translateY(10px);
        }

        .fade-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, 
                rgba(139, 92, 246, 0.1) 25%, 
                rgba(139, 92, 246, 0.2) 50%, 
                rgba(139, 92, 246, 0.1) 75%);
            background-size: 200% 100%;
            animation: skeletonShimmer 1.5s ease-in-out infinite;
            border-radius: 4px;
        }

        @keyframes skeletonShimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <?php if ($success_message): ?>
        <div class="alert-toast alert-success">
            <?php echo $success_message; ?>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.alert-toast').style.display = 'none';
            }, 5000);
        </script>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert-toast alert-error">
            <?php echo $error_message; ?>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.alert-toast').style.display = 'none';
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Lesson Header -->
    <div class="lesson-header">
        <div class="lesson-header-content">
            <div class="lesson-title-section">
                <div class="lesson-title-with-logo">
                    <?php 
                    $lesson_logo = getLanguageIcon($course_data['judul_course']);
                    if ($lesson_logo): 
                    ?>
                    <img src="<?php echo $lesson_logo; ?>" alt="Language Logo" class="lesson-language-logo">
                    <?php endif; ?>
                    <h1><?php echo htmlspecialchars($lesson_data['judul_lesson']); ?></h1>
                </div>
                <p><?php echo htmlspecialchars($course_data['judul_course']); ?> • Lesson <?php echo $lesson_data['urutan']; ?></p>
            </div>
            
            
            <div class="lesson-navigation">
                <?php if ($prev_lesson): ?>
                    <a href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $prev_lesson['id']; ?>" 
                       class="nav-btn">
                        ← Sebelumnya
                    </a>
                <?php else: ?>
                    <button class="nav-btn" disabled>← Sebelumnya</button>
                <?php endif; ?>
                
                <?php if ($next_lesson): ?>
                    <a href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $next_lesson['id']; ?>" 
                       class="nav-btn">
                        Selanjutnya →
                    </a>
                <?php else: ?>
                    <button class="nav-btn" disabled>Selanjutnya →</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($lesson_type === 'quiz'): ?>
        <!-- Quiz Mode -->
        <div class="lesson-wrapper mode-theory">
            <div class="slide-container">
                <?php
                $quiz_data = json_decode($lesson_data['konten'] ?? '[]', true);
                if (empty($quiz_data)) {
                    echo '<div class="alert alert-warning">Data kuis tidak valid atau kosong.</div>';
                } else {
                ?>
                <div class="quiz-container" style="background: var(--bg-card); padding: 2rem; border-radius: 1rem; border: 1px solid var(--border-color);">
                    <h2 style="margin-bottom: 1.5rem; color: var(--primary-purple);">Kuis: <?php echo htmlspecialchars($lesson_data['judul_lesson']); ?></h2>
                    
                    <form id="quizForm" onsubmit="submitQuiz(event)">
                        <?php foreach ($quiz_data as $index => $q): ?>
                            <div class="quiz-question" style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <p style="font-size: 1.1rem; margin-bottom: 1rem; font-weight: 600;">
                                    <?php echo ($index + 1) . '. ' . htmlspecialchars($q['question']); ?>
                                </p>
                                <div class="quiz-options" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <?php foreach ($q['options'] as $optIndex => $option): ?>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border-radius: 0.5rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                                            <input type="radio" name="q<?php echo $index; ?>" value="<?php echo $optIndex; ?>" required>
                                            <span><?php echo htmlspecialchars($option); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <button type="submit" class="btn-run" style="width: 100%; padding: 1rem; font-size: 1.1rem;">Kirim Jawaban</button>
                    </form>

                    <div id="quizResult" style="display: none; margin-top: 2rem; text-align: center;">
                        <div id="scoreDisplay" style="font-size: 3rem; font-weight: 800; margin-bottom: 1rem;"></div>
                        <p id="scoreMessage" style="font-size: 1.2rem; margin-bottom: 1.5rem;"></p>
                        <form method="POST" id="completeQuizForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="complete_lesson" value="1">
                            <button type="submit" class="btn-run">Lanjutkan</button>
                        </form>
                    </div>
                </div>

                <script>
                    const quizData = <?php echo json_encode($quiz_data); ?>;
                    
                    function submitQuiz(e) {
                        e.preventDefault();
                        let score = 0;
                        let total = quizData.length;
                        
                        quizData.forEach((q, index) => {
                            const selected = document.querySelector(`input[name="q${index}"]:checked`);
                            if (selected && parseInt(selected.value) === q.correct) {
                                score++;
                            }
                        });
                        
                        const percentage = Math.round((score / total) * 100);
                        const resultDiv = document.getElementById('quizResult');
                        const scoreDisplay = document.getElementById('scoreDisplay');
                        const scoreMessage = document.getElementById('scoreMessage');
                        const quizForm = document.getElementById('quizForm');
                        
                        quizForm.style.display = 'none';
                        resultDiv.style.display = 'block';
                        
                        scoreDisplay.textContent = `${score} / ${total}`;
                        scoreDisplay.style.color = percentage >= 70 ? '#10b981' : '#ef4444';
                        
                        if (percentage >= 70) {
                            scoreMessage.textContent = "Selamat! Anda lulus kuis ini.";
                            scoreMessage.style.color = '#10b981';
                        } else {
                            scoreMessage.textContent = "Maaf, nilai Anda belum mencukupi. Silakan coba lagi.";
                            scoreMessage.style.color = '#ef4444';
                            document.getElementById('completeQuizForm').style.display = 'none';
                            
                            // Add retry button
                            const retryBtn = document.createElement('button');
                            retryBtn.textContent = "Coba Lagi";
                            retryBtn.className = "btn-run";
                            retryBtn.style.marginTop = "1rem";
                            retryBtn.onclick = () => location.reload();
                            resultDiv.appendChild(retryBtn);
                        }
                    }
                </script>
                <?php } ?>
            </div>
        </div>

    <?php elseif ($lesson_type === 'theory'): ?>
        <!-- Theory Mode: Interactive Slide-Based Material -->
        <div class="lesson-wrapper mode-theory">
            <div class="slide-container">
                <?php 
                // Parse content into slides
                $content = $lesson_data['konten'] ?? $lesson_data['instruksi'] ?? '';
                $slides = [];
                
                if (!empty($content)) {
                    // Try to parse as JSON first (new format)
                    $parsed = json_decode($content, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                        // New JSON format with 'teori' key
                        if (isset($parsed['teori'])) {
                            $slides[] = [
                                'title' => $lesson_data['judul_lesson'],
                                'content' => $parsed['teori'],
                                'type' => 'markdown'
                            ];
                        } else {
                            // Old format (array of slides)
                            $slides = $parsed;
                        }
                    } else {
                        // Content is plain text/markdown - create single slide
                        $slides[] = [
                            'title' => $lesson_data['judul_lesson'],
                            'content' => $content,
                            'type' => 'markdown'
                        ];
                    }
                }
                
                if (empty($slides)) {
                    // Create default slides for demonstration
                    $slides = [
                        [
                            'title' => 'Selamat Datang',
                            'content' => '<p>Selamat datang di materi pembelajaran interaktif!</p>',
                            'type' => 'welcome'
                        ]
                    ];
                }
                ?>
                
                <!-- Slide Navigation -->
                <div class="slide-navigation">
                    <button class="slide-nav-btn prev-btn" onclick="previousSlide()" id="prevBtn">
                        ← Sebelumnya
                    </button>
                    <div class="slide-indicator">
                        <span id="currentSlide">1</span> / <span id="totalSlides"><?php echo count($slides); ?></span>
                    </div>
                    <button class="slide-nav-btn next-btn" onclick="nextSlide()" id="nextBtn">
                        Selanjutnya →
                    </button>
                    <button class="slide-nav-btn next-btn" onclick="completeTheory()" id="finishBtn" style="display: none; background: #10b981; border-color: #10b981; color: white;">
                        Selesai & Lanjut →
                    </button>
                </div>
                
                <!-- Slide Progress Bar -->
                <div class="slide-progress">
                    <div class="slide-progress-bar" id="progressBar"></div>
                </div>
                
                <!-- Slides Container -->
                <div class="slides-wrapper" id="slidesWrapper">
                    <?php foreach ($slides as $index => $slide): ?>
                        <div class="slide-card <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                            <div class="slide-header">
                                <h2 class="slide-title"><?php echo htmlspecialchars($slide['title'] ?? 'Slide ' . ($index + 1)); ?></h2>
                            </div>
                            
                            <div class="slide-body">
                                <?php 
                                $slideContent = $slide['content'] ?? $slide['text'] ?? '';
                                $slideType = $slide['type'] ?? 'text';
                                
                                if ($slideType === 'markdown'):
                                ?>
                                    <!-- Markdown Content - rendered via JS -->
                                    <div class="slide-content markdown-slide" data-slide-index="<?php echo $index; ?>" data-raw-content="<?php echo htmlspecialchars($slideContent, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="loading-content">
                                            <div class="loading-spinner"></div>
                                            <span>Memuat materi...</span>
                                        </div>
                                    </div>
                                <?php elseif ($slideType === 'box-model' || strpos(strtolower($slideContent), 'box model') !== false): ?>
                                    <!-- CSS Box Model Visualization -->
                                    <div class="visual-box-model">
                                        <div class="box-model-container">
                                            <div class="box-margin">
                                                <div class="box-label">Margin</div>
                                                <div class="box-border">
                                                    <div class="box-label">Border</div>
                                                    <div class="box-padding">
                                                        <div class="box-label">Padding</div>
                                                        <div class="box-content">
                                                            <div class="box-label">Content</div>
                                                            <div class="box-content-text">Width × Height</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="box-model-explanation">
                                            <div class="explanation-item">
                                                <div class="color-indicator margin"></div>
                                                <div>
                                                    <strong>Margin</strong>
                                                    <p>Jarak di luar border, memisahkan elemen dari elemen lain</p>
                                                </div>
                                            </div>
                                            <div class="explanation-item">
                                                <div class="color-indicator border"></div>
                                                <div>
                                                    <strong>Border</strong>
                                                    <p>Garis yang mengelilingi padding dan content</p>
                                                </div>
                                            </div>
                                            <div class="explanation-item">
                                                <div class="color-indicator padding"></div>
                                                <div>
                                                    <strong>Padding</strong>
                                                    <p>Jarak di dalam border, antara border dan content</p>
                                                </div>
                                            </div>
                                            <div class="explanation-item">
                                                <div class="color-indicator content"></div>
                                                <div>
                                                    <strong>Content</strong>
                                                    <p>Area yang berisi konten aktual (teks, gambar, dll)</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($slideType === 'shorthand' || strpos(strtolower($slideContent), 'shorthand') !== false): ?>
                                    <!-- CSS Shorthand Visualization -->
                                    <div class="visual-shorthand">
                                        <div class="shorthand-comparison">
                                            <div class="shorthand-long">
                                                <h3>Longhand (Panjang)</h3>
                                                <div class="code-example">
                                                    <code>
margin-top: 10px;<br>
margin-right: 20px;<br>
margin-bottom: 10px;<br>
margin-left: 20px;
                                                    </code>
                                                </div>
                                            </div>
                                            <div class="shorthand-arrow">→</div>
                                            <div class="shorthand-short">
                                                <h3>Shorthand (Pendek)</h3>
                                                <div class="code-example highlight">
                                                    <code>
margin: 10px 20px;
                                                    </code>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="shorthand-rules">
                                            <h4>Aturan Shorthand:</h4>
                                            <div class="rule-item">
                                                <strong>1 nilai:</strong> <code>margin: 10px;</code> → semua sisi
                                            </div>
                                            <div class="rule-item">
                                                <strong>2 nilai:</strong> <code>margin: 10px 20px;</code> → vertikal horizontal
                                            </div>
                                            <div class="rule-item">
                                                <strong>3 nilai:</strong> <code>margin: 10px 20px 15px;</code> → atas horizontal bawah
                                            </div>
                                            <div class="rule-item">
                                                <strong>4 nilai:</strong> <code>margin: 10px 20px 15px 25px;</code> → atas kanan bawah kiri
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Default Text/HTML Content with Markdown -->
                                    <div class="slide-content markdown-slide" data-slide-index="<?php echo $index; ?>">
                                        <?php echo $slideContent; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Script to render markdown in slides -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof marked !== 'undefined') {
                            // Configure marked for better rendering
                            marked.setOptions({
                                breaks: true,
                                gfm: true,
                                headerIds: false
                            });
                            
                            document.querySelectorAll('.markdown-slide').forEach(function(el) {
                                // Get content from data attribute if available
                                let content = el.getAttribute('data-raw-content');
                                if (!content) {
                                    content = el.innerHTML.trim();
                                }
                                
                                if (content) {
                                    // Check if content looks like markdown
                                    if (content.match(/^#|^\*\*|\n-|\n\d\.|```|\|/m)) {
                                        el.innerHTML = marked.parse(content);
                                    } else {
                                        el.innerHTML = content;
                                    }
                                }
                            });
                        }
                    });
                </script>
            </div>
            
            <div class="theory-navigation">
                <a href="course.php?id=<?php echo $course_id; ?>" class="theory-nav-btn secondary">
                    ← Kembali ke Course
                </a>
                
                <div style="display: flex; gap: 1rem;">
                    <?php if ($prev_lesson): ?>
                        <a href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $prev_lesson['id']; ?>" 
                           class="theory-nav-btn secondary">
                            ← Lesson Sebelumnya
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($next_lesson): ?>
                        <a href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $next_lesson['id']; ?>" 
                           class="theory-nav-btn">
                            Lesson Selanjutnya →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Practice Mode: 3-Panel Layout with Instructions, Editor, and Preview -->
        
        <!-- Practice Mode: 3-Panel Layout -->
        <div class="lesson-wrapper">
            <!-- Left Panel: Instructions (25%) -->
            <div class="instructions-panel">
                <div class="instructions-header">
                    <h2><?php icon('book', 18); ?> Materi & Instruksi</h2>
                    <p class="instructions-subtitle">Pelajari materi dan ikuti langkah praktik</p>
                </div>
                
                <div class="instructions-content">
                    <?php 
                    // Display konten (materi) first if available
                    $has_konten = !empty($lesson_data['konten']) && $lesson_data['konten'] !== $lesson_data['instruksi'];
                    if ($has_konten): 
                    ?>
                    <div class="material-section">
                        <h3 class="material-title">📚 Materi Pembelajaran</h3>
                        <div class="instruction-box markdown-content material-box" id="konten-markdown">
                            <div class="loading-content">
                                <div class="loading-spinner"></div>
                                <span>Memuat materi...</span>
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                let rawKonten = <?php echo json_encode($lesson_data['konten']); ?>;
                                const kontenContainer = document.getElementById('konten-markdown');
                                
                                if (rawKonten) {
                                    // Remove all broken emoji patterns (???, ???? etc)
                                    rawKonten = rawKonten.replace(/\?{2,}/g, '');
                                    // Remove lines that only contain whitespace or question marks
                                    rawKonten = rawKonten.replace(/^\s*\?*\s*$/gm, '');
                                    // Clean up multiple newlines
                                    rawKonten = rawKonten.replace(/\n{3,}/g, '\n\n');
                                    
                                    if (typeof marked !== 'undefined') {
                                        marked.setOptions({ breaks: true, gfm: true });
                                        kontenContainer.innerHTML = marked.parse(rawKonten);
                                    } else {
                                        kontenContainer.innerHTML = rawKonten.replace(/\n/g, '<br>');
                                    }
                                }
                            });
                        </script>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Parse konten JSON untuk practice mode
                    $practice_content = '';
                    $starter_code = '';
                    $solution_code = '';
                    
                    if (!empty($lesson_data['konten'])) {
                        $parsed = json_decode($lesson_data['konten'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                            $practice_content = $parsed['teori'] ?? '';
                            $starter_code = $parsed['kode_awal'] ?? '';
                            $solution_code = $parsed['kode_solusi'] ?? '';
                        }
                    }
                    
                    if (empty($instructions) && !empty($practice_content)): ?>
                        <div class="instruction-box markdown-content" id="practice-content-md">
                            <div class="loading-content">
                                <div class="loading-spinner"></div>
                                <span>Memuat materi...</span>
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                let rawContent = <?php echo json_encode($practice_content); ?>;
                                const container = document.getElementById('practice-content-md');
                                
                                if (rawContent) {
                                    // Remove all broken emoji patterns (???, ???? etc)
                                    rawContent = rawContent.replace(/\?{2,}/g, '');
                                    // Remove lines that only contain whitespace or question marks
                                    rawContent = rawContent.replace(/^\s*\?*\s*$/gm, '');
                                    // Clean up multiple newlines
                                    rawContent = rawContent.replace(/\n{3,}/g, '\n\n');
                                    
                                    if (typeof marked !== 'undefined') {
                                        container.innerHTML = marked.parse(rawContent);
                                    } else {
                                        container.innerHTML = rawContent.replace(/\n/g, '<br>');
                                    }
                                }
                            });
                        </script>
                    <?php elseif (!empty($instructions)): ?>
                        <div class="instruction-section">
                            <h3 class="instruction-section-title">✅ Tugas Praktik</h3>
                            
                            <!-- Cara Menyelesaikan Section -->
                            <div class="how-to-complete-section">
                                <h4 class="how-to-title">📋 Cara Menyelesaikan Lesson Ini (Step-by-Step):</h4>
                                <div class="visual-steps">
                                    <div class="visual-step">
                                        <div class="visual-step-number">1</div>
                                        <div class="visual-step-content">
                                            <strong>📖 Baca Instruksi</strong>
                                            <p>Pelajari setiap langkah di bawah ini dengan teliti. Setiap langkah menjelaskan apa yang harus Anda lakukan.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">2</div>
                                        <div class="visual-step-content">
                                            <strong>📋 Salin Kode</strong>
                                            <p>Klik tombol <strong>"Copy"</strong> pada setiap blok kode yang disediakan. Kode akan tersalin ke clipboard Anda.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">3</div>
                                        <div class="visual-step-content">
                                            <strong>📝 Tempel di Editor</strong>
                                            <p>Paste kode (Ctrl+V atau Cmd+V) ke editor di tengah. Editor adalah kotak besar di panel tengah.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">4</div>
                                        <div class="visual-step-content">
                                            <strong>✏️ Modifikasi Kode</strong>
                                            <p>Sesuaikan kode sesuai instruksi. Misalnya: ubah teks, tambah elemen, atau ubah warna.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">5</div>
                                        <div class="visual-step-content">
                                            <strong>▶️ Test dengan Run</strong>
                                            <p>Klik tombol <strong>"Run"</strong> (biru) untuk melihat hasil di preview. Bandingkan dengan tab <strong>"Target"</strong>.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">6</div>
                                        <div class="visual-step-content">
                                            <strong>✅ Kirim Jawaban</strong>
                                            <p>Jika hasil sudah sesuai dengan Target, klik tombol <strong>"Kirim"</strong> (ungu) untuk validasi.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="instruction-intro-enhanced">
                                <div class="intro-icon">🎯</div>
                                <div class="intro-content">
                                    <strong>Panduan Penting:</strong> Ikuti langkah-langkah di bawah ini <strong>secara berurutan</strong>. Setiap langkah memiliki kode yang bisa dicopy. Setelah menyalin, modifikasi sesuai instruksi di langkah tersebut.
                                </div>
                            </div>
                            
                            <?php foreach ($instructions as $index => $step): ?>
                                <div class="instruction-box">
                                    <div class="instruction-number"><?php echo $index + 1; ?></div>
                                    
                                    <?php if (isset($step['title'])): ?>
                                        <h4 class="instruction-step-title"><?php echo htmlspecialchars($step['title']); ?></h4>
                                    <?php else: ?>
                                        <h4 class="instruction-step-title">Langkah <?php echo $index + 1; ?></h4>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($step['text'])): ?>
                                        <?php 
                                        // Clean up text - remove markdown headers and fix broken emoji
                                        $clean_text = $step['text'];
                                        // Remove markdown headers at the beginning (###, ##, #)
                                        $clean_text = preg_replace('/^#+\s*[\?\x{1F300}-\x{1F9FF}]*\s*(Instruksi|Instructions?)?\s*/mu', '', $clean_text);
                                        // Remove all broken emoji patterns (???, ???? etc) from anywhere in text
                                        $clean_text = preg_replace('/\?{2,}/', '', $clean_text);
                                        // Remove lines that only contain whitespace or question marks
                                        $clean_text = preg_replace('/^\s*\?*\s*$/m', '', $clean_text);
                                        // Clean up multiple newlines
                                        $clean_text = preg_replace('/\n{3,}/', "\n\n", $clean_text);
                                        // Trim whitespace
                                        $clean_text = trim($clean_text);
                                        ?>
                                        <?php if (!empty($clean_text)): ?>
                                            <div class="instruction-text">
                                                <div class="instruction-action">
                                                    <span class="action-icon">📝</span>
                                                    <span class="action-text"><strong>Instruksi Langkah <?php echo $index + 1; ?>:</strong></span>
                                                </div>
                                                <div class="instruction-content-text">
                                                    <?php 
                                                    // Format text with better structure
                                                    $lines = explode("\n", $clean_text);
                                                    foreach ($lines as $line) {
                                                        $line = trim($line);
                                                        if (empty($line)) {
                                                            echo '<br>';
                                                        } elseif (preg_match('/^[-*•]\s*(.+)$/', $line, $matches)) {
                                                            echo '<div class="instruction-bullet">• ' . htmlspecialchars($matches[1]) . '</div>';
                                                        } elseif (preg_match('/^\d+[\.\)]\s*(.+)$/', $line, $matches)) {
                                                            echo '<div class="instruction-bullet">' . htmlspecialchars($line) . '</div>';
                                                        } else {
                                                            echo '<p>' . htmlspecialchars($line) . '</p>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($step['code']) && !empty($step['code'])): ?>
                                        <div class="code-snippet">
                                            <div class="code-header">
                                                <span class="code-label">
                                                    <?php icon('code', 14); ?> Kode untuk Disalin
                                                </span>
                                                <button class="copy-btn" onclick="copyToClipboard('<?php echo addslashes($step['code']); ?>', this)">
                                                    <?php icon('copy', 12); ?> Copy
                                                </button>
                                            </div>
                                            <code><?php echo htmlspecialchars($step['code']); ?></code>
                                        </div>
                                        <div class="code-instruction">
                                            <span class="code-instruction-icon">💡</span>
                                            <span>Salin kode di atas, lalu tempel di editor dan modifikasi sesuai instruksi</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($step['file'])): ?>
                                        <?php 
                                        // Replace generic index.html with the correct file name for this language
                                        $display_file = $step['file'];
                                        if ($display_file === 'index.html' && $main_file !== 'main.html') {
                                            $display_file = $main_file;
                                        }
                                        ?>
                                        <div class="file-info">
                                            <span class="file-label">
                                                <?php icon('file', 12); ?> 
                                                <strong>File yang digunakan:</strong> <?php echo htmlspecialchars($display_file); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Completion Guide -->
                            <div class="completion-guide">
                                <h4 class="guide-title">🎯 Cara Menyelesaikan:</h4>
                                <div class="guide-steps">
                                    <div class="guide-step">
                                        <div class="guide-step-number">1</div>
                                        <div class="guide-step-content">
                                            <strong>Klik "Run"</strong> untuk melihat hasil kode Anda
                                        </div>
                                    </div>
                                    <div class="guide-step">
                                        <div class="guide-step-number">2</div>
                                        <div class="guide-step-content">
                                            <strong>Periksa Preview</strong> - Bandingkan dengan "Target" di tab preview
                                        </div>
                                    </div>
                                    <div class="guide-step">
                                        <div class="guide-step-number">3</div>
                                        <div class="guide-step-content">
                                            <strong>Klik "Kirim"</strong> jika hasil sudah sesuai dengan target
                                        </div>
                                    </div>
                                    <div class="guide-step">
                                        <div class="guide-step-number">4</div>
                                        <div class="guide-step-content">
                                            <strong>Selesai!</strong> - Anda akan mendapat XP dan bisa lanjut ke lesson berikutnya
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="instruction-tip">
                                <strong>💡 Tips Penting:</strong>
                                <ul class="tip-list">
                                    <li>Pastikan kode yang Anda tulis sesuai dengan instruksi di setiap langkah</li>
                                    <li>Gunakan tombol "Run" untuk mengecek hasil sebelum mengirim</li>
                                    <li>Jika ada error, periksa kembali kode Anda atau lihat "Target" untuk referensi</li>
                                    <li>Kode akan otomatis tersimpan saat Anda mengetik</li>
                                </ul>
                            </div>

                            <!-- Help Section for Validation Errors -->
                            <div class="help-section">
                                <h4 class="help-title">❓ Jika Validasi Error atau Output Belum Sesuai:</h4>
                                <div class="help-content">
                                    <div class="help-step">
                                        <div class="help-step-icon">1️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Periksa Detail Validasi</strong> - Setelah klik "Kirim", panel validasi akan muncul dengan detail pengecekan. Lihat elemen mana yang belum sesuai.
                                        </div>
                                    </div>
                                    <div class="help-step">
                                        <div class="help-step-icon">2️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Bandingkan dengan Target</strong> - Klik tab "Target" di preview untuk melihat contoh hasil yang benar. Bandingkan dengan hasil Anda.
                                        </div>
                                    </div>
                                    <div class="help-step">
                                        <div class="help-step-icon">3️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Ikuti Hints</strong> - Panel validasi akan memberikan hints spesifik tentang apa yang perlu diperbaiki. Ikuti petunjuk tersebut.
                                        </div>
                                    </div>
                                    <div class="help-step">
                                        <div class="help-step-icon">4️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Periksa Kembali Instruksi</strong> - Baca ulang setiap langkah instruksi. Pastikan Anda tidak melewatkan langkah apapun.
                                        </div>
                                    </div>
                                    <div class="help-step">
                                        <div class="help-step-icon">5️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Test dengan Run</strong> - Setelah memperbaiki, klik "Run" untuk melihat hasil baru sebelum mengirim lagi.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="instruction-section">
                            <h3 class="instruction-section-title">✅ Tugas Praktik</h3>
                            
                            <!-- Cara Menyelesaikan Section -->
                            <div class="how-to-complete-section">
                                <h4 class="how-to-title">📋 Cara Menyelesaikan Lesson Ini (Step-by-Step):</h4>
                                <div class="visual-steps">
                                    <div class="visual-step">
                                        <div class="visual-step-number">1</div>
                                        <div class="visual-step-content">
                                            <strong>📖 Baca Instruksi</strong>
                                            <p>Pelajari setiap langkah di bawah ini dengan teliti. Setiap langkah menjelaskan apa yang harus Anda lakukan.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">2</div>
                                        <div class="visual-step-content">
                                            <strong>📋 Salin Kode</strong>
                                            <p>Klik tombol <strong>"Copy"</strong> pada setiap blok kode yang disediakan. Kode akan tersalin ke clipboard Anda.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">3</div>
                                        <div class="visual-step-content">
                                            <strong>📝 Tempel di Editor</strong>
                                            <p>Paste kode (Ctrl+V atau Cmd+V) ke editor di tengah. Editor adalah kotak besar di panel tengah.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">4</div>
                                        <div class="visual-step-content">
                                            <strong>✏️ Modifikasi Kode</strong>
                                            <p>Sesuaikan kode sesuai instruksi. Misalnya: ubah teks, tambah elemen, atau ubah warna.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">5</div>
                                        <div class="visual-step-content">
                                            <strong>▶️ Test dengan Run</strong>
                                            <p>Klik tombol <strong>"Run"</strong> (biru) untuk melihat hasil di preview. Bandingkan dengan tab <strong>"Target"</strong>.</p>
                                        </div>
                                    </div>
                                    <div class="visual-step">
                                        <div class="visual-step-number">6</div>
                                        <div class="visual-step-content">
                                            <strong>✅ Kirim Jawaban</strong>
                                            <p>Jika hasil sudah sesuai dengan Target, klik tombol <strong>"Kirim"</strong> (ungu) untuk validasi.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="instruction-intro-enhanced">
                                <div class="intro-icon">🎯</div>
                                <div class="intro-content">
                                    <strong>Panduan Penting:</strong> Ikuti langkah-langkah di bawah ini <strong>secara berurutan</strong>. Setiap langkah memiliki kode yang bisa dicopy. Setelah menyalin, modifikasi sesuai instruksi di langkah tersebut.
                                </div>
                            </div>
                            
                            <?php if (!empty($lesson_data['instruksi'])): ?>
                                <div class="instruction-box">
                                    <div class="instruction-number">📝</div>
                                    <div class="instruction-text">
                                        <div class="instruction-action">
                                            <span class="action-icon">📝</span>
                                            <span class="action-text"><strong>Instruksi:</strong></span>
                                        </div>
                                        <div class="instruction-content-text">
                                            <?php 
                                            $instruksi_text = $lesson_data['instruksi'];
                                            // Clean up broken emoji
                                            $instruksi_text = preg_replace('/\?{2,}/', '', $instruksi_text);
                                            $instruksi_text = preg_replace('/^\s*\?*\s*$/m', '', $instruksi_text);
                                            $instruksi_text = preg_replace('/\n{3,}/', "\n\n", $instruksi_text);
                                            
                                            // Format text with better structure
                                            $lines = explode("\n", trim($instruksi_text));
                                            foreach ($lines as $line) {
                                                $line = trim($line);
                                                if (empty($line)) {
                                                    echo '<br>';
                                                } elseif (preg_match('/^[-*•]\s*(.+)$/', $line, $matches)) {
                                                    echo '<div class="instruction-bullet">• ' . htmlspecialchars($matches[1]) . '</div>';
                                                } elseif (preg_match('/^\d+[\.\)]\s*(.+)$/', $line, $matches)) {
                                                    echo '<div class="instruction-bullet">' . htmlspecialchars($line) . '</div>';
                                                } else {
                                                    echo '<p>' . htmlspecialchars($line) . '</p>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($lesson_data['kode_contoh'])): ?>
                                <div class="code-snippet">
                                    <div class="code-header">
                                        <span class="code-label">
                                            <?php icon('code', 14); ?> Kode untuk Disalin
                                        </span>
                                        <button class="copy-btn" onclick="copyToClipboard('<?php echo addslashes($lesson_data['kode_contoh']); ?>', this)">
                                            <?php icon('copy', 12); ?> Copy
                                        </button>
                                    </div>
                                    <code><?php echo htmlspecialchars($lesson_data['kode_contoh']); ?></code>
                                </div>
                                <div class="code-instruction">
                                    <span class="code-instruction-icon">💡</span>
                                    <span>Salin kode di atas, lalu tempel di editor dan modifikasi sesuai instruksi</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Completion Guide -->
                            <div class="completion-guide">
                                <h4 class="guide-title">🎯 Cara Menyelesaikan:</h4>
                                <div class="guide-steps">
                                    <div class="guide-step">
                                        <div class="guide-step-number">1</div>
                                        <div class="guide-step-content">
                                            <strong>Klik "Run"</strong> untuk melihat hasil kode Anda
                                        </div>
                                    </div>
                                    <div class="guide-step">
                                        <div class="guide-step-number">2</div>
                                        <div class="guide-step-content">
                                            <strong>Periksa Preview</strong> - Bandingkan dengan "Target" di tab preview
                                        </div>
                                    </div>
                                    <div class="guide-step">
                                        <div class="guide-step-number">3</div>
                                        <div class="guide-step-content">
                                            <strong>Klik "Kirim"</strong> jika hasil sudah sesuai dengan target
                                        </div>
                                    </div>
                                    <div class="guide-step">
                                        <div class="guide-step-number">4</div>
                                        <div class="guide-step-content">
                                            <strong>Selesai!</strong> - Anda akan mendapat XP dan bisa lanjut ke lesson berikutnya
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="instruction-tip">
                                <strong>💡 Tips Penting:</strong>
                                <ul class="tip-list">
                                    <li>Pastikan kode yang Anda tulis sesuai dengan instruksi di setiap langkah</li>
                                    <li>Gunakan tombol "Run" untuk mengecek hasil sebelum mengirim</li>
                                    <li>Jika ada error, periksa kembali kode Anda atau lihat "Target" untuk referensi</li>
                                    <li>Kode akan otomatis tersimpan saat Anda mengetik</li>
                                </ul>
                            </div>

                            <!-- Help Section for Validation Errors -->
                            <div class="help-section">
                                <h4 class="help-title">❓ Jika Validasi Error atau Output Belum Sesuai:</h4>
                                <div class="help-content">
                                    <div class="help-step">
                                        <div class="help-step-icon">1️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Periksa Detail Validasi</strong> - Setelah klik "Kirim", panel validasi akan muncul dengan detail pengecekan. Lihat elemen mana yang belum sesuai.
                                        </div>
                                    </div>
                                    <div class="help-step">
                                        <div class="help-step-icon">2️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Bandingkan dengan Target</strong> - Klik tab "Target" di preview untuk melihat contoh hasil yang benar. Bandingkan dengan hasil Anda.
                                        </div>
                                    </div>
                                    <div class="help-step">
                                        <div class="help-step-icon">3️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Ikuti Hints</strong> - Panel validasi akan memberikan hints spesifik tentang apa yang perlu diperbaiki. Ikuti petunjuk tersebut.
                                        </div>
                                    </div>
                                    <div class="help-step">
                                        <div class="help-step-icon">4️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Periksa Kembali Instruksi</strong> - Baca ulang setiap langkah instruksi. Pastikan Anda tidak melewatkan langkah apapun.
                                        </div>
                                    </div>
                                    <div class="help-step">
                                        <div class="help-step-icon">5️⃣</div>
                                        <div class="help-step-text">
                                            <strong>Test dengan Run</strong> - Setelah memperbaiki, klik "Run" untuk melihat hasil baru sebelum mengirim lagi.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- File List -->
                    <div class="file-list">
                        <div class="file-list-title"><?php icon('folder', 16); ?> File yang Digunakan</div>
                        <div class="file-item" onclick="switchTab('html')">
                            <div class="file-icon"><?php icon($file_icon, 16); ?></div>
                            <div class="file-name">main<?php echo $file_ext; ?></div>
                        </div>
                        <?php if ($show_css_tab): ?>
                        <div class="file-item" onclick="switchTab('css')">
                            <div class="file-icon"><?php icon('paint', 16); ?></div>
                            <div class="file-name">stylesheet.css</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Task Summary Box -->
                    <div class="task-summary">
                        <h4><?php icon('clipboard', 16); ?> Yang Harus Dilakukan</h4>
                        <div class="task-box">
                            <div class="task-step">
                                <span class="task-num">1</span>
                                <span>Baca materi & instruksi di atas</span>
                            </div>
                            <div class="task-step">
                                <span class="task-num">2</span>
                                <span>Lihat tab <strong>"Target"</strong> di Preview untuk melihat hasil yang diharapkan</span>
                            </div>
                            <div class="task-step">
                                <span class="task-num">3</span>
                                <span>Tulis kode di Editor sesuai instruksi</span>
                            </div>
                            <div class="task-step">
                                <span class="task-num">4</span>
                                <span>Klik <strong>Run</strong> → bandingkan "Hasil Anda" dengan "Target"</span>
                            </div>
                            <div class="task-step">
                                <span class="task-num">5</span>
                                <span>Klik <strong>Kirim</strong> untuk validasi (minimal skor 80%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Success Criteria -->
                    <div class="success-criteria">
                        <h4><?php icon('target', 16); ?> Kriteria Validasi</h4>
                        <div class="criteria-info">
                            <ul>
                                <?php if (in_array($file_ext, ['.html', '.htm'])): ?>
                                <li><span class="check-icon">☐</span> Elemen HTML sesuai (tag & struktur)</li>
                                <li><span class="check-icon">☐</span> Properti CSS lengkap</li>
                                <li><span class="check-icon">☐</span> Hasil mirip dengan "Target"</li>
                                <?php else: ?>
                                <li><span class="check-icon">☐</span> Output program benar</li>
                                <li><span class="check-icon">☐</span> Struktur kode sesuai</li>
                                <li><span class="check-icon">☐</span> Logika berjalan benar</li>
                                <?php endif; ?>
                            </ul>
                            <div class="score-info">
                                <span class="score-badge success">≥80%</span> = Lulus
                            </div>
                        </div>
                    </div>

                    <div class="practice-tips">
                        <h4><?php icon('lightbulb', 16); ?> Tips</h4>
                        <ul>
                            <li>Perhatikan <strong>hints</strong> yang muncul saat validasi gagal</li>
                            <li>Bandingkan hasil Anda dengan <strong>"Jawaban Benar"</strong> di Preview</li>
                            <li>Pastikan tidak ada <strong>typo</strong> pada nama tag/properti</li>
                            <li>Kode akan <strong>auto-save</strong> setiap beberapa detik</li>
                        </ul>
                    </div>
                </div>

                <div class="instructions-footer">
                    <button class="back-to-slide-btn" onclick="window.location.href='course.php?id=<?php echo $course_id; ?>'">
                        ← Kembali ke Course
                    </button>
                </div>
            </div>

            <!-- Middle Panel: Code Editor -->
            <div class="editor-panel">
                <div class="editor-tabs">
                    <button class="editor-tab active" onclick="switchTab('html')">
                        <?php icon($file_icon, 14); ?> <?php echo $main_file; ?>
                    </button>
                    <?php if ($show_css_tab): ?>
                    <button class="editor-tab" onclick="switchTab('css')">
                        <?php icon('css3', 14); ?> stylesheet.css
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="editor-content">
                    <textarea id="htmlEditor" style="display: block;"><?php 
                        // Use starter_code from JSON if available
                        if (!empty($starter_code)) {
                            echo htmlspecialchars($starter_code);
                        } else if (empty($user_html) && !empty($lesson_data['kode_contoh'])) {
                            echo htmlspecialchars($lesson_data['kode_contoh']);
                        } else if (!empty($user_html)) {
                            echo htmlspecialchars($user_html);
                        } else {
                            echo htmlspecialchars($default_code);
                        }
                    ?></textarea>
                    <?php if ($show_css_tab): ?>
                    <textarea id="cssEditor" style="display: none;"><?php echo htmlspecialchars($user_css); ?></textarea>
                    <?php else: ?>
                    <textarea id="cssEditor" style="display: none;"></textarea>
                    <?php endif; ?>
                </div>

                <form method="POST" id="lessonForm" class="editor-actions">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="html_code" id="htmlCodeInput">
                    <input type="hidden" name="css_code" id="cssCodeInput">
                    <input type="hidden" name="status" id="statusInput" value="<?php echo $progress['status'] ?? 'in_progress'; ?>">
                    <input type="hidden" name="skor" value="0">
                    <input type="hidden" name="save_progress" id="saveProgressInput" value="">
                    
                    <button type="button" onclick="runPreview()" class="btn-run">
                        <?php icon('play', 14); ?> Run
                    </button>
                    <button type="button" onclick="submitCode()" class="btn-submit">
                        <?php icon('send', 14); ?> Kirim
                    </button>
                </form>
                
                <!-- Validation Result Panel -->
                <!-- Enhanced Validation Modal -->
                <div id="validationModal" class="validation-modal-overlay" style="display: none;">
                    <div class="validation-modal">
                        <div class="validation-modal-header">
                            <div class="validation-modal-title-section">
                                <span class="validation-modal-icon">⚠️</span>
                                <div>
                                    <h3 class="validation-modal-title">Hasil Validasi</h3>
                                    <p class="validation-modal-subtitle">Skor: <span id="modalScore" class="modal-score-text">0%</span></p>
                                </div>
                            </div>
                            <button class="validation-modal-close" type="button" aria-label="Tutup" onclick="window.closeValidationModal && window.closeValidationModal(); event.stopPropagation(); return false;">×</button>
                        </div>
                        
                        <div class="validation-modal-body">
                            <div id="modalMessage" class="validation-modal-message"></div>
                            
                            <!-- Comparison Section -->
                            <div id="comparisonSection" class="comparison-section" style="display: none;">
                                <h4 class="comparison-title">📊 Perbandingan:</h4>
                                <div class="comparison-grid">
                                    <div class="comparison-item">
                                        <div class="comparison-label">✅ Yang Sudah Benar</div>
                                        <div id="passedChecks" class="comparison-content passed"></div>
                                    </div>
                                    <div class="comparison-item">
                                        <div class="comparison-label">❌ Yang Perlu Diperbaiki</div>
                                        <div id="failedChecks" class="comparison-content failed"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Items -->
                            <div id="actionItems" class="action-items-section">
                                <h4 class="action-items-title">🎯 Yang Harus Anda Lakukan:</h4>
                                <div id="actionItemsList" class="action-items-list"></div>
                            </div>
                            
                            <!-- Quick Fix Guide -->
                            <div class="quick-fix-guide">
                                <h4 class="quick-fix-title">⚡ Perbaikan Cepat:</h4>
                                <div class="quick-fix-steps">
                                    <div class="quick-fix-step">
                                        <span class="quick-fix-number">1</span>
                                        <span>Buka tab <strong>"Target"</strong> di Preview untuk melihat contoh yang benar</span>
                                    </div>
                                    <div class="quick-fix-step">
                                        <span class="quick-fix-number">2</span>
                                        <span>Perbaiki kode sesuai dengan item di atas yang perlu diperbaiki</span>
                                    </div>
                                    <div class="quick-fix-step">
                                        <span class="quick-fix-number">3</span>
                                        <span>Klik <strong>"Run"</strong> untuk melihat hasil baru</span>
                                    </div>
                                    <div class="quick-fix-step">
                                        <span class="quick-fix-number">4</span>
                                        <span>Jika sudah sesuai, klik <strong>"Kirim"</strong> lagi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="validation-modal-footer">
                            <button onclick="if(typeof switchPreviewTab === 'function') switchPreviewTab('expected'); window.closeValidationModal();" class="modal-action-btn primary" type="button">
                                <?php icon('eye', 16); ?> Lihat Target
                            </button>
                            <button class="modal-action-btn secondary" type="button" onclick="window.closeValidationModal && window.closeValidationModal(); event.stopPropagation(); return false;">
                                <?php icon('x', 16); ?> Tutup
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Old validation result (kept for compatibility) -->
                <div id="validationResult" class="validation-result" style="display: none;">
                    <div class="validation-header">
                        <span class="validation-icon">✓</span>
                        <span class="validation-title">Hasil Validasi</span>
                        <button onclick="closeValidation()" class="validation-close">×</button>
                    </div>
                    <div class="validation-body">
                        <div class="validation-score">
                            <span class="score-value">0%</span>
                        </div>
                        <div class="validation-message"></div>
                        <div class="validation-hints"></div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Preview -->
            <div class="preview-panel">
                <div class="preview-header">
                    <h3>Preview</h3>
                    <div class="preview-tabs">
                        <button class="preview-tab-btn active" onclick="switchPreviewTab('user')">Hasil Anda</button>
                        <button class="preview-tab-btn" onclick="switchPreviewTab('expected')">Target</button>
                    </div>
                </div>
                <div class="preview-body">
                    <iframe id="previewFrame" class="preview-iframe"></iframe>
                    <iframe id="expectedFrame" class="preview-iframe" style="display: none;"></iframe>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="assets/js/navbar.js"></script>
    
    <script>
        // Slide Navigation
        <?php if ($lesson_type === 'theory'): ?>
        let currentSlideIndex = 0;
        const totalSlides = <?php echo count($slides); ?>;
        const slides = document.querySelectorAll('.slide-card');

        function updateSlide() {
            slides.forEach((slide, index) => {
                slide.classList.remove('active');
                if (index === currentSlideIndex) {
                    slide.classList.add('active');
                }
            });

            // Update navigation buttons
            document.getElementById('prevBtn').disabled = currentSlideIndex === 0;
            
            const isLastSlide = currentSlideIndex === totalSlides - 1;
            const nextBtn = document.getElementById('nextBtn');
            const finishBtn = document.getElementById('finishBtn');
            
            if (isLastSlide) {
                nextBtn.style.display = 'none';
                finishBtn.style.display = 'inline-block';
            } else {
                nextBtn.style.display = 'inline-block';
                finishBtn.style.display = 'none';
                nextBtn.disabled = false;
            }

            // Update indicator
            document.getElementById('currentSlide').textContent = currentSlideIndex + 1;

            // Update progress bar
            const progress = ((currentSlideIndex + 1) / totalSlides) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }

        async function completeTheory() {
            const finishBtn = document.getElementById('finishBtn');
            const originalText = finishBtn.innerText;
            finishBtn.innerText = 'Menyimpan...';
            finishBtn.disabled = true;

            const formData = new FormData();
            formData.append('course_id', <?php echo $course_id; ?>);
            formData.append('lesson_id', <?php echo $lesson_id; ?>);
            formData.append('status', 'completed');
            formData.append('skor', 100);
            
            try {
                const response = await fetch('api/save-progress.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    // Show XP reward if earned
                    if (result.xp_earned > 0) {
                        showXPReward(result.xp_earned);
                        setTimeout(() => {
                            redirectNext();
                        }, 2000);
                    } else {
                        redirectNext();
                    }
                } else {
                    alert('Gagal menyimpan progress: ' + result.message);
                    finishBtn.innerText = originalText;
                    finishBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan koneksi');
                finishBtn.innerText = originalText;
                finishBtn.disabled = false;
            }
        }

        function redirectNext() {
            <?php if ($next_lesson): ?>
            window.location.href = "lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $next_lesson['id']; ?>";
            <?php else: ?>
            window.location.href = "course.php?id=<?php echo $course_id; ?>";
            <?php endif; ?>
        }

        function nextSlide() {
            if (currentSlideIndex < totalSlides - 1) {
                currentSlideIndex++;
                updateSlide();
            }
        }

        function previousSlide() {
            if (currentSlideIndex > 0) {
                currentSlideIndex--;
                updateSlide();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                previousSlide();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
            }
        });

        // Initialize
        updateSlide();
        <?php endif; ?>


        <?php if ($lesson_type === 'practice'): ?>
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Detect course language for syntax highlighting
            // Will be updated from realtime data
            window.courseTitle = "<?php echo addslashes($course_data['judul_course'] ?? ''); ?>";
            const editorMode = "<?php echo $editor_mode ?? 'htmlmixed'; ?>";
            let detectedLanguage = editorMode; // Use PHP-detected mode
            
            // Function to detect language from course title
            function detectLanguageFromTitle(title) {
                if (!title) return 'htmlmixed';
                const lowerTitle = title.toLowerCase();
                if (lowerTitle.includes('python')) {
                    return 'python';
                } else if (lowerTitle.includes('php')) {
                    return 'application/x-httpd-php';
                } else if (lowerTitle.includes('javascript') || lowerTitle.includes('js')) {
                    return 'javascript';
                } else if (lowerTitle.includes('java') && !lowerTitle.includes('javascript')) {
                    return 'text/x-java';
                } else if (lowerTitle.includes('c++') || lowerTitle.includes('cpp')) {
                    return 'text/x-c++src';
                }
                return 'htmlmixed';
            }
            
            // Fallback detection from title if needed
            if (detectedLanguage === 'htmlmixed') {
                detectedLanguage = detectLanguageFromTitle(window.courseTitle);
            }
            
            // Update language detection when realtime data updates
            window.addEventListener('lessonDataUpdated', function(event) {
                const data = event.detail;
                if (data.course) {
                    window.courseTitle = data.course.judul_course || '';
                    // Re-detect language if editor exists
                    if (htmlEditor && htmlEditor.codeMirror) {
                        const newLanguage = detectLanguageFromTitle(window.courseTitle);
                        if (newLanguage !== detectedLanguage) {
                            detectedLanguage = newLanguage;
                            htmlEditor.codeMirror.setOption('mode', detectedLanguage);
                        }
                    }
                }
            });

        // Initialize CodeMirror editors (only for practice mode)
        let currentTab = 'html';
        let htmlEditor, cssEditor;
        
        // Expose editor to global scope for AI chatbot
        window.htmlEditor = null;

        // Initialize editors with auto-detected language
        try {
            htmlEditor = CodeMirror.fromTextArea(document.getElementById('htmlEditor'), {
                mode: detectedLanguage,
                theme: 'monokai',
                lineNumbers: true,
                autoCloseTags: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 4,
                tabSize: 4,
                lineWrapping: true,
                extraKeys: {
                    "Ctrl-Space": "autocomplete"
                }
            });

            cssEditor = CodeMirror.fromTextArea(document.getElementById('cssEditor'), {
                mode: 'css',
                theme: 'monokai',
                lineNumbers: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 2,
                tabSize: 2,
                lineWrapping: true
            });

            // Log detected language for debugging
            console.log('CodeMirror initialized with mode:', detectedLanguage);
            
            // Expose to global scope for AI chatbot
            window.htmlEditor = htmlEditor;
            window.cssEditor = cssEditor;
        } catch(e) {
            console.error('CodeMirror initialization error:', e);
        }
        
        // Expose key functions to global scope
        window.runPreview = null;
        window.validateCode = null;
        window.handleSave = null;
        window.completeLesson = null;

        function switchTab(tab) {
            if (!htmlEditor || !cssEditor) return;
            
            currentTab = tab;
            const htmlTab = document.querySelector('.editor-tab:first-child');
            const cssTab = document.querySelector('.editor-tab:last-child');
            
            if (tab === 'html') {
                htmlTab.classList.add('active');
                cssTab.classList.remove('active');
                htmlEditor.getWrapperElement().style.display = 'block';
                cssEditor.getWrapperElement().style.display = 'none';
                setTimeout(() => htmlEditor.refresh(), 100);
            } else {
                cssTab.classList.add('active');
                htmlTab.classList.remove('active');
                htmlEditor.getWrapperElement().style.display = 'none';
                cssEditor.getWrapperElement().style.display = 'block';
                setTimeout(() => cssEditor.refresh(), 100);
            }
        }

        // Variable to store last run output for validation
        let lastRunOutput = '';

        async function runPreview() {
            if (!htmlEditor || !cssEditor) {
                console.error('Editor not initialized');
                showNotification('Editor belum siap. Silakan refresh halaman.', 'error');
                return;
            }
            
            const htmlCode = htmlEditor.getValue();
            const cssCode = cssEditor.getValue();
            
            // Show running feedback
            const runBtn = document.querySelector('.btn-run');
            const originalText = runBtn.innerHTML;
            runBtn.innerHTML = 'Running...';
            runBtn.disabled = true;
            
            // Handle different languages
            const previewFrame = document.getElementById('previewFrame');
            
            if (detectedLanguage === 'htmlmixed') {
                // HTML/CSS Mode - Show in iframe
                let combinedCode = htmlCode;
                if (cssCode.trim()) {
                    if (combinedCode.includes('</head>')) {
                        combinedCode = combinedCode.replace('</head>', '<style>' + cssCode + '</style></head>');
                    } else if (combinedCode.includes('<body>')) {
                        combinedCode = combinedCode.replace('<body>', '<head><style>' + cssCode + '</style></head><body>');
                    } else if (combinedCode.includes('<html>')) {
                        combinedCode = combinedCode.replace('<html>', '<html><head><style>' + cssCode + '</style></head>');
                    } else {
                        combinedCode = '<!DOCTYPE html><html><head><style>' + cssCode + '</style></head><body>' + combinedCode + '</body></html>';
                    }
                } else if (!combinedCode.includes('<!DOCTYPE') && !combinedCode.includes('<html')) {
                    combinedCode = '<!DOCTYPE html><html><head></head><body>' + combinedCode + '</body></html>';
                }
                previewFrame.srcdoc = combinedCode;
                
                // Restore button
                setTimeout(() => {
                    runBtn.innerHTML = 'Jalankan';
                    runBtn.disabled = false;
                }, 300);

            } else if (detectedLanguage === 'javascript') {
                // Client-side JS execution with console capture
                const script = `
                    <script>
                        // Capture console.log
                        const originalLog = console.log;
                        const outputDiv = document.getElementById('output');
                        
                        function appendOutput(text, type = 'log') {
                            const line = document.createElement('div');
                            line.textContent = text;
                            line.style.color = type === 'error' ? '#ff5555' : '#e2e8f0';
                            line.style.borderBottom = '1px solid #333';
                            line.style.padding = '4px 0';
                            outputDiv.appendChild(line);
                        }

                        console.log = function(...args) {
                            const text = args.map(arg => 
                                typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
                            ).join(' ');
                            appendOutput(text);
                            originalLog.apply(console, args);
                        };
                        
                        // Capture errors
                        window.onerror = function(msg, url, line) {
                            appendOutput('Error: ' + msg, 'error');
                            return true;
                        };

                        try {
                            ${htmlCode}
                        } catch (e) {
                            console.log('Error: ' + e.message);
                        }
                    <\/script>
                `;
                
                const html = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { background:#1e1e1e; color:#e2e8f0; font-family:'Fira Code', monospace; padding:15px; margin:0; }
                            #output { white-space: pre-wrap; }
                        </style>
                    </head>
                    <body>
                        <div id="output"></div>
                        ${script}
                    </body>
                    </html>
                `;
                previewFrame.srcdoc = html;
                
                // Restore button
                setTimeout(() => {
                    runBtn.innerHTML = 'Jalankan';
                    runBtn.disabled = false;
                }, 300);

            } else {
                // Backend execution for PHP, Python, Java
                previewFrame.srcdoc = `
                    <html><body style="background:#1e1e1e; color:#aaa; font-family:sans-serif; display:flex; justify-content:center; align-items:center; height:100%; margin:0;">
                        <div style="text-align:center">
                            <div style="font-size:24px; margin-bottom:10px;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></div>
                            <div>Running code on server...</div>
                        </div>
                    </body></html>
                `;
                
                try {
                    const response = await fetch('api/run-code.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ language: detectedLanguage, code: htmlCode })
                    });
                    
                    const text = await response.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid server response. Check console for details.');
                    }
                    
                    const output = data.output !== null ? data.output : 'No output returned';
                    
                    // Store output for validation
                    lastRunOutput = output;
                    
                    // Check if compiler not found - show online compiler buttons
                    const isCompilerMissing = output.includes('belum terinstall') || output.includes('not found');
                    
                    // Escape HTML in output
                    const escapedOutput = String(output)
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;");

                    // Generate online compiler buttons if needed
                    let onlineCompilerButtons = '';
                    if (isCompilerMissing) {
                        const code = encodeURIComponent(htmlCode);
                        if (detectedLanguage === 'java' || detectedLanguage === 'text/x-java') {
                            onlineCompilerButtons = `
                                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #333;">
                                    <div style="margin-bottom:10px; color:#10b981; font-weight:bold;">🚀 Run Online:</div>
                                    <a href="https://www.jdoodle.com/online-java-compiler/" target="_blank" 
                                       style="display:inline-block; padding:8px 16px; background:#8b5cf6; color:white; text-decoration:none; border-radius:6px; margin-right:8px; margin-bottom:8px;">
                                       JDoodle
                                    </a>
                                    <a href="https://www.programiz.com/java-programming/online-compiler/" target="_blank"
                                       style="display:inline-block; padding:8px 16px; background:#3b82f6; color:white; text-decoration:none; border-radius:6px; margin-right:8px; margin-bottom:8px;">
                                       Programiz
                                    </a>
                                    <a href="https://www.onlinegdb.com/online_java_compiler" target="_blank"
                                       style="display:inline-block; padding:8px 16px; background:#10b981; color:white; text-decoration:none; border-radius:6px; margin-bottom:8px;">
                                       OnlineGDB
                                    </a>
                                </div>`;
                        } else if (detectedLanguage === 'cpp' || detectedLanguage === 'c++' || detectedLanguage === 'text/x-c++src') {
                            onlineCompilerButtons = `
                                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #333;">
                                    <div style="margin-bottom:10px; color:#10b981; font-weight:bold;">🚀 Run Online:</div>
                                    <a href="https://www.onlinegdb.com/online_c++_compiler" target="_blank"
                                       style="display:inline-block; padding:8px 16px; background:#8b5cf6; color:white; text-decoration:none; border-radius:6px; margin-right:8px; margin-bottom:8px;">
                                       OnlineGDB
                                    </a>
                                    <a href="https://www.programiz.com/cpp-programming/online-compiler/" target="_blank"
                                       style="display:inline-block; padding:8px 16px; background:#3b82f6; color:white; text-decoration:none; border-radius:6px; margin-right:8px; margin-bottom:8px;">
                                       Programiz
                                    </a>
                                    <a href="https://cpp.sh/" target="_blank"
                                       style="display:inline-block; padding:8px 16px; background:#10b981; color:white; text-decoration:none; border-radius:6px; margin-bottom:8px;">
                                       cpp.sh
                                    </a>
                                </div>`;
                        }
                    }

                    previewFrame.srcdoc = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>
                                body { background:#1e1e1e; color:#e2e8f0; font-family:'Fira Code', monospace; padding:15px; margin:0; }
                                pre { white-space: pre-wrap; word-wrap: break-word; margin: 0; }
                            </style>
                        </head>
                        <body>
                            <pre>${escapedOutput}</pre>
                            ${onlineCompilerButtons}
                        </body>
                        </html>
                    `;
                } catch (e) {
                    previewFrame.srcdoc = `
                        <html><body style="background:#1e1e1e; color:#ff5555; font-family:monospace; padding:15px;">Error connecting to server: ${e.message}</body></html>
                    `;
                }
                
                // Restore button
                runBtn.innerHTML = 'Jalankan';
                runBtn.disabled = false;
            }
        }
        <?php endif; ?>

        function copyToClipboard(text, btnElement) {
            navigator.clipboard.writeText(text).then(() => {
                if (btnElement) {
                    const originalHTML = btnElement.innerHTML;
                    btnElement.textContent = 'Copied!';
                    setTimeout(() => {
                        btnElement.innerHTML = originalHTML;
                    }, 1500);
                }
            }).catch(() => {
                alert('Gagal menyalin kode. Silakan salin manual.');
            });
        }

        // Show notification toast
        function showNotification(message, type = 'success') {
            // Remove existing notifications
            const existing = document.querySelectorAll('.notification-toast');
            existing.forEach(el => el.remove());
            
            const toast = document.createElement('div');
            toast.className = 'notification-toast notification-' + type;
            toast.innerHTML = `
                <span class="notification-message">${message}</span>
            `;
            document.body.appendChild(toast);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                toast.classList.add('notification-hide');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Show XP reward animation
        function showXPReward(xp) {
            const xpToast = document.createElement('div');
            xpToast.className = 'xp-reward-toast';
            xpToast.innerHTML = `
                <div class="xp-content">
                    <div class="xp-title">Lesson Selesai!</div>
                    <div class="xp-amount">+${xp} XP</div>
                </div>
            `;
            document.body.appendChild(xpToast);
            
            // Animate and remove
            setTimeout(() => {
                xpToast.classList.add('xp-hide');
                setTimeout(() => xpToast.remove(), 500);
            }, 3500);
        }

        <?php if ($lesson_type === 'practice'): ?>
        function saveCode() {
            if (!htmlEditor || !cssEditor) return;
            
            document.getElementById('htmlCodeInput').value = htmlEditor.getValue();
            document.getElementById('cssCodeInput').value = cssEditor.getValue();
        }

        // Validate code against expected output
        async function submitCode() {
            if (!htmlEditor || !cssEditor) {
                showNotification('Editor belum siap. Silakan refresh halaman.', 'error');
                return;
            }

            const submitBtn = document.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Memeriksa...';
            submitBtn.disabled = true;

            // First run preview
            runPreview();

            const htmlCode = htmlEditor.getValue();
            const cssCode = cssEditor.getValue();

            // Combine HTML and CSS
            let combinedCode = htmlCode;
            if (cssCode.trim()) {
                if (combinedCode.includes('</head>')) {
                    combinedCode = combinedCode.replace('</head>', '<style>' + cssCode + '</style></head>');
                } else {
                    combinedCode = '<!DOCTYPE html><html><head><style>' + cssCode + '</style></head><body>' + combinedCode + '</body></html>';
                }
            }

            try {
                // Validate the code
                const response = await fetch('api/validate-code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lesson_id: <?php echo $lesson_id; ?>,
                        user_code: combinedCode,
                        user_output: lastRunOutput,
                        language: detectedLanguage
                    })
                });

                const result = await response.json();

                if (result.success && result.validation) {
                    const validation = result.validation;
                    
                    if (validation.score >= 80) {
                        // Code is correct! Save progress and show XP
                        await saveProgressAndComplete(combinedCode);
                    } else {
                        // Code needs improvement - show validation result
                        showValidationResult(validation);
                    }
                } else {
                    showNotification('Gagal validasi: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Validation error:', error);
                showNotification('Terjadi kesalahan saat validasi', 'error');
            }

            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }

        // Save progress and mark complete when code is correct
        async function saveProgressAndComplete(combinedCode) {
            try {
                const formData = new FormData();
                formData.append('course_id', <?php echo $course_id; ?>);
                formData.append('lesson_id', <?php echo $lesson_id; ?>);
                formData.append('status', 'completed');
                formData.append('kode_user', combinedCode);
                formData.append('skor', 100);

                const response = await fetch('api/save-progress.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    // Show success modal with XP info
                    const xpEarned = result.xp_earned || window.lessonXpReward || 10;
                    showSuccessModal(xpEarned);
                } else {
                    showNotification('Gagal menyimpan progress: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Save error:', error);
                showNotification('Terjadi kesalahan saat menyimpan', 'error');
            }
        }

        // Show success modal with next lesson option
        function showSuccessModal(xpEarned = 0) {
            // Use global variables that are updated in realtime
            const hasNextLesson = window.hasNextLesson || false;
            const nextLessonUrl = window.nextLessonUrl || '';
            const courseUrl = window.courseUrl || `course.php?id=${courseId}`;
            
            const modal = document.createElement('div');
            modal.className = 'success-modal-overlay';
            modal.innerHTML = `
                <div class="success-modal">
                    <div class="success-modal-icon">🎉</div>
                    <h2 class="success-modal-title">Kerja Bagus!</h2>
                    ${xpEarned > 0 ? `<div class="success-modal-xp">+${xpEarned} XP</div>` : ''}
                    <p class="success-modal-message">Kode kamu sudah benar. Lesson ini telah diselesaikan!</p>
                    <div class="success-modal-actions">
                        ${hasNextLesson ? 
                            `<button class="success-modal-btn primary" onclick="window.location.href='${nextLessonUrl}'">
                                Lanjut ke Lesson Berikutnya →
                            </button>` : 
                            `<button class="success-modal-btn primary" onclick="window.location.href='${courseUrl}'">
                                Kembali ke Course
                            </button>`
                        }
                        <button class="success-modal-btn secondary" onclick="this.closest('.success-modal-overlay').remove()">
                            Tetap di Halaman Ini
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Show validation result panel
        function showValidationResult(validation) {
            // Show enhanced modal for better visibility
            const modal = document.getElementById('validationModal');
            const modalScore = document.getElementById('modalScore');
            const modalMessage = document.getElementById('modalMessage');
            const comparisonSection = document.getElementById('comparisonSection');
            const passedChecks = document.getElementById('passedChecks');
            const failedChecks = document.getElementById('failedChecks');
            const actionItemsList = document.getElementById('actionItemsList');

            // Set score
            modalScore.textContent = validation.score + '%';
            if (validation.score >= 80) {
                modalScore.style.color = '#10b981';
            } else if (validation.score >= 50) {
                modalScore.style.color = '#f59e0b';
            } else {
                modalScore.style.color = '#ef4444';
            }

            // Set message
            let messageHtml = `<strong style="font-size: 1.2rem; display: block; margin-bottom: 0.5rem;">${escapeHtml(validation.message)}</strong>`;
            if (validation.score < 80) {
                messageHtml += `<p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: var(--text-muted);">Lihat detail di bawah untuk mengetahui apa yang perlu diperbaiki</p>`;
            }
            modalMessage.innerHTML = messageHtml;

            // Show comparison if checks available
            if (validation.checks && validation.checks.length > 0) {
                comparisonSection.style.display = 'block';
                
                const passed = validation.checks.filter(c => c.passed);
                const failed = validation.checks.filter(c => !c.passed);
                
                let passedHtml = '';
                if (passed.length > 0) {
                    passed.forEach(check => {
                        passedHtml += `<div class="comparison-check-item passed">✓ ${escapeHtml(check.element)}</div>`;
                    });
                } else {
                    passedHtml = '<div style="color: var(--text-muted); font-size: 0.85rem; padding: 0.5rem;">Belum ada yang benar</div>';
                }
                passedChecks.innerHTML = passedHtml;
                
                let failedHtml = '';
                if (failed.length > 0) {
                    failed.forEach(check => {
                        failedHtml += `<div class="comparison-check-item failed">✗ ${escapeHtml(check.element)}</div>`;
                    });
                } else {
                    failedHtml = '<div style="color: var(--text-muted); font-size: 0.85rem; padding: 0.5rem;">Semua sudah benar!</div>';
                }
                failedChecks.innerHTML = failedHtml;
            } else {
                comparisonSection.style.display = 'none';
            }

            // Set action items from hints
            if (validation.hints && validation.hints.length > 0) {
                let actionHtml = '';
                validation.hints.forEach((hint, index) => {
                    actionHtml += `
                        <div class="action-item">
                            <span class="action-item-icon">${index + 1}️⃣</span>
                            <div class="action-item-text">
                                <strong>Tindakan ${index + 1}:</strong> ${escapeHtml(hint)}
                            </div>
                        </div>
                    `;
                });
                actionItemsList.innerHTML = actionHtml;
            } else {
                actionItemsList.innerHTML = `
                    <div class="action-item">
                        <span class="action-item-icon">📋</span>
                        <div class="action-item-text">
                            <strong>Periksa kembali:</strong> Baca ulang instruksi dan bandingkan hasil Anda dengan tab "Target" di preview
                        </div>
                    </div>
                `;
            }

            // Update modal icon based on score
            const modalIcon = modal.querySelector('.validation-modal-icon');
            if (validation.score >= 80) {
                modalIcon.textContent = '✅';
            } else if (validation.score >= 50) {
                modalIcon.textContent = '⚠️';
            } else {
                modalIcon.textContent = '❌';
            }

            // Show modal
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';

            // Also update old validation panel for compatibility
            const oldPanel = document.getElementById('validationResult');
            if (oldPanel) {
                const scoreEl = oldPanel.querySelector('.score-value');
                const messageEl = oldPanel.querySelector('.validation-message');
                const hintsEl = oldPanel.querySelector('.validation-hints');
                
                if (scoreEl) scoreEl.textContent = validation.score + '%';
                if (messageEl) messageEl.textContent = validation.message;
                if (hintsEl && validation.hints) {
                    let hintsHtml = '<ul>';
                    validation.hints.forEach(hint => {
                        hintsHtml += '<li>' + escapeHtml(hint) + '</li>';
                    });
                    hintsHtml += '</ul>';
                    hintsEl.innerHTML = hintsHtml;
                }
            }
        }

        // Make closeValidationModal globally accessible
        window.closeValidationModal = function() {
            console.log('closeValidationModal called');
            const modal = document.getElementById('validationModal');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.style.overflow = '';
                console.log('Modal closed');
            } else {
                console.log('Modal not found');
            }
        };

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('validationModal');
            if (modal && modal.classList.contains('show') && e.target === modal) {
                window.closeValidationModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('validationModal');
                if (modal && modal.classList.contains('show')) {
                    window.closeValidationModal();
                }
            }
        });

        // Use event delegation for close buttons (works even if modal is created dynamically)
        document.addEventListener('click', function(e) {
            // Close button in header (× button)
            if (e.target.classList.contains('validation-modal-close') || 
                e.target.closest('.validation-modal-close')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Close button clicked (header)');
                window.closeValidationModal();
                return false;
            }

            // Close button in footer (Tutup button)
            const secondaryBtn = e.target.closest('.modal-action-btn.secondary');
            if (secondaryBtn) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Close button clicked (footer)');
                window.closeValidationModal();
                return false;
            }
        }, true); // Use capture phase

        // Prevent modal from closing when clicking inside modal content
        document.addEventListener('click', function(e) {
            const modalContent = e.target.closest('.validation-modal');
            if (modalContent) {
                e.stopPropagation();
            }
        });

        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close validation panel
        function closeValidation() {
            document.getElementById('validationResult').style.display = 'none';
        }

        // Auto-save and preview with debounce
        let autoSaveTimeout = null;
        let autoPreviewTimeout = null;
        
        if (htmlEditor && cssEditor) {
            htmlEditor.on('change', function() {
                // Debounce auto-save
                if (autoSaveTimeout) clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(saveCode, 1000);
                
                // Debounce preview
                if (autoPreviewTimeout) clearTimeout(autoPreviewTimeout);
                autoPreviewTimeout = setTimeout(runPreview, 800);
            });

            cssEditor.on('change', function() {
                // Debounce auto-save
                if (autoSaveTimeout) clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(saveCode, 1000);
                
                // Debounce preview
                if (autoPreviewTimeout) clearTimeout(autoPreviewTimeout);
                autoPreviewTimeout = setTimeout(runPreview, 800);
            });
        }

        // Save code before form submit
        document.getElementById('lessonForm')?.addEventListener('submit', function() {
            saveCode();
        });

        // Initial preview
        setTimeout(runPreview, 500);
        
        // Expose functions to global scope for external access
        window.runPreview = runPreview;
        window.submitCode = submitCode;
        window.switchTab = switchTab;
        window.closeValidation = closeValidation;
        window.showValidationResult = showValidationResult;
        window.switchPreviewTab = switchPreviewTab;
        
        // Load expected solution into expected frame
        loadExpectedPreview();
        
        }); // End DOMContentLoaded
        
        // Switch between user preview and expected preview
        function switchPreviewTab(tab) {
            const userFrame = document.getElementById('previewFrame');
            const expectedFrame = document.getElementById('expectedFrame');
            const tabs = document.querySelectorAll('.preview-tab-btn');
            
            tabs.forEach(t => {
                t.classList.remove('active', 'expected-active');
            });
            
            if (tab === 'user') {
                userFrame.style.display = 'block';
                expectedFrame.style.display = 'none';
                tabs[0].classList.add('active');
            } else {
                userFrame.style.display = 'none';
                expectedFrame.style.display = 'block';
                tabs[1].classList.add('active', 'expected-active');
            }
        }
        
        // Load expected solution preview
        function loadExpectedPreview() {
            const expectedFrame = document.getElementById('expectedFrame');
            if (!expectedFrame) return;
            
            // Get solution code from realtime data or fallback
            const solutionHtml = window.lessonSolutionCode || <?php echo json_encode($solution_html ?: ''); ?>;
            const solutionCss = <?php echo json_encode($solution_css ?: ''); ?>;
            
            if (!solutionHtml && !solutionCss) {
                // No solution available
                const doc = expectedFrame.contentDocument || expectedFrame.contentWindow.document;
                doc.open();
                doc.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body {
                                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                height: 100vh;
                                margin: 0;
                                background: #f8fafc;
                                color: #64748b;
                                text-align: center;
                                padding: 2rem;
                            }
                            .message {
                                max-width: 300px;
                            }
                            .icon { font-size: 3rem; margin-bottom: 1rem; }
                            h3 { color: #334155; margin-bottom: 0.5rem; }
                            p { font-size: 0.9rem; line-height: 1.6; }
                        </style>
                    </head>
                    <body>
                        <div class="message">
                            <div class="icon">📝</div>
                            <h3>Ikuti Instruksi</h3>
                            <p>Baca instruksi di panel kiri dengan teliti, lalu tulis kode sesuai dengan petunjuk yang diberikan.</p>
                        </div>
                    </body>
                    </html>
                `);
                doc.close();
                return;
            }
            
            // Build expected preview
            let fullHtml = solutionHtml;
            if (solutionCss && fullHtml.includes('</head>')) {
                fullHtml = fullHtml.replace('</head>', '<style>' + solutionCss + '</style></head>');
            }
            
            const doc = expectedFrame.contentDocument || expectedFrame.contentWindow.document;
            doc.open();
            doc.write(fullHtml);
            doc.close();
        }
        <?php endif; ?>
    </script>

    <!-- Realtime Data Loader -->
    <script>
        // Realtime data management
        (function() {
            const courseId = <?php echo $course_id; ?>;
            const lessonId = <?php echo $lesson_id; ?>;
            let lessonData = null;
            let refreshInterval = null;
            const REFRESH_INTERVAL = 5000; // 5 seconds

            // Fetch lesson data from API
            async function fetchLessonData() {
                try {
                    const response = await fetch(`api/get-lesson-data.php?course_id=${courseId}&lesson_id=${lessonId}`);
                    const result = await response.json();
                    
                    if (result.success && result.data) {
                        lessonData = result.data;
                        updatePageData(lessonData);
                    } else {
                        console.error('Failed to fetch lesson data:', result.message);
                    }
                } catch (error) {
                    console.error('Error fetching lesson data:', error);
                }
            }

            // Update page with realtime data
            function updatePageData(data) {
                if (!data || !data.lesson) return;

                // Update page title
                document.title = `${data.lesson.judul_lesson} - <?php echo APP_NAME; ?>`;

                // Update lesson header section
                updateLessonHeader(data);

                // Update navigation
                updateNavigation(data.prev_lesson, data.next_lesson, courseId);

                // Update konten/materi if changed
                if (data.lesson.konten) {
                    updateKonten(data.lesson.konten);
                }

                // Update instructions if changed
                if (data.instructions && data.instructions.length > 0) {
                    updateInstructions(data.instructions, data.main_file);
                } else if (data.lesson.instruksi || data.lesson.konten) {
                    updateInstructionsFallback(data.lesson.instruksi || data.lesson.konten);
                }

                // Update progress
                if (data.progress) {
                    updateProgress(data.progress);
                }

                // Update quiz data if quiz type
                if (data.lesson_type === 'quiz' && data.lesson.konten) {
                    updateQuizData(data.lesson.konten, data.lesson.judul_lesson);
                }

                // Update editor starter code if available
                if (data.lesson.kode_contoh) {
                    updateStarterCode(data.lesson.kode_contoh);
                }

                // Update solution code
                if (data.lesson.kode_solusi) {
                    window.lessonSolutionCode = data.lesson.kode_solusi;
                }

                // Update XP reward
                if (data.lesson.xp_reward) {
                    window.lessonXpReward = data.lesson.xp_reward;
                }

                // Update global JavaScript variables
                updateGlobalVariables(data);

                // Dispatch custom event for other scripts
                window.dispatchEvent(new CustomEvent('lessonDataUpdated', { detail: data }));
            }

            // Update global JavaScript variables used throughout the page
            function updateGlobalVariables(data) {
                // Update course title
                if (data.course) {
                    window.courseTitle = data.course.judul_course || '';
                }

                // Update quiz data if quiz type
                if (data.lesson_type === 'quiz' && data.lesson.konten) {
                    try {
                        const quizData = JSON.parse(data.lesson.konten);
                        if (Array.isArray(quizData)) {
                            window.quizData = quizData;
                        }
                    } catch (e) {
                        console.error('Error parsing quiz data:', e);
                    }
                }

                // Update next lesson info
                window.hasNextLesson = data.next_lesson ? true : false;
                window.nextLessonUrl = data.next_lesson 
                    ? `lesson.php?course_id=${courseId}&lesson_id=${data.next_lesson.id}` 
                    : '';
                window.courseUrl = `course.php?id=${courseId}`;

                // Update solution code if available
                if (data.lesson.kode_solusi) {
                    window.lessonSolutionCode = data.lesson.kode_solusi;
                }
            }

            // Update lesson header (title, logo, subtitle)
            function updateLessonHeader(data) {
                // Update lesson title
                const lessonTitle = document.querySelector('.lesson-header-content h1, .lesson-title-section h1');
                if (lessonTitle && data.lesson) {
                    lessonTitle.textContent = data.lesson.judul_lesson;
                }

                // Update lesson subtitle
                const lessonSubtitle = document.querySelector('.lesson-header-content p, .lesson-title-section p');
                if (lessonSubtitle && data.course && data.lesson) {
                    lessonSubtitle.textContent = `${data.course.judul_course} • Lesson ${data.lesson.urutan}`;
                }

                // Update language logo if needed
                if (data.course && typeof getLanguageIcon === 'function') {
                    const logo = getLanguageIcon(data.course.judul_course);
                    const logoImg = document.querySelector('.lesson-language-logo');
                    if (logoImg && logo) {
                        logoImg.src = logo;
                        logoImg.style.display = '';
                    } else if (logoImg && !logo) {
                        logoImg.style.display = 'none';
                    }
                }
            }

            // Update konten/materi
            function updateKonten(konten) {
                const kontenContainer = document.getElementById('konten-markdown');
                if (!kontenContainer) return;

                let cleanKonten = konten;
                cleanKonten = cleanKonten.replace(/\?{2,}/g, '');
                cleanKonten = cleanKonten.replace(/^\s*\?*\s*$/gm, '');
                cleanKonten = cleanKonten.replace(/\n{3,}/g, '\n\n');
                
                if (typeof marked !== 'undefined') {
                    marked.setOptions({ breaks: true, gfm: true });
                    kontenContainer.innerHTML = marked.parse(cleanKonten);
                } else {
                    kontenContainer.innerHTML = cleanKonten.replace(/\n/g, '<br>');
                }
            }

            // Update instructions fallback (when no structured instructions)
            function updateInstructionsFallback(content) {
                const instructionContainer = document.getElementById('instruction-markdown');
                if (!instructionContainer) return;

                let cleanContent = content;
                cleanContent = cleanContent.replace(/^#+\s*[^\n]+\n*/m, '');
                cleanContent = cleanContent.replace(/\?{2,}/g, '');
                cleanContent = cleanContent.replace(/^\s*\?*\s*$/gm, '');
                cleanContent = cleanContent.replace(/\n{3,}/g, '\n\n');

                if (typeof marked !== 'undefined') {
                    instructionContainer.innerHTML = '<div class="instruction-number">📝</div>' + marked.parse(cleanContent);
                } else {
                    instructionContainer.innerHTML = '<div class="instruction-number">📝</div><p>' + cleanContent.replace(/\n/g, '<br>') + '</p>';
                }
            }

            // Update quiz data
            function updateQuizData(konten, lessonTitle) {
                try {
                    const quizData = JSON.parse(konten);
                    if (!Array.isArray(quizData)) return;

                    // Update quiz title
                    const quizTitle = document.querySelector('.quiz-container h2');
                    if (quizTitle) {
                        quizTitle.textContent = `Kuis: ${lessonTitle}`;
                    }

                    // Update quiz form if exists
                    const quizForm = document.getElementById('quizForm');
                    if (quizForm && window.quizData) {
                        window.quizData = quizData;
                        // Rebuild quiz form
                        rebuildQuizForm(quizData);
                    }
                } catch (e) {
                    console.error('Error parsing quiz data:', e);
                }
            }

            // Rebuild quiz form
            function rebuildQuizForm(quizData) {
                const quizForm = document.getElementById('quizForm');
                if (!quizForm) return;

                let html = '';
                quizData.forEach((q, index) => {
                    html += `
                        <div class="quiz-question" style="margin-bottom: 2rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: 0.5rem;">
                            <h3 style="margin-bottom: 1rem; color: var(--text-primary);">${index + 1}. ${escapeHtml(q.question || '')}</h3>
                            <div class="quiz-options" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    `;
                    (q.options || []).forEach((opt, optIndex) => {
                        html += `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: var(--bg-card); border-radius: 0.375rem; cursor: pointer; transition: all 0.2s;">
                                <input type="radio" name="q${index}" value="${optIndex}" required>
                                <span>${escapeHtml(opt)}</span>
                            </label>
                        `;
                    });
                    html += `</div></div>`;
                });

                const submitBtn = quizForm.querySelector('button[type="submit"]');
                const oldHTML = submitBtn ? submitBtn.outerHTML : '';
                quizForm.innerHTML = html + oldHTML;
            }

            // Update starter code in editor
            function updateStarterCode(code) {
                // Only update if editor is empty or hasn't been modified
                const htmlEditor = document.getElementById('htmlEditor');
                if (htmlEditor && (!htmlEditor.value || htmlEditor.value.trim() === '')) {
                    htmlEditor.value = code;
                    // Trigger CodeMirror update if exists
                    if (htmlEditor.codeMirror) {
                        htmlEditor.codeMirror.setValue(code);
                    }
                }
            }

            // Update instructions section
            function updateInstructions(instructions, mainFile) {
                const instructionSection = document.querySelector('.instruction-section');
                if (!instructionSection) return;

                // Rebuild instructions
                let html = `
                    <h3 class="instruction-section-title">✅ Tugas Praktik</h3>
                    
                    <div class="how-to-complete-section">
                        <h4 class="how-to-title">📋 Cara Menyelesaikan Lesson Ini (Step-by-Step):</h4>
                        <div class="visual-steps">
                            <div class="visual-step">
                                <div class="visual-step-number">1</div>
                                <div class="visual-step-content">
                                    <strong>📖 Baca Instruksi</strong>
                                    <p>Pelajari setiap langkah di bawah ini dengan teliti. Setiap langkah menjelaskan apa yang harus Anda lakukan.</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="visual-step-number">2</div>
                                <div class="visual-step-content">
                                    <strong>📋 Salin Kode</strong>
                                    <p>Klik tombol <strong>"Copy"</strong> pada setiap blok kode yang disediakan. Kode akan tersalin ke clipboard Anda.</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="visual-step-number">3</div>
                                <div class="visual-step-content">
                                    <strong>📝 Tempel di Editor</strong>
                                    <p>Paste kode (Ctrl+V atau Cmd+V) ke editor di tengah. Editor adalah kotak besar di panel tengah.</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="visual-step-number">4</div>
                                <div class="visual-step-content">
                                    <strong>✏️ Modifikasi Kode</strong>
                                    <p>Sesuaikan kode sesuai instruksi. Misalnya: ubah teks, tambah elemen, atau ubah warna.</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="visual-step-number">5</div>
                                <div class="visual-step-content">
                                    <strong>▶️ Test dengan Run</strong>
                                    <p>Klik tombol <strong>"Run"</strong> (biru) untuk melihat hasil di preview. Bandingkan dengan tab <strong>"Target"</strong>.</p>
                                </div>
                            </div>
                            <div class="visual-step">
                                <div class="visual-step-number">6</div>
                                <div class="visual-step-content">
                                    <strong>✅ Kirim Jawaban</strong>
                                    <p>Jika hasil sudah sesuai dengan Target, klik tombol <strong>"Kirim"</strong> (ungu) untuk validasi.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="instruction-intro-enhanced">
                        <div class="intro-icon">🎯</div>
                        <div class="intro-content">
                            <strong>Panduan Penting:</strong> Ikuti langkah-langkah di bawah ini <strong>secara berurutan</strong>. Setiap langkah memiliki kode yang bisa dicopy. Setelah menyalin, modifikasi sesuai instruksi di langkah tersebut.
                        </div>
                    </div>
                `;

                instructions.forEach((step, index) => {
                    let cleanText = step.text || '';
                    cleanText = cleanText.replace(/\?{2,}/g, '');
                    cleanText = cleanText.replace(/^\s*\?*\s*$/gm, '');
                    cleanText = cleanText.replace(/\n{3,}/g, '\n\n');
                    cleanText = cleanText.trim();

                    // Process markdown if available
                    let textHtml = '';
                    if (cleanText) {
                        if (typeof marked !== 'undefined') {
                            textHtml = marked.parse(cleanText);
                        } else {
                            textHtml = escapeHtml(cleanText).replace(/\n/g, '<br>');
                        }
                    }

                    const codeHtml = step.code ? escapeHtml(step.code).replace(/'/g, "\\'").replace(/\n/g, '\\n').replace(/\r/g, '') : '';
                    const fileDisplay = step.file || mainFile || 'main.html';

                    html += `
                        <div class="instruction-box">
                            <div class="instruction-number">${index + 1}</div>
                            ${step.title ? `<h4 class="instruction-step-title">${escapeHtml(step.title)}</h4>` : `<h4 class="instruction-step-title">Langkah ${index + 1}</h4>`}
                            ${textHtml ? `
                                <div class="instruction-text">
                                    <div class="instruction-action">
                                        <span class="action-icon">📝</span>
                                        <span class="action-text"><strong>Instruksi Langkah ${index + 1}:</strong></span>
                                    </div>
                                    <div class="instruction-content-text">${formatInstructionText(cleanText)}</div>
                                </div>
                            ` : ''}
                            ${step.code ? `
                                <div class="code-snippet">
                                    <div class="code-header">
                                        <span class="code-label">💻 Kode untuk Disalin</span>
                                        <button class="copy-btn" onclick="copyToClipboard('${codeHtml}', this)">Copy</button>
                                    </div>
                                    <code>${escapeHtml(step.code)}</code>
                                </div>
                                <div class="code-instruction">
                                    <span class="code-instruction-icon">💡</span>
                                    <span>Salin kode di atas, lalu tempel di editor dan modifikasi sesuai instruksi</span>
                                </div>
                            ` : ''}
                            ${step.file ? `
                                <div class="file-info">
                                    <span class="file-label">
                                        <strong>File yang digunakan:</strong> ${escapeHtml(fileDisplay)}
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });

                html += `
                    <div class="completion-guide">
                        <h4 class="guide-title">🎯 Cara Menyelesaikan:</h4>
                        <div class="guide-steps">
                            <div class="guide-step">
                                <div class="guide-step-number">1</div>
                                <div class="guide-step-content">
                                    <strong>Klik "Run"</strong> untuk melihat hasil kode Anda
                                </div>
                            </div>
                            <div class="guide-step">
                                <div class="guide-step-number">2</div>
                                <div class="guide-step-content">
                                    <strong>Periksa Preview</strong> - Bandingkan dengan "Target" di tab preview
                                </div>
                            </div>
                            <div class="guide-step">
                                <div class="guide-step-number">3</div>
                                <div class="guide-step-content">
                                    <strong>Klik "Kirim"</strong> jika hasil sudah sesuai dengan target
                                </div>
                            </div>
                            <div class="guide-step">
                                <div class="guide-step-number">4</div>
                                <div class="guide-step-content">
                                    <strong>Selesai!</strong> - Anda akan mendapat XP dan bisa lanjut ke lesson berikutnya
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="instruction-tip">
                        <strong>💡 Tips Penting:</strong>
                        <ul class="tip-list">
                            <li>Pastikan kode yang Anda tulis sesuai dengan instruksi di setiap langkah</li>
                            <li>Gunakan tombol "Run" untuk mengecek hasil sebelum mengirim</li>
                            <li>Jika ada error, periksa kembali kode Anda atau lihat "Target" untuk referensi</li>
                            <li>Kode akan otomatis tersimpan saat Anda mengetik</li>
                        </ul>
                    </div>
                `;

                instructionSection.innerHTML = html;
            }

            // Update progress
            function updateProgress(progress) {
                // Update progress indicators if they exist
                const progressElements = document.querySelectorAll('[data-progress-status]');
                progressElements.forEach(el => {
                    if (el.dataset.progressStatus !== progress.status) {
                        el.dataset.progressStatus = progress.status;
                        el.classList.toggle('completed', progress.status === 'completed');
                    }
                });

                // Update status input
                const statusInput = document.getElementById('statusInput');
                if (statusInput && statusInput.value !== progress.status) {
                    statusInput.value = progress.status || 'in_progress';
                }

                // Update user code if available and editor is empty
                if (progress.kode_user && progress.kode_user.trim() !== '') {
                    const htmlEditor = document.getElementById('htmlEditor');
                    if (htmlEditor && (!htmlEditor.value || htmlEditor.value.trim() === '')) {
                        htmlEditor.value = progress.kode_user;
                        if (htmlEditor.codeMirror) {
                            htmlEditor.codeMirror.setValue(progress.kode_user);
                        }
                    }
                }
            }

            // Update navigation
            function updateNavigation(prevLesson, nextLesson, courseId) {
                // Update prev button
                const prevBtn = document.querySelector('.nav-btn.prev, .lesson-navigation .nav-btn:first-child');
                const prevLink = prevBtn?.closest('a') || prevBtn;
                
                if (prevLink && prevLesson) {
                    if (prevLink.tagName === 'A') {
                        prevLink.href = `lesson.php?course_id=${courseId}&lesson_id=${prevLesson.id}`;
                        prevLink.style.display = '';
                    } else {
                        // Convert button to link
                        const newLink = document.createElement('a');
                        newLink.href = `lesson.php?course_id=${courseId}&lesson_id=${prevLesson.id}`;
                        newLink.className = prevLink.className;
                        newLink.textContent = '← Sebelumnya';
                        prevLink.parentNode.replaceChild(newLink, prevLink);
                    }
                } else if (prevLink && !prevLesson) {
                    if (prevLink.tagName === 'BUTTON') {
                        prevLink.disabled = true;
                    } else {
                        prevLink.style.display = 'none';
                    }
                }

                // Update next button
                const nextBtn = document.querySelector('.nav-btn.next, .lesson-navigation .nav-btn:last-child');
                const nextLink = nextBtn?.closest('a') || nextBtn;
                
                if (nextLink && nextLesson) {
                    if (nextLink.tagName === 'A') {
                        nextLink.href = `lesson.php?course_id=${courseId}&lesson_id=${nextLesson.id}`;
                        nextLink.style.display = '';
                    } else {
                        // Convert button to link
                        const newLink = document.createElement('a');
                        newLink.href = `lesson.php?course_id=${courseId}&lesson_id=${nextLesson.id}`;
                        newLink.className = nextLink.className;
                        newLink.textContent = 'Selanjutnya →';
                        nextLink.parentNode.replaceChild(newLink, nextLink);
                    }
                } else if (nextLink && !nextLesson) {
                    if (nextLink.tagName === 'BUTTON') {
                        nextLink.disabled = true;
                    } else {
                        nextLink.style.display = 'none';
                    }
                }
            }

            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Format instruction text with better structure
            function formatInstructionText(text) {
                if (!text) return '';
                
                const lines = text.split('\n');
                let html = '';
                
                lines.forEach(line => {
                    line = line.trim();
                    if (!line) {
                        html += '<br>';
                    } else if (/^[-*•]\s*(.+)$/.test(line)) {
                        const match = line.match(/^[-*•]\s*(.+)$/);
                        html += `<div class="instruction-bullet">• ${escapeHtml(match[1])}</div>`;
                    } else if (/^\d+[\.\)]\s*(.+)$/.test(line)) {
                        html += `<div class="instruction-bullet">${escapeHtml(line)}</div>`;
                    } else {
                        html += `<p>${escapeHtml(line)}</p>`;
                    }
                });
                
                return html;
            }

            // Start realtime updates
            function startRealtimeUpdates() {
                // Initial fetch
                fetchLessonData();

                // Set up interval for auto-refresh
                refreshInterval = setInterval(fetchLessonData, REFRESH_INTERVAL);
            }

            // Stop realtime updates
            function stopRealtimeUpdates() {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                }
            }

            // Start when page is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startRealtimeUpdates);
            } else {
                startRealtimeUpdates();
            }

            // Stop when page is hidden (save resources)
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    stopRealtimeUpdates();
                } else {
                    startRealtimeUpdates();
                }
            });

            // Expose to global scope
            window.lessonRealtime = {
                fetch: fetchLessonData,
                start: startRealtimeUpdates,
                stop: stopRealtimeUpdates,
                getData: () => lessonData
            };
        })();
    </script>
</body>
</html>
