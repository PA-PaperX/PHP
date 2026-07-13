# Hardcore Native PHP Architecture (V2)

## Overview
This architecture is designed to transition a procedural, legacy PHP codebase into a modern, Object-Oriented, Clean Architecture without breaking existing functionality. We employ the **Strangler Fig Pattern**.

## Key Features
1. **Front Controller (`public/index.php`)**: All requests funnel through this single entry point.
2. **FastRoute (`nikic/fast-route`)**: Provides blazing-fast, regex-based routing.
3. **PSR-4 Autoloading**: Powered by Composer (`App\` mapped to `app/`).
4. **Separation of Concerns**:
   - `Controllers/`: Handles HTTP requests/responses.
   - `Services/`: Contains all business logic.
   - `Repositories/`: Handles all direct Database (SQL/PDO) operations.
5. **The "Strangler Fig" Fallback**:
   - If a route is defined in `routes/api.php`, it is handled by the new architecture.
   - If a route is NOT defined, `index.php` dynamically falls back to requiring the legacy procedural `.php` file. 
   - This allows the frontend to strip `.php` from all endpoints immediately (`/api/auth/me` instead of `/api/auth/me.php`), while giving backend developers the time to migrate endpoints one by one.

## Next Steps for Claude/Developer
1. Review the structure in `app/`.
2. Migrate `api/auth/me.php` to an `AuthController`.
3. Set up a central Dependency Injection container (or simple Factory) to wire up Repositories and Services.
4. Set up an `AppException` global error handler in `public/index.php`.
