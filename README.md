# Dealership Creative Automation Tool

A web-based tool to automate generation of dealership creatives by combining brand assets, dealership panels, logos, and background images.

## Features
- Brand and dealership selection with dynamic loading
- Bulk creative generation (multiple dealerships at once)
- Logo toggle (dark/light)
- Background image upload (JPG/PNG)
- Output formats: Instagram Post (1080×1080, 1080×1350), Instagram Story (1080×1920)
- Individual and ZIP download
- AI-powered smart panel positioning
- Admin login protection

## Requirements
- PHP 8.3
- SQLite (built-in)
- PHP extensions: gd, pdo_sqlite, zip

## Setup Instructions

### 1. Clone the repository
git clone https://github.com/Hari-TN/dealership-tool.git
cd dealership-tool

### 2. Install PHP extensions
sudo apt-get install -y php8.3 php8.3-gd php8.3-sqlite3 php8.3-zip

### 3. Set up the database
php8.3 -r "$db = new PDO('sqlite:/path/to/dealership-tool/database.sqlite'); $db->exec(file_get_contents('database.sql')); echo 'Done';"

### 4. Set permissions
chmod 777 uploads output

### 5. Start the server
php8.3 -S 0.0.0.0:8000

### 6. Open in browser
http://localhost:8000

## Default Admin Login
| Field    | Value    |
|----------|----------|
| Username | admin    |
| Password | admin123 |

## AI Feature
The tool uses brightness analysis of the background image to automatically position the dealership panel — placing it higher when the bottom is dark, and lower when the bottom is bright — ensuring the panel never overlaps key visual elements.

## Project Structure
```
dealership-tool/
├── api/
│   ├── brands.php          # Returns brands list as JSON
│   └── dealerships.php     # Returns dealerships by brand as JSON
├── assets/assets/
│   ├── Dealership-panels/  # Panel PNGs per dealership
│   ├── Logos/              # Brand logos
│   └── Sample-input-images/
├── css/
│   └── style.css
├── js/
│   └── app.js
├── output/                 # Generated creatives saved here
├── uploads/                # Temporary background uploads
├── database.sql            # MySQL schema and seed data
├── database.sqlite         # SQLite database (runtime)
├── generate.php            # Image generation engine
├── index.php               # Main UI
├── login.php               # Admin login
├── logout.php              # Logout
└── README.md
```