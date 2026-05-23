<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = new PDO('sqlite:' . dirname(__DIR__) . '/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, name, slug FROM brands ORDER BY name");
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($brands);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}