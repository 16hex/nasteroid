<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$message = '';
$backups = getBackups();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_backup'])) {
        $sourcePath = $_POST['source_path'];
        
        if (!isValidPath($sourcePath)) {
            $message = 'Geçersiz dosya yolu.';
        } else {
            if (createBackup($sourcePath)) {
                $message = 'Yedekleme başarıyla oluşturuldu.';
                $backups = getBackups(); // Listeyi güncelle
            } else {
                $message = 'Yedekleme oluşturulurken bir hata oluştu.';
            }
        }
    } elseif (isset($_POST['delete_backup'])) {
        $backupId = $_POST['backup_id'];
        if (deleteBackup($backupId)) {
            $message = 'Yedek silindi.';
            $backups = getBackups(); // Listeyi güncelle
        } else {
            $message = 'Yedek silinirken bir hata oluştu.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yedeklemeler - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Yedeklemeler</h1>
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

            <div class="backups-list">
                <h2>Yedekler</h2>
                <?php if (empty($backups)): ?>
                    <p>Henüz yedekleme yapılmamış.</p>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <div class="backup-item">
                            <div class="backup-info">
                                <p class="backup-path">Kaynak: <?php echo htmlspecialchars($backup['source_path']); ?></p>
                                <p class="backup-date">Tarih: <?php echo htmlspecialchars($backup['created_at']); ?></p>
                                <p class="backup-file">
                                    Yedek dosyası: 
                                    <a href="download.php?file=<?php echo urlencode($backup['backup_path']); ?>">
                                        <?php echo htmlspecialchars(basename($backup['backup_path'])); ?>
                                    </a>
                                </p>
                            </div>
                            <form method="post" class="delete-backup-form">
                                <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                <button type="submit" name="delete_backup" class="delete-btn">Yedeği Sil</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 