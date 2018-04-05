#!/bin/bash

geth --identity "node" --networkid 42 --datadir "." --nodiscover --rpc --rpcport "6000" --rpcapi "db,eth,net,web3,miner" --port "6001"  --unlock 0 --password ./password.sec --ipcpath "~/.ethereum/geth.ipc"
