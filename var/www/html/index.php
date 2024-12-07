<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

$files = scanDirectory($current_dir);
$files = sortFiles($files, $sort_by, $order);

$parent_dir = dirname($current_dir);
if ($parent_dir === '.') {
    $parent_dir = '';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>NASteroid</h1>
            <nav>
                <a href="tags.php">Etiketler</a>
                <a href="backup.php">Yedekler</a>
                <div class="user-nav">
                    <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a href="logout.php" class="logout">Çıkış Yap</a>
                </div>
            </nav>
        </header>

        <div class="action-bar">
            <div class="action-bar-row">
                <form method="post" action="upload.php" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($current_dir); ?>">
                    <input type="file" name="file" required>
                    <button type="submit" name="upload">Yükle</button>
                </form>

                <form method="post" action="index.php" class="new-folder-form">
                    <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($current_dir); ?>">
                    <input type="text" name="folder_name" placeholder="Yeni klasör adı" required>
                    <button type="submit" name="create_folder">Oluştur</button>
                </form>
            </div>

            <div class="action-bar-row">
                <form method="get" action="search.php" class="search-form">
                    <input type="text" name="q" placeholder="Dosya ara..." required>
                    <button type="submit">Ara</button>
                </form>
            </div>
        </div>

        <div class="current-path">
            <?php if (!empty($current_dir)): ?>
                <a href="?dir=<?php echo urlencode($parent_dir); ?>">← Üst Dizin</a>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($current_dir ?: '/'); ?></span>
        </div>

        <div class="file-grid">
            <?php if (empty($files)): ?>
                <p class="no-files">Bu dizinde dosya bulunamadı.</p>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <div class="file-item">
                        <?php if ($file['type'] === 'dir'): ?>
                            <a href="?dir=<?php echo urlencode($current_dir . '/' . $file['name']); ?>" class="folder">
                                <img src="images/folder.png" alt="Klasör">
                                <?php echo htmlspecialchars($file['name']); ?>
                            </a>
                        <?php else: ?>
                            <a href="details.php?file=<?php echo urlencode($current_dir . '/' . $file['name']); ?>">
                                <img src="images/file.png" alt="Dosya">
                                <?php echo htmlspecialchars($file['name']); ?>
                                <span class="file-size"><?php echo formatFileSize($file['size']); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 