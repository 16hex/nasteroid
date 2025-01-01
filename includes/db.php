<?php
try {
    // Hata ayıklama için
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    error_log("Database connection attempt starting...");

    // Windows için yolu düzelt
    $db_path = str_replace('\\', '/', __DIR__ . '/../data/nas.db');
    error_log("Database path: " . $db_path);

    // SQLite sürücüsünü kontrol et
    if (!in_array('sqlite', PDO::getAvailableDrivers())) {
        die("SQLite sürücüsü yüklü değil. Mevcut sürücüler: " . implode(', ', PDO::getAvailableDrivers()));
    }

    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connection successful");

    // Tabloları oluştur
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS shares (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        title TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS backups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        share_id INTEGER,
        backup_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(share_id) REFERENCES shares(id)
    )");

    // Varsayılan admin kullanıcısını ekle (eğer yoksa)
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $hash]);
        error_log("Admin user created");
    }

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Veritabanı hatası: " . $e->getMessage() . " (Detaylar için error.log dosyasına bakın)");
}
?> 