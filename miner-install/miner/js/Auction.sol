pragma solidity ^0.4.11;

//func SimpleAuction()
//func bid()
//func withdraw()
//func auctionEnd()

contract Auction {
    uint32 public conType;          //컨트랙트 타입(1 판매)
    uint32 public nodeID;           //노드 식별자
    uint32 public groupID;          //그룹 식별자

    address public beneficiary;     //수혜자
    uint32 public auctionStart;     //경매 시작(자동)
    uint32 public biddingTime;      //경매 종료(지정)
    uint public min;                //최저가
    uint public max;                //최대가
    
    uint32 public power;            //거래 전력량
    uint32 public powerStart;       //거래 시작 시각
    uint32 public powerEnd;         //거래 종료 시각

    address public Bidder;          //최고가 입찰자
    uint public Bid;                //현재 입찰가격

    bool public ended;              //경매 종료를 나타냄
    uint32 public Fee;              //수수료 필드

    uint32 creditTx;                //거래 신용등급
    uint32 creditReq;               //요구 신용등급
    uint32 Reserved;                //예약

    //이전 입찰자의 금액 회수를 위해 주소와 입찰가 매핑
    mapping(address => uint) pendingReturns;

    //컨트랙트의 변화를 노드에게 알림

    function Auction(         //생성자: 경매 시작- 종료, 거래 시작-종료, 최저가 등 초기화
        uint32 _type,
        address _beneficiary,
        uint32 _auctionStart,
        uint32 _biddingTime,

        uint _min, uint _max,

        uint32 _power,

        uint32 _powerStart,
        uint32 _powerEnd
    ) {
        conType = _type;
        beneficiary = _beneficiary;
        auctionStart = _auctionStart;
        biddingTime = _biddingTime;
        min = _min;
        max = _max;

        power = _power;
        powerStart = _powerStart;
        powerEnd = _powerEnd;

   }

    function bid() payable {            //송금 가능한 함수
        require(now <= (auctionStart + biddingTime));

        if(conType == 1) {               //구매 컨트랙트
            require(msg.value >= min);
            require(msg.value > Bid);
            
            if (Bidder != 0) {
                pendingReturns[Bidder] += Bid;
            }

            Bidder = msg.sender;
            Bid = msg.value;

        } else if(conType == 2) {
            require(false);
        } else require(false);
    }

    /// Withdraw a bid that was overbid.
    function withdraw() returns (bool) {
        uint amount = pendingReturns[msg.sender];
        if (amount > 0) {
            // It is important to set this to zero because the recipient
            // can call this function again as part of the receiving call
            // before `send` returns.
            pendingReturns[msg.sender] = 0;

            if (!msg.sender.send(amount)) {
                // No need to call throw here, just reset the amount owing
                pendingReturns[msg.sender] = amount;
                return false;
            }
        }
        return true;
    }

    function auctionEnd() {                 //경매를 끝내고 입찰가 회수
        // 1. 컨트랙트 상태
        require(now >= (auctionStart + biddingTime));   //경매가 끝나지 않음
        require(!ended);                                //이미 종료된 겨래

        // 2. 종료 적용
        ended = true;

        // 3. 입찰금 회수
        beneficiary.transfer(Bid);
    }
}
