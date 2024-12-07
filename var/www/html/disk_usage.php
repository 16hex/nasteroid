<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '/';
$usage = getDiskUsage($current_dir);

if (!$usage) {
    header('Location: index.php');
    exit;
}

$total_size = formatDiskUsage($usage['total_size']);
$disk_free = formatDiskUsage($usage['disk_free']);
$disk_total = formatDiskUsage($usage['disk_total']);
$disk_used = formatDiskUsage($usage['disk_total'] - $usage['disk_free']);
$usage_percent = round(($usage['disk_total'] - $usage['disk_free']) / $usage['disk_total'] * 100, 2);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disk Kullanımı - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Disk Kullanımı</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
                <div class="user-nav">
                    <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a href="logout.php" class="logout">Çıkış Yap</a>
                </div>
            </nav>
        </header>

        <main>
            <div class="disk-usage">
                <div class="disk-stats">
                    <div class="disk-stat-item">
                        <h3>Toplam Alan</h3>
                        <p><?php echo $disk_total['value'] . ' ' . $disk_total['unit']; ?></p>
                    </div>
                    <div class="disk-stat-item">
                        <h3>Kullanılan Alan</h3>
                        <p><?php echo $disk_used['value'] . ' ' . $disk_used['unit']; ?></p>
                    </div>
                    <div class="disk-stat-item">
                        <h3>Boş Alan</h3>
                        <p><?php echo $disk_free['value'] . ' ' . $disk_free['unit']; ?></p>
                    </div>
                </div>

                <div class="disk-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $usage_percent; ?>%;">
                            <?php echo $usage_percent; ?>%
                        </div>
                    </div>
                </div>

                <div class="directory-stats">
                    <h3>Dizin İstatistikleri</h3>
                    <p>Toplam Boyut: <?php echo $total_size['value'] . ' ' . $total_size['unit']; ?></p>
                    <p>Dosya Sayısı: <?php echo $usage['file_count']; ?></p>
                    <p>Klasör Sayısı: <?php echo $usage['dir_count']; ?></p>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 