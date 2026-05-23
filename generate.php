<?php
require_once dirname(__FILE__) . '/config.php';
header('Content-Type: application/json');

$baseDir   = dirname(__FILE__) . '/';
$uploadDir = $baseDir . 'uploads/';
$outputDir = $baseDir . 'output/';

// Get POST data
$dealershipIds = json_decode($_POST['dealerships'] ?? '[]', true);
$formats       = json_decode($_POST['formats'] ?? '[]', true);
$logoEnabled   = ($_POST['logo_enabled'] ?? '0') === '1';
$logoType      = $_POST['logo_type'] ?? 'logo_dark';

// Validate background image
if (!isset($_FILES['bg_image']) || $_FILES['bg_image']['error'] !== 0) {
    echo json_encode(['error' => 'No background image uploaded']);
    exit;
}

if (empty($dealershipIds)) {
    echo json_encode(['error' => 'No dealerships selected']);
    exit;
}

$bgExt  = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
$bgPath = $uploadDir . 'bg_' . time() . '.' . $bgExt;
move_uploaded_file($_FILES['bg_image']['tmp_name'], $bgPath);

$bgImage = ($bgExt === 'png') ? imagecreatefrompng($bgPath) : imagecreatefromjpeg($bgPath);
if (!$bgImage) {
    echo json_encode(['error' => 'Could not load background image']);
    exit;
}

// Connect to DB
$pdo = new PDO('sqlite:' . $baseDir . 'database.sqlite');

// Output format sizes
$sizes = [
    '1080x1080' => [1080, 1080],
    '1080x1350' => [1080, 1350],
    '1080x1920' => [1080, 1920],
];

array_map('unlink', glob($outputDir . '*.jpg'));
array_map('unlink', glob($outputDir . '*.png'));

$generatedFiles = [];

foreach ($dealershipIds as $dealershipId) {
    // Get dealership info
    $stmt = $pdo->prepare("SELECT d.name, d.slug FROM dealerships d WHERE d.id = ?");
    $stmt->execute([$dealershipId]);
    $dealership = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dealership) continue;

    // Get assets
    $stmt = $pdo->prepare("SELECT asset_type, file_path FROM dealership_assets WHERE dealership_id = ?");
    $stmt->execute([$dealershipId]);
    $assets = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $assets[$row['asset_type']] = $row['file_path'];
    }

    $panelPath = $baseDir . ($assets['panel'] ?? '');
    $logoPath  = $baseDir . ($assets[$logoType] ?? $assets['logo_dark'] ?? '');

    foreach ($formats as $format) {
        if (!isset($sizes[$format])) continue;
        [$canvasW, $canvasH] = $sizes[$format];

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $bgW = imagesx($bgImage);
        $bgH = imagesy($bgImage);

        $scale = max($canvasW / $bgW, $canvasH / $bgH);
        $newW  = (int)($bgW * $scale);
        $newH  = (int)($bgH * $scale);
        $offsetX = (int)(($canvasW - $newW) / 2);
        $offsetY = (int)(($canvasH - $newH) / 2);

        imagecopyresampled($canvas, $bgImage, $offsetX, $offsetY, 0, 0, $newW, $newH, $bgW, $bgH);

        $panelY = smartPanelPosition($canvas, $canvasW, $canvasH);

        if (file_exists($panelPath)) {
            $panel = imagecreatefrompng($panelPath);
            if ($panel) {
                imagealphablending($panel, true);
                $pW = imagesx($panel);
                $pH = imagesy($panel);

                $pScale  = $canvasW / $pW;
                $pNewW   = $canvasW;
                $pNewH   = (int)($pH * $pScale);

                $scaledPanel = imagecreatetruecolor($pNewW, $pNewH);
                imagealphablending($scaledPanel, false);
                imagesavealpha($scaledPanel, true);
                $transparent = imagecolorallocatealpha($scaledPanel, 0, 0, 0, 127);
                imagefill($scaledPanel, 0, 0, $transparent);
                imagealphablending($scaledPanel, true);

                imagecopyresampled($scaledPanel, $panel, 0, 0, 0, 0, $pNewW, $pNewH, $pW, $pH);
                imagecopy($canvas, $scaledPanel, 0, $panelY, 0, 0, $pNewW, $pNewH);

                imagedestroy($panel);
                imagedestroy($scaledPanel);
            }
        }

        if ($logoEnabled && file_exists($logoPath)) {
            $logo = imagecreatefrompng($logoPath);
            if ($logo) {
                imagealphablending($logo, true);
                $lW = imagesx($logo);
                $lH = imagesy($logo);

                $lScale  = ($canvasW * 0.25) / $lW;
                $lNewW   = (int)($lW * $lScale);
                $lNewH   = (int)($lH * $lScale);

                $scaledLogo = imagecreatetruecolor($lNewW, $lNewH);
                imagealphablending($scaledLogo, false);
                imagesavealpha($scaledLogo, true);
                $transparent = imagecolorallocatealpha($scaledLogo, 0, 0, 0, 127);
                imagefill($scaledLogo, 0, 0, $transparent);
                imagealphablending($scaledLogo, true);

                imagecopyresampled($scaledLogo, $logo, 0, 0, 0, 0, $lNewW, $lNewH, $lW, $lH);

                $padding = (int)($canvasW * 0.04);
                $lX = $padding;
                $lY = $canvasH - $lNewH - $padding;

                imagecopy($canvas, $scaledLogo, $lX, $lY, 0, 0, $lNewW, $lNewH);

                imagedestroy($logo);
                imagedestroy($scaledLogo);
            }
        }

        $filename = $dealership['slug'] . '_' . $format . '_' . time() . '.jpg';
        $filepath = $outputDir . $filename;
        imagejpeg($canvas, $filepath, 95);
        imagedestroy($canvas);

        $generatedFiles[] = $filepath;
    }
}

