#!/bin/bash

sudo cp geth /usr/local/bin/
geth version
geth --datadir ./node init ./genesis.json
geth --datadir ./node account new
