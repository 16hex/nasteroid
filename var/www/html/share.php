<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$message = '';
$shares = getUserShares();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_share'])) {
        $filePath = $_POST['file_path'];
        $expiresIn = $_POST['expires_in'] ?? 24;
        
        if (!isValidPath($filePath)) {
            $message = 'Geçersiz dosya yolu.';
        } else {
            $shareCode = createShare($filePath, $expiresIn);
            if ($shareCode) {
                $shareUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/shared.php?code=' . $shareCode;
                $message = 'Paylaşım bağlantısı oluşturuldu: ' . $shareUrl;
            } else {
                $message = 'Paylaşım oluşturulurken bir hata oluştu.';
            }
        }
    } elseif (isset($_POST['delete_share'])) {
        $shareCode = $_POST['share_code'];
        if (deleteShare($shareCode)) {
            $message = 'Paylaşım silindi.';
        } else {
            $message = 'Paylaşım silinirken bir hata oluştu.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paylaşımlarım - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Paylaşımlarım</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
                <div class="user-nav">
                    <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a href="logout.php" class="logout">Çıkış Yap</a>
                </div>
            </nav>
        </header>

        <main>
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="shares-list">
                <h2>Aktif Paylaşımlar</h2>
                <?php if (empty($shares)): ?>
                    <p>Henüz paylaşım yapılmamış.</p>
                <?php else: ?>
                    <?php foreach ($shares as $share): ?>
                        <div class="share-item">
                            <div class="share-info">
                                <p class="share-path"><?php echo htmlspecialchars($share['file_path']); ?></p>
                                <p class="share-expires">Son kullanma: <?php echo htmlspecialchars($share['expires_at']); ?></p>
                                <p class="share-url">
                                    Paylaşım linki: 
                                    <a href="shared.php?code=<?php echo htmlspecialchars($share['share_code']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/shared.php?code=<?php echo htmlspecialchars($share['share_code']); ?>
                                    </a>
                                </p>
                            </div>
                            <form method="post" class="delete-share-form">
                                <input type="hidden" name="share_code" value="<?php echo htmlspecialchars($share['share_code']); ?>">
                                <button type="submit" name="delete_share" class="delete-btn">Paylaşımı Sil</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 