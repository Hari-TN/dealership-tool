<?php
require_once dirname(__FILE__) . '/config.php';
header('Content-Type: application/json');

$baseDir   = dirname(__FILE__) . '/';
$uploadDir = $baseDir . 'uploads/';
$outputDir = $baseDir . 'output/';

$dealershipIds = json_decode($_POST['dealerships'] ?? '[]', true);
$formats       = json_decode($_POST['formats']      ?? '[]', true);
$logoEnabled   = ($_POST['logo_enabled'] ?? '0') === '1';
$logoType      = $_POST['logo_type'] ?? 'logo_dark';

if (!isset($_FILES['bg_image']) || $_FILES['bg_image']['error'] !== 0) {
    echo json_encode(['error' => 'No background image uploaded']);
    exit;
}
if (empty($dealershipIds)) {
    echo json_encode(['error' => 'No dealerships selected']);
    exit;
}
if (empty($formats)) {
    echo json_encode(['error' => 'No output formats selected']);
    exit;
}

$bgExt  = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
$bgPath = $uploadDir . 'bg_' . uniqid() . '.' . $bgExt;
move_uploaded_file($_FILES['bg_image']['tmp_name'], $bgPath);

$bgImage = match($bgExt) {
    'png'         => imagecreatefrompng($bgPath),
    'jpg', 'jpeg' => imagecreatefromjpeg($bgPath),
    default       => false,
};

if (!$bgImage) {
    echo json_encode(['error' => 'Could not load background image. Use JPG or PNG.']);
    exit;
}

$pdo = new PDO('sqlite:' . $baseDir . 'database.sqlite');

$sizes = [
    '1080x1080' => [1080, 1080],
    '1080x1350' => [1080, 1350],
    '1080x1920' => [1080, 1920],
];

$panelFile = [
    '1080x1080' => 'template.png',
    '1080x1350' => 'template1.png',
    '1080x1920' => 'template1.png',
];

$runId          = uniqid('run_');
$generatedFiles = [];

