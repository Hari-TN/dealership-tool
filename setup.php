<?php
$dbPath = dirname(__FILE__) . '/database.sqlite';

if (file_exists($dbPath)) unlink($dbPath);

$db = new PDO('sqlite:' . $dbPath);

$db->exec('CREATE TABLE brands (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT NOT NULL UNIQUE)');
$db->exec('CREATE TABLE dealerships (id INTEGER PRIMARY KEY AUTOINCREMENT, brand_id INTEGER NOT NULL, name TEXT NOT NULL, slug TEXT NOT NULL)');
$db->exec('CREATE TABLE dealership_assets (id INTEGER PRIMARY KEY AUTOINCREMENT, dealership_id INTEGER NOT NULL, asset_type TEXT NOT NULL, file_path TEXT NOT NULL)');

$db->exec("INSERT INTO brands (name, slug) VALUES ('Tata','tata'),('Volkswagen','vw')");

$db->exec("INSERT INTO dealerships (brand_id, name, slug) VALUES 
    (1,'Bellad Tata','bellad-tata'),
    (2,'VW Autobahn','vw-autobhan'),
    (2,'VW Hubli','vw-hubli')");
    
$db->exec("INSERT INTO dealership_assets (dealership_id, asset_type, file_path) VALUES
(1,'panel','assets/assets/Dealership-panels/Tata-dealers/Bellad-tata/template.png'),
(1,'logo_dark','assets/assets/Dealership-panels/Tata-dealers/Bellad-tata/logo-dark.png'),
(1,'logo_light','assets/assets/Dealership-panels/Tata-dealers/Bellad-tata/logo-light.png'),
(2,'panel','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/template.png'),
(2,'logo_dark','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-dark.png'),
(2,'logo_light','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-light.png'),
(3,'panel','assets/assets/Dealership-panels/VW-dealers/VW-Hubli/template.png'),
(3,'logo_dark','assets/assets/Dealership-panels/VW-dealers/VW-Hubli/logo-dark.png'),
(3,'logo_light','assets/assets/Dealership-panels/VW-dealers/VW-Hubli/logo-light.png'),
(4,'panel','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/template.png'),
(4,'logo_dark','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-dark.png'),
(4,'logo_light','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-light.png'),
(5,'panel','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/template.png'),
(5,'logo_dark','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-dark.png'),
(5,'logo_light','assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-light.png'),
(6,'panel','assets/assets/Dealership-panels/VW-dealers/VW-Hubli/template.png'),
(6,'logo_dark','assets/assets/Dealership-panels/VW-dealers/VW-Hubli/logo-dark.png'),
(6,'logo_light','assets/assets/Dealership-panels/VW-dealers/VW-Hubli/logo-light.png')");

echo "Database setup complete!\n";