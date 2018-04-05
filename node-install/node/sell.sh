#!/bin/bash

geth attach  --preload 'js/loadsol.js,js/seller.js' --exec 'supply()'
