<?php

namespace App\Http\Controllers;
use JWTAuth;
use Validator;
use App\Models\Coupon;
use App\Models\CouponInit;
use App\Models\CouponGroup;
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
     * type: all, redeem, notredeem
     */    
    public function index(Request $request)
    {
        // should implement pagination, filter here
        $page = $request->has('page') ? $request->get('page') : 1;
        $type = $request->has('type') ? $request->get('type') : 'all';
        $limit = env('MAX_DISPLAY_RESULT');
        $coupons = [];
        $totalRecords = 0;
        switch($type){
            case 'all':
                $coupons = Coupon::limit($limit)->offset(($page - 1) * $limit)->get();
                $totalRecords = Coupon::count(); 
                break;
            case 'redeem':
                $coupons = Coupon::leftJoin('coupon_redeems', function($join){
                    $join->on('coupons.id', '=', 'coupon_redeems.couponId');
                })
                ->select('coupons.*')
                ->whereNotNull('coupon_redeems.id')
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->get();
                $totalRecords = Coupon::leftJoin('coupon_redeems', function($join){
                    $join->on('coupons.id', '=', 'coupon_redeems.couponId');
                })
                ->select('coupons.*')
                ->whereNotNull('coupon_redeems.id')
                ->count();
                break;
            case 'notredeem':
                $coupons = Coupon::leftJoin('coupon_redeems', function($join){
                    $join->on('coupons.id', '=', 'coupon_redeems.couponId');
                })
                ->select('coupons.*')
                ->whereNull('coupon_redeems.id')
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->get();
                $totalRecords = Coupon::leftJoin('coupon_redeems', function($join){
                    $join->on('coupons.id', '=', 'coupon_redeems.couponId');
                })
                ->whereNull('coupon_redeems.id')
                ->count();
                break;
            case 'expired':
                $coupons = Coupon::whereDate('expiredDate', '<=', date('Y-m-d H:i:s'))
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->get();
                $totalRecords = Coupon::whereDate('expiredDate', '<=', date('Y-m-d H:i:s'))->count();
                break;
        }
        $result = [
            "records"=>JsonResource::collection($coupons),
            "total"=>$totalRecords
        ];
        return $this->sendResponse($result, 'MSG_GET_ALL_COUPONS_SUCCESS');
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
            'expiredDate' => 'required|date|date_format:"Y-m-d"'
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
        
        DB::beginTransaction();
        try{
            $couponGroup = CouponGroup::create([
                'totalInit' => $total,
                'userId' => $user->id
            ]);
            for ($i = 0; $i < $total; $i++) {
                $id = Uuid::uuid4()->toString();
                $coupons[] = [
                    'id' =>Uuid::uuid4()->toString(),
                    'code' => bin2hex(random_bytes(5)),
                    'expiredDate' => $expiredDate,
                    'userId' => $user->id,
                    'groupId' => $couponGroup->id,
                    'created_at' => $curDate,
                    'updated_at' => $curDate
    
                ];
                $couponsInit[] = [
                    'id' =>Uuid::uuid4()->toString(),
                    'couponId' => $id,
                    'groupId' => $couponGroup->id,
                    'created_at' => $curDate,
                    'updated_at' => $curDate
                ];
            }
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


    /**
     * Get all coupon having code in a code array
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getByCode(Request $request){
        $req = Validator::make($request->all(), [
            'codes' => 'required|array|min:1',            
        ]);

        if($req->fails()){            
            return $this->sendError('INVALID_INPUT', $req->errors()->toJson(), 400);
        }
        $codes = $req->validated()['codes'];
        $coupons = Coupon::whereIn('code', $codes)->get();
        return $this->sendResponse(JsonResource::collection($coupons), 'MSG_GET_COUPONS_SUCCESS');
    }
}
