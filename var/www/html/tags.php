<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_tag'])) {
        $filePath = $_POST['file_path'];
        $tagName = trim($_POST['tag_name']);
        
        if (!isValidPath($filePath)) {
            $message = 'Geçersiz dosya yolu.';
        } elseif (empty($tagName)) {
            $message = 'Etiket adı boş olamaz.';
        } else {
            if (addTag($filePath, $tagName)) {
                $message = 'Etiket eklendi.';
            } else {
                $message = 'Etiket eklenirken bir hata oluştu.';
            }
        }
    } elseif (isset($_POST['remove_tag'])) {
        $filePath = $_POST['file_path'];
        $tagName = $_POST['tag_name'];
        
        if (removeTag($filePath, $tagName)) {
            $message = 'Etiket kaldırıldı.';
        } else {
            $message = 'Etiket kaldırılırken bir hata oluştu.';
        }
    }
}

$tag = $_GET['tag'] ?? '';
$selectedTags = isset($_GET['tags']) ? explode(',', $_GET['tags']) : [];
$matchAll = isset($_GET['match_all']) && $_GET['match_all'] === '1';

$files = !empty($tag) ? searchByTag($tag) : 
        (!empty($selectedTags) ? searchByMultipleTags($selectedTags, $matchAll) : []);

$allTags = getAllTags();
$tagStats = getTagStats();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiketler - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Etiketler</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
                <a href="tag_analytics.php">Etiket Analizi</a>
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

            <div class="tags-container">
                <div class="tag-filters">
                    <h2>Etiket Filtreleme</h2>
                    <form action="tags.php" method="get" class="tag-filter-form">
                        <div class="tag-select">
                            <label>Etiketler:</label>
                            <select name="tags[]" multiple>
                                <?php foreach ($tagStats as $tagStat): ?>
                                    <option value="<?php echo htmlspecialchars($tagStat['tag_name']); ?>"
                                            <?php echo in_array($tagStat['tag_name'], $selectedTags) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tagStat['tag_name']); ?> 
                                        (<?php echo $tagStat['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="match-type">
                            <label>
                                <input type="radio" name="match_all" value="1" <?php echo $matchAll ? 'checked' : ''; ?>>
                                Tüm etiketlerle eşleşen
                            </label>
                            <label>
                                <input type="radio" name="match_all" value="0" <?php echo !$matchAll ? 'checked' : ''; ?>>
                                Herhangi bir etiketle eşleşen
                            </label>
                        </div>
                        <button type="submit">Filtrele</button>
                    </form>
                </div>

                <div class="tags-list">
                    <h2>Tüm Etiketler</h2>
                    <?php if (empty($allTags)): ?>
                        <p>Henüz etiket eklenmemiş.</p>
                    <?php else: ?>
                        <div class="tag-cloud">
                            <?php foreach ($allTags as $t): ?>
                                <a href="?tag=<?php echo urlencode($t); ?>" class="tag-item">
                                    <?php echo htmlspecialchars($t); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($tag)): ?>
                    <div class="tagged-files">
                        <h2>"<?php echo htmlspecialchars($tag); ?>" Etiketli Dosyalar</h2>
                        <?php if (empty($files)): ?>
                            <p>Bu etiketle ilişkili dosya bulunamadı.</p>
                        <?php else: ?>
                            <div class="file-grid">
                                <?php foreach ($files as $file): ?>
                                    <div class="file-item">
                                        <?php if ($file['type'] === 'dir'): ?>
                                            <a href="index.php?dir=<?php echo urlencode($file['path']); ?>" class="folder">
                                                <img src="images/folder.png" alt="Klasör">
                                                <?php echo htmlspecialchars($file['name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="preview.php?file=<?php echo urlencode($file['path']); ?>" class="file">
                                                <img src="images/file.png" alt="Dosya">
                                                <?php echo htmlspecialchars($file['name']); ?>
                                                <span class="file-size"><?php echo formatFileSize($file['size']); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 