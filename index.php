<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO('sqlite:/workspaces/dealership-tool/database.sqlite');
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dealership Creative Tool</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>🚗 Dealership Creative Tool</h1>
        <p>Generate dealership creatives in bulk</p>
    </header>
    <div class="form-card">

        <!-- Step 1: Brand -->
        <div class="form-group">
            <label>1. Select Brand</label>
            <select id="brandSelect">
                <option value="">-- Select a Brand --</option>
                <?php foreach($brands as $brand): ?>
                <option value="<?= $brand['id'] ?>"><?= $brand['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Step 2: Dealerships -->
        <div class="form-group" id="dealershipGroup" style="display:none">
            <label>2. Select Dealership(s)</label>
            <div class="select-actions">
                <button type="button" onclick="selectAll()">Select All</button>
                <button type="button" onclick="deselectAll()">Deselect All</button>
            </div>
            <div id="dealershipList" class="dealership-list"></div>
        </div>

        <!-- Step 3: Logo -->
        <div class="form-group">
            <label>3. Include Logo?</label>
            <div class="toggle-group">
                <label class="toggle">
                    <input type="checkbox" id="logoToggle" checked>
                    <span class="slider"></span>
                </label>
                <span id="logoToggleLabel">Yes</span>
            </div>
            <div id="logoOptions" class="logo-options">
                <label><input type="radio" name="logoType" value="logo_dark" checked> Dark Logo</label>
                <label><input type="radio" name="logoType" value="logo_light"> Light Logo</label>
            </div>
        </div>

        <!-- Step 4: Background -->
        <div class="form-group">
            <label>4. Upload Background Image</label>
            <div class="upload-area" id="uploadArea">
                <input type="file" id="bgImage" accept="image/jpeg,image/png" hidden>
                <div onclick="document.getElementById('bgImage').click()">
                    <p>📁 Click to upload JPG or PNG</p>
                    <p class="hint">Original quality maintained</p>
                </div>
                <img id="previewImg" src="" alt="Preview" style="display:none">
            </div>
        </div>

        <!-- Step 5: Format -->
        <div class="form-group">
            <label>5. Output Format</label>
            <div class="format-group">
                <label class="format-option">
                    <input type="checkbox" name="format" value="1080x1080" checked>
                    Instagram Post (1080×1080)
                </label>
                <label class="format-option">
                    <input type="checkbox" name="format" value="1080x1350">
                    Instagram Post (1080×1350)
                </label>
                <label class="format-option">
                    <input type="checkbox" name="format" value="1080x1920">
                    Instagram Story (1080×1920)
                </label>
            </div>
        </div>

        <!-- Generate -->
        <div class="form-group">
            <button id="generateBtn" onclick="generateCreatives()">⚡ Generate Creatives</button>
        </div>

        <!-- Progress -->
        <div id="progressArea" style="display:none">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <p id="progressText">Generating...</p>
        </div>

        <!-- Download -->
        <div id="downloadArea" style="display:none">
            <a id="downloadLink" href="#" class="download-btn">📦 Download ZIP</a>
        </div>
        <div id="individualDownloads" style="margin-top:15px"></div>

    </div>
</div>
<script src="js/app.js?v=4"></script>
</body>
</html>