<?php
session_start();
require_once 'includes/functions.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ev NAS Sistemi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="file-explorer">
            <h2>Dosyalarım</h2>
            <div class="upload-section">
                <form action="upload.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="file" id="file">
                    <button type="submit">Yükle</button>
                </form>
            </div>
            
            <div class="files-list">
                <?php
                $files = listFiles('/uploads');
                foreach ($files as $file) {
                    echo "<div class='file-item'>";
                    echo "<span class='file-name'>{$file['name']}</span>";
                    echo "<span class='file-size'>{$file['size']}</span>";
                    echo "<div class='file-actions'>";
                    echo "<a href='download.php?file={$file['name']}'>İndir</a>";
                    echo "<a href='delete.php?file={$file['name']}'>Sil</a>";
                    echo "</div>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html> 