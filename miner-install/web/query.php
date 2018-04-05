<?php
require("ethereum-php-edit/ethereum.php");
require("contract.php");

//echo "마이크로그리드 test 페이지<br/><br/>\n";
// 한국시간 설정
date_default_timezone_set('Asia/Seoul');

$ethereum = new Ethereum('127.0.0.1', 6000);
$eth = $ethereum;
$from = $ethereum->eth_coinbase();
$blocks = array();
$blocktime = array();
$conarr = array();

function ethcall($to, $data) {
    global $eth, $from;
    $msg = new Ethereum_Message($from, $to, "0x0", "0x0", "0x0", $data, null);
    return $eth->eth_call($msg,1);
}

function tohex($INT) {
    return "0x".dechex($INT);
}
function toint($HEX) {
    return hexdec($HEX);
}

function getBlock($a, $b) {
    global $eth, $blocks, $blocktime;
    $i = 0;
    if($a<$i) $a = $i;

    $curBlock = hexdec($eth->eth_blockNumber());
    for($i = $a; $i<=$b && $i<= $curBlock; $i++) {
        if(!isset($block[$i])) {
            $blocks[$i] = $eth->eth_getBlockByNumber(tohex($i));
            $blocktime[$i] = toint($blocks[$i]->timestamp);
        }
    }
}

function blockEcho($i) {
    global $blocks;
    echo json_encode($blocks[$i], JSON_PRETTY_PRINT);
}

function getCon($a, $b) {
    global $blocks, $eth, $conarr, $from;
    $i;
    $j;
    $len;
    for($i = $a; $i<=$b; $i++) {
        $len = count($blocks[$i]->transactions);
        for($j = 0; $j < $len; $j++) {
            if( $blocks[$i]->transactions[$j]->to == null &&
                $blocks[$i]->transactions[$j]->v == 0 &&
                strlen($blocks[$i]->transactions[$j]->input) > 100) {
                
                array_push($conarr, new Contract(//from,to,tx,block
                    $from,
                    $eth->eth_getTransactionReceipt($blocks[$i]->transactions[$j]->hash)->contractAddress,
                    $blocks[$i]->transactions[$j]->hash,
                    $i
                ));
            }
        }
    }
}

function conEcho($a, $b) {
    global $conarr;
    
    $len = count($conarr);
    for($i=0; $i < $len; $i++) {
        echo json_encode($conarr[$i]->coninfo, JSON_PRETTY_PRINT) . "<br/>";
    }
}

function auctionInfobyAuctiontime($t1, $t2) {
    global $conarr, $toint;
    //전력 공급, 판매 카운드
    $sellerCount=0;
    //판매자 최고가
    $sellerHighest=0;
    //판매 총 금액(평균)
    $sellerSum=0;
    //판매자 최저가(billion ether)
    $sellerLowest=pow(10, 27);
    $sellerPower=0;

    //구매자 카운트, 거래완료 컨트랙트, 소비량
    $buyerCount=0;
    //구매자 최고가
    $buyerHighest=0;
    //구매자 총함(평균), Bid();
    $buyerSum=0;
    //구매자최저가(billion ether)
    $buyerLowest=pow(10,27);
    $buyerPower=0;

    $len = count($conarr);

    for($i=0; $i < $len; $i++) {
        //contract의 최신정보 가져오기
        $conarr[$i]->update();
        //옥션이 전달받은 시간 안에서 동작하는 지 확인
        if( $conarr[$i]->coninfo['auctionStart'] + $conarr[$i]->coninfo['biddingTime'] <= $t2 &&
            $conarr[$i]->coninfo['auctionStart'] + $conarr[$i]->coninfo['biddingTime'] >= $t1
            ) {
            //구간 내에서 경매 개시 카운팅
            $sellerCount++;
            //가격, 전력 총합 계산
            $sellerSum += $conarr[$i]->coninfo['min'];
            $sellerPower += $conarr[$i]->coninfo['power'];

            //최대값, 최소값 찾아내기
            if($conarr[$i]->coninfo['min'] > $sellerHighest) $sellerHighest = $conarr[$i]->coninfo['min'];
            if($conarr[$i]->coninfo['min'] < $sellerLowest) $sellerLowest = $conarr[$i]->coninfo['min'];
            // echo $conarr[$i]->coninfo['address']."<br/>";

            // 종료된 경매라면 구매자 정보 세팅
            if( toint($conarr[$i]->callfunc("ended()")) == 1 && toint($conarr[$i]->callfunc("Bid()")) != 0) {
                //구간내 개시된 경매의 낙찰 카운팅
                $buyerCount++;
                $temp =  toint($conarr[$i]->callfunc('Bid()'));
                
                //가격, 전력 총합 계산
                $buyerSum += $temp;
                $buyerPower += $conarr[$i]->coninfo['power'];
                //최대값, 최소값 계산
                if($temp > $buyerHighest) $buyerHighest = $temp;
                if($temp < $buyerLowest) $buyerLowest = $temp;
            }
        }
    }
    if($sellerCount == 0) $sellerCount = -1;
    if($buyerCount == 0) $buyerCount = -1;
    if($buyerLowest == pow(10,27)) $buyerLowest =-1;
    if($sellerLowest == pow(10,27)) $sellerLowest =-1;
    return array(
        "sellerCount" => $sellerCount,
        "sellerHighest" => $sellerHighest,
        "sellerAverage" => $sellerSum / $sellerCount,
        "sellerLowest" => $sellerLowest,
        "sellerSum" => $sellerSum,
        "sellerPower" => $sellerPower,

        "buyerCount" => $buyerCount,
        "buyerHighest" => $buyerHighest,
        "buyerAverage" => $buyerSum / $buyerCount,
        "buyerLowest" => $buyerLowest,
        "buyerPower" => $buyerPower
        );
}

