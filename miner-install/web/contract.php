<?php
require_once("ethereum-php-edit/ethereum.php");

class Contract {
    public $confunc, $coninfo, $msg, $eth;


    function __construct($_from, $_to, $_tx, $_block) {
        $this->eth = new Ethereum('127.0.0.1', 6000);
                                        //from      to  gas gasprice    value   data    nonce;
        $this->msg = new Ethereum_Message($_from, $_to, "0x0", "0x0", "0x0", "0x10", null);

        $this->confunc = array(       //to_sha3
                "conType()"         =>  "0xfd39011c",
                "auctionStart()"    =>  "0x4f245ef7",
                "biddingTime()"     =>  "0xd074a38d",

                "benefieciary()"    =>  "0xf1eeed6e",
                "min()"             =>  "0xf8897945",
                "max()"             =>  "0x6ac5db19",

                "power()"           =>  "0x4a4d59fa",
                "powerStart()"      =>  "0x15026d82",
                "powerEnd()"        =>  "0xd437746c",

                "countbid()"        =>  "0xafaee812",
                "Bidder()"          =>  "0xc7d2a2a5",
                "Bid()"             =>  "0x6e6452cb",
                "ended()"           =>  "0x12fa6feb"
                );

        $this->coninfo = array(       //con info
                "conType"       =>  toint($this->callfunc("conType()")),
                "auctionStart"  =>  toint($this->callfunc("auctionStart()")),
                "biddingTime"   =>  toint($this->callfunc("biddingTime()")),

                "benefieciary"  =>  $this->callfunc("benefieciary()"),
                "min"           =>  toint($this->callfunc("min()")),
                "max"           =>  toint($this->callfunc("max()")),

                "power"         =>  toint($this->callfunc("power()")),
                "powerStart"    =>  toint($this->callfunc("powerStart()")),
                "powerEnd"      =>  toint($this->callfunc("powerEnd()")),

                "countbid"      =>  toint($this->callfunc("countbid()")),
                "Bidder"        =>  $this->callfunc("Bidder()"),
                "Bid"           =>  toint($this->callfunc("Bid()")),
                "ended"         =>  toint($this->callfunc("ended()")),

                "address"       =>  $_to,
                "txhash"        =>  $_tx,
                "block"         =>  $_block
                );

    }
    function update() {
                $this->coninfo["conType"]       = toint($this->callfunc("conType()"));
                $this->coninfo["auctionStart"]  = toint($this->callfunc("auctionStart()"));
                $this->coninfo["biddingTime"]   = toint($this->callfunc("biddingTime()"));

                $this->coninfo["benefieciary"]  = $this->callfunc("benefieciary()");
                $this->coninfo["min"]           = toint($this->callfunc("min()"));
                $this->coninfo["max"]           = toint($this->callfunc("max()"));

                $this->coninfo["power"]         = toint($this->callfunc("power()"));
                $this->coninfo["powerStart"]    = toint($this->callfunc("powerStart()"));
                $this->coninfo["powerEnd"]      = toint($this->callfunc("powerEnd()"));

                $this->coninfo["countbid"]      = toint($this->callfunc("countbid()"));
                $this->coninfo["Bidder"]        = $this->callfunc("Bidder()");
                $this->coninfo["Bid"]           = toint($this->callfunc("Bid()"));
                $this->coninfo["ended"]         = toint($this->callfunc("ended()"));
    }
    function tohex($INT) {
            return "0x".dechex($INT);
    }
    function toint($HEX) {
            return hexdec($HEX);
    }
    function contractinfo() {return $this->coninfo;}
    function setto($_to) { $this->msg->setto($_to); }
    function setdata($_data) { $this->msg->setdata($_data); }

    function callfunc($_key) {
        $this->setdata($this->confunc[$_key]);
        return $this->eth->eth_call($this->msg, 1);
    }
    function powerperMin() {
        return $this->callfunc("power()") / $this->callfunc("min()");
    }
    function powerperBid() {
        return $this->callfunc("power()") / $this->callfunc("Bid()");
    }
    function auctionClose() {
        return $this->callfunc("auctionStart()") + $this->callfunc("biddingTime()");
    }
    function powerperSec() {
        return $this->callfunc("power()") / $this->callfunc("powerEnd()")-$this->callfunc("powerStart()");
    }
}
?>
