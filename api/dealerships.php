<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = new PDO('sqlite:' . dirname(__DIR__) . '/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;

    if ($brand_id === 0) {
        echo json_encode(['error' => 'brand_id is required']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT d.id, d.name, d.slug, da.asset_type, da.file_path
        FROM dealerships d
        LEFT JOIN dealership_assets da ON da.dealership_id = d.id
        WHERE d.brand_id = ?
        ORDER BY d.id
    ");
    $stmt->execute([$brand_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dealerships = [];
    foreach ($rows as $row) {
        $id = $row['id'];
        if (!isset($dealerships[$id])) {
            $dealerships[$id] = [
                'id'     => $row['id'],
                'name'   => $row['name'],
                'slug'   => $row['slug'],
                'assets' => []
            ];
        }
        $dealerships[$id]['assets'][$row['asset_type']] = $row['file_path'];
    }

    echo json_encode(array_values($dealerships));

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}