<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\Coupon;
use App\Models\CouponInit;
use App\Models\CouponGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponGroupController extends BaseController
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
        $limit = env('MAX_DISPLAY_RESULT');
        $groups = CouponGroup::limit($limit)->offset(($page - 1) * $limit)->get();
        $totalRecords = CouponGroup::count(); 
        $result = [
            "records"=>JsonResource::collection($groups),
            "total"=>$totalRecords
        ];
        return $this->sendResponse($result, 'MSG_GET_ALL_COUPON_GROUPS_SUCCESS');
    }

    
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CouponGroup  $couponInitCOUPON_GROUP
     * @return \Illuminate\Http\Response
     */
    public function show(CouponGroup $couponInitCOUPON_GROUP)
    {
        //
        return $this->sendResponse($couponInitCOUPON_GROUP, 'MSG_GET_COUPON_GROUP_SUCCESS');
    }    

    /**
     * Update the coupon on the specified COUPON_GROUP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CouponGroup  $couponInitCOUPON_GROUP
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CouponGroup $group)
    {
        //
        $req = Validator::make($request->all(), [
            'extDate' => 'required|date|date_format:"Y-m-d"'
        ]);

        if($req->fails()){            
            return $this->sendError('INVALID_INPUT', $req->errors()->toJson(), 400);
        }
        $extDate = $req->validated()['extDate'];
        $result = Coupon::where('groupId', '=', $group->id)->update(array('expiredDate'=>$extDate));
        return $this->sendResponse($result, 'MSG_UPDATE_COUPON_EXPIRED_DATE_SUCCESS');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CouponGroup  $couponInitCOUPON_GROUP
     * @return \Illuminate\Http\Response
     */
    public function destroy(CouponGroup $couponInitCOUPON_GROUP)
    {
        //
    }

    public function getCoupons(Request $request, $groupId){
        $group = CouponGroup::where('id', '=', $groupId)->first();
        if (!$group){
            return $this->sendError('INVALID_INPUT', 'group not found', 400);
        }
        $coupons = Coupon::leftJoin('coupon_redeems', function($join){
            $join->on('coupons.id', '=', 'coupon_redeems.couponId');
        })        
        ->where('coupons.groupId', '=', $groupId)
        ->get(['coupons.*', 'coupon_redeems.id AS redeemId']);
        
        // $totalRecords = Coupon::leftJoin('coupon_redeems', function($join){
        //     $join->on('coupons.id', '=', 'coupon_redeems.couponId');
        // })        
        // ->where('coupons.groupId', '=', $groupId)->count();
        
        $result = [
            "records"=>JsonResource::collection($coupons),
            "total"=>count($coupons)
        ];
        return $this->sendResponse($result, 'MSG_GET_ALL_COUPON_IN_GROUP_SUCCESS');
    }
}
