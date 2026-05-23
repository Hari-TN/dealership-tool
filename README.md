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
- PHP extensions: gd, pdo_sqlite, zip, curl

## Setup Instructions

### 1. Clone the repository
git clone https://github.com/Hari-TN/dealership-tool.git
cd dealership-tool

### 2. Install PHP extensions
sudo apt-get install -y php8.3 php8.3-gd php8.3-sqlite3 php8.3-zip php8.3-curl

### 3. Add Groq API key
cp config.php.example config.php
Edit `config.php` and replace `your_groq_api_key_here` with your actual key.
Get a free key at: https://console.groq.com

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
The tool uses **Groq AI (LLaMA 3.1)** for intelligent panel positioning:

1. **Image Analysis** — samples pixel brightness across top and bottom halves of the background image
2. **AI Decision** — sends brightness data to Groq AI (LLaMA 3.1-8b) which decides the optimal Y coordinate for panel placement
3. **Smart Positioning** — places the dealership panel where it least interferes with the main subject
4. **Fallback** — if AI API is unavailable, falls back to built-in brightness analysis

To use your own Groq API key, update `config.php`:
```php
define('GROQ_API_KEY', 'your_key_here');
```
Get a free key at: https://console.groq.com

## Approach & Assumptions

### Approach
1. Built with PHP + SQLite for zero-dependency setup
2. PHP GD library handles all image compositing — background scaling, panel overlay, logo placement
3. Bulk generation loops through each selected dealership and each selected format
4. ZIP packaging allows downloading all creatives at once

### Assumptions
- Dealership panels are PNG files with transparency
- Background images are JPG or PNG
- Logo files are PNG with transparent background
- The tool runs on PHP 8.3 with GD, SQLite, and ZIP extensions
- Asset files are stored in `assets/assets/` as provided in the assignment zip

### Dependencies
- PHP 8.3
- php8.3-gd (image processing)
- php8.3-sqlite3 (database)
- php8.3-zip (ZIP file generation)
- php8.3-curl (Groq AI API calls)
- No external libraries or composer packages required

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
├── .gitignore              
├── config.php              # API keys
├── config.php.example      # API key template
├── database.sql            # MySQL schema and seed data
├── database.sqlite         # SQLite database (runtime)
├── generate.php            # Image generation engine
├── index.php               # Main UI
├── login.php               # Admin login
├── logout.php              # Logout
├── README.md              
└── setup.php
```