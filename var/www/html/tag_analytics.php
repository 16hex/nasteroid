<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$analytics = getTagAnalytics();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiket Analizi - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Etiket Analizi</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
                <a href="tags.php">Etiketler</a>
                <div class="user-nav">
                    <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a href="logout.php" class="logout">Çıkış Yap</a>
                </div>
            </nav>
        </header>

        <main>
            <div class="analytics-container">
                <div class="analytics-section">
                    <h2>Etiket Kullanım İstatistikleri</h2>
                    <div class="chart-container">
                        <canvas id="tagUsageChart"></canvas>
                    </div>
                    <div class="stats-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Etiket</th>
                                    <th>Kullanım</th>
                                    <th>İlk Kullanım</th>
                                    <th>Son Kullanım</th>
                                    <th>Kullanıcı Sayısı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['stats'] as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['tag_name']); ?></td>
                                        <td><?php echo $stat['usage_count']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($stat['first_used'])); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($stat['last_used'])); ?></td>
                                        <td><?php echo $stat['user_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analytics-section">
                    <h2>Popüler Etiket Kombinasyonları</h2>
                    <div class="combinations-list">
                        <?php foreach ($analytics['combinations'] as $combo): ?>
                            <div class="combo-item">
                                <span class="combo-tags">
                                    <span class="tag-item"><?php echo htmlspecialchars($combo['tag1']); ?></span>
                                    <span class="combo-plus">+</span>
                                    <span class="tag-item"><?php echo htmlspecialchars($combo['tag2']); ?></span>
                                </span>
                                <span class="combo-count"><?php echo $combo['combo_count']; ?> kez birlikte kullanıldı</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Etiket kullanım grafiği
    const ctx = document.getElementById('tagUsageChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($analytics['stats'], 'tag_name')); ?>,
            datasets: [{
                label: 'Kullanım Sayısı',
                data: <?php echo json_encode(array_column($analytics['stats'], 'usage_count')); ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.5)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>
</body>
</html> 