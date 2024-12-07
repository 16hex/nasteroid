<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$query = $_GET['q'] ?? '';
$content_search = isset($_GET['content_search']);
$case_sensitive = isset($_GET['case_sensitive']);
$file_types = isset($_GET['file_types']) ? $_GET['file_types'] : [];
$min_size = isset($_GET['min_size']) ? intval($_GET['min_size']) : null;
$max_size = isset($_GET['max_size']) ? intval($_GET['max_size']) : null;
$date_from = $_GET['date_from'] ?? null;
$date_to = $_GET['date_to'] ?? null;

$results = [];
if (!empty($query)) {
    $search_options = [
        'content_search' => $content_search,
        'case_sensitive' => $case_sensitive,
        'file_types' => $file_types,
        'min_size' => $min_size,
        'max_size' => $max_size,
        'date_from' => $date_from,
        'date_to' => $date_to
    ];
    $results = searchFiles($query, '/storage', $search_options);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Sonuçları - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Arama Sonuçları</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
                <div class="user-nav">
                    <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a href="logout.php" class="logout">Çıkış Yap</a>
                </div>
            </nav>
        </header>

        <main>
            <div class="search-header">
                <h2>"<?php echo htmlspecialchars($query); ?>" için arama sonuçları</h2>
                <p><?php echo count($results); ?> sonuç bulundu</p>
                
                <form class="search-form" action="search.php" method="get">
                    <input type="text" 
                           name="q" 
                           placeholder="Dosya veya klasör ara..."
                           value="<?php echo htmlspecialchars($query); ?>"
                           required>
                    <div class="search-options">
                        <label>
                            <input type="checkbox" name="content_search" <?php echo $content_search ? 'checked' : ''; ?>>
                            Dosya içeriğinde ara
                        </label>
                        <label>
                            <input type="checkbox" name="case_sensitive" <?php echo $case_sensitive ? 'checked' : ''; ?>>
                            Büyük/küçük harf duyarlı
                        </label>
                        <div class="file-types">
                            <label>Dosya Türleri:</label>
                            <select name="file_types[]" multiple>
                                <option value="txt" <?php echo in_array('txt', $file_types) ? 'selected' : ''; ?>>Metin (.txt)</option>
                                <option value="pdf" <?php echo in_array('pdf', $file_types) ? 'selected' : ''; ?>>PDF (.pdf)</option>
                                <option value="doc,docx" <?php echo in_array('doc', $file_types) ? 'selected' : ''; ?>>Word (.doc, .docx)</option>
                                <option value="xls,xlsx" <?php echo in_array('xls', $file_types) ? 'selected' : ''; ?>>Excel (.xls, .xlsx)</option>
                                <option value="jpg,jpeg,png" <?php echo in_array('jpg', $file_types) ? 'selected' : ''; ?>>Resim (.jpg, .png)</option>
                            </select>
                        </div>
                        <div class="size-range">
                            <label>Boyut Aralığı:</label>
                            <input type="number" name="min_size" placeholder="Min (byte)" value="<?php echo $min_size; ?>">
                            <input type="number" name="max_size" placeholder="Max (byte)" value="<?php echo $max_size; ?>">
                        </div>
                        <div class="date-range">
                            <label>Tarih Aralığı:</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                    </div>
                    <button type="submit">Ara</button>
                </form>
            </div>

            <div class="file-grid">
                <?php if (empty($results)): ?>
                    <div class="no-results">
                        <p>Aramanızla eşleşen sonuç bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $file): ?>
                        <div class="file-item">
                            <?php if ($file['type'] === 'dir'): ?>
                                <a href="index.php?dir=<?php echo urlencode($file['path']); ?>" class="folder">
                                    <img src="images/folder.png" alt="Klasör">
                                    <?php echo htmlspecialchars($file['name']); ?>
                                    <span class="file-path"><?php echo htmlspecialchars($file['path']); ?></span>
                                </a>
                            <?php else: ?>
                                <a href="download.php?file=<?php echo urlencode($file['path']); ?>" class="file">
                                    <img src="images/file.png" alt="Dosya">
                                    <?php echo htmlspecialchars($file['name']); ?>
                                    <span class="file-size"><?php echo formatFileSize($file['size']); ?></span>
                                    <span class="file-path"><?php echo htmlspecialchars($file['path']); ?></span>
                                </a>
                            <?php endif; ?>
                            <button class="delete-btn" data-path="<?php echo htmlspecialchars($file['path']); ?>">Sil</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', async function() {
            if (confirm('Bu öğeyi silmek istediğinizden emin misiniz?')) {
                const path = this.dataset.path;
                const formData = new FormData();
                formData.append('path', path);
                
                try {
                    const response = await fetch('delete.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    alert(result.message);
                    
                    if (result.success) {
                        window.location.reload();
                    }
                } catch (error) {
                    alert('Bir hata oluştu.');
                }
            }
        });
    });
    </script>
</body>
</html> 