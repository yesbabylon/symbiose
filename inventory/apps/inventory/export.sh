#!/bin/bash
# cp -r version ../version && cp -r web.app ../web.app && cp -r manifest.json ../manifest.json
rm -rf ../../../../public/inventory && mkdir ../../../../public/inventory && cp -a dist/symbiose/* ../../../../public/inventory/