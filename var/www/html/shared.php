<?php
require_once 'includes/functions.php';
session_start();

$code = $_GET['code'] ?? '';
$error = '';
$share = null;

if ($code) {
    $share = getShareInfo($code);
    if (!$share) {
        $error = 'Geçersiz veya süresi dolmuş paylaşım bağlantısı.';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paylaşılan Dosya - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Paylaşılan Dosya</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
            </nav>
        </header>

        <main>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($share): ?>
                <div class="shared-file">
                    <h2>Paylaşılan Öğe</h2>
                    <div class="file-info">
                        <p class="file-path">Dosya: <?php echo htmlspecialchars($share['file_path']); ?></p>
                        <p class="share-expires">Son kullanma: <?php echo htmlspecialchars($share['expires_at']); ?></p>
                        <a href="download.php?code=<?php echo urlencode($code); ?>" class="download-btn">İndir</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 