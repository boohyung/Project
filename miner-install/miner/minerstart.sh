#!/bin/bash

geth --identity "miner" --networkid 42 --datadir "." --mine --minerthreads 4 --nodiscover --rpc --rpcport "6000" --rpcapi "db,eth,net,web3,miner" --port "6001"  --unlock 0 --password ./password.sec --ipcpath "~/.ethereum/geth.ipc"
