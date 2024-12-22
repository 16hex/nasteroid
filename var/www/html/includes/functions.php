<?php

require_once 'db.php';

function scanDirectory($dir) {
    $files = [];
    $base_path = '/var/www/html/storage'; // Tam yolu belirt
    $full_path = $base_path . '/' . trim($dir, '/');
    
    if (is_dir($full_path)) {
        $items = scandir($full_path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = $full_path . '/' . $item;
            $relative_path = trim($dir, '/') . '/' . $item;
            
            $files[] = [
                'name' => $item,
                'path' => $relative_path,
                'type' => is_dir($item_path) ? 'dir' : 'file',
                'size' => is_file($item_path) ? filesize($item_path) : 0,
                'modified' => filemtime($item_path)
            ];
        }
    }
    
    return $files;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function isValidPath($path) {
    // Güvenlik kontrolleri
    $normalized = str_replace('\\', '/', $path);
    if (strpos($normalized, '..') !== false) {
        return false;
    }
    
    // İzin verilen karakterler
    if (!preg_match('/^[a-zA-Z0-9\/_-]+$/', $normalized)) {
        return false;
    }
    
    return true;
}

function isAllowedFileType($filename) {
    // İzin verilmeyen dosya uzantıları
    $blocked_extensions = ['php', 'php3', 'php4', 'phtml', 'exe', 'sh', 'bat'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    return !in_array($ext, $blocked_extensions);
}

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // Oturum süresini kontrol et (2 saat)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

function login($username, $password) {
    global $db;
    
    $stmt = $db->prepare('SELECT id, password FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    
    return false;
}

function register($username, $password) {
    global $db;
    
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
        return $stmt->execute([$username, $hash]);
    } catch (PDOException $e) {
        return false;
    }
}

function getCurrentUsername() {
    global $db;
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare('SELECT username FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchColumn() ?: 'Misafir';
    }
    
    return 'Misafir';
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

function searchFileContent($file_path, $query) {
    $mime_type = mime_content_type($file_path);
    
    // Sadece metin dosyalarında ara
    if (strpos($mime_type, 'text/') === 0 || 
        in_array(strtolower(pathinfo($file_path, PATHINFO_EXTENSION)), ['txt', 'md', 'csv', 'json', 'xml', 'html', 'css', 'js'])) {
        $content = file_get_contents($file_path);
        return mb_stripos($content, $query) !== false;
    }
    
    return false;
}

function searchFiles($query, $dir = '/storage', $options = []) {
    $results = [];
    $items = scandir($dir);
    
    // Varsayılan seçenekler
    $default_options = [
        'content_search' => false,  // İçerik araması
        'case_sensitive' => false,  // Büyük/küçük harf duyarlı
        'file_types' => [],         // Belirli dosya türleri
        'min_size' => null,         // Minimum boyut (byte)
        'max_size' => null,         // Maximum boyut (byte)
        'date_from' => null,        // Başlangıç tarihi
        'date_to' => null          // Bitiş tarihi
    ];
    
    $options = array_merge($default_options, $options);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        $relativePath = substr($path, strlen('/storage'));
        $stats = stat($path);
        
        // Tarih filtreleri
        if ($options['date_from'] && $stats['mtime'] < strtotime($options['date_from'])) continue;
        if ($options['date_to'] && $stats['mtime'] > strtotime($options['date_to'])) continue;
        
        if (is_file($path)) {
            // Boyut filtreleri
            $size = filesize($path);
            if ($options['min_size'] && $size < $options['min_size']) continue;
            if ($options['max_size'] && $size > $options['max_size']) continue;
            
            // Dosya türü filtreleri
            if (!empty($options['file_types'])) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (!in_array($ext, $options['file_types'])) continue;
            }
        }
        
        // İsim araması
        $match = $options['case_sensitive'] ? 
            strpos($item, $query) !== false : 
            stripos($item, $query) !== false;
        
        // İçerik araması
        if (!$match && $options['content_search'] && is_file($path)) {
            $match = searchFileContent($path, $query);
        }
        
        if ($match) {
            $results[] = [
                'name' => $item,
                'path' => $relativePath,
                'type' => is_dir($path) ? 'dir' : 'file',
                'size' => is_file($path) ? filesize($path) : 0,
                'modified' => filemtime($path)
            ];
        }
        
        // Klasörlerin içinde arama
        if (is_dir($path)) {
            $subResults = searchFiles($query, $path, $options);
            $results = array_merge($results, $subResults);
        }
    }
    
    return $results;
}

function createShare($filePath, $expiresIn = 24) {
    global $db;
    
    try {
        $shareCode = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresIn} hours"));
        
        $stmt = $db->prepare('INSERT INTO shares (user_id, file_path, share_code, expires_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $filePath, $shareCode, $expiresAt]);
        
        return $shareCode;
    } catch (Exception $e) {
        return false;
    }
}

function getShareInfo($shareCode) {
    global $db;
    
    $stmt = $db->prepare('SELECT * FROM shares WHERE share_code = ? AND (expires_at > DATETIME("now") OR expires_at IS NULL)');
    $stmt->execute([$shareCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserShares() {
    global $db;
    
    $stmt = $db->prepare('SELECT * FROM shares WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteShare($shareCode) {
    global $db;
    
    $stmt = $db->prepare('DELETE FROM shares WHERE share_code = ? AND user_id = ?');
    return $stmt->execute([$shareCode, $_SESSION['user_id']]);
}

function sortFiles($files, $sort_by = 'name', $order = 'asc') {
    $sort_functions = [
        'name' => function($a, $b) use ($order) {
            return $order === 'asc' ? 
                strcasecmp($a['name'], $b['name']) : 
                strcasecmp($b['name'], $a['name']);
        },
        'size' => function($a, $b) use ($order) {
            if ($a['type'] === 'dir' && $b['type'] === 'file') return -1;
            if ($a['type'] === 'file' && $b['type'] === 'dir') return 1;
            return $order === 'asc' ? 
                $a['size'] - $b['size'] : 
                $b['size'] - $a['size'];
        },
        'modified' => function($a, $b) use ($order) {
            return $order === 'asc' ? 
                $a['modified'] - $b['modified'] : 
                $b['modified'] - $a['modified'];
        }
    ];

    if (isset($sort_functions[$sort_by])) {
        usort($files, $sort_functions[$sort_by]);
    }

    return $files;
}

function getFileDetails($path) {
    $full_path = '/storage/' . trim($path, '/');
    
    if (!file_exists($full_path)) {
        return null;
    }
    
    $stats = stat($full_path);
    $mime_type = is_dir($full_path) ? 'directory' : mime_content_type($full_path);
    
    return [
        'name' => basename($full_path),
        'path' => $path,
        'type' => is_dir($full_path) ? 'dir' : 'file',
        'mime_type' => $mime_type,
        'size' => is_file($full_path) ? filesize($full_path) : 0,
        'created' => $stats['ctime'],
        'modified' => $stats['mtime'],
        'accessed' => $stats['atime'],
        'permissions' => substr(sprintf('%o', fileperms($full_path)), -4),
        'owner' => posix_getpwuid($stats['uid'])['name'],
        'group' => posix_getgrgid($stats['gid'])['name']
    ];
}

function formatTimestamp($timestamp) {
    return date('d.m.Y H:i:s', $timestamp);
}

function changePermissions($path, $permissions) {
    $full_path = '/storage/' . trim($path, '/');
    
    if (!file_exists($full_path)) {
        return false;
    }
    
    // Sekizlik sayı sistemine çevir
    $mode = octdec(str_pad($permissions, 4, '0', STR_PAD_LEFT));
    return chmod($full_path, $mode);
}

function getPermissionsDescription($permissions) {
    $perms = [
        '7' => 'Tam Yetki (Okuma, Yazma, Çalıştırma)',
        '6' => 'Okuma ve Yazma',
        '5' => 'Okuma ve Çalıştırma',
        '4' => 'Sadece Okuma',
        '3' => 'Yazma ve Çalıştırma',
        '2' => 'Sadece Yazma',
        '1' => 'Sadece Çalıştırma',
        '0' => 'Yetki Yok'
    ];
    
    $parts = str_split(substr($permissions, -3));
    $desc = [];
    
    $types = ['Sahip', 'Grup', 'Diğer'];
    foreach ($parts as $i => $p) {
        $desc[] = $types[$i] . ': ' . $perms[$p];
    }
    
    return $desc;
}

function renameItem($oldPath, $newName) {
    $base_path = '/storage';
    $old_full_path = $base_path . '/' . trim($oldPath, '/');
    $new_full_path = dirname($old_full_path) . '/' . $newName;
    
    // Yeni isim geçerli mi kontrol et
    if (!preg_match('/^[a-zA-Z0-9-_.]+$/', $newName)) {
        return false;
    }
    
    // Aynı isimde dosya/klasör var mı kontrol et
    if (file_exists($new_full_path)) {
        return false;
    }
    
    return rename($old_full_path, $new_full_path);
}

function copyItem($sourcePath, $destPath) {
    $base_path = '/storage';
    $source_full_path = $base_path . '/' . trim($sourcePath, '/');
    $dest_full_path = $base_path . '/' . trim($destPath, '/');
    
    // Hedef klasör yoksa oluştur
    $dest_dir = dirname($dest_full_path);
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }
    
    if (is_dir($source_full_path)) {
        return copyDirectory($source_full_path, $dest_full_path);
    } else {
        return copy($source_full_path, $dest_full_path);
    }
}

function copyDirectory($source, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $dir = opendir($source);
    $result = true;
    
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $src = $source . '/' . $file;
            $dst = $dest . '/' . $file;
            
            if (is_dir($src)) {
                $result = $result && copyDirectory($src, $dst);
            } else {
                $result = $result && copy($src, $dst);
            }
        }
    }
    
    closedir($dir);
    return $result;
}

function moveItem($sourcePath, $destPath) {
    $base_path = '/storage';
    $source_full_path = $base_path . '/' . trim($sourcePath, '/');
    $dest_full_path = $base_path . '/' . trim($destPath, '/');
    
    // Hedef klasör yoksa oluştur
    $dest_dir = dirname($dest_full_path);
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }
    
    return rename($source_full_path, $dest_full_path);
}

function getDirectories($dir = '/') {
    $base_path = '/storage';
    $full_path = $base_path . '/' . trim($dir, '/');
    $dirs = [];
    
    if (is_dir($full_path)) {
        $items = scandir($full_path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $item_path = $full_path . '/' . $item;
            if (is_dir($item_path)) {
                $relative_path = trim($dir, '/') . '/' . $item;
                $dirs[] = [
                    'name' => $item,
                    'path' => $relative_path
                ];
            }
        }
    }
    
    return $dirs;
}

function createArchive($sourcePath, $archiveName) {
    $base_path = '/storage';
    $source_full_path = $base_path . '/' . trim($sourcePath, '/');
    $archive_path = dirname($source_full_path) . '/' . $archiveName;
    
    // Arşiv dosyası zaten varsa
    if (file_exists($archive_path)) {
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($archive_path, ZipArchive::CREATE) !== true) {
        return false;
    }
    
    if (is_dir($source_full_path)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_full_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source_full_path) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    } else {
        $zip->addFile($source_full_path, basename($source_full_path));
    }
    
    return $zip->close();
}

