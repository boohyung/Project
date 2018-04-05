#!/bin/bash

sudo cp geth /usr/local/bin/
geth version
geth --datadir ./miner init ./genesis.json
geth --datadir ./miner account new
