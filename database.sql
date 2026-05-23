-- Dealership Creative Tool - Database Setup
-- MySQL Compatible

CREATE DATABASE IF NOT EXISTS dealership_tool;
USE dealership_tool;

CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dealerships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dealership_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dealership_id INT NOT NULL,
    asset_type ENUM('panel', 'logo_dark', 'logo_light') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (dealership_id) REFERENCES dealerships(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO brands (name, slug) VALUES
('Tata', 'tata'),
('Volkswagen', 'vw');

INSERT INTO dealerships (brand_id, name, slug) VALUES
(1, 'Bellad Tata', 'bellad-tata'),
(2, 'VW Autobahn', 'vw-autobhan'),
(2, 'VW Hubli', 'vw-hubli');

INSERT INTO dealership_assets (dealership_id, asset_type, file_path) VALUES
(1, 'panel',      'assets/assets/Dealership-panels/Tata-dealers/Bellad-tata/template.png'),
(1, 'logo_dark',  'assets/assets/Dealership-panels/Tata-dealers/Bellad-tata/logo-dark.png'),
(1, 'logo_light', 'assets/assets/Dealership-panels/Tata-dealers/Bellad-tata/logo-light.png'),
(2, 'panel',      'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/template.png'),
(2, 'logo_dark',  'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-dark.png'),
(2, 'logo_light', 'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-light.png'),
(3, 'panel',      'assets/assets/Dealership-panels/VW-dealers/VW-Hubli/template.png'),
(3, 'logo_dark',  'assets/assets/Dealership-panels/VW-dealers/VW-Hubli/logo-dark.png'),
(3, 'logo_light', 'assets/assets/Dealership-panels/VW-dealers/VW-Hubli/logo-light.png'),
(4, 'panel',      'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/template.png'),
(4, 'logo_dark',  'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-dark.png'),
(4, 'logo_light', 'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-light.png'),
(5, 'panel',      'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/template.png'),
(5, 'logo_dark',  'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-dark.png'),
(5, 'logo_light', 'assets/assets/Dealership-panels/VW-dealers/VW-Autobhan/logo-light.png'),
(6, 'panel',      'assets/assets/Dealership-panels/VW-dealers/VW-Hubli/template.png'),
(6, 'logo_dark',  'assets/assets/Dealership-panels/VW-dealers/VW-Hubli/logo-dark.png'),
(6, 'logo_light', 'assets/assets/Dealership-panels/VW-dealers/VW-Hubli/logo-light.png');

-- Default admin (password: admin123)
INSERT INTO admin_users (username, password) VALUES
('admin', '$2y$10$EBJRMtKvGgN9xxlVZpx7buyDLnBI4hTKSZT6.YEJq6dqsuSmZj/Ka');