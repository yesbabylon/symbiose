#!/bin/bash
ng build && cd "dist/sb-shared-lib" && npm link && cd  ../..