foreach ($dealershipIds as $dealershipId) {

    $stmt = $pdo->prepare("SELECT name, slug FROM dealerships WHERE id = ?");
    $stmt->execute([$dealershipId]);
    $dealership = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dealership) continue;

    $stmt = $pdo->prepare("SELECT asset_type, file_path FROM dealership_assets WHERE dealership_id = ?");
    $stmt->execute([$dealershipId]);
    $assets = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $assets[$row['asset_type']] = $row['file_path'];
    }

    $logoPath = $baseDir . ($assets[$logoType] ?? $assets['logo_dark'] ?? '');

    foreach ($formats as $format) {
        if (!isset($sizes[$format])) continue;
        [$canvasW, $canvasH] = $sizes[$format];

        $panelDir      = $baseDir . dirname($assets['panel'] ?? '');
        $panelFilename = $panelFile[$format];
        $panelPath     = $panelDir . '/' . $panelFilename;
        if (!file_exists($panelPath)) {
            $panelPath = $baseDir . ($assets['panel'] ?? '');
        }

        $decisions = aiDecidePlacement(
            $bgPath,
            file_exists($panelPath) ? $panelPath : null,
            $logoEnabled && file_exists($logoPath) ? $logoPath : null,
            $canvasW,
            $canvasH
        );

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $bgW = imagesx($bgImage);
        $bgH = imagesy($bgImage);

        $scale   = max($canvasW / $bgW, $canvasH / $bgH);
        $newW    = (int)($bgW * $scale);
        $newH    = (int)($bgH * $scale);
        $offsetX = (int)(($canvasW - $newW) / 2);
        $offsetY = (int)(($canvasH - $newH) / 2);

        imagecopyresampled($canvas, $bgImage, $offsetX, $offsetY, 0, 0, $newW, $newH, $bgW, $bgH);

        $brightnessAdj = (int)($decisions['bg_brightness'] ?? 0);   
        $contrastAdj   = (float)($decisions['bg_contrast'] ?? 1.0); 

        if ($brightnessAdj !== 0 || abs($contrastAdj - 1.0) > 0.01) {
            applyBrightnessContrast($canvas, $canvasW, $canvasH, $brightnessAdj, $contrastAdj);
        }

        if (file_exists($panelPath)) {
            $panel = imagecreatefrompng($panelPath);
            if ($panel) {
                $pW = imagesx($panel);
                $pH = imagesy($panel);

                $pNewW = $canvasW;
                $pNewH = (int)($pH * ($canvasW / $pW));

                $scaledPanel = imagecreatetruecolor($pNewW, $pNewH);
                imagealphablending($scaledPanel, false);
                imagesavealpha($scaledPanel, true);
                $transparent = imagecolorallocatealpha($scaledPanel, 0, 0, 0, 127);
                imagefill($scaledPanel, 0, 0, $transparent);
                imagealphablending($scaledPanel, true);

                imagecopyresampled($scaledPanel, $panel, 0, 0, 0, 0, $pNewW, $pNewH, $pW, $pH);

                if ($canvasH > $pNewH) {
                    $panelY = $canvasH - $pNewH;
                } else {
                    $panelYOffset = (int)($decisions['panel_y_offset'] ?? 0);
                    $panelY       = max(0, min(30, $panelYOffset));
                }

                imagecopy($canvas, $scaledPanel, 0, $panelY, 0, 0, $pNewW, $pNewH);

                imagedestroy($panel);
                imagedestroy($scaledPanel);
            }
        }

        $logoCorner = $decisions['logo_corner'] ?? 'skip';

        {
            $padding    = (int)($canvasW * 0.04);
            $probeSize  = (int)($canvasW * 0.25);
            $probeH     = (int)($canvasH * 0.20);
            $stepX      = max(1, (int)($probeSize / 12));
            $stepY      = max(1, (int)($probeH   / 12));

            $cornersToProbe = ($logoCorner === 'skip')
                ? ['left', 'right']
                : [$logoCorner];

            $cornerBlocked = [];
            foreach ($cornersToProbe as $c) {
                $startX    = ($c === 'left') ? 0 : $canvasW - $probeSize;
                $nearWhite = 0;
                $highSat   = 0;
                $total     = 0;

                for ($py = 0; $py < $probeH; $py += $stepY) {
                    for ($px = $startX; $px < $startX + $probeSize; $px += $stepX) {
                        $rgb  = imagecolorat($canvas, $px, $py);
                        $r    = ($rgb >> 16) & 0xFF;
                        $g    = ($rgb >> 8)  & 0xFF;
                        $b    = $rgb & 0xFF;
                        if ($r > 200 && $g > 200 && $b > 200) $nearWhite++;
                        $maxC = max($r, $g, $b);
                        $minC = min($r, $g, $b);
                        if ($maxC > 0 && ($maxC - $minC) / $maxC > 0.35) $highSat++;
                        $total++;
                    }
                }

                $nwFrac = $total > 0 ? $nearWhite / $total : 0.0;
                $hsFrac = $total > 0 ? $highSat   / $total : 0.0;
                $cornerBlocked[$c] = ($nwFrac > 0.50 && $hsFrac > 0.03);
            }

            if ($logoCorner === 'skip') {
                $leftClear  = isset($cornerBlocked['left'])  && !$cornerBlocked['left'];
                $rightClear = isset($cornerBlocked['right']) && !$cornerBlocked['right'];
                if ($leftClear || $rightClear) {
                    $fallback    = brightnessFallbackCorner($bgPath, $canvasW);
                    $logoCorner  = ($fallback === 'right' && $rightClear) ? 'right'
                                 : ($leftClear  ? 'left'  : 'right');
                }
            } else {
                if ($cornerBlocked[$logoCorner]) {
                    $logoCorner = 'skip';
                }
            }
        }

        if ($logoEnabled && $logoCorner !== 'skip' && file_exists($logoPath)) {
            $logo = imagecreatefrompng($logoPath);
            if ($logo) {
                $lW = imagesx($logo);
                $lH = imagesy($logo);

                $logoScalePct = (float)($decisions['logo_scale_pct'] ?? 0.12);
                $logoScalePct = max(0.08, min(0.16, $logoScalePct));

                $lScale = ($canvasW * $logoScalePct) / $lW;
                $lNewW  = (int)($lW * $lScale);
                $lNewH  = (int)($lH * $lScale);

                $padding = (int)($canvasW * 0.04);

                $lX = ($logoCorner === 'left')
                    ? $padding
                    : $canvasW - $lNewW - $padding;
                $lY = $padding;

                $scaledLogo = imagecreatetruecolor($lNewW, $lNewH);
                imagealphablending($scaledLogo, false);
                imagesavealpha($scaledLogo, true);
                $transparent = imagecolorallocatealpha($scaledLogo, 0, 0, 0, 127);
                imagefill($scaledLogo, 0, 0, $transparent);
                imagealphablending($scaledLogo, true);

                imagecopyresampled($scaledLogo, $logo, 0, 0, 0, 0, $lNewW, $lNewH, $lW, $lH);
                imagecopy($canvas, $scaledLogo, $lX, $lY, 0, 0, $lNewW, $lNewH);

                imagedestroy($logo);
                imagedestroy($scaledLogo);
            }
        }

        $filename = $dealership['slug'] . '_' . $format . '_' . $runId . '.jpg';
        $filepath = $outputDir . $filename;
        imagejpeg($canvas, $filepath, 95);
        imagedestroy($canvas);

        $generatedFiles[] = $filepath;
    }
}

