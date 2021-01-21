<?php

namespace App\Http\Controllers;
use JWTAuth;
use Validator;
use App\Models\Coupon;
use App\Models\CouponInit;
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
        //
        $coupons = Coupon::all();
        return $this->sendResponse(JsonResource::collection($coupons), 'MSG_GET_ALL_COUPONS_SUCCESS');
        // return response()->json([
        //     'message' => 'MSG_GET_ALL_COUPONS_SUCCESS',
        //     'coupons' => $coupons
        // ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $req = Validator::make($request->all(), [
            'total' => 'required|integer|max:100|min:1',
            'expiredDate' => 'required|date|date_format:m-d-Y'
        ]);

        if($req->fails()){
            // return response()->json($req->errors()->toJson(), 400);
            return $this->sendError($req->errors()->toJson(), 'INVALID_INPUT', 400);
        }        
        $user = JWTAuth::parseToken()->authenticate();
        $total = $req->validated()['total'];
        $expiredDate = $req->validated()['expiredDate'];
        $coupons = [];
        $couponsInit = [];
        for ($i = 0; $i < $total; $i++) {
            $id = Uuid::uuid4()->toString();
            $coupons[] = [
                'id' =>Uuid::uuid4()->toString(),
                'code' => bin2hex(random_bytes(5)),
                'expiredDate' => $expiredDate,
                'userId' => $user->id
            ];
            $couponsInit[] = [
                'id' =>Uuid::uuid4()->toString(),
                'couponId' => $id,
                'initBy' => $user->id
            ];
        }
        DB::beginTransaction();
        try{
            $result = Coupon::insert($coupons);
            $initResult = CouponInit::insert($couponsInit);
        }
        catch (Exception $e){
            DB::rollback();
            return $this->sendError($e, 'ERROR_DB_ERROR');
        }
        DB::commit();
        
        return $this->sendResponse($result, 'MSG_CREATE_COUPONS_SUCCESS');
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
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function edit(Coupon $coupon)
    {
        //
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
    }
}
