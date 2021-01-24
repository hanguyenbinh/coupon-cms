<?php

namespace App\Http\Controllers;
use JWTAuth;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Gift;
use App\Models\LangCommon;
use App\Models\Coupon;
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
    public function index(Request $request)
    {
        //
        $page = $request->has('page') ? $request->get('page') : 1;
        // $lang = $request->has('lang') ? $request->get('lang') : 'en';
        $limit = env('MAX_DISPLAY_RESULT');
        $gifts = Gift::leftJoin('lang_commons', function ($join){
                $join->on('gifts.id', '=', 'lang_commons.giftId');
            })
            ->whereIn(
                'gifts.id',
                Gift::select('id')
                    ->limit($limit)
                    ->offset(($page - 1) * $limit)
                    ->orderBy('defaultName')
                )            
            ->get([
                'gifts.*',
                'lang_commons.id as langId',
                'lang_commons.lang',
                'lang_commons.value'                
            ]);
        $totalRecords = Gift::count();
        $formattedGifts = [];
        foreach ($gifts as $gift){
            $isExisted = false;
            $language = new \stdClass;
            $language->id = $gift->langId;
            $language->lang = $gift->lang;
            $language->value = $gift->value;
            foreach($formattedGifts as $key=>$value){
                if ($value->id === $gift->id){
                    $isExisted = true;
                    if ($language->id) $formattedGifts[$key]->languages[] = $language;
                }
            }
            if (!$isExisted){
                $newGift = new \stdClass;
                $newGift->id = $gift->id;
                $newGift->defaultName = $gift->defaultName;
                $newGift->couponExchangeRate = $gift->couponExchangeRate;
                $newGift->inStock = $gift->inStock;
                $newGift->created_at = $gift->created_at;
                $newGift->updated_at = $gift->updated_at;
                $newGift->languages = [];
                if ($language->id) $newGift->languages[] = $language;
                $formattedGifts[] = ($newGift);
            }
        }
        $result = [
            "records"=>json_decode(json_encode($formattedGifts)),
            "total"=>$totalRecords
        ];
        return $this->sendResponse($result, 'MSG_GET_ALL_GIFTS_SUCCESS');
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
            'languages' => 'required|array|min:1',
            'couponExchangeRate' => 'required|integer|min:1',
            'inStock' => 'required|integer|min:0'
        ]);

        if($req->fails()){
            // return response()->json($req->errors()->toJson(), 400);
            return $this->sendError('INVALID_INPUT', $req->errors()->toJson(), 400);
        }
        $languages = $req->validated()['languages'];
        $defaultName = '';
        foreach($languages as $lang){
            if (empty($lang['lang']) || strlen($lang['lang']) !== 2){
                return $this->sendError('INVALID_INPUT', 'language code is incorrect!', 400);
            }
            if ('en' == $lang['lang']){
                $defaultName = $lang['value'];
            }
        }
        if (empty($defaultName)){
            return $this->sendError('INVALID_INPUT', 'languages must contain "en" for default!', 400);
        }
        $couponExchangeRate = $req->validated()['couponExchangeRate'];
        $inStock = $req->validated()['inStock'];
        $gift = null;
        DB::beginTransaction();
        try{
            $gift = Gift::create([
                'defaultName' => $defaultName,
                'couponExchangeRate' => $couponExchangeRate,
                'inStock' => $inStock
            ]);
            $curDate = date('Y-m-d H:i:s');            
            foreach($languages as $key=>$lang){
                $languages[$key]['id'] = Uuid::uuid4()->toString();
                $languages[$key]['giftId'] = $gift->id;
                $languages[$key]['created_at'] = $curDate;
                $languages[$key]['updated_at'] = $curDate;
            }
            $result = LangCommon::insert($languages);
            $gift->languages = $languages;
        }
        catch (Exception $e){
            DB::rollback();
            return $this->sendError('ERROR_DB_ERROR', $e, 500);
        }
        DB::commit();        
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
        $req = Validator::make($request->all(), [
            'languages' => 'required|array|min:1',
            'couponExchangeRate' => 'required|integer|min:1',
            'inStock' => 'required|integer|min:0'
        ]);

        if($req->fails()){
            // return response()->json($req->errors()->toJson(), 400);
            return $this->sendError('INVALID_INPUT', $req->errors()->toJson(), 400);
        }
        $languages = $req->validated()['languages'];
        $defaultName = '';
        foreach($languages as $lang){
            if (empty($lang['lang']) || strlen($lang['lang']) !== 2){
                return $this->sendError('INVALID_INPUT', 'language code is incorrect!', 400);
            }
            if ('en' == $lang['lang']){
                $defaultName = $lang['value'];
            }
        }
        if (empty($defaultName)){
            return $this->sendError('INVALID_INPUT', 'languages must contain "en" for default!', 400);
        }
        $languages = $req->validated()['languages'];
        $couponExchangeRate = $req->validated()['couponExchangeRate'];
        $inStock = $req->validated()['inStock'];
        $newLanguages = [];
        DB::beginTransaction();
        try{
            $gift->update([
                'defaultName' => $defaultName,
                'couponExchangeRate' => $couponExchangeRate,
                'inStock' => $inStock
            ]);
            $curDate = date('Y-m-d H:i:s');            
            foreach($languages as $key=>$lang){
                $language = LangCommon::find($lang['id']);
                if ($language){
                    $language->lang = $lang['lang'];
                    $language->value = $lang['value'];
                    $language->save();
                }
                else{
                    $languages[$key]['id'] = Uuid::uuid4()->toString();
                    $languages[$key]['giftId'] = $gift->id;
                    $languages[$key]['created_at'] = $curDate;
                    $languages[$key]['updated_at'] = $curDate;
                    $newLanguages[] = $languages[$key];
                }                
            }
            if (count($newLanguages) > 0) LangCommon::insert($newLanguages);
            $gift->languages = $languages;
        }
        catch (Exception $e){
            DB::rollback();
            return $this->sendError('ERROR_DB_ERROR', $e, 500);
        }
        DB::commit();
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
        DB::beginTransaction();
        try{
            LangCommon::where('giftId', '=', $gift->id)->delete();
            $gift->delete();
        }
        catch (Exception $e){
            DB::rollback();
            return $this->sendError('ERROR_DB_ERROR', $e, 500);
        }
        DB::commit();
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
        if ($gift->inStock === 0){
            return $this->sendResponse(null, 'NOT_ENOUGH_GIFT');
        }
        $couponCodeArray = $req->validated()['coupons'];
        if(count($couponCodeArray) < $gift->couponExchangeRate){
            return $this->sendResponse(null, 'MSG_NOT_ENOUGH_COUPONS_TO_EXCHANGE_GIFT');
        }
        $coupons = Coupon::whereIn('code', $couponCodeArray)
            ->whereDate('expiredDate', '>=', date('Y-m-d H:i:s'))
            ->get('id');
        if (count($coupons) < count($couponCodeArray)){
            return $this->sendResponse(JsonResource($coupons), 'MSG_COUPON_IS_EXPIRED');
        }
        $redeemedCoupons = CouponRedeem::whereIn('couponId', $coupons)->get();
        if (count($redeemedCoupons) > 0){
            return $this->sendResponse(null, 'MSG_COUPON_ALREADY_REDEEMED');
        }

        $user = JWTAuth::parseToken()->authenticate();
        $redeemCoupons = [];
        $curDate = date('Y-m-d H:i:s');
        for ($i = 0; $i < $gift->couponExchangeRate; $i++){
            $redeemCoupons[] = [
                'id' =>Uuid::uuid4()->toString(),
                'couponId' => $coupons[$i]->id,
                'redeemBy' => $user->id,
                'giftId' => $gift->id,
                'created_at' => $curDate,
                'updated_at' => $curDate
            ];
        }
        DB::beginTransaction();
        try{
            CouponRedeem::insert($redeemCoupons);
            $gift->inStock = $gift->inStock - 1;
            $gift->save();
        }
        catch (Exception $e){
            DB::rollback();
            return $this->sendError('ERROR_DB_ERROR', $e, 500);
        }
        DB::commit();
        // return $this->sendResponse(JsonResource::collection($couponIdArr), 'MSG_REDEEM_COUPON_SUCCESS');
        return $this->sendResponse(($redeemedCoupons), 'MSG_REDEEM_COUPON_SUCCESS');
    }
}
