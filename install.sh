#!/bin/bash
git checkout dev-2.0
mv ./packages ./packages.core
git clone https://github.com/yesbabylon/symbiose.git packages
cp -r ./packages.core/core ./packages/