#!/usr/bin/env bash
set -e

echo "Waiting for database connection..."

TIMEOUT=60
COUNTER=0

until php -r "
try {
    new PDO(
        'pgsql:host=' . getenv('DB_HOST') .
        ';port=' . getenv('DB_PORT') .
        ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    exit(0);
} catch (Exception \$e) {
    exit(1);
}" ; do
  COUNTER=$((COUNTER+1))
  if [ $COUNTER -ge $TIMEOUT ]; then
    echo "Database not ready after ${TIMEOUT}s. Continuing anyway..."
    break
  fi
  echo "DB not ready yet... retrying ($COUNTER)"
  sleep 1
done

# Run migrations (force so non-interactive)
echo "Running migrations..."
php artisan key:generate --force || true
php artisan migrate --force || true
php artisan optimize:clear || true

# Run only the superadmin seeder (idempotent check recommended)
echo "Running super admin seeder..."
php artisan db:seed --class=SuperAdminSeeder --force || true

# You can add other seeders here, but prefer specific seeder classes rather than run DatabaseSeeder blindly

# Start the main process (example: php-fpm)
# Replace with whatever your Dockerfile expects as CMD
echo "Starting Laravel server..."
exec "$@"
