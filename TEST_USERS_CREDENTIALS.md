# Test Users Credentials

All users have the same password: **`password`**

## Admin Users (3)

| Full Name | Email | Phone | Role |
|-----------|-------|-------|------|
| Admin Dua Insan | admin@duainsan.story | 081234567890 | admin |
| Siti Nurhaliza | siti.admin@duainsan.story | 081298765432 | admin |
| Admin User | admin@example.com | - | admin |

## Customer Users (15)

| ID | Full Name | Email | Phone | City | State |
|----|-----------|-------|-------|------|-------|
| 2 | Ihza Mahendra Sofyan | customer@example.com | 089692070270 | Kota Pontianak | Kalimantan Barat |
| 5 | Budi Santoso | budi.santoso@gmail.com | 081234567891 | Jakarta Pusat | DKI Jakarta |
| 6 | Dewi Lestari | dewi.lestari@yahoo.com | 082345678901 | Bandung | Jawa Barat |
| 7 | Ahmad Hidayat | ahmad.hidayat@gmail.com | 083456789012 | Yogyakarta | DI Yogyakarta |
| 8 | Rina Kusuma | rina.kusuma@gmail.com | 084567890123 | Surabaya | Jawa Timur |
| 9 | Faisal Rahman | faisal.rahman@gmail.com | 085678901234 | Medan | Sumatera Utara |
| 10 | Siti Aisyah | siti.aisyah@yahoo.com | 086789012345 | Semarang | Jawa Tengah |
| 11 | Irfan Hakim | irfan.hakim@gmail.com | 087890123456 | Depok | Jawa Barat |
| 12 | Maya Sari | maya.sari@gmail.com | 088901234567 | Malang | Jawa Timur |
| 13 | Andi Wijaya | andi.wijaya@gmail.com | 089012345678 | Makassar | Sulawesi Selatan |
| 14 | Lina Marlina | lina.marlina@yahoo.com | 081123456789 | Denpasar | Bali |
| 15 | Rudi Hermawan | rudi.hermawan@gmail.com | 082234567890 | Palembang | Sumatera Selatan |
| 16 | Putri Ayu | putri.ayu@gmail.com | 083345678901 | Balikpapan | Kalimantan Timur |
| 17 | Hendra Gunawan | hendra.gunawan@gmail.com | 084456789012 | Samarinda | Kalimantan Timur |
| 18 | Nurul Fadilah | nurul.fadilah@yahoo.com | 085567890123 | Tangerang | Banten |

## Coverage

### Geographic Distribution:
- **Kalimantan**: Pontianak, Balikpapan, Samarinda
- **Jawa**: Jakarta, Bandung, Yogyakarta, Surabaya, Semarang, Depok, Malang
- **Sumatera**: Medan, Palembang
- **Sulawesi**: Makassar
- **Bali**: Denpasar
- **Banten**: Tangerang

### Features:
- ✅ All customers have complete addresses
- ✅ Indonesian phone numbers (08X format)
- ✅ Mix of Gmail and Yahoo email providers
- ✅ Realistic Indonesian names
- ✅ Covers 15 major cities across Indonesia

## Usage

### Login as Admin:
```bash
Email: admin@duainsan.story
Password: password
```

### Login as Customer:
```bash
Email: customer@example.com
Password: password
```

### Re-seed Database:
```bash
php artisan db:seed --class=UserSeeder
```

### Reset and Re-seed:
```bash
php artisan migrate:fresh --seed
```

## Notes

- All users use `firstOrCreate()` so running the seeder multiple times won't create duplicates
- Passwords are hashed using Laravel's `Hash::make()`
- All customer users have associated addresses
- Phone numbers follow Indonesian format (08X-XXXX-XXXX)
- Addresses include street, city, state, subdistrict, postal code, and country
