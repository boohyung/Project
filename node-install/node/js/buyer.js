ether = Math.pow(10,18);
//new Date(year,month,day,hour,minute,seconds);
t1 = new Date(2017, 9, 31, 12,0,0).getTime()/1000;
t2 = new Date(2017, 9, 31, 13,0,0).getTime()/1000;

function buy() {
    purchase(50*ether, 12*ether, 300, 0, t1, t2, 20);
}
function buy1() {
    purchase(50*ether, 5*ether, 500, 0, t1, t2, 20);
}
function buy2() {
    purchase(55*ether, 5*ether, 400, 0, t1, t2, 20);
}

function buy3() {
    purchase(60*ether, 7*ether, 400, 0, t1, t2, 20);
}
function buy4() {
    purchase(65*ether, 9*ether, 200, 0, t1, t2, 20);
}

function purchase(max, add, power, rate, powerStart, powerEnd, prepare){
    var POWER = parseInt(100);

    var i = j = k = l = 0;
    var finish = false;
    
    var candidate = new Array();
    var bidding = new Array();
    var successCon = new Array();
    var failCon = new Array();
    var block_num;

    var num = power/POWER;
    num = parseInt(num);

    eth.defaultAccount = eth.coinbase;

    console.log("My Wallet: "+ bal(eth.coinbase) +"\nRaising "+ add/ether +" ether each!\n");
    
    var cur_block = eth.blockNumber;
    get_candidate(candidate, (parseInt(cur_block)-parseInt(prepare)) ,max, POWER, rate, powerStart, powerEnd);

    console.log("Init candidate contract number: "+ candidate.length);

    var nowtime;
    cur_block = eth.blockNumber;
    while( !finish ){
        
        if(cur_block < eth.blockNumber){
            cur_block = eth.blockNumber;
            console.log("\n\nCurrent block number <"+ cur_block +">");
            add_flag = candidate.length;
            get_candidate(candidate,cur_block ,max, POWER, rate, powerStart, powerEnd);
            
            //if candidate contract ended!
            for(i=0;i<candidate.length;i++){
                if( candidate[i].ended() ) candidate.splice(i--,1);
            }

            //if candidate array changed, show candidate array
            if(add_flag != candidate.length){
                for(i=0;i<candidate.length;i++){
                    print_con(candidate[i],i);
                }
            }
            
            //move to bidding from candidate (if something fail or bidding empty and minus success contract)
            while(bidding.length < (num - successCon.length) && candidate.length > 0) {
                bidding[bidding.length] = get_lowest_con(candidate);
            }

            //bidding array process
            for(j=0;j<bidding.length;j++){
                //find success contract, move to successCon array
                if(bidding[j].ended() && bidding[j].Bidder() == eth.coinbase){
                    console.log("Success Contract ["+(successCon.length+1)+"]...");
                    successCon[successCon.length] = bidding[j];
                    bidding.splice(j,1);

                    if( (parseInt(successCon.length) * POWER) == power){
                        finish = true;
                        for(i=0;i<successCon.length;i++){
                            print_con(successCon[i],i);
                        }
                    }
                    j--;
                } 
                
                //find fail or bidding contract
                else if(bidding[j].Bidder() != eth.coinbase){
                    var nowbid = parseInt(bidding[j].Bid());

                    //how much next money
                    addbid_money = parseInt(bidding[j].min());
                    if(addbid_money <= nowbid ) {
                        addbid_money = nowbid + parseInt(add);
                    }
                    while(addbid_money <= nowbid) {
                        addbid_money = parseInt(addbid_money) + parseInt(add);
                    }
                    if( (addbid_money <= nowbid) && (addbid_money+add > nowbid) ) addbid_money = parseInt(max);
                    
                    // find fail contract in bidding array, move to failCon array
                    nowtime = eth.getBlock(eth.blockNumber).timestamp;

                    if( (addbid_money > max) || (( parseInt(bidding[j].auctionStart()) + parseInt(bidding[j].biddingTime())) < nowtime) ){ 
                        failCon[failCon.length] = bidding[j];
                        bidding.splice(j,1);
                        j--;
                    } 
                    
                    //more bid in bidding Contract array
                    else if(select_candidate(bidding[j], addbid_money, powerStart, powerEnd)){
                        console.log("Try bidding["+(j+1)+"]: "+addbid_money);
                        print_con(bidding[j],j);
                        money = addbid_money;
                        conBid(money,bidding[j]);
                    }

                //bidding on me
                }
                else if(bidding[j].Bidder() == eth.coinbase) {
                    console.log("bidding["+j+"]: highest bidder is me!");
                }
            }
        }
    }

    //withdraw(failCon);
    for(i=0;i<failCon.length;i++) failCon[i].withdraw();
    failCon.splice(0,failCon.length-1);

    //withdraw(successCon);
    for(i=0;i<successCon.length;i++) successCon[i].withdraw();
    successCon.splice(0,successCon.length-1);
}

