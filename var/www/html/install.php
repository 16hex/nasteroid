<?php
require_once 'includes/functions.php';

// Kurulum durumunu kontrol et
$is_installed = false;
try {
    $stmt = $db->query('SELECT COUNT(*) FROM users');
    $count = $stmt->fetchColumn();
    $is_installed = ($count > 0);
} catch (PDOException $e) {
    die('Veritabanı hatası: ' . $e->getMessage());
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_installed) {
    $admin_username = $_POST['username'] ?? '';
    $admin_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($admin_password) < 8) {
        $message = 'Şifre en az 8 karakter olmalıdır.';
    } elseif ($admin_password !== $confirm_password) {
        $message = 'Şifreler eşleşmiyor.';
    } else {
        if (register($admin_username, $admin_password)) {
            $message = 'Yönetici hesabı başarıyla oluşturuldu. Şimdi giriş yapabilirsiniz.';
            $is_installed = true;
        } else {
            $message = 'Hesap oluşturulurken bir hata oluştu.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NASteroid Kurulum</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1>NASteroid Kurulum</h1>
            
            <?php if ($message): ?>
                <div class="<?php echo $is_installed ? 'message' : 'error-message'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($is_installed): ?>
                <p>Sistem zaten kurulu. <a href="login.php">Giriş yapmak için tıklayın</a>.</p>
            <?php else: ?>
                <form method="post" action="install.php">
                    <div class="form-group">
                        <label for="username">Yönetici Kullanıcı Adı:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Şifre:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Şifre Tekrar:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit">Kurulumu Tamamla</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 