imagedestroy($bgImage);
@unlink($bgPath);


$zipPath = $outputDir . 'creatives_' . $runId . '.zip';
$zip     = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
    foreach ($generatedFiles as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
}


echo json_encode([
    'success' => true,
    'count'   => count($generatedFiles),
    'zip_url' => 'output/' . basename($zipPath),
    'files'   => array_map(fn($f) => [
        'name' => basename($f),
        'url'  => 'output/' . basename($f),
    ], $generatedFiles),
]);


function aiDecidePlacement(
    string  $bgPath,
    ?string $panelPath,
    ?string $logoPath,
    int     $canvasW,
    int     $canvasH
): array {

    $defaults = [
        'logo_corner'    => 'left',
        'logo_scale_pct' => 0.12,
        'panel_y_offset' => 0,
        'bg_brightness'  => 0,
        'bg_contrast'    => 1.0,
    ];

    $defaults['logo_corner'] = brightnessFallbackCorner($bgPath, $canvasW);

    if (!defined('GROQ_API_KEY') || GROQ_API_KEY === 'your_groq_api_key_here') {
        return $defaults;
    }

    $images = [];

    $bgThumb = resizeForApi($bgPath, 400);
    if ($bgThumb) {
        $images[] = [
            'type'      => 'image_url',
            'image_url' => ['url' => 'data:image/jpeg;base64,' . $bgThumb],
        ];
    }

    if ($panelPath) {
        $panelThumb = resizeForApi($panelPath, 400);
        if ($panelThumb) {
            $images[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => 'data:image/png;base64,' . $panelThumb],
            ];
        }
    }

    if ($logoPath) {
        $logoThumb = resizeForApi($logoPath, 200);
        if ($logoThumb) {
            $images[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => 'data:image/png;base64,' . $logoThumb],
            ];
        }
    }

    $prompt = <<<PROMPT
You are a professional social media creative director for an automotive dealership.

You are given {$canvasW}×{$canvasH}px images in this order:
1. BACKGROUND — the photo or template used as the ad background
2. PANEL — a transparent PNG overlay with dealership info (text, footer, offers) visible near the bottom
3. LOGO — the brand/dealership logo PNG to optionally place in a corner

Your task: decide how to composite these beautifully.

SKIP LOGO RULES — set logo_corner to "skip" if ANY of the following:
- The background already has a logo, watermark, or brand mark visible in the top-left OR top-right corners
- The background looks like a finished/designed template (has text, icons, or branding already)
- The top corners already contain dark/coloured shapes that look like logos (circles, badges, icons)
- There is already a circular badge, gear icon, or brand symbol in either top corner

PLACE LOGO RULES — only place if ALL of the following:
- The background is a plain photograph with no pre-existing logos or watermarks
- The chosen corner has clear empty space (plain sky, wall, floor, or neutral area)
- There is no existing circular or badge-shaped graphic in that corner

LOGO SIZE — logo_scale_pct must be between 0.08 and 0.14:
- 0.10 for most photos (results in ~108px logo on 1080px canvas — clean and proportional)
- 0.12 for backgrounds with large empty corner areas
- Never exceed 0.14 under any circumstances

PANEL — always at y=0 (panel_y_offset=0). Do not move it.

BACKGROUND ADJUSTMENTS — bg_brightness and bg_contrast:
- Most backgrounds: both at 0 and 1.0 respectively (no change)
- Only adjust if panel text would be unreadable against the background
- Maximum brightness change: ±15. Maximum contrast: 0.9–1.1