function get_lowest_con(candidate){
    var min_con = candidate[0];
    var min_index;

    for(var i=0;i<candidate.length;i++){
        if(parseInt(min_con.Bid()) > parseInt(candidate[i].Bid())){
            min_con = candidate[i];
            min_index = i;
        }
    }
    candidate.splice(min_index,1);
    return min_con;
}

function get_candidate(arr_con, block_start, max, power, rate, powerStart, powerEnd){
    var block_i;
    var cur_block = eth.blockNumber+1;
    var arr_tx = [];
    var i=0;
    var con;
    
    for(block_i=block_start;block_i<cur_block; block_i++){
        arr_tx = eth.getBlock(block_i).transactions;        
        for(i=0;i<arr_tx.length;i++){
            if(assort_con(arr_tx,i)){
                con = getcon(eth.getTransactionReceipt(arr_tx[i]).contractAddress,abi);
                if(select_candidate(con, max, powerStart, powerEnd)){
                    arr_con[arr_con.length] = con;
                }
            }
        }   
    }
}

function select_candidate(con, max, Start, End){
    var highest = parseInt(con.Bid());
    //# about power time
    var conpowerstart = parseInt(con.powerStart());
    var conpowerend = parseInt(con.powerEnd());
    if( (!ended_check(con)) && (highest < max) && 
        (eth.getBalance(eth.coinbase)*1 > max) && 
        ( (conpowerstart <= Start && conpowerstart <= End) && (conpowerend >= Start && conpowerend <= End) ) ) {
        return true;
    }
    else return false;
}

function ended_check(con){
    var timeEnd = (parseInt(con.auctionStart()) + parseInt(con.biddingTime())) * 1000;
    if((timeEnd < new Date().getTime()) && con.ended()){
        return true;
    }
    else return false;

}

function assort_con(arr_tx,i){
    if( eth.getTransaction(arr_tx[i]).value == 0 && eth.getTransaction(arr_tx[i]).to == null &&
        eth.getTransactionReceipt(arr_tx[i]).contractAddress != null){
    return true;
    }   
    else return false;
}

function print_con(con,i){
    console.log("["+(i+1)+"]     Contract address: \"" + con.address + "\"");
    console.log("\tPower: " + con.power());
    if(con.Bidder() == eth.coinbase){
        console.log("\tMy Contract!!!");
    }
    else{
        console.log("\tHighest bidder is not me!");
    }
    console.log("\tHighest price: " + con.Bid()/Math.pow(10,18) + " ether" ); 
    console.log("\tStart price: " + con.min() + " ether\n");
}

function bal(a) {
    return web3.fromWei(eth.getBalance(a));
}

function getcon(addr, abi) {
	var con = web3.eth.contract(abi);
	return con.at(addr);
}

function conBid(b,con) {
    return con.bid({from: eth.coinbase, value: b});
}