function extractArchive($archivePath, $destPath) {
    $base_path = '/storage';
    $archive_full_path = $base_path . '/' . trim($archivePath, '/');
    $dest_full_path = $base_path . '/' . trim($destPath, '/');
    
    if (!file_exists($archive_full_path)) {
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($archive_full_path) !== true) {
        return false;
    }
    
    if (!is_dir($dest_full_path)) {
        mkdir($dest_full_path, 0755, true);
    }
    
    return $zip->extractTo($dest_full_path) && $zip->close();
}

function createBackup($sourcePath, $backupName = null) {
    $base_path = '/storage';
    $backup_dir = $base_path . '/_backups';
    $source_full_path = $base_path . '/' . trim($sourcePath, '/');
    
    // Yedekleme klasörünü oluştur
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Yedek dosya adını oluştur
    if (!$backupName) {
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = basename($sourcePath) . '_' . $timestamp . '.zip';
    }
    
    $backup_path = $backup_dir . '/' . $backupName;
    
    $zip = new ZipArchive();
    if ($zip->open($backup_path, ZipArchive::CREATE) !== true) {
        return false;
    }
    
    if (is_dir($source_full_path)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_full_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source_full_path) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    } else {
        $zip->addFile($source_full_path, basename($source_full_path));
    }
    
    $success = $zip->close();
    
    if ($success) {
        // Yedekleme kaydını veritabanına ekle
        global $db;
        $stmt = $db->prepare('INSERT INTO backups (user_id, source_path, backup_path, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
        $stmt->execute([$_SESSION['user_id'], $sourcePath, '_backups/' . $backupName]);
    }
    
    return $success;
}

