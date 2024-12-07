<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$path = $_GET['path'] ?? '';
$error = '';
$details = null;

if (!empty($path) && isValidPath($path)) {
    $details = getFileDetails($path);
    if (!$details) {
        $error = 'Dosya veya klasör bulunamadı.';
    }
} else {
    $error = 'Geçersiz dosya yolu.';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dosya Detayları - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Dosya Detayları</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
                <div class="user-nav">
                    <span class="username"><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a href="logout.php" class="logout">Çıkış Yap</a>
                </div>
            </nav>
        </header>

        <main>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($details): ?>
                <div class="details-container">
                    <h2><?php echo htmlspecialchars($details['name']); ?></h2>
                    
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Tür:</div>
                            <div class="detail-value"><?php echo $details['type'] === 'dir' ? 'Klasör' : 'Dosya'; ?></div>
                        </div>
                        
                        <?php if ($details['type'] === 'file'): ?>
                        <div class="detail-item">
                            <div class="detail-label">MIME Türü:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($details['mime_type']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Boyut:</div>
                            <div class="detail-value"><?php echo formatFileSize($details['size']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <div class="detail-label">Oluşturulma:</div>
                            <div class="detail-value"><?php echo formatTimestamp($details['created']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Değiştirilme:</div>
                            <div class="detail-value"><?php echo formatTimestamp($details['modified']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Son Erişim:</div>
                            <div class="detail-value"><?php echo formatTimestamp($details['accessed']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">İzinler:</div>
                            <div class="detail-value">
                                <div class="permissions-info">
                                    <span class="permission-code"><?php echo $details['permissions']; ?></span>
                                    <?php foreach (getPermissionsDescription($details['permissions']) as $desc): ?>
                                        <div class="permission-desc"><?php echo htmlspecialchars($desc); ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="change-permissions-btn" onclick="showPermissionsModal()">İzinleri Değiştir</button>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Sahip:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($details['owner']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Grup:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($details['group']); ?></div>
                        </div>
                    </div>
                    
                    <div class="versions-section">
                        <h3>Dosya Sürümleri</h3>
                        <?php if ($details['type'] === 'file'): ?>
                            <div class="version-controls">
                                <button class="create-version-btn" onclick="showCreateVersionModal()">Yeni Sürüm Olu��tur</button>
                            </div>
                            <?php $versions = getVersions($details['path']); ?>
                            <?php if (!empty($versions)): ?>
                                <div class="versions-list">
                                    <?php foreach ($versions as $version): ?>
                                        <div class="version-item">
                                            <div class="version-info">
                                                <span class="version-number">v<?php echo $version['version_number']; ?></span>
                                                <span class="version-date"><?php echo date('d.m.Y H:i', strtotime($version['created_at'])); ?></span>
                                                <span class="version-user"><?php echo htmlspecialchars($version['username']); ?></span>
                                                <?php if ($version['comment']): ?>
                                                    <p class="version-comment"><?php echo htmlspecialchars($version['comment']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="version-actions">
                                                <?php if ($version['version_number'] > 1): ?>
                                                    <a href="compare_versions.php?file=<?php echo urlencode($details['path']); ?>&v1=<?php echo $version['version_number']-1; ?>&v2=<?php echo $version['version_number']; ?>" 
                                                       class="compare-btn">
                                                        Önceki ile Karşılaştır
                                                    </a>
                                                <?php endif; ?>
                                                <button onclick="restoreVersion(<?php echo $version['version_number']; ?>)">Geri Yükle</button>
                                                <button onclick="deleteVersion(<?php echo $version['version_number']; ?>)" class="delete-btn">Sil</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>Henüz sürüm oluşturulmamış.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tags-section">
                        <h3>Etiketler</h3>
                        <?php $tags = getFileTags($details['path']); ?>
                        <div class="current-tags">
                            <?php foreach ($tags as $tag): ?>
                                <div class="tag-item">
                                    <?php echo htmlspecialchars($tag); ?>
                                    <form method="post" action="tags.php" class="remove-tag-form">
                                        <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($details['path']); ?>">
                                        <input type="hidden" name="tag_name" value="<?php echo htmlspecialchars($tag); ?>">
                                        <button type="submit" name="remove_tag" class="remove-tag-btn">&times;</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="post" action="tags.php" class="add-tag-form">
                            <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($details['path']); ?>">
                            <div class="tag-input-container">
                                <input type="text" 
                                       name="tag_name" 
                                       id="tagInput" 
                                       placeholder="Yeni etiket..." 
                                       autocomplete="off"
                                       required>
                                <div id="tagSuggestions" class="tag-suggestions"></div>
                            </div>
                            <div class="tag-suggestions-container">
                                <h4>Önerilen Etiketler:</h4>
                                <div id="suggestedTags" class="suggested-tags"></div>
                            </div>
                            <button type="submit" name="add_tag">Ekle</button>
                        </form>
                    </div>
                    
                    <div class="details-actions">
                        <?php if ($details['type'] === 'file'): ?>
                            <a href="preview.php?file=<?php echo urlencode($details['path']); ?>" class="action-btn">Önizle</a>
                            <a href="download.php?file=<?php echo urlencode($details['path']); ?>" class="action-btn">İndir</a>
                        <?php endif; ?>
                        <button class="rename-btn" onclick="showRenameModal()">Yeniden Adlandır</button>
                        <button class="copy-btn" onclick="showCopyMoveModal('copy')">Kopyala</button>
                        <button class="move-btn" onclick="showCopyMoveModal('move')">Taşı</button>
                        <button class="backup-btn" onclick="createBackup('<?php echo htmlspecialchars(addslashes($details['path'])); ?>')">Yedekle</button>
                        <?php if ($details['type'] === 'dir' || pathinfo($details['name'], PATHINFO_EXTENSION) === 'zip'): ?>
                            <button class="archive-btn" onclick="handleArchive('<?php echo $details['type'] === 'dir' ? 'compress' : 'extract'; ?>')">
                                <?php echo $details['type'] === 'dir' ? 'Sıkıştır' : 'Çıkart'; ?>
                            </button>
                        <?php endif; ?>
                        <button class="delete-btn" onclick="deleteItem('<?php echo htmlspecialchars(addslashes($details['path'])); ?>')">Sil</button>
                        <button class="share-btn" onclick="shareFile('<?php echo htmlspecialchars(addslashes($details['path'])); ?>')">Paylaş</button>
                        <a href="index.php" class="back-btn">Geri Dön</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- İzin Değiştirme Modal -->
    <div id="permissionsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePermissionsModal()">&times;</span>
            <h2>İzinleri Değiştir</h2>
            <form id="permissionsForm" onsubmit="return changePermissions(event)">
                <input type="hidden" name="path" value="<?php echo htmlspecialchars($details['path']); ?>">
                <div class="permissions-grid">
                    <div class="permissions-row">
                        <label>Sahip:</label>
                        <select name="owner" required>
                            <?php for ($i = 0; $i <= 7; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="permissions-row">
                        <label>Grup:</label>
                        <select name="group" required>
                            <?php for ($i = 0; $i <= 7; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="permissions-row">
                        <label>Diğer:</label>
                        <select name="other" required>
                            <?php for ($i = 0; $i <= 7; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <button type="submit">İzinleri Kaydet</button>
            </form>
        </div>
    </div>

    <!-- Yeniden Adlandırma Modal -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRenameModal()">&times;</span>
            <h2>Yeniden Adlandır</h2>
            <form id="renameForm" onsubmit="return renameItem(event)">
                <input type="hidden" name="old_path" value="<?php echo htmlspecialchars($details['path']); ?>">
                <div class="form-group">
                    <label for="new_name">Yeni İsim:</label>
                    <input type="text" 
                           id="new_name" 
                           name="new_name" 
                           value="<?php echo htmlspecialchars($details['name']); ?>"
                           pattern="[a-zA-Z0-9-_.]+"
                           title="Sadece harf, rakam, tire, nokta ve alt çizgi kullanabilirsiniz"
                           required>
                </div>
                <button type="submit">Kaydet</button>
            </form>
        </div>
    </div>

    <!-- Kopyala/Taşı Modal -->
    <div id="copyMoveModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCopyMoveModal()">&times;</span>
            <h2 id="copyMoveTitle">Kopyala</h2>
            <form id="copyMoveForm" onsubmit="return handleCopyMove(event)">
                <input type="hidden" name="source_path" value="<?php echo htmlspecialchars($details['path']); ?>">
                <input type="hidden" name="operation" id="operation" value="">
                <div class="form-group">
                    <label for="dest_path">Hedef Klasör:</label>
                    <select name="dest_path" id="dest_path" required>
                        <option value="/">/</option>
                        <?php foreach (getDirectories() as $dir): ?>
                            <option value="<?php echo htmlspecialchars($dir['path']); ?>">
                                <?php echo htmlspecialchars($dir['path']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Uygula</button>
            </form>
        </div>
    </div>

    <!-- Sürüm Oluşturma Modal -->
    <div id="createVersionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCreateVersionModal()">&times;</span>
            <h2>Yeni Sürüm Oluştur</h2>
            <form id="createVersionForm" onsubmit="return handleCreateVersion(event)">
                <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($details['path']); ?>">
                <div class="form-group">
                    <label for="version_comment">Sürüm Açıklaması:</label>
                    <textarea name="comment" 
                              id="version_comment" 
                              rows="4" 
                              placeholder="Bu sürümde yapılan değişiklikleri açıklayın..."
                              required></textarea>
                </div>
                <button type="submit">Sürüm Oluştur</button>
            </form>
        </div>
    </div>

    <script>
    async function deleteItem(path) {
        if (confirm('Bu öğeyi silmek istediğinizden emin misiniz?')) {
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
                    window.location.href = 'index.php';
                }
            } catch (error) {
                alert('Bir hata oluştu.');
            }
        }
    }

    const permissionsModal = document.getElementById('permissionsModal');

    function showPermissionsModal() {
        permissionsModal.style.display = 'block';
        
        // Mevcut izinleri form'a yerleştir
        const currentPerms = '<?php echo $details['permissions']; ?>'.substr(-3);
        document.querySelector('select[name="owner"]').value = currentPerms[0];
        document.querySelector('select[name="group"]').value = currentPerms[1];
        document.querySelector('select[name="other"]').value = currentPerms[2];
    }

    function closePermissionsModal() {
        permissionsModal.style.display = 'none';
    }

    async function changePermissions(event) {
        event.preventDefault();
        const form = event.target;
        const permissions = form.owner.value + form.group.value + form.other.value;
        
        const formData = new FormData();
        formData.append('path', form.path.value);
        formData.append('permissions', permissions);
        
        try {
            const response = await fetch('change_permissions.php', {
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
        
        return false;
    }

    const renameModal = document.getElementById('renameModal');

    function showRenameModal() {
        renameModal.style.display = 'block';
    }

    function closeRenameModal() {
        renameModal.style.display = 'none';
    }

    async function renameItem(event) {
        event.preventDefault();
        const form = event.target;
        
        const formData = new FormData();
        formData.append('old_path', form.old_path.value);
        formData.append('new_name', form.new_name.value);
        
        try {
            const response = await fetch('rename.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            alert(result.message);
            
            if (result.success) {
                window.location.href = 'index.php';
            }
        } catch (error) {
            alert('Bir hata oluştu.');
        }
        
        return false;
    }

    const copyMoveModal = document.getElementById('copyMoveModal');
    const copyMoveTitle = document.getElementById('copyMoveTitle');
    const operationInput = document.getElementById('operation');

    function showCopyMoveModal(operation) {
        copyMoveModal.style.display = 'block';
        operationInput.value = operation;
        copyMoveTitle.textContent = operation === 'copy' ? 'Kopyala' : 'Taşı';
    }

    function closeCopyMoveModal() {
        copyMoveModal.style.display = 'none';
    }

    async function handleCopyMove(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('copy_move.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            alert(result.message);
            
            if (result.success) {
                window.location.href = 'index.php';
            }
        } catch (error) {
            alert('Bir hata oluştu.');
        }
        
        return false;
    }

    async function handleArchive(operation) {
        const formData = new FormData();
        formData.append('source_path', '<?php echo htmlspecialchars($details['path']); ?>');
        formData.append('operation', operation);
        
        try {
            const response = await fetch('archive.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            alert(result.message);
            
            if (result.success) {
                window.location.href = 'index.php';
            }
        } catch (error) {
            alert('Bir hata oluştu.');
        }
    }

    async function createBackup(path) {
        if (confirm('Bu öğeyi yedeklemek istediğinizden emin misiniz?')) {
            const formData = new FormData();
            formData.append('source_path', path);
            formData.append('create_backup', '1');
            
            try {
                const response = await fetch('backup.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                alert('Yedekleme başarıyla oluşturuldu.');
                window.location.href = 'backup.php';
            } catch (error) {
                alert('Bir hata oluştu.');
            }
        }
    }

    // Etiket otomatik tamamlama
    const tagInput = document.getElementById('tagInput');
    const tagSuggestions = document.getElementById('tagSuggestions');
    let currentFocus = -1;
    
    tagInput.addEventListener('input', async function() {
        const query = this.value.trim();
        if (query.length < 1) {
            tagSuggestions.innerHTML = '';
            return;
        }
        
        try {
            const response = await fetch(`search_tags.php?q=${encodeURIComponent(query)}`);
            const tags = await response.json();
            
            let html = '';
            tags.forEach((tag, index) => {
                html += `<div class="tag-suggestion" data-index="${index}">${tag}</div>`;
            });
            
            tagSuggestions.innerHTML = html;
            
            // Önerileri tıklanabilir yap
            document.querySelectorAll('.tag-suggestion').forEach(item => {
                item.addEventListener('click', function() {
                    tagInput.value = this.textContent;
                    tagSuggestions.innerHTML = '';
                });
            });
        } catch (error) {
            console.error('Etiket arama hatası:', error);
        }
    });

    // Klavye navigasyonu
    tagInput.addEventListener('keydown', function(e) {
        const suggestions = tagSuggestions.getElementsByClassName('tag-suggestion');
        if (suggestions.length === 0) return;
        
        if (e.key === 'ArrowDown') {
            currentFocus++;
            if (currentFocus >= suggestions.length) currentFocus = 0;
            setActiveSuggestion(suggestions);
        } else if (e.key === 'ArrowUp') {
            currentFocus--;
            if (currentFocus < 0) currentFocus = suggestions.length - 1;
            setActiveSuggestion(suggestions);
        } else if (e.key === 'Enter' && currentFocus > -1) {
            e.preventDefault();
            if (suggestions[currentFocus]) {
                tagInput.value = suggestions[currentFocus].textContent;
                tagSuggestions.innerHTML = '';
            }
        }
    });

    function setActiveSuggestion(suggestions) {
        Array.from(suggestions).forEach((item, index) => {
            item.classList.remove('active');
            if (index === currentFocus) {
                item.classList.add('active');
            }
        });
    }

    // Dışarı tıklandığında önerileri kapat
    document.addEventListener('click', function(e) {
        if (!tagInput.contains(e.target) && !tagSuggestions.contains(e.target)) {
            tagSuggestions.innerHTML = '';
        }
    });

    // Etiket önerilerini yükle
    async function loadTagSuggestions() {
        const filePath = '<?php echo htmlspecialchars(addslashes($details['path'])); ?>';
        try {
            const response = await fetch(`suggest_tags.php?file=${encodeURIComponent(filePath)}`);
            const suggestions = await response.json();
            
            const container = document.getElementById('suggestedTags');
            container.innerHTML = suggestions.map(tag => `
                <span class="suggested-tag" onclick="useTag('${tag}')">${tag}</span>
            `).join('');
        } catch (error) {
            console.error('Etiket önerileri yüklenirken hata:', error);
        }
    }

    function useTag(tag) {
        document.getElementById('tagInput').value = tag;
    }

    // Sayfa yüklendiğinde önerileri yükle
    document.addEventListener('DOMContentLoaded', loadTagSuggestions);

    // Sürüm kontrol işlemleri
    const createVersionModal = document.getElementById('createVersionModal');

    function showCreateVersionModal() {
        createVersionModal.style.display = 'block';
    }

    function closeCreateVersionModal() {
        createVersionModal.style.display = 'none';
    }

    async function handleCreateVersion(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('create_version.php', {
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
        
        return false;
    }

    async function restoreVersion(versionNumber) {
        if (confirm('Bu sürümü geri yüklemek istediğinizden emin misiniz?')) {
            const formData = new FormData();
            formData.append('file_path', '<?php echo htmlspecialchars($details['path']); ?>');
            formData.append('version_number', versionNumber);
            
            try {
                const response = await fetch('restore_version.php', {
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
    }

    async function deleteVersion(versionNumber) {
        if (confirm('Bu sürümü silmek istediğinizden emin misiniz?')) {
            const formData = new FormData();
            formData.append('file_path', '<?php echo htmlspecialchars($details['path']); ?>');
            formData.append('version_number', versionNumber);
            
            try {
                const response = await fetch('delete_version.php', {
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
    }
    </script>
</body>
</html> 