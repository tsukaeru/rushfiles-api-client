#!/bin/bash

declare -A php_patch_versions
php_patch_versions=(["5.6"]="5.6.40" ["7.0"]="7.0.33" ["7.1"]="7.1.33" ["7.2"]="7.2.34" ["7.3"]="7.3.29" ["7.4"]="7.4.21" ["8.0"]="8.0.8")

help() {
    cat << EndOfHelp
This script automatically runs PHPUnit tests for different PHP versions.
Usage:
	./test.sh [OPTIONS]

Options:
	-a|-all         adds all supported versions

	-v|--version    add specific PHP versions (can be used multiple times)

	-h|--help       display this help message

Note:
	If patch version is omitted, latest (as of writing this help message) patch will be automatically used. E.g.
		./test.sh -v 7.3
	will run tests against PHP versions 7.3.29.
EndOfHelp
}

php_versions=()
while [[ $# -gt 0 ]]; do
  key="$1"

  case $key in
    -a|--all)
      php_versions+=("5.6" "7.0" "7.1" "7.2" "7.3" "7.4" "8.0")
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
    php_versions=("8.0")
fi

COMPOSER_CONFIG="${COMPOSER_HOME:-$HOME/.composer}/config.json"
COMPSOER_CONFIG_BACKUP="${COMPOSER_CONFIG}.bak"

failed=()
for version in "${php_versions[@]}"
do
    # Normalize PHP version
    version="${php_patch_versions[$version]:-$version}"

    rm -f composer.lock

    mv $COMPOSER_CONFIG $COMPSOER_CONFIG_BACKUP

    # install dependencies for this version
    echo "Installing dependencies for PHP versions $version..."
    docker run --rm -v ${PWD}:/app -v ${COMPOSER_HOME:-$HOME/.composer}:/tmp --user $(id -u):$(id -g) composer /bin/bash -c "composer config -g platform.php ${version} && composer install"
    # run tests for this version
    docker run -v ${PWD}:/app -w /app php:${version}-alpine ./vendor/phpunit/phpunit/phpunit

    mv $COMPSOER_CONFIG_BACKUP $COMPOSER_CONFIG

    if [ $? -ne 0 ]; then
        failed+=("$version")
    fi
done

if [ ${#failed[@]} -ne 0 ]; then
    echo "Tests have failed for PHP versions:"
    for version in "${failed[@]}"
    do
        echo -e "\t$version"
    done
    exit 1
else
    echo "All tests have passed"
    exit 0
fi
