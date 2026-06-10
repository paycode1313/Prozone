<?php
/**
 * SEO Meta Tags Helper
 * Include this file and call seo_meta() function with appropriate parameters
 * Usage: echo seo_meta($title, $description, $keywords, $image);
 */

function seo_meta($title = '', $description = '', $keywords = '', $image = '') {
    $defaults = [
        'title' => 'Prozone - Platform Belajar Coding Interaktif',
        'description' => 'Belajar coding dengan cara yang menyenangkan! Platform pembelajaran coding interaktif dengan gamifikasi, XP, achievements, dan sertifikat untuk developer masa depan Indonesia.',
        'keywords' => 'belajar coding, programming, web development, html, css, javascript, php, python, kursus online, tutorial coding, indonesia',
        'image' => 'assets/img/Prozone Purple.png',
        'url' => '',
        'type' => 'website',
        'locale' => 'id_ID',
        'site_name' => 'Prozone'
    ];

    // Override defaults with provided parameters
    $meta = [
        'title' => !empty($title) ? $title : $defaults['title'],
        'description' => !empty($description) ? $description : $defaults['description'],
        'keywords' => !empty($keywords) ? $keywords : $defaults['keywords'],
        'image' => !empty($image) ? $image : $defaults['image'],
        'url' => $defaults['url'],
        'type' => $defaults['type'],
        'locale' => $defaults['locale'],
        'site_name' => $defaults['site_name']
    ];
    
    // Build current URL if not provided
    if (empty($meta['url'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $meta['url'] = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    // Build absolute image URL
    if (!empty($meta['image']) && strpos($meta['image'], 'http') !== 0) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $meta['image'] = rtrim($base_url, '/') . '/' . ltrim($meta['image'], '/');
    }

    $output = '';
    
    // Basic Meta Tags
    $output .= '<meta name="description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $output .= '    <meta name="keywords" content="' . htmlspecialchars($meta['keywords']) . '">' . "\n";
    $output .= '    <meta name="author" content="Prozone Team">' . "\n";
    $output .= '    <meta name="robots" content="index, follow">' . "\n";
    
    // Open Graph Meta Tags
    $output .= '    <meta property="og:type" content="' . htmlspecialchars($meta['type']) . '">' . "\n";
    $output .= '    <meta property="og:title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
    $output .= '    <meta property="og:description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $output .= '    <meta property="og:image" content="' . htmlspecialchars($meta['image']) . '">' . "\n";
    $output .= '    <meta property="og:url" content="' . htmlspecialchars($meta['url']) . '">' . "\n";
    $output .= '    <meta property="og:site_name" content="' . htmlspecialchars($meta['site_name']) . '">' . "\n";
    $output .= '    <meta property="og:locale" content="' . htmlspecialchars($meta['locale']) . '">' . "\n";
    
    // Twitter Card Meta Tags
    $output .= '    <meta name="twitter:card" content="summary_large_image">' . "\n";
    $output .= '    <meta name="twitter:title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
    $output .= '    <meta name="twitter:description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $output .= '    <meta name="twitter:image" content="' . htmlspecialchars($meta['image']) . '">' . "\n";
    
    // Additional SEO Tags
    $output .= '    <link rel="canonical" href="' . htmlspecialchars($meta['url']) . '">' . "\n";
    
    echo $output;
}

/**
 * Generate JSON-LD structured data for courses
 */
function seo_course_schema($course) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => $course['judul_course'] ?? 'Course',
        'description' => $course['deskripsi'] ?? '',
        'provider' => [
            '@type' => 'Organization',
            'name' => 'Prozone',
            'sameAs' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
        ]
    ];

    if (!empty($course['level'])) {
        $schema['educationalLevel'] = ucfirst($course['level']);
    }

    if (!empty($course['durasi_jam'])) {
        $schema['timeRequired'] = 'PT' . $course['durasi_jam'] . 'H';
    }

    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate JSON-LD structured data for organization
 */
function seo_organization_schema() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'];
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOrganization',
        'name' => 'Prozone',
        'description' => 'Platform pembelajaran coding interaktif dengan gamifikasi untuk developer masa depan Indonesia.',
        'url' => $base_url,
        'logo' => $base_url . '/assets/img/Prozone Purple.png',
        'sameAs' => []
    ];

    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}
?>