Reply with ONLY a valid JSON object, no explanation, no markdown:
{
  "logo_corner": "left" or "right" or "skip",
  "logo_scale_pct": 0.10,
  "panel_y_offset": 0,
  "bg_brightness": 0,
  "bg_contrast": 1.0
}
PROMPT;

    $content   = $images;
    $content[] = ['type' => 'text', 'text' => $prompt];

    $payload = json_encode([
        'model'       => 'meta-llama/llama-4-scout-17b-16e-instruct',
        'messages'    => [['role' => 'user', 'content' => $content]],
        'max_tokens'  => 120,
        'temperature' => 0,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
    ]);

    $response = curl_exec($ch);
    $curlErr  = curl_errno($ch);
    curl_close($ch);

    if ($curlErr || !$response) {
        return $defaults;
    }

    $result = json_decode($response, true);
    $text   = trim($result['choices'][0]['message']['content'] ?? '');

    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/', '', $text);

    $decisions = json_decode($text, true);
    if (!is_array($decisions)) {
        return $defaults;
    }

    $corner = strtolower(trim($decisions['logo_corner'] ?? 'left'));
    if (!in_array($corner, ['left', 'right', 'skip'])) $corner = $defaults['logo_corner'];

    return [
        'logo_corner'    => $corner,
        'logo_scale_pct' => max(0.08, min(0.16, (float)($decisions['logo_scale_pct'] ?? 0.12))),
        'panel_y_offset' => max(0,   min(50,   (int)($decisions['panel_y_offset']   ?? 0))),
        'bg_brightness'  => max(-50, min(50,   (int)($decisions['bg_brightness']    ?? 0))),
        'bg_contrast'    => max(0.7, min(1.3,  (float)($decisions['bg_contrast']    ?? 1.0))),
    ];
}


function applyBrightnessContrast($image, int $w, int $h, int $brightness, float $contrast): void
{
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $r   = ($rgb >> 16) & 0xFF;
            $g   = ($rgb >> 8)  & 0xFF;
            $b   = $rgb & 0xFF;

            $r += $brightness;
            $g += $brightness;
            $b += $brightness;

            $r = (int)(($r - 128) * $contrast + 128);
            $g = (int)(($g - 128) * $contrast + 128);
            $b = (int)(($b - 128) * $contrast + 128);

            $r = max(0, min(255, $r));
            $g = max(0, min(255, $g));
            $b = max(0, min(255, $b));

            imagesetpixel($image, $x, $y, imagecolorallocate($image, $r, $g, $b));
        }
    }
}


function brightnessFallbackCorner(string $bgPath, int $canvasW): string
{
    $ext = strtolower(pathinfo($bgPath, PATHINFO_EXTENSION));
    $img = match($ext) {
        'png'         => @imagecreatefrompng($bgPath),
        'jpg', 'jpeg' => @imagecreatefromjpeg($bgPath),
        default       => false,
    };
    if (!$img) return 'left';

    $imgW   = imagesx($img);
    $imgH   = imagesy($img);
    $sample = max(1, (int)($imgW * 0.25));
    $step   = max(1, (int)($sample / 10));

    $leftB = 0.0; $rightB = 0.0; $n = 0;
    for ($y = 0; $y < $sample; $y += $step) {
        for ($x = 0; $x < $sample; $x += $step) {
            $leftB  += luminance(imagecolorat($img, $x, $y));
            $rightB += luminance(imagecolorat($img, $imgW - $x - 1, $y));
            $n++;
        }
    }
    imagedestroy($img);

    return ($n > 0 && ($leftB / $n) > ($rightB / $n)) ? 'right' : 'left';
}


function resizeForApi(string $path, int $maxSide): string|false
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $src = match($ext) {
        'png'         => @imagecreatefrompng($path),
        'jpg', 'jpeg' => @imagecreatefromjpeg($path),
        default       => false,
    };
    if (!$src) return false;

    $w = imagesx($src);
    $h = imagesy($src);

    if (max($w, $h) > $maxSide) {
        $scale = $maxSide / max($w, $h);
        $nw    = max(1, (int)($w * $scale));
        $nh    = max(1, (int)($h * $scale));
        $dst   = imagecreatetruecolor($nw, $nh);
        // Preserve transparency for PNG
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $trans);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst;
    }

    ob_start();
    imagejpeg($src, null, 80);
    $bytes = ob_get_clean();
    imagedestroy($src);

    return base64_encode($bytes);
}

function luminance(int $rgb): float
{
    return 0.299 * (($rgb >> 16) & 0xFF)
         + 0.587 * (($rgb >> 8)  & 0xFF)
         + 0.114 * ($rgb & 0xFF);
}
