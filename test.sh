#!/bin/bash

help() {
    cat << EndOfHelp
This script automatically runs PHPUnit tests for different PHP versions.
Usage:
	./test.sh [OPTIONS]

Options:
	-a|-all         adds all supported versions

	-v|--version    add specific PHP versions (can be used multiple times)

	-h|--help       display this help message

EndOfHelp
}

php_versions=()
while [[ $# -gt 0 ]]; do
  key="$1"

  case $key in
    -a|--all)
      php_versions+=("7.3" "7.4" "8.0" "8.1" "8.2")
      shift # past argument
      ;;
    -v|--version)
      php_versions+=("$2")
      shift # past argument
      shift # past value
      ;;
    -h|--help)
      help
      exit 0
      ;;
  esac
done

if [ ${#php_versions[@]} -eq 0 ]; then
    php_versions=("latest")
fi

COMPOSER_CONFIG="${COMPOSER_HOME:-$HOME/.composer}/config.json"
COMPSOER_CONFIG_BACKUP="${COMPOSER_CONFIG}.bak"

failed=()
for version in "${php_versions[@]}"
do
    rm -f composer.lock

    mv $COMPOSER_CONFIG $COMPSOER_CONFIG_BACKUP

    # install dependencies for this version
    echo "Installing dependencies for PHP version: $version..."
    docker run --rm -t -v ${PWD}:/app -v ${COMPOSER_HOME:-$HOME/.composer}:/tmp --user $(id -u):$(id -g) composer:lts /bin/bash -c "composer config -g platform.php ${version} && composer install" > /dev/null
    # run tests for this version
    tag=$([ "$version" != "latest" ] && echo "$version-alpine" || echo "alpine")
    docker run -t --rm -v ${PWD}:/app -w /app php:${tag} ./vendor/phpunit/phpunit/phpunit

    if [ $? -ne 0 ]; then
        failed+=("$version")
    fi

    mv $COMPSOER_CONFIG_BACKUP $COMPOSER_CONFIG

    echo ""
done

Red='\033[0;31m'          # Red
Green='\033[0;32m'        # Green
Reset='\033[0m'           # No Color

if [ ${#failed[@]} -ne 0 ]; then
    echo -e "${Red}Tests have failed for PHP versions:${Reset}"
    for version in "${failed[@]}"
    do
        echo -e "\t$version"
    done
    exit 1
else
    echo -e "${Green}All tests have passed${Reset}"
    exit 0
fi
