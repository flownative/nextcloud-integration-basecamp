#!/bin/bash
# Wrapper script for executing commands in the Nextcloud dev container
# Usage: ./nc-dev.sh <command> [args...]
# Examples:
#   ./nc-dev.sh occ app:enable integration_basecamp
#   ./nc-dev.sh log
#   ./nc-dev.sh log-basecamp
#   ./nc-dev.sh php -r "echo 'hello';"
#   ./nc-dev.sh curl <url>
#   ./nc-dev.sh bash -c "some command"

CONTAINER_NAME="nc-dev"

case "$1" in
  occ)
    shift
    docker exec -u www-data "$CONTAINER_NAME" php occ "$@"
    ;;
  log)
    docker exec "$CONTAINER_NAME" cat /var/www/html/data/nextcloud.log | tail -${2:-20}
    ;;
  log-basecamp)
    docker exec "$CONTAINER_NAME" cat /var/www/html/data/nextcloud.log | grep -i "basecamp\|IntegrationBasecamp" | tail -${2:-20}
    ;;
  log-errors)
    docker exec "$CONTAINER_NAME" cat /var/www/html/data/nextcloud.log | grep '"level":[34]' | tail -${2:-20}
    ;;
  curl)
    shift
    docker exec "$CONTAINER_NAME" curl -s -u admin:admin "$@"
    ;;
  php)
    shift
    docker exec -u www-data "$CONTAINER_NAME" php "$@"
    ;;
  *)
    docker exec -u www-data "$CONTAINER_NAME" "$@"
    ;;
esac
