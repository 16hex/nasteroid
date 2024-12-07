<?php
require_once 'includes/functions.php';
session_start();
checkAuth();

$filePath = $_GET['file'] ?? '';
$version1 = $_GET['v1'] ?? '';
$version2 = $_GET['v2'] ?? '';
$error = '';

if (!isValidPath($filePath) || !is_numeric($version1) || !is_numeric($version2)) {
    $error = 'Geçersiz parametreler.';
} else {
    $content1 = getVersionContent($filePath, $version1);
    $content2 = getVersionContent($filePath, $version2);
    $diff = compareVersions($filePath, $version1, $version2);
    
    // Dosya türünü belirle
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $language_map = [
        'php' => 'php',
        'js' => 'javascript',
        'css' => 'css',
        'html' => 'html',
        'xml' => 'xml',
        'json' => 'json',
        'md' => 'markdown',
        'py' => 'python',
        'java' => 'java',
        'cpp' => 'cpp',
        'c' => 'c',
        'sql' => 'sql',
        'sh' => 'bash',
        'yml' => 'yaml',
        'yaml' => 'yaml',
        'txt' => 'plaintext'
    ];
    $language = $language_map[$extension] ?? 'plaintext';
    
    if ($diff === false) {
        $error = 'Sürümler karşılaştırılırken bir hata oluştu.';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sürüm Karşılaştırma - NASteroid</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-okaidia.min.css">
    <style>
        .compare-view-controls {
            margin-bottom: 20px;
            text-align: right;
        }
        
        .view-mode-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .view-mode-btn.active {
            background-color: #2980b9;
        }
        
        .side-by-side {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .version-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .version-content pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            cursor: pointer;
        }
        
        .version-header {
            margin-bottom: 10px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        /* Satır numaraları için stil */
        .version-content {
            display: flex;
        }
        
        .line-numbers {
            padding: 15px 10px;
            border-right: 1px solid #ddd;
            background-color: #f1f1f1;
            color: #666;
            text-align: right;
            user-select: none;
            min-width: 40px;
        }
        
        .code-content {
            padding: 15px;
            flex: 1;
        }
        
        .line-numbers span,
        .code-content pre {
            font-family: monospace;
            line-height: 1.5;
        }
        
        /* Değişiklik vurgulama */
        .highlight-old {
            background-color: #ffecec;
        }
        
        .highlight-new {
            background-color: #eaffea;
        }
        
        /* Prism.js özelleştirmeleri */
        pre[class*="language-"] {
            margin: 0;
            background: transparent;
        }
        
        .code-content {
            background-color: #272822;
        }
        
        .highlight-old {
            background-color: rgba(255, 0, 0, 0.1);
        }
        
        .highlight-new {
            background-color: rgba(0, 255, 0, 0.1);
        }
        
        .line-numbers {
            background-color: #2d2d2d;
            color: #8f908a;
            border-right-color: #464646;
        }
        
        .code-content pre span {
            display: block;
            padding: 0 5px;
            transition: background-color 0.2s;
        }
        
        .code-content pre span:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .search-controls {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 15px;
        }
        
        .search-controls input {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }
        
        .search-controls button {
            padding: 5px 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-controls button:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }
        
        .line-controls {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 15px;
        }
        
        .line-controls input {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 80px;
        }
        
        .match-highlight {
            background-color: rgba(255, 255, 0, 0.3) !important;
        }
        
        .current-match {
            background-color: rgba(255, 165, 0, 0.5) !important;
        }
        
        /* İstatistik stilleri */
        .diff-stats {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .stats-header {
            color: #fff;
            margin-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background-color: #383838;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        .stat-label {
            color: #8f908a;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .stat-value {
            color: #fff;
            font-size: 1.5em;
            font-weight: bold;
        }
        
        /* Görselleştirme stilleri */
        .diff-visualization {
            margin-top: 20px;
        }
        
        .visualization-header {
            color: #8f908a;
            margin-bottom: 10px;
        }
        
        .visualization-content {
            display: flex;
            flex-wrap: wrap;
            gap: 2px;
            background-color: #383838;
            padding: 10px;
            border-radius: 6px;
        }
        
        .line-marker {
            width: 8px;
            height: 8px;
            background-color: #4a4a4a;
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .line-marker.changed {
            background-color: #e67e22;
        }
        
        .line-marker:hover {
            transform: scale(1.2);
        }
        
        /* Yorum stilleri */
        .comment-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
        }
        
        .comment-dialog textarea {
            width: 100%;
            min-width: 300px;
            min-height: 100px;
            margin: 10px 0;
            padding: 10px;
            background-color: #383838;
            border: 1px solid #464646;
            border-radius: 4px;
            color: #fff;
            resize: vertical;
        }
        
        .comment-dialog button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .comment-dialog .save-btn {
            background-color: #2ecc71;
            color: white;
        }
        
        .comment-dialog .cancel-btn {
            background-color: #e74c3c;
            color: white;
        }
        
        .line-comments {
            position: absolute;
            right: -300px;
            top: 0;
            width: 280px;
            background-color: #2d2d2d;
            border-radius: 8px;
            padding: 10px;
        }
        
        .comment {
            background-color: #383838;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        
        .comment-author {
            color: #2ecc71;
            font-weight: bold;
        }
        
        .comment-date {
            color: #8f908a;
        }
        
        .comment-content {
            color: #fff;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Sürüm Karşılaştırma</h1>
            <nav>
                <a href="index.php">Ana Dizin</a>
                <a href="details.php?file=<?php echo urlencode($filePath); ?>">Detaylara Dön</a>
            </nav>
        </header>

        <main>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <div class="compare-container">
                    <div class="diff-stats">
                        <div class="stats-header">
                            <h3>Değişiklik İstatistikleri</h3>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">Değişen Satırlar</div>
                                <div class="stat-value"><?php echo count($diff); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Eski Sürüm Satır Sayısı</div>
                                <div class="stat-value"><?php echo substr_count($content1, "\n") + 1; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Yeni Sürüm Satır Sayısı</div>
                                <div class="stat-value"><?php echo substr_count($content2, "\n") + 1; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Değişim Oranı</div>
                                <div class="stat-value">
                                    <?php 
                                    $total_lines = substr_count($content1, "\n") + 1;
                                    echo round((count($diff) / $total_lines) * 100, 1); 
                                    ?>%
                                </div>
                            </div>
                        </div>
                        <div class="diff-visualization">
                            <div class="visualization-header">Değişiklik Haritası</div>
                            <div class="visualization-content">
                                <?php
                                $total_lines = max(substr_count($content1, "\n"), substr_count($content2, "\n")) + 1;
                                $changed_lines = array_column($diff, 'line');
                                
                                for ($i = 1; $i <= $total_lines; $i++) {
                                    $is_changed = in_array($i, $changed_lines);
                                    echo '<div class="line-marker' . ($is_changed ? ' changed' : '') . '" 
                                              data-line="' . $i . '"
                                              onclick="goToLine(' . $i . ')"></div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="compare-view-controls">
                        <div class="search-controls">
                            <input type="text" id="searchInput" placeholder="Metin ara...">
                            <button id="prevMatch" disabled>↑</button>
                            <button id="nextMatch" disabled>↓</button>
                            <span id="matchInfo"></span>
                        </div>
                        <div class="line-controls">
                            <input type="number" id="lineInput" placeholder="Satır no...">
                            <button id="goToLine">Git</button>
                        </div>
                        <button class="view-mode-btn active" data-mode="diff">Farkları Göster</button>
                        <button class="view-mode-btn" data-mode="side">Yan Yana Göster</button>
                    </div>

                    <div class="compare-header">
                        <h2>v<?php echo $version1; ?> ile v<?php echo $version2; ?> arasındaki farklar</h2>
                    </div>
                    
                    <div id="diffView" class="diff-list">
                        <?php if (empty($diff)): ?>
                            <p class="no-diff">İki sürüm arasında fark bulunmamaktadır.</p>
                        <?php else: ?>
                            <?php foreach ($diff as $change): ?>
                                <div class="diff-item">
                                    <div class="diff-line">Satır <?php echo $change['line']; ?></div>
                                    <div class="diff-content">
                                        <div class="diff-old">
                                            <span class="diff-label">Eski:</span>
                                            <pre><?php echo htmlspecialchars($change['old']); ?></pre>
                                        </div>
                                        <div class="diff-new">
                                            <span class="diff-label">Yeni:</span>
                                            <pre><?php echo htmlspecialchars($change['new']); ?></pre>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div id="sideView" class="side-by-side" style="display: none;">
                        <div class="version-content">
                            <div class="version-header">v<?php echo $version1; ?></div>
                            <div class="code-wrapper">
                                <div class="line-numbers">
                                    <?php 
                                    $lines1 = explode("\n", $content1);
                                    for ($i = 1; $i <= count($lines1); $i++): 
                                    ?>
                                        <span><?php echo $i; ?></span><br>
                                    <?php endfor; ?>
                                </div>
                                <div class="code-content">
                                    <pre><code class="language-<?php echo $language; ?>"><?php
                                    foreach ($lines1 as $idx => $line) {
                                        $lineNum = $idx + 1;
                                        $class = '';
                                        foreach ($diff as $change) {
                                            if ($change['line'] == $lineNum && $change['old'] === $line) {
                                                $class = 'highlight-old';
                                                break;
                                            }
                                        }
                                        echo '<span class="' . $class . '" data-line="' . $lineNum . '" onclick="showCommentDialog(this, ' . $version1 . ')">' . 
                                             htmlspecialchars($line) . "</span>\n";
                                    }
                                    ?></code></pre>
                                    <div class="line-comments">
                                        <?php foreach (getVersionComments($filePath, $version1) as $comment): ?>
                                            <?php if ($comment['line_number']): ?>
                                                <div class="comment" data-line="<?php echo $comment['line_number']; ?>">
                                                    <div class="comment-header">
                                                        <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                                        <span class="comment-date"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></span>
                                                    </div>
                                                    <div class="comment-content"><?php echo htmlspecialchars($comment['comment']); ?></div>
                                                    <?php if (!isset($comment['parent_id'])): ?>
                                                        <div class="comment-actions">
                                                            <button onclick="showReplyDialog(<?php echo $comment['id']; ?>)">Yanıtla</button>
                                                        </div>
                                                        <?php $replies = getCommentReplies($comment['id']); ?>
                                                        <?php if (!empty($replies)): ?>
                                                            <div class="comment-replies">
                                                                <?php foreach ($replies as $reply): ?>
                                                                    <div class="reply">
                                                                        <div class="reply-header">
                                                                            <span class="reply-author"><?php echo htmlspecialchars($reply['username']); ?></span>
                                                                            <span class="reply-date"><?php echo date('d.m.Y H:i', strtotime($reply['created_at'])); ?></span>
                                                                        </div>
                                                                        <div class="reply-content"><?php echo htmlspecialchars($reply['comment']); ?></div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="version-content">
                            <div class="version-header">v<?php echo $version2; ?></div>
                            <div class="code-wrapper">
                                <div class="line-numbers">
                                    <?php 
                                    $lines2 = explode("\n", $content2);
                                    for ($i = 1; $i <= count($lines2); $i++): 
                                    ?>
                                        <span><?php echo $i; ?></span><br>
                                    <?php endfor; ?>
                                </div>
                                <div class="code-content">
                                    <pre><?php
                                    foreach ($lines2 as $idx => $line) {
                                        $lineNum = $idx + 1;
                                        $class = '';
                                        foreach ($diff as $change) {
                                            if ($change['line'] == $lineNum && $change['new'] === $line) {
                                                $class = 'highlight-new';
                                                break;
                                            }
                                        }
                                        echo '<span class="' . $class . '">' . htmlspecialchars($line) . "</span>\n";
                                    }
                                    ?></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-java.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-markdown.min.js"></script>
    <script>
    // Arama işlevselliği
    let currentMatches = [];
    let currentMatchIndex = -1;
    
    function searchInCode(searchText) {
        currentMatches = [];
        currentMatchIndex = -1;
        
        if (!searchText) {
            updateMatchHighlights();
            return;
        }
        
        document.querySelectorAll('.code-content pre span').forEach((line, index) => {
            if (line.textContent.toLowerCase().includes(searchText.toLowerCase())) {
                currentMatches.push(index);
            }
        });
        
        document.getElementById('matchInfo').textContent = 
            currentMatches.length ? `${currentMatches.length} eşleşme bulundu` : 'Eşleşme bulunamadı';
        
        document.getElementById('prevMatch').disabled = currentMatches.length === 0;
        document.getElementById('nextMatch').disabled = currentMatches.length === 0;
        
        if (currentMatches.length > 0) {
            goToMatch(0);
        } else {
            updateMatchHighlights();
        }
    }
    
    function updateMatchHighlights() {
        document.querySelectorAll('.match-highlight, .current-match').forEach(el => {
            el.classList.remove('match-highlight', 'current-match');
        });
        
        currentMatches.forEach((lineIndex, i) => {
            const lines = document.querySelectorAll('.code-content pre span');
            if (lines[lineIndex]) {
                lines[lineIndex].classList.add('match-highlight');
                if (i === currentMatchIndex) {
                    lines[lineIndex].classList.add('current-match');
                }
            }
        });
    }
    
    function goToMatch(index) {
        if (currentMatches.length === 0) return;
        
        currentMatchIndex = index;
        updateMatchHighlights();
        
        const currentLine = document.querySelectorAll('.code-content pre span')[currentMatches[currentMatchIndex]];
        currentLine.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    function goToLine(lineNumber) {
        const lines = document.querySelectorAll('.code-content pre span');
        if (lineNumber > 0 && lineNumber <= lines.length) {
            lines[lineNumber - 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    // Event listeners
    document.getElementById('searchInput').addEventListener('input', e => {
        searchInCode(e.target.value);
    });
    
    document.getElementById('prevMatch').addEventListener('click', () => {
        if (currentMatchIndex > 0) {
            goToMatch(currentMatchIndex - 1);
        } else {
            goToMatch(currentMatches.length - 1);
        }
    });
    
    document.getElementById('nextMatch').addEventListener('click', () => {
        if (currentMatchIndex < currentMatches.length - 1) {
            goToMatch(currentMatchIndex + 1);
        } else {
            goToMatch(0);
        }
    });
    
    document.getElementById('goToLine').addEventListener('click', () => {
        const lineNumber = parseInt(document.getElementById('lineInput').value);
        goToLine(lineNumber);
    });

    // Senkronize kaydırma
    const leftContent = document.querySelector('.side-by-side .version-content:first-child');
    const rightContent = document.querySelector('.side-by-side .version-content:last-child');
    let isScrolling = false;

    function syncScroll(source, target) {
        if (!isScrolling) {
            isScrolling = true;
            const scrollPercentage = source.scrollTop / (source.scrollHeight - source.clientHeight);
            target.scrollTop = scrollPercentage * (target.scrollHeight - target.clientHeight);
            setTimeout(() => isScrolling = false, 50);
        }
    }

    if (leftContent && rightContent) {
        leftContent.addEventListener('scroll', () => syncScroll(leftContent, rightContent));
        rightContent.addEventListener('scroll', () => syncScroll(rightContent, leftContent));
    }

    // Satır vurgulama
    const codeLines = document.querySelectorAll('.code-content pre span');
    codeLines.forEach(line => {
        line.addEventListener('mouseover', function() {
            const lineNumber = Array.from(this.parentNode.children).indexOf(this) + 1;
            document.querySelectorAll('.code-content pre span:nth-child(' + lineNumber + ')').forEach(l => {
                l.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            });
        });
        
        line.addEventListener('mouseout', function() {
            const lineNumber = Array.from(this.parentNode.children).indexOf(this) + 1;
            document.querySelectorAll('.code-content pre span:nth-child(' + lineNumber + ')').forEach(l => {
                l.style.backgroundColor = '';
            });
        });
    });

    document.querySelectorAll('.view-mode-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const mode = this.dataset.mode;
            document.querySelectorAll('.view-mode-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            if (mode === 'diff') {
                document.getElementById('diffView').style.display = 'grid';
                document.getElementById('sideView').style.display = 'none';
            } else {
                document.getElementById('diffView').style.display = 'none';
                document.getElementById('sideView').style.display = 'grid';
                Prism.highlightAll();
            }
        });
    });

    // Görselleştirme etkileşimleri
    document.querySelectorAll('.line-marker').forEach(marker => {
        marker.addEventListener('mouseover', function() {
            const lineNumber = this.dataset.line;
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = `Satır ${lineNumber}`;
            this.appendChild(tooltip);
        });
        
        marker.addEventListener('mouseout', function() {
            const tooltip = this.querySelector('.tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });

    // Yorum işlevselliği
    function showCommentDialog(element, versionNumber) {
        const lineNumber = element.dataset.line;
        const dialog = document.createElement('div');
        dialog.className = 'comment-dialog';
        dialog.innerHTML = `
            <h3>Satır ${lineNumber} için yorum ekle</h3>
            <textarea placeholder="Yorumunuzu yazın..."></textarea>
            <div>
                <button class="save-btn" onclick="saveComment(this, '${lineNumber}', ${versionNumber})">Kaydet</button>
                <button class="cancel-btn" onclick="this.parentElement.parentElement.remove()">İptal</button>
            </div>
        `;
        document.body.appendChild(dialog);
    }

    async function saveComment(button, lineNumber, versionNumber) {
        const dialog = button.parentElement.parentElement;
        const comment = dialog.querySelector('textarea').value;
        
        if (!comment.trim()) {
            alert('Lütfen bir yorum yazın.');
            return;
        }
        
        const formData = new FormData();
        formData.append('file_path', '<?php echo htmlspecialchars($filePath); ?>');
        formData.append('version_number', versionNumber);
        formData.append('line_number', lineNumber);
        formData.append('comment', comment);
        
        try {
            const response = await fetch('add_comment.php', {
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
        
        dialog.remove();
    }

    // Yorum yanıtlama işlevselliği
    function showReplyDialog(commentId) {
        const dialog = document.createElement('div');
        dialog.className = 'comment-dialog';
        dialog.innerHTML = `
            <h3>Yorumu Yanıtla</h3>
            <textarea placeholder="Yanıtınızı yazın..."></textarea>
            <div>
                <button class="save-btn" onclick="saveReply(this, ${commentId})">Gönder</button>
                <button class="cancel-btn" onclick="this.parentElement.parentElement.remove()">İptal</button>
            </div>
        `;
        document.body.appendChild(dialog);
    }

    async function saveReply(button, commentId) {
        const dialog = button.parentElement.parentElement;
        const comment = dialog.querySelector('textarea').value;
        
        if (!comment.trim()) {
            alert('Lütfen bir yanıt yazın.');
            return;
        }
        
        const formData = new FormData();
        formData.append('parent_id', commentId);
        formData.append('comment', comment);
        
        try {
            const response = await fetch('add_reply.php', {
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
        
        dialog.remove();
    }
    </script>
</body>
</html> 