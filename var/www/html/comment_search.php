<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$query = $_GET['q'] ?? '';
$comments = !empty($query) ? searchVersionComments($query) : getRecentComments();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorum Arama - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Yorum Arama</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
            </nav>
        </header>

        <main>
            <div class="comment-search-container">
                <form class="comment-search-form" method="get">
                    <input type="text" 
                           name="q" 
                           placeholder="Yorumlarda ara..." 
                           value="<?php echo htmlspecialchars($query); ?>">
                    <button type="submit">Ara</button>
                </form>

                <div class="comments-list">
                    <h2><?php echo empty($query) ? 'Son Yorumlar' : 'Arama Sonuçları'; ?></h2>
                    <?php if (empty($comments)): ?>
                        <p class="no-results">Yorum bulunamadı.</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <div class="comment-meta">
                                        <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                        <span class="comment-date"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    <div class="comment-location">
                                        <a href="compare_versions.php?file=<?php echo urlencode($comment['file_path']); ?>&v1=<?php echo $comment['version_number']-1; ?>&v2=<?php echo $comment['version_number']; ?>">
                                            <?php echo htmlspecialchars(basename($comment['file_path'])); ?> (v<?php echo $comment['version_number']; ?>)
                                            <?php if ($comment['line_number']): ?>
                                                - Satır <?php echo $comment['line_number']; ?>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="comment-content">
                                    <?php echo htmlspecialchars($comment['comment']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 