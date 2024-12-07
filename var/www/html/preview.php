<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$file = $_GET['file'] ?? '';
$error = '';
$mime_type = '';
$content = '';

if (!empty($file) && isValidPath($file)) {
    $file_path = '/storage/' . trim($file, '/');
    if (file_exists($file_path) && is_file($file_path)) {
        $mime_type = mime_content_type($file_path);
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Önizlenebilir dosya türleri
        $previewable_types = [
            'text/plain', 'text/html', 'text/css', 'text/javascript',
            'application/json', 'application/xml',
            'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml',
            'video/mp4', 'video/webm', 'audio/mpeg', 'audio/wav'
        ];
        
        if (in_array($mime_type, $previewable_types)) {
            if (strpos($mime_type, 'text/') === 0 || 
                in_array($extension, ['json', 'xml', 'md', 'csv'])) {
                $content = htmlspecialchars(file_get_contents($file_path));
            }
        } else {
            $error = 'Bu dosya türü önizlenemez.';
        }
    } else {
        $error = 'Dosya bulunamadı.';
    }
} else {
    $error = 'Geçersiz dosya yolu.';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dosya Önizleme - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Dosya Önizleme</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
                <div class="user-nav">
                    <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a href="logout.php" class="logout">Çıkış Yap</a>
                </div>
            </nav>
        </header>

        <main>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <div class="preview-container">
                    <?php if (strpos($mime_type, 'image/') === 0): ?>
                        <img src="raw.php?file=<?php echo urlencode($file); ?>" alt="Resim önizleme">
                    <?php elseif (strpos($mime_type, 'video/') === 0): ?>
                        <video controls>
                            <source src="raw.php?file=<?php echo urlencode($file); ?>" type="<?php echo $mime_type; ?>">
                            Tarayıcınız video oynatmayı desteklemiyor.
                        </video>
                    <?php elseif (strpos($mime_type, 'audio/') === 0): ?>
                        <audio controls>
                            <source src="raw.php?file=<?php echo urlencode($file); ?>" type="<?php echo $mime_type; ?>">
                            Tarayıcınız ses oynatmayı desteklemiyor.
                        </audio>
                    <?php elseif ($content): ?>
                        <pre class="content-preview"><?php echo $content; ?></pre>
                    <?php endif; ?>
                </div>
                <div class="preview-actions">
                    <a href="download.php?file=<?php echo urlencode($file); ?>" class="download-btn">İndir</a>
                    <a href="index.php" class="back-btn">Geri Dön</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 