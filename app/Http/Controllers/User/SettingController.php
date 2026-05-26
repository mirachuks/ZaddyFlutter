<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Validator, DB;

class SettingController extends Controller
{
      /**
    * saves examtype details
    *
    * @param $request
    *
    * @return id
     */
    public static function save($user_id){
        $create = Setting::create([

            'user_id' => $user_id,

        ]);

        return $create->id;

    }
    /**
     * This method creates
     *
     * exam type
     *
     * @param $request
     *
     * @return response
     */
    public function create(Request $request){
        $validation = Validator::make($request->all(),
        [
         'currency' => 'required',
        ]);

    if($validation->fails()){
        return $validation->errors();
    }
   // return $request->all();
        $result = self::save($request);
        if($result){
            return response()->json([
                'status' =>'success',
                'message' => 'Setting created successfully'

                                     ]);
        }else{
            return response()->json([
                'status' =>'error',
                'message' => 'Setting not created successfully'
            ]);

        }

    }

     /**
     * This method retrieves
     *
     *  user(s)
     *
     * @param $id
     */
    public static function get($id = null){
        if(!empty($id)){
            return $data = Setting::where('id', $id)->with(['user'])->first();
        }elseif(empty($id)){
              return $data = Setting::where('id', '!=', null)->with(['user'])->paginate(20);
            }else{
          return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong, data could not be retrieved'
            ]);
        }
    }

    /**
     * This method deletes
     *
     *  exam type
     *
     * @param $id
     */
    public static function delete($id){
        $delete = Setting::where('id', $id)->delete();
        if($delete){
            return response()->json([
                'status' =>'success',
                'message' => 'Setting deleted successfully'

                                     ]);
        }else{
            return response()->json([
                'status' =>'error',
                'message' => 'Something went wrong setting not deleted successfully'
            ]);

        }
    }

    /**
     * This method updates
     *
     *  exam type
     *
     * @param $id
     */
    public static function update(Request $request){

    $update = Setting::find($request['id']);

    if(!empty($request['currency'])){
    $update->currency = $request['currency'];
    }

    if(!empty($request['country'])){
        $update->country = $request['country'];
        }


    $saved = $update->save();

    if($saved){
        return response()->json([
            'status' =>'success',
            'message' => 'Setting updated successfully',
            'updated' =>$update
                                 ]);
    }else{
        return response()->json([
            'status' =>'error',
            'message' => 'Something went wrong setting not updated successfully'
        ]);

    }


    }


}
