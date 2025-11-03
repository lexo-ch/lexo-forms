#/bin/bash

NEXT_VERSION=$1
CURRENT_VERSION=$(cat composer.json | grep version | head -1 | awk -F= "{ print $2 }" | sed 's/[version:,\",]//g' | tr -d '[[:space:]]')

sed -ie "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEXT_VERSION\"/g" composer.json
rm -rf composer.jsone

sed -ie "s/Version:           $CURRENT_VERSION/Version:           $NEXT_VERSION/g" lexo-forms.php
rm -rf lexo-forms.phpe

sed -ie "s/Stable tag: $CURRENT_VERSION/Stable tag: $NEXT_VERSION/g" readme.txt
rm -rf readme.txte

sed -ie "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEXT_VERSION\"/g" info.json
rm -rf info.jsone

sed -ie "s/v$CURRENT_VERSION/v$NEXT_VERSION/g" info.json
rm -rf info.jsone

sed -ie "s/$CURRENT_VERSION.zip/$NEXT_VERSION.zip/g" info.json
rm -rf info.jsone

npx mix --production
sudo composer dump-autoload -oa

mkdir lexo-forms

cp -r assets lexo-forms
cp -r languages lexo-forms
cp -r dist lexo-forms
cp -r src lexo-forms
cp -r vendor lexo-forms
cp -r template-forms lexo-forms

cp ./*.php lexo-forms
cp LICENSE lexo-forms
cp readme.txt lexo-forms
cp README.md lexo-forms
cp CHANGELOG.md lexo-forms

zip -r ./build/lexo-forms-$NEXT_VERSION.zip lexo-forms -q
rm -rf lexo-forms
