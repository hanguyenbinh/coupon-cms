<?php

namespace App\Http\Controllers;
use JWTAuth;
use Validator;
use App\Models\Coupon;
use App\Models\CouponInit;
use App\Models\CouponRedeem;
use App\Models\Gift;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;



class CouponController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // should implement pagination, filter here
        $coupons = Coupon::all();
        return $this->sendResponse(JsonResource::collection($coupons), 'MSG_GET_ALL_COUPONS_SUCCESS');        
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
            'total' => 'required|integer|max:100|min:1',
            'expiredDate' => 'required|date|date_format:m-d-Y'
        ]);

        if($req->fails()){
            // return response()->json($req->errors()->toJson(), 400);
            return $this->sendError('INVALID_INPUT', $req->errors()->toJson(), 400);
        }        
        $user = JWTAuth::parseToken()->authenticate();
        $total = $req->validated()['total'];
        $expiredDate = $req->validated()['expiredDate'];
        $coupons = [];
        $couponsInit = [];
        $curDate = date('Y-m-d H:i:s');
        for ($i = 0; $i < $total; $i++) {
            $id = Uuid::uuid4()->toString();
            $coupons[] = [
                'id' =>Uuid::uuid4()->toString(),
                'code' => bin2hex(random_bytes(5)),
                'expiredDate' => $expiredDate,
                'userId' => $user->id,
                'created_at' => $curDate,
                'updated_at' => $curDate

            ];
            $couponsInit[] = [
                'id' =>Uuid::uuid4()->toString(),
                'couponId' => $id,
                'initBy' => $user->id,
                'created_at' => $curDate,
                'updated_at' => $curDate
            ];
        }
        DB::beginTransaction();
        try{
            $result = Coupon::insert($coupons);
            $initResult = CouponInit::insert($couponsInit);
        }
        catch (Exception $e){
            DB::rollback();
            return $this->sendError('ERROR_DB_ERROR', $e, 500);
        }
        DB::commit();
        
        return $this->sendResponse($result, 'MSG_CREATE_COUPONS_SUCCESS');
    }

    /**
     * Display the specified resource.validateNumber
     *
     * @param  \App\Models\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function show(Coupon $coupon)
    {
        //
        return $this->sendResponse($coupon, 'MSG_GET_COUPON_SUCCESS');
    }    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Coupon $coupon)
    {
        //
        $coupon->update($request->all());
        return $this->sendResponse($coupon, 'MSG_UPDATE_COUPON_SUCCESS');        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function destroy(Coupon $coupon)
    {
        //
        $coupon->destroy();
        return $this->sendResponse($coupon, 'MSG_DELETE_COUPON_SUCCESS');        
    }

    public function init(Request $request, $couponId) {
        $user = JWTAuth::parseToken()->authenticate();
        $iniCoupon = CouponInit::create([
            'couponId' => $couponId,
            'initBy' => $user->id,
        ]);
        return $this->sendResponse($iniCoupon, 'MSG_INIT_COUPON_SUCCESS');
    }
    
}