function auctionInfobySupplytime($t1, $t2) {
    global $conarr, $toint;
    //전력 공급, 판매 카운드
    $sellerCount=0;
    //판매자 최고가
    $sellerHighest=0;
    //판매 총 금액(평균)
    $sellerSum=0;
    //판매자 최저가(billion ether)
    $sellerLowest=pow(10, 27);
    $sellerPower=0;

    //구매자 카운트, 거래완료 컨트랙트, 소비량
    $buyerCount=0;
    //구매자 최고가
    $buyerHighest=0;
    //구매자 총함(평균), Bid();
    $buyerSum=0;
    //구매자최저가(billion ether)
    $buyerLowest=pow(10,27);
    $buyerPower=0;

    $len = count($conarr);

    for($i=0; $i < $len; $i++) {
        //contract의 최신정보 가져오기
        $conarr[$i]->update();
        //전력 공급이 전달받은 시간 안에서 동작하는 지 확인
        if( ($conarr[$i]->coninfo['powerStart'] >= $t1 && $conarr[$i]->coninfo['powerStart'] <= $t2) &&
            ($conarr[$i]->coninfo['powerEnd'] >= $t1 && $conarr[$i]->coninfo['powerEnd'] <= $t2) &&
            ($conarr[$i]->coninfo['powerStart'] < $conarr[$i]->coninfo['powerEnd'])
            ) {
            //구간 내에서 전력 공급 카운팅
            $sellerCount++;
            //가격, 전력 총합 계산
            $sellerSum += $conarr[$i]->coninfo['min'];
            $sellerPower += $conarr[$i]->coninfo['power'];

            //최대값, 최소값 찾아내기
            if($conarr[$i]->coninfo['min'] > $sellerHighest) $sellerHighest = $conarr[$i]->coninfo['min'];
            if($conarr[$i]->coninfo['min'] < $sellerLowest) $sellerLowest = $conarr[$i]->coninfo['min'];
            // echo $conarr[$i]->coninfo['address']."<br/>";

            // 종료된 경매라면 구매자 정보 세팅
            if( toint($conarr[$i]->callfunc("ended()")) == 1 && toint($conarr[$i]->callfunc("Bid()")) != 0) {
                //구간내 개시된 경매의 낙찰 카운팅
                $buyerCount++;
                $temp =  toint($conarr[$i]->callfunc('Bid()'));
                
                //가격, 전력 총합 계산
                $buyerSum += $temp;
                $buyerPower += $conarr[$i]->coninfo['power'];
                //최대값, 최소값 계산
                if($temp > $buyerHighest) $buyerHighest = $temp;
                if($temp < $buyerLowest) $buyerLowest = $temp;
            }
        }
    }
    if($sellerCount == 0) $sellerCount = -1;
    if($buyerCount == 0) $buyerCount = -1;
    if($buyerLowest == pow(10,27)) $buyerLowest =-1;
    if($sellerLowest == pow(10,27)) $sellerLowest =-1;
    return array(
        "sellerCount" => $sellerCount,
        "sellerHighest" => $sellerHighest,
        "sellerAverage" => $sellerSum / $sellerCount,
        "sellerLowest" => $sellerLowest,
        "sellerSum" => $sellerSum,
        "sellerPower" => $sellerPower,

        "buyerCount" => $buyerCount,
        "buyerHighest" => $buyerHighest,
        "buyerAverage" => $buyerSum / $buyerCount,
        "buyerLowest" => $buyerLowest,
        "buyerPower" => $buyerPower
        );
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////


$from = $ethereum->eth_coinbase();
$to = "0xda983322d8ac92d1564c40fd7658301652ca00d9";
//$data = "0x4f245ef70000000000000000000000000000000000000000000000000000000000000000";

$data = "0x4f245ef7";

$msg = new Ethereum_Message($from, $to,"0x0","0x0","0x0",$data,null);

echo "<br/> -----------------------------------------<br/>";

//$a = 151900;
//$b = 152026;
$a = 151880;
$b = 152026;


$curBlock = hexdec($eth->eth_blockNumber());
//$b = $curBlock;
echo "cur block num: ".$curBlock."<br/>";

//a와 b 번호 사의값을 갖는 블록 정보 수집
getBlock($a, $b);
//블록 중 컨트랙트 정보만을 수집
getCon($a, $b);

echo 
date("Y-m-d H:i:s", $blocktime[$a])
."~~".
date("Y-m-d H:i:s", $blocktime[$b]);

echo " !!!<br/>";

echo 
toint($eth->eth_getBlockBynumber(tohex($a))->timestamp)
."~~~".
toint($eth->eth_getBlockBynumber(tohex($b))->timestamp);

echo "<br/>";
echo "<br/>-----------------------------------------------------<br/>";
echo "auctionInfobyAuctiontime:";
echo "<br/>";
echo json_encode(auctionInfobyAuctiontime( $blocktime[$a], $blocktime[$b]));

//strtotime("2017-10-13 04:00:00"),strtotime("2017-10-13 05:13:00")));
echo "<br/>-----------------------------------------------------<br/>";
echo "auctionInfobySupplytime:";
echo "<br/>";
echo json_encode(auctionInfobySupplytime(
                    1600000000,
                    1700000000
                    ));
?>