function getBackups() {
    global $db;
    
    $stmt = $db->prepare('SELECT * FROM backups WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteBackup($backupId) {
    global $db;
    
    $stmt = $db->prepare('SELECT backup_path FROM backups WHERE id = ? AND user_id = ?');
    $stmt->execute([$backupId, $_SESSION['user_id']]);
    $backup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($backup) {
        $backup_full_path = '/storage/' . trim($backup['backup_path'], '/');
        if (file_exists($backup_full_path) && unlink($backup_full_path)) {
            $stmt = $db->prepare('DELETE FROM backups WHERE id = ?');
            return $stmt->execute([$backupId]);
        }
    }
    
    return false;
}

function getDiskUsage($path = '/') {
    $base_path = '/storage';
    $full_path = $base_path . '/' . trim($path, '/');
    
    if (!is_dir($full_path)) {
        return null;
    }
    
    $total_size = 0;
    $file_count = 0;
    $dir_count = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($full_path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            $dir_count++;
        } else {
            $file_count++;
            $total_size += $item->getSize();
        }
    }
    
    return [
        'total_size' => $total_size,
        'file_count' => $file_count,
        'dir_count' => $dir_count,
        'disk_free' => disk_free_space($base_path),
        'disk_total' => disk_total_space($base_path)
    ];
}

