#!/bin/bash
mv ./packages ./packages.core
git clone https://github.com/yesbabylon/symbiose.git packages
cp -r ./packages.core/core ./packages/