<?php

namespace App\Http\Controllers\Funding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaticVirtualAccount;
use Auth;

class StaticVirtualAccountController extends Controller
{
    /**
     * saves user level
     *
     * @param $request
     *
     * @return
     */
    public static function save($request){
        $create = new StaticVirtualAccount;

        $create->bank_name = $request['bank_name'];
        $create->account_number = $request['account_number'];
        $create->txt_ref = $request['txt_ref'];
        $create->order_ref = $request['order_ref'];
        $create->email  = $request['email'];
        $create->user_id = $request['user_id'];
        $create->status = 'active';
        $create->bvn = $request['bvn']?? 'No-bvn';
        $create->save();

        return $create->id;

    }

    /**
     * check if there are active virtual
     *
     * accounts
     * @returns collections
     *
     * @params $user_id
     */
    public static function checkVirtualAccount(int $user_id){
        return StaticVirtualAccount::where('user_id', $user_id)->get();
       /* if($check->isNotEmpty()){
            return $check;
        }else{
            return response()->json([
                "status"=>"error",
                "message"=>"No static virtual accounts available. Please create one"
            ]);
        }*/
    }

     /**
     * get my virtual account
     *
     * accounts
     * @returns collections
     *
     * @params $user_id
     * 
     */
    public static function getMyVirtualAccount($user_id){
           $accdata = StaticVirtualAccount::where('user_id', $user_id)->first();
          // return $data->account_number;
          // return Auth::user()->staticAccount->bank_name;

        return view('/dashboard/src/html/account-details', ['accdata'=> $accdata]);
    }

}
