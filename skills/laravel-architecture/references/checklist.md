# Post-Setup Checklist

Step-by-step implementation checklist for new projects.

**Related guides:**
- [structure.md](structure.md) - Directory structure to create
- [packages.md](../../laravel-packages/references/packages.md) - Packages to install
- [quality.md](../../laravel-quality/references/quality.md) - Quality tools to configure
- [controllers.md](../../laravel-controllers/references/controllers.md) - Controller naming and patterns
- [validation-testing.md](../../laravel-validation/references/validation-testing.md) - Validation testing with datasets
- [examples.md](examples.md) - Reference implementations

## Immediate Tasks

### 1. Configure Database
- [ ] Update `.env` with database credentials
- [ ] Run `php artisan migrate`

### 2. Configure Queue
- [ ] Set `QUEUE_CONNECTION` in `.env`
- [ ] Create queue enum in `app/Enums/Queue.php`
- [ ] Configure queue workers

### 3. Configure Cache
- [ ] Set `CACHE_STORE` in `.env`
- [ ] Run `php artisan config:cache` (production)

### 4. API Authentication (if using)
- [ ] Install Sanctum: `php artisan install:api`
- [ ] Publish Sanctum config
- [ ] Configure token abilities

### 5. Test Setup
- [ ] Run `./vendor/bin/pest` to verify
- [ ] Check architecture tests pass
- [ ] Add project-specific arch tests

## First Domain Implementation

### 6. Create Your First Feature
Example: User registration

- [ ] Create action: `CreateUserAction`
- [ ] Create DTO: `CreateUserData`
- [ ] Create form request: `CreateUserRequest`
- [ ] Write validation test dataset
- [ ] Create controller: `UserController`
- [ ] Create transformer: `UserDataTransformer`
- [ ] Create resource: `UserResource`
- [ ] Write feature test
- [ ] Write unit test for action

### 7. Add Custom Query Builder (if needed)
- [ ] Create builder: `app/Builders/UserBuilder.php`
- [ ] Update model to use builder
- [ ] Add query methods

### 8. Add Enums
- [ ] Create relevant enums (Status, Type, etc.)
- [ ] Add enum concerns if needed

## Optional Tasks

### 9. State Machines (if using)
- [ ] Create base state class
- [ ] Create concrete state classes
- [ ] Add transitions
- [ ] Add state column to model
- [ ] Configure model to use states

### 10. Multi-Tenancy (if using)
- [ ] Configure tenancy package
- [ ] Separate Central/Tenanted actions
- [ ] Set up tenant migrations
- [ ] Configure tenant identification

### 11. External Services (if using)
- [ ] Create service directory structure
- [ ] Create Saloon connector
- [ ] Create request classes
- [ ] Create service manager
- [ ] Add service provider

## Production Preparation

### 12. Environment Configuration
- [ ] Review all `.env` variables
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure logging
- [ ] Set up error tracking (Sentry, etc.)

### 13. Performance
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Configure Redis for cache/sessions/queue
- [ ] Set up horizon (if using queues)

### 14. Security
- [ ] Review CORS settings
- [ ] Configure rate limiting
- [ ] Set up API token management
- [ ] Review middleware configuration
- [ ] Enable HTTPS

### 15. Documentation
- [ ] Document API endpoints (if public API)
- [ ] Add README with setup instructions
- [ ] Document custom patterns/conventions
- [ ] Add architecture decision records

## Verification

### 16. Run Tests
```bash
./vendor/bin/pest
./vendor/bin/pest --coverage
```

### 17. Static Analysis (optional but recommended)
```bash
composer require phpstan/phpstan --dev
./vendor/bin/phpstan analyse
```

### 18. Code Style (optional but recommended)
```bash
composer require laravel/pint --dev
./vendor/bin/pint
```

## Common Commands Reference

```bash
# Development
php artisan serve
php artisan queue:work
php artisan migrate:fresh --seed

# Testing
./vendor/bin/pest
./vendor/bin/pest --filter=OrderTest

# Production
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
