<?php
require_once 'includes/functions.php';
session_start();

// Kimlik doğrulama kontrolü
checkAuth();

$message = '';
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        
        // Hata ayıklama
        error_log("Dosya yükleme başladı: " . $file['name']);
        
        // Güvenlik kontrolleri
        if (!isValidPath($current_dir)) {
            $message = 'Geçersiz dizin yolu.';
            error_log("Geçersiz dizin yolu: " . $current_dir);
        } 
        elseif (!isAllowedFileType($file['name'])) {
            $message = 'Bu dosya türüne izin verilmiyor.';
            error_log("Geçersiz dosya türü: " . $file['name']);
        }
        else {
            $target_dir = '/var/www/html/storage';
            if (!empty($current_dir) && $current_dir !== '/') {
                $target_dir .= '/' . trim($current_dir, '/');
            }
            error_log("Hedef dizin: " . $target_dir);
            
            if (!file_exists($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    error_log("Dizin oluşturma hatası: " . error_get_last()['message']);
                    $message = 'Dizin oluşturulamadı.';
                    error_log("Dizin oluşturulamadı: " . $target_dir);
                }
            }
            
            $target_file = $target_dir . '/' . basename($file['name']);
            error_log("Hedef dosya: " . $target_file);
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                chmod($target_file, 0666);
                $message = 'Dosya başarıyla yüklendi.';
                error_log("Dosya başarıyla yüklendi: " . $target_file);
                
                // Başarılı yükleme sonrası ana dizine yönlendir
                header('Location: index.php?dir=' . urlencode($current_dir));
                exit;
            } else {
                $message = 'Dosya yüklenirken bir hata oluştu.';
                error_log("Dosya yükleme hatası: " . error_get_last()['message']);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dosya Yükle - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .drop-zone {
            border: 2px dashed #2980b9;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
            border-radius: 8px;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        .drop-zone.dragover {
            background-color: #ecf0f1;
            border-color: #27ae60;
        }
        
        .upload-progress {
            margin-top: 20px;
            display: none;
        }
        
        .progress-bar {
            height: 20px;
            background-color: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-bar-fill {
            height: 100%;
            background-color: #2980b9;
            width: 0%;
            transition: width 0.3s;
        }
        
        .upload-list {
            margin-top: 20px;
        }
        
        .upload-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .upload-item-name {
            flex: 1;
        }
        
        .upload-item-status {
            color: #666;
        }
        
        .upload-item-status.success {
            color: #27ae60;
        }
        
        .upload-item-status.error {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Dosya Yükle</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
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

            <form id="uploadForm" class="upload-form">
                <div class="drop-zone" id="dropZone">
                    <p>Dosyaları buraya sürükleyin veya seçmek için tıklayın</p>
                    <div class="form-group">
                        <input type="file" name="files[]" id="fileInput" multiple style="display: none;">
                    </div>
                </div>
                <div class="upload-progress" id="uploadProgress">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="progressBarFill"></div>
                    </div>
                    <p id="uploadStatus"></p>
                </div>
                <div class="upload-list" id="uploadList"></div>
            </form>
        </main>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBarFill = document.getElementById('progressBarFill');
        const uploadStatus = document.getElementById('uploadStatus');
        const uploadList = document.getElementById('uploadList');

        // Sürükle-bırak olayları
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('dragover');
        }

        function unhighlight(e) {
            dropZone.classList.remove('dragover');
        }

        dropZone.addEventListener('drop', handleDrop, false);
        dropZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFiles);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles({ target: { files } });
        }

        async function handleFiles(e) {
            const files = [...e.target.files];
            uploadProgress.style.display = 'block';
            let uploadedCount = 0;

            for (const file of files) {
                const listItem = document.createElement('div');
                listItem.className = 'upload-item';
                listItem.innerHTML = `
                    <div class="upload-item-name">${file.name}</div>
                    <div class="upload-item-status">Yükleniyor...</div>
                `;
                uploadList.appendChild(listItem);

                const formData = new FormData();
                formData.append('file', file);
                formData.append('dir', '<?php echo $current_dir; ?>');

                try {
                    const response = await fetch('upload.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.text();
                    uploadedCount++;

                    const progress = (uploadedCount / files.length) * 100;
                    progressBarFill.style.width = progress + '%';
                    uploadStatus.textContent = `${uploadedCount}/${files.length} dosya yüklendi`;

                    const statusElement = listItem.querySelector('.upload-item-status');
                    if (response.ok) {
                        statusElement.textContent = 'Başarılı';
                        statusElement.classList.add('success');
                    } else {
                        statusElement.textContent = 'Hata';
                        statusElement.classList.add('error');
                    }

                    if (uploadedCount === files.length) {
                        setTimeout(() => {
                            window.location.href = 'index.php?dir=<?php echo urlencode($current_dir); ?>';
                        }, 1000);
                    }
                } catch (error) {
                    const statusElement = listItem.querySelector('.upload-item-status');
                    statusElement.textContent = 'Hata';
                    statusElement.classList.add('error');
                }
            }
        }
    </script>
</body>
</html> 