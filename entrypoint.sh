#!/usr/bin/env bash
set -e

# wait-for-db (simple loop) - optional but helps container wait for DB to be ready
# timeout after 60s
TIMEOUT=60
COUNTER=0
until php -r "try { new PDO('pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'ok'; } catch (Exception \$e) { exit(1);}"; do
  COUNTER=$((COUNTER+1))
  if [ $COUNTER -ge $TIMEOUT ]; then
    echo "Database didn't become ready after ${TIMEOUT}s"
    break
  fi
  echo "Waiting for database... ($COUNTER)"
  sleep 1
done

# Run migrations (force so non-interactive)
php artisan migrate --force

# Run only the superadmin seeder (idempotent check recommended)
php artisan db:seed --class=SuperAdminSeeder --force || true

# You can add other seeders here, but prefer specific seeder classes rather than run DatabaseSeeder blindly

# Start the main process (example: php-fpm)
# Replace with whatever your Dockerfile expects as CMD
exec "$@"
