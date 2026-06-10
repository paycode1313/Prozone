<?php
/**
 * ============================================
 * PROZONE DESIGN SYSTEM HEAD
 * File: includes/head.php
 * Deskripsi: Partial untuk head section yang konsisten.
 * Memuat design system + tema + helper JS.
 *
 * Pemakaian (di <head> halaman):
 *   <?php require_once 'config/config.php'; ?>
 *   <?php $page_title = 'Judul Halaman'; require_once 'includes/head.php'; ?>
 *
 * Variabel opsional:
 *   $page_title       - string, title halaman (default: APP_NAME)
 *   $page_css         - array|false, file CSS tambahan khusus halaman (mis: ['pages/dashboard.css'])
 *   $page_description - string, meta description
 *   $hide_theme_toggle - bool, sembunyikan theme toggle UI (default false)
 *   $body_class       - string tambahan untuk body class
 * ============================================
 */

$page_title       = $page_title ?? APP_NAME;
$page_css         = $page_css ?? [];
$page_description = $page_description ?? APP_DESCRIPTION;
$hide_theme_toggle = $hide_theme_toggle ?? false;
$body_class       = $body_class ?? '';

// Hitung body class (theme + tambahan)
$theme_class = getThemeClass();
$full_body_class = trim($theme_class . ' ' . $body_class);
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
<meta name="theme-color" content="#4F46E5">

<title><?php echo htmlspecialchars($page_title); ?> | <?php echo APP_NAME; ?></title>

<!-- Favicon -->
<?php include __DIR__ . '/favicon.php'; ?>

<!-- Fonts: Inter + JetBrains Mono -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<!-- === Design System (urutan penting) === -->
<!-- 1. Design tokens (warna, spacing, typography) -->
<link rel="stylesheet" href="assets/css/tokens.css">

<!-- 2. Themes (light default, dark override) -->
<link rel="stylesheet" href="assets/css/themes/light.css">
<link rel="stylesheet" href="assets/css/themes/dark.css">

<!-- 3. Base reset & utilities -->
<link rel="stylesheet" href="assets/css/base.css">

<!-- 4. Animation library -->
<link rel="stylesheet" href="assets/css/animations.css">

<!-- 5. Halaman-spesifik CSS -->
<?php if (!empty($page_css) && is_array($page_css)): ?>
  <?php foreach ($page_css as $css): ?>
    <link rel="stylesheet" href="assets/css/<?php echo htmlspecialchars($css); ?>">
  <?php endforeach; ?>
<?php endif; ?>

<!-- === Inline FOUC prevention (Flash of Unstyled Content) === -->
<!-- Set tema class di <html> SEBELUM CSS dimuat, agar tidak ada flash saat reload -->
<script>
  (function() {
    try {
      var stored = localStorage.getItem('prozone-theme');
      var theme = stored;
      if (!theme) {
        theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
          ? 'dark' : 'light';
      }
      document.documentElement.setAttribute('data-theme', theme);
      if (theme === 'dark') {
        document.documentElement.classList.add('dark-mode');
      } else {
        document.documentElement.classList.add('light-mode');
      }
    } catch(e) {}
  })();
</script>

<!-- === Login flag untuk theme-toggle.js === -->
<script>
  window.PROZONE_USER_LOGGED_IN = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
  window.APP_NAME = <?php echo json_encode(APP_NAME); ?>;
  window.BASE_URL = <?php echo json_encode(BASE_URL); ?>;
</script>

<!-- === Theme toggle script === -->
<script src="assets/js/theme-toggle.js" defer></script>
