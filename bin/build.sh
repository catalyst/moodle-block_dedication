#!/bin/bash

if [ ! -e ./dedication.php ]; then
    echo "Path fail"
    exit 1
fi

echo "Cleaning dist folder"
rm -rf ./dist/
mkdir -p dist/dedication

echo "Copying files to dist folder"
cp -r ./db ./dist/dedication
cp -r ./lang ./dist/dedication
cp *.php ./dist/dedication
cp *.css ./dist/dedication
cp ./COPYING ./dist/dedication
cp ./README.txt ./dist/dedication

#echo "Running composer in production mode"
#composer install --quiet --no-dev --working-dir ./dist/dedication/
#composer dumpautoload --quiet --optimize --no-dev --working-dir ./dist/dedication/

#echo "Removing composer entries"
#rm -f ./dist/dedication/composer.json
#rm -f ./dist/dedication/composer.lock

# Create zip file
(cd ./dist && zip -q -r dedication.zip dedication/)

echo "Dist zip files created in ./dist/**.zip"

# Cleaning dist folder
rm -rf ./dist/dedication

exit 0