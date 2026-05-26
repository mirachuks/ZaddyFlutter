<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\PasswordResetMail;
use App\Mail\VerifyEmail;
use App\Mail\CreditMail;
use App\Mail\DebitMail;
use App\Mail\GenericMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Mail, Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Mailer extends Controller
{
     /**
     * send password reset mail
     * generates a random password
     * and sends to the registered mail
     *
     */
    public function postResetPassword(Request $request)
    {    $validation = Validator::make($request->all(),
        [
            'email' => 'required|email',

        ]);

        if($validation->fails()){
            return response()->json([
                'status' => 403,
                'message' => $validation->errors(),
            ]);

        }
        $random = strtoupper(Str::random(2));
        $psd = $random.mt_rand(100000, 1000000);
        $sentpassword =  $psd;
        $dbpassword = Hash::make($sentpassword);
        $data = [ 'password' => $sentpassword
        ];
        $check = User::where('email', $request['email'])->first();
        if(!empty($check->id)){
            $check->password = $dbpassword;
            $check->save();
            $data = ['password' => $sentpassword];
            // $check->password = $sentpassword;
            Mail::to($request['email'])->send(new PasswordResetMail($sentpassword));
            return response()->json(['status' => 'success',
                'message' => 'Account reset successfully. Please check your email to get your new password. Note: Sometimes check your email spam folders to see it'
            ]);

        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Email not registered on this platform. Please check if email is correct and try again'
            ]);


        }
    }

    /**
     * This method sends a mail to contact form
     *
     *
     */
    public function sendContact(Request $request){

        $validation = Validator::make($request->all(),
            [
                'email'=>'required|email',
                'name'=>'required|min:2',
                'subject'=>'required|min:3',
                'content' => 'required|min:20',

            ]);

        if($validation->fails()){
            return response()->json([
                'status' => 403,
                'message' => $validation->errors(),
            ]);
        }
        $delay = (new \Carbon\Carbon)->now()->addMinutes(2);
        Mail::to('billballer@gmail.com')->later($delay, new ContactMail($request['name'], $request['email'], $request['subject'], $request['content']));
        return redirect()->back()->with('success', 'Contact mail sent successfully. Thanks for contacting us');
       /* return response()->json([
            'status' => 'success',
            'message' => 'Contact mail sent successfully. Thanks for contacting us'
        ]);*/
    }

    /**
     * This method sends a welcome mail
     * to a new user
     *
     */
    public static function welcomeMail($email, $name, $code){
        $delay = (new \Carbon\Carbon)->now()->addMinutes(1);
        Mail::to($email)->later($delay, new WelcomeMail($name, $code));
    }

    /**
     * This method sends a welcome mail
     * to a new admin
     *
     */
    public static function welcomeMailAdmin($email, $name, $password){
        $delay = (new \Carbon\Carbon)->now()->addMinutes(1);
        Mail::to($email)->later($delay, new WelcomeMailAdmin($name, $password));
    }

    /**
     * This method is a generic email template
     * @param $email, $title, $message
     *
     */
    public static function genericMail($email, $title, $msg){
        $delay = (new \Carbon\Carbon)->now()->addMinutes(1);
        Mail::to($email)->later($delay, new GenericMail($email, $title, $msg));
    }

    /**
     * This method sends notification
     * @param $user_id, $title, $message
     *
     */
    public static function notifyMail(array $data){
        $delay = (new \Carbon\Carbon)->now()->addMinutes(1);
        return Mail::to($data['email'])->later($delay, new MailNotify($data['email'], $data['title'], $data['msg'], $data['post_id'], $data['post_title']));

    }

    /**
     * This method sends a verification
     * code to a user
     *
     */
    public static function verifyEmail($email, $code){
        $delay = (new \Carbon\Carbon)->now()->addMinutes(1);
        Mail::to($email)->later($delay, new VerifyEmail($code));
    }



    /**
     * This method sends a token
     * to every admin
     *
     */
    public static function sendAdminToken($token, $email){
        //$delay = (new \Carbon\Carbon)->now()->addMinutes(1);
        Mail::to($email)->send( new SendAdminToken($token, $email));
    }

    /**
     * This method sends a notification email
     * to a user
     *
     */
    public static function creditMail($email, $amount, $balance, $purpose){
        $delay = (new \Carbon\Carbon)->now()->addMinutes(2);
       // Mail::to($email)->later($delay, new CreditMail($amount, $balance, $purpose));
    }

    /**
     * This method sends a notification email
     * to a user
     * $amount, $balance, $wallet_no
     */
    public static function debitMail($email, $amount, $balance, $purpose){
        $delay = (new \Carbon\Carbon)->now()->addMinutes(1);
       // Mail::to($email)->later($delay, new DebitMail($amount, $balance, $purpose));
    }

    /**
     * Sends email to users who has noe
     *
     * logged in for about 30 days
     *
     *
     */
    public static function lastLoginReminder($email){
        $delay = (new \Carbon\Carbon)->now()->addMinutes(1);
        Mail::to($email)->later($delay, new LastLoginMail());
    }


    /**
     * Sends email to everyone
     *
     * on the database or
     *
     */
    public static function sendMassMail(Request $request){
        $validation = Validator::make($request->all(),
            [
                'content' => 'required|min:10',
                'subject' => 'required|min:3',
            ]);

        if($validation->fails()){
            return response()->json([
                'status' => 403,
                'message' => $validation->errors(),
            ]);
        }
        $subject = $request['subject'];
        $content = $request['content'];
        User::where('status', 'active')->chunk(100, function($users) use ($subject, $content){
            foreach($users as $user){
                self::genericMail($user->email, $subject, $content);
            }
        });

        return response()->json([
            "message" => "Emails sent successfully",
            "status" => "success",
        ]);

    }


}