function formatDiskUsage($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return [
        'value' => round($bytes, 2),
        'unit' => $units[$pow]
    ];
}

function addTag($filePath, $tagName) {
    global $db;
    
    try {
        $stmt = $db->prepare('INSERT INTO tags (user_id, file_path, tag_name) VALUES (?, ?, ?)');
        return $stmt->execute([$_SESSION['user_id'], $filePath, $tagName]);
    } catch (PDOException $e) {
        return false;
    }
}

function removeTag($filePath, $tagName) {
    global $db;
    
    $stmt = $db->prepare('DELETE FROM tags WHERE user_id = ? AND file_path = ? AND tag_name = ?');
    return $stmt->execute([$_SESSION['user_id'], $filePath, $tagName]);
}

function getFileTags($filePath) {
    global $db;
    
    $stmt = $db->prepare('SELECT tag_name FROM tags WHERE user_id = ? AND file_path = ? ORDER BY tag_name');
    $stmt->execute([$_SESSION['user_id'], $filePath]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getAllTags() {
    global $db;
    
    $stmt = $db->prepare('SELECT DISTINCT tag_name FROM tags WHERE user_id = ? ORDER BY tag_name');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function searchByTag($tagName) {
    global $db;
    
    $stmt = $db->prepare('SELECT DISTINCT file_path FROM tags WHERE user_id = ? AND tag_name = ?');
    $stmt->execute([$_SESSION['user_id'], $tagName]);
    $paths = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results = [];
    foreach ($paths as $path) {
        $full_path = '/storage/' . trim($path, '/');
        if (file_exists($full_path)) {
            $results[] = [
                'name' => basename($path),
                'path' => $path,
                'type' => is_dir($full_path) ? 'dir' : 'file',
                'size' => is_file($full_path) ? filesize($full_path) : 0,
                'modified' => filemtime($full_path)
            ];
        }
    }
    
    return $results;
}

function getTagStats() {
    global $db;
    
    $stmt = $db->prepare('
        SELECT tag_name, COUNT(*) as count 
        FROM tags 
        WHERE user_id = ? 
        GROUP BY tag_name 
        ORDER BY count DESC, tag_name ASC
    ');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchByMultipleTags($tags, $matchAll = true) {
    global $db;
    
    if (empty($tags)) {
        return [];
    }
    
    $placeholders = str_repeat('?,', count($tags) - 1) . '?';
    $sql = $matchAll ? 
        "SELECT t1.file_path, COUNT(DISTINCT t1.tag_name) as tag_count 
         FROM tags t1 
         WHERE t1.user_id = ? AND t1.tag_name IN ($placeholders)
         GROUP BY t1.file_path 
         HAVING tag_count = ?" :
        "SELECT DISTINCT file_path 
         FROM tags 
         WHERE user_id = ? AND tag_name IN ($placeholders)";
    
    $params = array_merge([$_SESSION['user_id']], $tags);
    if ($matchAll) {
        $params[] = count($tags);
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $paths = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results = [];
    foreach ($paths as $path) {
        $full_path = '/storage/' . trim($path, '/');
        if (file_exists($full_path)) {
            $results[] = [
                'name' => basename($path),
                'path' => $path,
                'type' => is_dir($full_path) ? 'dir' : 'file',
                'size' => is_file($full_path) ? filesize($full_path) : 0,
                'modified' => filemtime($full_path),
                'tags' => getFileTags($path)
            ];
        }
    }
    
    return $results;
}

function searchTags($query) {
    global $db;
    
    $stmt = $db->prepare('
        SELECT DISTINCT tag_name 
        FROM tags 
        WHERE user_id = ? AND tag_name LIKE ? 
        ORDER BY tag_name ASC 
        LIMIT 10
    ');
    $stmt->execute([$_SESSION['user_id'], $query . '%']);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getTagAnalytics() {
    global $db;
    
    // Etiket kullanım istatistikleri
    $stats = $db->prepare('
        SELECT 
            tag_name,
            COUNT(*) as usage_count,
            MIN(created_at) as first_used,
            MAX(created_at) as last_used,
            (
                SELECT COUNT(DISTINCT user_id) 
                FROM tags t2 
                WHERE t2.tag_name = t1.tag_name
            ) as user_count
        FROM tags t1
        GROUP BY tag_name
        ORDER BY usage_count DESC
    ');
    $stats->execute();
    
    // Popüler etiket kombinasyonları
    $combinations = $db->prepare('
        SELECT 
            t1.tag_name as tag1,
            t2.tag_name as tag2,
            COUNT(*) as combo_count
        FROM tags t1
        JOIN tags t2 ON t1.file_path = t2.file_path AND t1.tag_name < t2.tag_name
        GROUP BY t1.tag_name, t2.tag_name
        HAVING combo_count > 1
        ORDER BY combo_count DESC
        LIMIT 10
    ');
    $combinations->execute();
    
    return [
        'stats' => $stats->fetchAll(PDO::FETCH_ASSOC),
        'combinations' => $combinations->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function suggestTags($filePath) {
    $base_path = '/storage';
    $full_path = $base_path . '/' . trim($filePath, '/');
    $suggestions = [];
    
    if (!file_exists($full_path)) {
        return $suggestions;
    }
    
    // Dosya türüne göre öneriler
    $mime_type = mime_content_type($full_path);
    $ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
    
    // Temel dosya türü etiketleri
    $type_tags = [
        'image/' => ['görsel', 'resim'],
        'video/' => ['video', 'medya'],
        'audio/' => ['ses', 'medya'],
        'text/' => ['metin', 'döküman'],
        'application/pdf' => ['pdf', 'döküman'],
        'application/msword' => ['word', 'döküman'],
        'application/vnd.ms-excel' => ['excel', 'tablo'],
        'application/zip' => ['arşiv', 'sıkıştırılmış']
    ];
    
    foreach ($type_tags as $type => $tags) {
        if (strpos($mime_type, $type) === 0) {
            $suggestions = array_merge($suggestions, $tags);
        }
    }
    
    // Metin dosyaları için içerik analizi
    if (strpos($mime_type, 'text/') === 0 || in_array($ext, ['txt', 'md', 'csv'])) {
        $content = file_get_contents($full_path);
        $suggestions = array_merge($suggestions, analyzeContent($content));
    }
    
    // Benzer dosyalarda kullanılan etiketler
    $similar_tags = getSimilarFileTags($filePath, $mime_type);
    $suggestions = array_merge($suggestions, $similar_tags);
    
    // Tekrar eden önerileri kaldır
    return array_unique($suggestions);
}

function analyzeContent($content) {
    $suggestions = [];
    
    // Basit içerik analizi
    if (preg_match('/\b(?:kod|program|script)\b/i', $content)) {
        $suggestions[] = 'kod';
    }
    if (preg_match('/\b(?:proje|project)\b/i', $content)) {
        $suggestions[] = 'proje';
    }
    if (preg_match('/\b(?:rapor|report)\b/i', $content)) {
        $suggestions[] = 'rapor';
    }
    if (preg_match('/\b(?:not|note)\b/i', $content)) {
        $suggestions[] = 'not';
    }
    
    return $suggestions;
}

function getSimilarFileTags($filePath, $mimeType) {
    global $db;
    
    // Aynı türdeki dosyalarda en çok kullanılan etiketleri bul
    $stmt = $db->prepare('
        SELECT t2.tag_name, COUNT(*) as count
        FROM files f1
        JOIN files f2 ON f1.mime_type = f2.mime_type
        JOIN tags t2 ON f2.file_path = t2.file_path
        WHERE f1.file_path = ? AND f1.mime_type = ?
        GROUP BY t2.tag_name
        ORDER BY count DESC
        LIMIT 5
    ');
    
    try {
        $stmt->execute([$filePath, $mimeType]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'tag_name');
    } catch (PDOException $e) {
        return [];
    }
}

function createVersion($filePath, $comment = '') {
    global $db;
    
    $base_path = '/storage';
    $source_full_path = $base_path . '/' . trim($filePath, '/');
    
    if (!file_exists($source_full_path) || !is_file($source_full_path)) {
        return false;
    }
    
    // Son sürüm numarasını al
    $stmt = $db->prepare('SELECT MAX(version_number) FROM versions WHERE file_path = ?');
    $stmt->execute([$filePath]);
    $lastVersion = $stmt->fetchColumn() ?: 0;
    $newVersion = $lastVersion + 1;
    
    // Sürüm dosyasını oluştur
    $version_dir = $base_path . '/_versions/' . dirname($filePath);
    if (!is_dir($version_dir)) {
        mkdir($version_dir, 0755, true);
    }
    
    $version_path = '_versions/' . $filePath . '.v' . $newVersion;
    $version_full_path = $base_path . '/' . $version_path;
    
    if (copy($source_full_path, $version_full_path)) {
        $stmt = $db->prepare('INSERT INTO versions (user_id, file_path, version_path, version_number, comment) VALUES (?, ?, ?, ?, ?)');
        return $stmt->execute([$_SESSION['user_id'], $filePath, $version_path, $newVersion, $comment]);
    }
    
    return false;
}

function getVersions($filePath) {
    global $db;
    
    $stmt = $db->prepare('
        SELECT v.*, u.username 
        FROM versions v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.file_path = ? 
        ORDER BY v.version_number DESC
    ');
    $stmt->execute([$filePath]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function restoreVersion($filePath, $versionNumber) {
    global $db;
    
    $stmt = $db->prepare('SELECT version_path FROM versions WHERE file_path = ? AND version_number = ?');
    $stmt->execute([$filePath, $versionNumber]);
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($version) {
        $base_path = '/storage';
        $version_full_path = $base_path . '/' . $version['version_path'];
        $target_full_path = $base_path . '/' . trim($filePath, '/');
        
        // Mevcut dosyayı yedekle
        createVersion($filePath, 'Otomatik yedek - v' . $versionNumber . ' geri yüklenmeden önce');
        
        return copy($version_full_path, $target_full_path);
    }
    
    return false;
}

function deleteVersion($filePath, $versionNumber) {
    global $db;
    
    $stmt = $db->prepare('SELECT version_path FROM versions WHERE file_path = ? AND version_number = ? AND user_id = ?');
    $stmt->execute([$filePath, $versionNumber, $_SESSION['user_id']]);
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($version) {
        $version_full_path = '/storage/' . $version['version_path'];
        if (file_exists($version_full_path) && unlink($version_full_path)) {
            $stmt = $db->prepare('DELETE FROM versions WHERE file_path = ? AND version_number = ?');
            return $stmt->execute([$filePath, $versionNumber]);
        }
    }
    
    return false;
}

function compareVersions($filePath, $version1, $version2) {
    global $db;
    
    // Her iki sürümün de yollarını al
    $stmt = $db->prepare('SELECT version_path FROM versions WHERE file_path = ? AND version_number IN (?, ?)');
    $stmt->execute([$filePath, $version1, $version2]);
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($versions) !== 2) {
        return false;
    }
    
    $base_path = '/storage';
    $file1_path = $base_path . '/' . $versions[0]['version_path'];
    $file2_path = $base_path . '/' . $versions[1]['version_path'];
    
    // Dosya içeriklerini al
    $content1 = file_get_contents($file1_path);
    $content2 = file_get_contents($file2_path);
    
    // Satır satır karşılaştır
    $lines1 = explode("\n", $content1);
    $lines2 = explode("\n", $content2);
    
    $diff = [];
    $max_lines = max(count($lines1), count($lines2));
    
    for ($i = 0; $i < $max_lines; $i++) {
        $line1 = isset($lines1[$i]) ? $lines1[$i] : '';
        $line2 = isset($lines2[$i]) ? $lines2[$i] : '';
        
        if ($line1 !== $line2) {
            $diff[] = [
                'line' => $i + 1,
                'old' => $line1,
                'new' => $line2
            ];
        }
    }
    
    return $diff;
}

function getVersionContent($filePath, $versionNumber) {
    global $db;
    
    $stmt = $db->prepare('SELECT version_path FROM versions WHERE file_path = ? AND version_number = ?');
    $stmt->execute([$filePath, $versionNumber]);
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($version) {
        $base_path = '/storage';
        $version_full_path = $base_path . '/' . $version['version_path'];
        return file_exists($version_full_path) ? file_get_contents($version_full_path) : false;
    }
    
    return false;
}

function addVersionComment($filePath, $versionNumber, $lineNumber, $comment) {
    global $db;
    
    $stmt = $db->prepare('INSERT INTO version_comments (user_id, file_path, version_number, line_number, comment) VALUES (?, ?, ?, ?, ?)');
    return $stmt->execute([$_SESSION['user_id'], $filePath, $versionNumber, $lineNumber, $comment]);
}

function getVersionComments($filePath, $versionNumber) {
    global $db;
    
    $stmt = $db->prepare('
        SELECT c.*, u.username 
        FROM version_comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.file_path = ? AND c.version_number = ? 
        ORDER BY c.line_number, c.created_at
    ');
    $stmt->execute([$filePath, $versionNumber]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchVersionComments($query) {
    global $db;
    
    $stmt = $db->prepare('
        SELECT c.*, u.username, v.version_number 
        FROM version_comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN versions v ON c.file_path = v.file_path AND c.version_number = v.version_number
        WHERE c.comment LIKE ? AND c.user_id = ?
        ORDER BY c.created_at DESC
    ');
    $stmt->execute(['%' . $query . '%', $_SESSION['user_id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentComments($limit = 10) {
    global $db;
    
    $stmt = $db->prepare('
        SELECT c.*, u.username, v.version_number 
        FROM version_comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN versions v ON c.file_path = v.file_path AND c.version_number = v.version_number
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT ?
    ');
    $stmt->execute([$_SESSION['user_id'], $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addCommentReply($parentId, $comment) {
    global $db;
    
    // Önce üst yorumu al
    $stmt = $db->prepare('SELECT file_path, version_number, line_number FROM version_comments WHERE id = ?');
    $stmt->execute([$parentId]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$parent) {
        return false;
    }
    
    // Yanıtı ekle
    $stmt = $db->prepare('
        INSERT INTO version_comments 
        (user_id, file_path, version_number, line_number, comment, parent_id) 
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    
    return $stmt->execute([
        $_SESSION['user_id'],
        $parent['file_path'],
        $parent['version_number'],
        $parent['line_number'],
        $comment,
        $parentId
    ]);
}

function getCommentReplies($commentId) {
    global $db;
    
    $stmt = $db->prepare('
        SELECT c.*, u.username 
        FROM version_comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.parent_id = ? 
        ORDER BY c.created_at ASC
    ');
    $stmt->execute([$commentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCommentThread($commentId) {
    global $db;
    
    $stmt = $db->prepare('
        WITH RECURSIVE comment_thread AS (
            SELECT c.*, u.username, 0 as depth
            FROM version_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
            
            UNION ALL
            
            SELECT c.*, u.username, ct.depth + 1
            FROM version_comments c
            JOIN users u ON c.user_id = u.id
            JOIN comment_thread ct ON c.parent_id = ct.id
        )
        SELECT * FROM comment_thread ORDER BY depth, created_at
    ');
    $stmt->execute([$commentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 