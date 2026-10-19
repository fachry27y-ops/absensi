# SecureAttend

SecureAttend is a full-stack attendance system composed of:

- Laravel REST API backend in `secureattend-backend/`
- Android native client in `secureattend-android/`
- MySQL-ready schema and sample seed data for local validation

## Backend quick start

```powershell
cd d:\Projectpop\secureattend-backend
php artisan migrate:fresh --seed
php artisan serve
```

Default demo login:

- Email: `rina@secureattend.test`
- Password: `password123`

## Android project quick start

Open `secureattend-android/` in Android Studio and sync Gradle. The app is configured for a Compose-based login flow and dashboard shell. When running on the Android emulator, use the backend at `http://10.0.2.2:8001`; for a physical device, replace it with your machine's LAN IP.

This environment does not include the Android SDK, so the app was scaffolded but not compiled here.

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
