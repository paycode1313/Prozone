# Panduan Deploy Prozone ke Hosting

## Persiapan Sebelum Upload

### 1. Export Database
Buka phpMyAdmin di Laragon, export database `prozone` (atau nama database Anda) sebagai file `.sql`

### 2. Edit config/config.php
Ubah sesuai hosting:
```php
define('BASE_URL', 'https://namadomain.com/');
```

### 3. Edit config/database.php
Ubah sesuai kredensial hosting:
```php
private $host = "localhost";
private $db_name = "nama_database_hosting";
private $username = "username_hosting";
private $password = "password_hosting";
```

---

## Langkah Upload ke Hosting

### 1. Login ke cPanel Hosting

### 2. Buat Database
- Buka **MySQL Databases**
- Buat database baru
- Buat user baru
- Assign user ke database dengan ALL PRIVILEGES

### 3. Import Database
- Buka **phpMyAdmin**
- Pilih database yang baru dibuat
- Import file `.sql` yang sudah di-export

### 4. Upload Files
- Buka **File Manager**
- Masuk ke folder `public_html`
- Upload semua file dari folder ProzoneWeb (bisa zip dulu lalu extract di hosting)

### 5. Update Config
Edit file `config/config.php` dan `config/database.php` di hosting sesuai kredensial

---

## Konfigurasi SMTP Hosting

Setelah website live, update `config/config.php`:

```php
// Matikan debug mode
define('EMAIL_DEBUG', false);

// SMTP dari hosting (biasanya seperti ini)
define('SMTP_HOST', 'mail.namadomain.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'noreply@namadomain.com');
define('SMTP_PASSWORD', 'password_email');
define('SMTP_ENCRYPTION', 'ssl');

define('EMAIL_FROM', 'noreply@namadomain.com');
define('EMAIL_FROM_NAME', 'Prozone');
```

### Cara Buat Email di Hosting:
1. Login cPanel
2. Buka **Email Accounts**
3. Buat email baru: `noreply@namadomain.com`
4. Catat password-nya
5. Gunakan kredensial ini di config

---

## Checklist Setelah Deploy

- [ ] Website bisa diakses
- [ ] Login berfungsi
- [ ] Database terkoneksi
- [ ] Upload gambar berfungsi
- [ ] Email reset password terkirim
- [ ] HTTPS aktif (biasanya gratis via Let's Encrypt di cPanel)

---

## Troubleshooting

### Error 500
- Cek file `.htaccess`
- Cek permission folder (755 untuk folder, 644 untuk file)

### Database Error
- Pastikan kredensial database benar
- Pastikan user punya akses ke database

### Email Tidak Terkirim
- Pastikan SMTP credentials benar
- Cek folder Spam
- Hubungi support hosting

---

## File yang Perlu Diubah Saat Deploy

1. `config/config.php` - BASE_URL, EMAIL settings
2. `config/database.php` - Database credentials
