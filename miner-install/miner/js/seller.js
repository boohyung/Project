ether = Math.pow(10,18);
miner = "0x5ce4479d082d627bb312522e413df8439873f129";

//new Date(year,month,day,hour,minute,seconds);
//2017-10-13 12:00:00 ~ 2017-10-13 13:00:00
t1 = new Date(2017, 9, 31, 12,0,0).getTime()/1000;
t2 = new Date(2017, 9, 31, 13,0,0).getTime()/1000;

function supply() {
    sell(200, 12*ether, 100, t1,t2,100,10, miner);
}

function supply1() {
    sell(400, 20*ether, 100, t1,t2,300,5, miner);
}
function supply2() {
    sell(300, 13*ether, 100, t1,t2,200,10, miner);
}

function sell(sec, min, power, powerStart, powerEnd, interval, num, miner){
    var i = j = k = l = end = 0;
    var finish = false;

    var pendingCon = new Array();
    var receiptCon = new Array();
    var auctionCon = new Array();
    var auctionHash = new Array();
    var end_flag = new Array();
    var fee_flag = new Array();

    console.log("Beneficiary: "+ eth.coinbase +"\nMy wallet: "+bal(eth.coinbase)+"\n");

    if(startTime > powerStart){             //powerstart time input error
        console.log("!powerStrat time error!");
    }

    var startTime =  eth.getBlock(eth.blockNumber).timestamp;
    var cur_block = eth.blockNumber;

    eth.defaultAccount = eth.coinbase;
    
    while( !finish ) {
        nowTime = eth.getBlock(cur_block).timestamp;

        //send contract and save the contract object in pendingCon array
        if( ( nowTime > (startTime + i*interval) * 1) && i < num ) {
            pendingCon[i] = sendcon(sec, min, power, powerStart, powerEnd);
            i++;
        }

        for(j=k; j<i; j++) {
            receiptCon[j] = eth.getTransactionReceipt(pendingCon[j].transactionHash);
            if(receiptCon[j] != null) { 
                auctionCon[k] = getcon(receiptCon[j].contractAddress, abi);
                auctionHash[k] = auctionCon[k].transactionHash; 
                console.log("auctionCon["+k+"]: "+ auctionCon[k].address);
                end_flag[k] = false;
                fee_flag[k] = false;
                k++;
            }
        }

        if(cur_block < eth.blockNumber){
            cur_block = eth.blockNumber;

            //check Auction end!
            for(l=0; l<k; l++) { 
                timeEnd = parseInt(auctionCon[l].auctionStart()) + parseInt(auctionCon[l].biddingTime());
                if(!end_flag[l] && (timeEnd < nowTime) ){
                    conEnd(auctionCon[l]);
                    end_flag[l] = true;
                } //check
                else if(!fee_flag[l] && end_flag[l] && auctionCon[l].ended()){ 
                    console.log("\nauctionCon["+l+"] is ended!");
                    fee_flag[l] = true;
                    coninfo(auctionCon[l]);
                    fee = parseInt(auctionCon[l].Bid()) * 0.01;
                    if(fee > 0) {
                        sendto(eth.coinbase,miner,parseInt(fee));
                        console.log("  ===>> Pay fee to miner!");
                    }
                    end++;
                }
            }
            if(num == end){
                finish = true;
                console.log("\nAll auction is End!!\nMy wallet: "+bal(eth.coinbase)+"\n");
            }
        }
    }
}

function sendcon(sec, min, power, powerStart, powerEnd) {
    var type = 1;

    var myContract = eth.contract(abi);            
    var txDeploy = {from: eth.coinbase, data: bin, gas: 1000000};

    var start = eth.getBlock(eth.blockNumber).timestamp;

    return myContract.new(type, eth.coinbase, start, sec, min, 0, power, powerStart, powerEnd, txDeploy, function(e, contracts){
            if(!e) {               
                if(!contract.address) {
                    console.log("need to mined\n");   
                }   
                else {
                    console.log("mined!: "+contracts.address);
                    console.log(contracts);             
                }
            }
        });
}

function getcon(addr, abi) {
        var con = web3.eth.contract(abi);
        return con.at(addr);
}
function conEnd(con){
        con.auctionEnd();
}

function coninfo(con) {
        console.log("\tAuction start time: "+con.auctionStart() +" seconds");
        console.log("\tBidding time: "+con.biddingTime() +" seconds");
        console.log("\tStart price: "+ parseInt(con.min())/ether +" ether");
        console.log("\tHighest Bidder: \""+con.Bidder()+"\"");
        console.log("\tHighest Bid: "+ parseInt(con.Bid())/ether +" ether");
}

function bal(a){
    return web3.fromWei(eth.getBalance(a));
}

function sendto(a, b, v){
    return eth.sendTransaction({from: a, to: b, value: v});
}

