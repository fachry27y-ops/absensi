# SecureAttend

Sistem absensi (attendance) berbasis lokasi.

SecureAttend is a full-stack attendance system composed of:

- Laravel REST API backend in `secureattend-backend/`
- Android native client in `secureattend-android/`
- MySQL-ready schema and sample seed data for local validation

## Backend quick start

```powershell
cd d:\Projectpop\secureattend-backend
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001
```

Default demo login:

- Email: `rina@secureattend.test`
- Password: `password123`

## Android project quick start

Open `secureattend-android/` in Android Studio and sync Gradle. The Compose app supports API login by email or username, a live dashboard (stats, recent attendance, and work locations), refresh, and API logout. Employee accounts can check in/out with a captured selfie and high-accuracy location; the API remains authoritative for geofence and GPS policy checks.

The default Android emulator API URL is `http://10.0.2.2:8001`. For a physical device, set `BASE_URL` in `AttendanceApi.kt` to your development machine's LAN IP and start Laravel with `php artisan serve --host=0.0.0.0 --port=8001`. The debug app build permits local HTTP; release builds disallow cleartext traffic.

The project includes a Gradle 8.9 wrapper and requires JDK 17. Build from PowerShell with `.\gradlew.bat :app:assembleDebug` or `.\gradlew.bat :app:assembleRelease`. The release APK is unsigned; sign it with your release keystore before distribution.

## API highlights

- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `GET /api/dashboard`
- `GET /api/employees`
- `GET /api/attendance/history`
- `POST /api/attendance/check-in`
- `POST /api/attendance/check-out`

## Notes

The implementation follows the business rules defined in the SecureAttend PRD: geofence validation, distance checks, GPS accuracy checks, selfie uploads, and role-based access control are enforced at the server layer.
