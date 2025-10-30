# EzSorting Web - Photo Organizer for Google Drive

Aplikasi web untuk sorting dan mengorganisir foto di Google Drive dengan mudah dan cepat. Versi web dari aplikasi desktop EzSorting yang dibangun dengan Laravel, React, dan Tailwind CSS.

## Features

### 3-Step Workflow yang Mudah
1. **Pilih Source Folder** - Pilih folder dari Google Drive yang berisi foto
2. **Buat Destination Folders** - Setup folder-folder tujuan untuk organizing (max 9 untuk keyboard shortcuts)
3. **Start Sorting** - Preview dan sort foto dengan mouse atau keyboard shortcuts

### Fitur Lengkap
- **Google Drive Integration** - Langsung bekerja dengan foto di Google Drive
- **Preview Foto Realtime** - Lihat preview foto dengan informasi detail (size, format, dimensi)
- **Keyboard Shortcuts** - Sort cepat dengan keyboard:
  - `1-9`: Move foto ke folder yang sesuai
  - `←`: Previous image
  - `→`: Next/Skip image
- **Automatic File Moving** - Foto otomatis dipindahkan ke folder yang dipilih
- **Statistik Realtime** - Track progress (Total, Sorted, Remaining)
- **Support Multiple Format**:
  - Standard: JPG, JPEG, PNG, HEIC, GIF, WebP
  - RAW: ARW, CR2, CR3, NEF, NRW, RAF, RW2, ORF, PEF, DNG
- **Responsive Design** - Works di desktop, tablet, dan mobile
- **Modern UI** - Beautiful interface dengan Tailwind CSS

## Tech Stack

- **Backend**: Laravel 10 (PHP 8.2+)
- **Frontend**: React 18 + TypeScript
- **Styling**: Tailwind CSS
- **Database**: MySQL/PostgreSQL
- **API**: Google Drive API
- **Build Tool**: Vite

## Requirements

- PHP 8.2 atau lebih tinggi
- Composer
- Node.js 16+ dan NPM
- MySQL 5.7+ atau PostgreSQL
- Google Cloud Project dengan Google Drive API enabled

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/TPMYLO/ezsorting-web.git
cd ezsorting-web
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup

Edit `.env` file dan configure database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ezsorting_web
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate
```

### 5. Google Drive API Setup

#### A. Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project atau pilih existing project
3. Enable **Google Drive API**:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Drive API"
   - Click "Enable"

#### B. Create OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth client ID"
3. Configure OAuth consent screen jika belum:
   - User Type: External (untuk testing) atau Internal (untuk organization)
   - Add scopes: `https://www.googleapis.com/auth/drive`
4. Create OAuth Client ID:
   - Application type: **Web application**
   - Authorized redirect URIs: `http://localhost:8000/google/callback` (adjust sesuai APP_URL)
5. Download credentials atau copy **Client ID** and **Client Secret**

#### C. Configure Environment

Add ke `.env` file:

```env
GOOGLE_DRIVE_CLIENT_ID=your-client-id-here
GOOGLE_DRIVE_CLIENT_SECRET=your-client-secret-here
```

### 6. Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 7. Run Application

```bash
# Start Laravel server
php artisan serve

# In another terminal, start Vite dev server (for development)
npm run dev
```

Access aplikasi di: `http://localhost:8000`

## Usage

### First Time Setup

1. **Register/Login** - Create account atau login
2. **Connect Google Drive**:
   - Click "Connect Google Drive" button
   - Authorize aplikasi untuk access Google Drive
   - Anda akan diredirect kembali ke aplikasi

### Sorting Photos

#### Step 1: Choose Source Folder

1. Click "Choose Source Folder"
2. Pilih folder dari Google Drive yang berisi foto
3. Aplikasi akan mendeteksi semua foto di folder tersebut

#### Step 2: Create Destination Folders

1. Masukkan nama folder di input field (contoh: "Best", "Delete", "Edit")
2. Click "Add Folder" atau tekan Enter
3. Ulangi untuk membuat lebih banyak folder (max 9)
4. Anda bisa remove folder dengan click tombol ×
5. Click "Start Sorting" ketika siap

#### Step 3: Start Sorting

1. Preview foto akan muncul dengan informasi detail
2. Pilih destination folder:
   - **Mouse**: Click folder button di sidebar
   - **Keyboard**: Tekan angka `1-9` sesuai nomor folder
3. Foto akan otomatis dipindahkan ke folder yang dipilih
4. Navigation:
   - **Previous**: Click "Previous" button atau tekan `←`
   - **Next/Skip**: Click "Next/Skip" button atau tekan `→`
5. Progress dipantau di sidebar (Total, Sorted, Remaining)
6. Setelah selesai, session akan complete dan Anda bisa start over

### Keyboard Shortcuts

Saat sedang sorting:

- `1-9`: Move foto ke folder dengan nomor tersebut
- `←` (Arrow Left): Previous image
- `→` (Arrow Right): Next/Skip image

## Development

### Project Structure

```
ezsorting-web/
├── app/
│   ├── Http/Controllers/
│   │   ├── GoogleDriveController.php
│   │   └── SortingController.php
│   ├── Models/
│   │   └── SortingSession.php
│   └── Services/
│       └── GoogleDriveService.php
├── config/
│   └── google.php
├── database/
│   └── migrations/
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   └── Sorting/
│   │   │       ├── Index.tsx
│   │   │       └── Components/
│   │   │           ├── WelcomeStep.tsx
│   │   │           ├── SetupStep.tsx
│   │   │           └── SortingStep.tsx
│   │   └── Components/
│   └── css/
└── routes/
    └── web.php
```

## Troubleshooting

### Google Drive Authentication Failed

1. Check apakah Client ID dan Client Secret sudah benar di `.env`
2. Pastikan redirect URI di Google Cloud Console match dengan APP_URL
3. Clear browser cache dan cookies
4. Try re-authorize

### Images Not Loading

1. Check Google Drive API quota
2. Verify file permissions di Google Drive
3. Check browser console untuk errors
4. Pastikan format file supported

### Database Connection Error

1. Check database credentials di `.env`
2. Pastikan database service running
3. Run `php artisan migrate` untuk ensure tables exist

## License

MIT License

## Author

TPMYLO

## Support

Jika ada masalah atau pertanyaan, silakan create issue di repository.
