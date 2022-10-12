#!/bin/bash
rm -rf .angular
npm link sb-shared-lib
ng build --configuration production --output-hashing none --base-href="//documents\\"