imagedestroy($bgImage);

//Create ZIP
$zipPath = $outputDir . 'creatives_' . time() . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE);
foreach ($generatedFiles as $file) {
    $zip->addFile($file, basename($file));
}
$zip->close();

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$zipRelative = 'output/' . basename($zipPath);
$fileList = [];
foreach ($generatedFiles as $file) {
    $fileList[] = [
        'name' => basename($file),
        'url'  => 'output/' . basename($file)
    ];
}

echo json_encode([
    'success'  => true,
    'count'    => count($generatedFiles),
    'zip_url'  => 'output/' . basename($zipPath),
    'files'    => $fileList,
]);

// Panel Positioning using Groq AI
function smartPanelPosition($image, $canvasW, $canvasH) {
    $sampleStepX = max(1, (int)($canvasW / 20));
    $sampleStepY = max(1, (int)($canvasH / 20));

    $topBrightness    = 0;
    $bottomBrightness = 0;
    $topSamples       = 0;
    $bottomSamples    = 0;

    for ($y = 0; $y < $canvasH / 2; $y += $sampleStepY) {
        for ($x = 0; $x < $canvasW; $x += $sampleStepX) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8)  & 0xFF;
            $b = $rgb & 0xFF;
            $topBrightness += (0.299 * $r + 0.587 * $g + 0.114 * $b);
            $topSamples++;
        }
    }

    for ($y = (int)($canvasH / 2); $y < $canvasH; $y += $sampleStepY) {
        for ($x = 0; $x < $canvasW; $x += $sampleStepX) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8)  & 0xFF;
            $b = $rgb & 0xFF;
            $bottomBrightness += (0.299 * $r + 0.587 * $g + 0.114 * $b);
            $bottomSamples++;
        }
    }

    $avgTop    = $topSamples > 0 ? $topBrightness / $topSamples : 128;
    $avgBottom = $bottomSamples > 0 ? $bottomBrightness / $bottomSamples : 128;

    return aiDecidePanelPosition($avgTop, $avgBottom, $canvasH);
}

function aiDecidePanelPosition($avgTop, $avgBottom, $canvasH) {
    $apiKey = GROQ_API_KEY;

    $prompt = "I have a background image for a car dealership creative. Top half brightness: {$avgTop}/255, Bottom half brightness: {$avgBottom}/255. Canvas height: {$canvasH}px. I need to place a dealership panel that is 28% of canvas height. What Y coordinate in pixels should I place it to avoid the busiest part of the image? Reply with ONLY a single integer.";

    $data = json_encode([
        'model'    => 'llama-3.1-8b-instant',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 10
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $result = json_decode($response, true);
        $text   = trim($result['choices'][0]['message']['content'] ?? '');
        $y      = intval(preg_replace('/[^0-9]/', '', $text));
        if ($y > 0 && $y < $canvasH) {
            return $y;
        }
    }

    if ($avgBottom > 180) return (int)($canvasH * 0.78);
    elseif ($avgBottom > 100) return (int)($canvasH * 0.72);
    else return (int)($canvasH * 0.68);
}