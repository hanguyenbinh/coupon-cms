<?php

namespace App\Http\Controllers;
use JWTAuth;
use Validator;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Gift;
use App\Models\CouponRedeem;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class GiftController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $gifts = Gift::all();
        return $this->sendResponse(JsonResource::collection($gifts), 'MSG_GET_ALL_GIFTS_SUCCESS');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $req = Validator::make($request->all(), [
            'defaultName' => 'required|string',
            'couponExchangeRate' => 'required|integer|min:1'
        ]);

        if($req->fails()){
            // return response()->json($req->errors()->toJson(), 400);
            return $this->sendError('INVALID_INPUT', $req->errors()->toJson(), 400);
        }
        $gift = Gift::create($req->validated());
        return $this->sendResponse($gift, 'MSG_CREATE_GIFT_SUCCESS');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Gift  $gift
     * @return \Illuminate\Http\Response
     */
    public function show(Gift $gift)
    {
        //
        return $this->sendResponse($gift, 'MSG_GET_GIFT_SUCCESS');
    }
    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Gift  $gift
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Gift $gift)
    {
        //
        $gift->update($request->all());
        return $this->sendResponse($gift, 'MSG_UPDATE_GIFT_SUCCESS');  
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Gift  $gift
     * @return \Illuminate\Http\Response
     */
    public function destroy(Gift $gift)
    {
        //
        $gift->destroy();
        return $this->sendResponse($gift, 'MSG_DELETE_GIFT_SUCCESS');
    }

    public function redeem(Request $request, $giftId) {
        $req = Validator::make($request->all(), [
            'coupons' => 'required|array|min:1'
        ]);

        if($req->fails()){
            // return response()->json($req->errors()->toJson(), 400);
            return $this->sendError('INVALID_INPUT', $req->errors()->toJson(), 400);
        }
        $gift = Gift::find($giftId);
        if (!$gift){
            return $this->sendError('INVALID_GIFT', 'gift not found', 400);
        }
        $couponIdArr = $req->validated()['coupons'];
        if(count($couponIdArr) < $gift->couponExchangeRate){
            return $this->sendResponse(null, 'MSG_NOT_ENOUGH_COUPONS_TO_EXCHANGE_GIFT');
        }
        $redeemedCoupons = CouponRedeem::whereIn('couponId', $couponIdArr)->get();
        if (count($redeemedCoupons) > 0){
            return $this->sendResponse(null, 'MSG_COUPON_ALREADY_REDEEMED');
        }

        $user = JWTAuth::parseToken()->authenticate();
        $redeemCoupons = [];
        $curDate = date('Y-m-d H:i:s');
        for ($i = 0; $i < $gift->couponExchangeRate; $i++){
            $redeemCoupons[] = [
                'id' =>Uuid::uuid4()->toString(),
                'couponId' => $couponIdArr[$i],
                'redeemBy' => $user->id,
                'giftId' => $gift->id,
                'created_at' => $curDate,
                'updated_at' => $curDate
            ];
        }
        $result = CouponRedeem::insert($redeemCoupons);
        // return $this->sendResponse(JsonResource::collection($couponIdArr), 'MSG_REDEEM_COUPON_SUCCESS');
        return $this->sendResponse(($redeemedCoupons), 'MSG_REDEEM_COUPON_SUCCESS');
    }
}
