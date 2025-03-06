# Memulai Proyek

## Instalasi

Clone repo:

```bash
git clone https://github.com/ilhamdika/to-do-list.git
```

## Instal Dependensi

```bash
cd to-do-list
composer install
```

## Pengaturan Lingkungan

```bash
cp .env.example .env
php artisan key:generate
```

## Konfigurasi Database

Edit file `.env` untuk mengatur database:

```plaintext
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database
```

Jalankan migrasi:

```bash
php artisan migrate
```

## Menjalankan Aplikasi

```bash
php artisan serve
```

Akses aplikasi di `http://127.0.0.1:8000`.
