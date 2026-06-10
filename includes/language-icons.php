<?php
/**
 * Language Icons Helper
 * Menyediakan icon/logo untuk berbagai bahasa pemrograman
 */

// CDN Base URL untuk Devicon
define('DEVICON_CDN', 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons');

/**
 * Mendapatkan URL icon berdasarkan judul course atau nama bahasa
 * @param string $title Judul course atau nama bahasa
 * @return string|null URL icon atau null jika tidak ditemukan
 */
function getLanguageIcon($title) {
    $title = strtolower($title);
    
    // C++ harus dicek sebelum C
    if (strpos($title, 'c++') !== false || strpos($title, 'cpp') !== false) {
        return DEVICON_CDN . '/cplusplus/cplusplus-original.svg';
    }
    
    // C language (bukan CSS, bukan C++, bukan C#)
    if (preg_match('/\bc\b/', $title) && strpos($title, 'css') === false && strpos($title, 'c#') === false) {
        return DEVICON_CDN . '/c/c-original.svg';
    }
    
    // HTML
    if (strpos($title, 'html') !== false) {
        return DEVICON_CDN . '/html5/html5-original.svg';
    }
    
    // CSS
    if (strpos($title, 'css') !== false) {
        return DEVICON_CDN . '/css3/css3-original.svg';
    }
    
    // Python
    if (strpos($title, 'python') !== false || strpos($title, 'py') !== false) {
        return DEVICON_CDN . '/python/python-original.svg';
    }
    
    // PHP
    if (strpos($title, 'php') !== false) {
        return DEVICON_CDN . '/php/php-original.svg';
    }
    
    // JavaScript (harus sebelum Java)
    if (strpos($title, 'javascript') !== false || preg_match('/\bjs\b/', $title)) {
        return DEVICON_CDN . '/javascript/javascript-original.svg';
    }
    
    // TypeScript
    if (strpos($title, 'typescript') !== false || preg_match('/\bts\b/', $title)) {
        return DEVICON_CDN . '/typescript/typescript-original.svg';
    }
    
    // Java (bukan JavaScript)
    if (strpos($title, 'java') !== false && strpos($title, 'script') === false) {
        return DEVICON_CDN . '/java/java-original.svg';
    }
    
    // C#
    if (strpos($title, 'c#') !== false || strpos($title, 'csharp') !== false) {
        return DEVICON_CDN . '/csharp/csharp-original.svg';
    }
    
    // Ruby
    if (strpos($title, 'ruby') !== false) {
        return DEVICON_CDN . '/ruby/ruby-original.svg';
    }
    
    // Go
    if (strpos($title, 'golang') !== false || preg_match('/\bgo\b/', $title)) {
        return DEVICON_CDN . '/go/go-original.svg';
    }
    
    // Rust
    if (strpos($title, 'rust') !== false) {
        return DEVICON_CDN . '/rust/rust-original.svg';
    }
    
    // Swift
    if (strpos($title, 'swift') !== false) {
        return DEVICON_CDN . '/swift/swift-original.svg';
    }
    
    // Kotlin
    if (strpos($title, 'kotlin') !== false) {
        return DEVICON_CDN . '/kotlin/kotlin-original.svg';
    }
    
    // React
    if (strpos($title, 'react') !== false) {
        return DEVICON_CDN . '/react/react-original.svg';
    }
    
    // Vue
    if (strpos($title, 'vue') !== false) {
        return DEVICON_CDN . '/vuejs/vuejs-original.svg';
    }
    
    // Angular
    if (strpos($title, 'angular') !== false) {
        return DEVICON_CDN . '/angularjs/angularjs-original.svg';
    }
    
    // Node.js
    if (strpos($title, 'node') !== false) {
        return DEVICON_CDN . '/nodejs/nodejs-original.svg';
    }
    
    // Laravel
    if (strpos($title, 'laravel') !== false) {
        return DEVICON_CDN . '/laravel/laravel-original.svg';
    }
    
    // Django
    if (strpos($title, 'django') !== false) {
        return DEVICON_CDN . '/django/django-plain.svg';
    }
    
    // MySQL / Database
    if (strpos($title, 'mysql') !== false || strpos($title, 'database') !== false || strpos($title, 'sql') !== false) {
        return DEVICON_CDN . '/mysql/mysql-original.svg';
    }
    
    // MongoDB
    if (strpos($title, 'mongo') !== false) {
        return DEVICON_CDN . '/mongodb/mongodb-original.svg';
    }
    
    // Git
    if (strpos($title, 'git') !== false) {
        return DEVICON_CDN . '/git/git-original.svg';
    }
    
    // Docker
    if (strpos($title, 'docker') !== false) {
        return DEVICON_CDN . '/docker/docker-original.svg';
    }
    
    // Linux
    if (strpos($title, 'linux') !== false) {
        return DEVICON_CDN . '/linux/linux-original.svg';
    }
    
    return null;
}

/**
 * Mendapatkan warna berdasarkan bahasa
 * @param string $title Judul course atau nama bahasa
 * @return string Warna hex
 */
function getLanguageColor($title) {
    $title = strtolower($title);
    
    if (strpos($title, 'c++') !== false || strpos($title, 'cpp') !== false) return '#00599C';
    if (strpos($title, 'html') !== false) return '#E34F26';
    if (strpos($title, 'css') !== false) return '#1572B6';
    if (strpos($title, 'python') !== false) return '#3776AB';
    if (strpos($title, 'php') !== false) return '#777BB4';
    if (strpos($title, 'javascript') !== false || preg_match('/\bjs\b/', $title)) return '#F7DF1E';
    if (strpos($title, 'java') !== false && strpos($title, 'script') === false) return '#007396';
    if (strpos($title, 'c#') !== false) return '#239120';
    if (strpos($title, 'ruby') !== false) return '#CC342D';
    if (strpos($title, 'go') !== false) return '#00ADD8';
    if (strpos($title, 'rust') !== false) return '#000000';
    if (strpos($title, 'swift') !== false) return '#FA7343';
    if (strpos($title, 'kotlin') !== false) return '#7F52FF';
    if (strpos($title, 'react') !== false) return '#61DAFB';
    if (strpos($title, 'vue') !== false) return '#4FC08D';
    if (strpos($title, 'node') !== false) return '#339933';
    if (strpos($title, 'laravel') !== false) return '#FF2D20';
    if (strpos($title, 'mysql') !== false || strpos($title, 'database') !== false) return '#4479A1';
    
    return '#8b5cf6'; // Default purple
}

/**
 * Render icon HTML
 * @param string $title Judul course atau nama bahasa
 * @param int $size Ukuran icon dalam pixel
 * @param string $class CSS class tambahan
 * @return string HTML icon
 */
function renderLanguageIcon($title, $size = 32, $class = '') {
    $iconUrl = getLanguageIcon($title);
    
    if ($iconUrl) {
        return '<img src="' . htmlspecialchars($iconUrl) . '" alt="' . htmlspecialchars($title) . '" 
                style="width: ' . $size . 'px; height: ' . $size . 'px; object-fit: contain;" 
                class="language-icon ' . htmlspecialchars($class) . '">';
    }
    
    // Fallback ke icon code
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="language-icon ' . htmlspecialchars($class) . '">
        <polyline points="16 18 22 12 16 6"></polyline>
        <polyline points="8 6 2 12 8 18"></polyline>
    </svg>';
}
?>
