<?php

namespace App\Http\Controllers;

use App\Admin;
use App\User;
use App\Home;

use Mail;

use App\Mail\FeedbackMail;
use App\Mail\GeneralMail;

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        // const = true;
    }

    public function index(){

        // dd('home');

        // $home = Home::find(1);

        return view('firstpage');

    }

    public function userTryLogin(Request $request)
    {

        $request->validate([
            'username' => 'required|max:255',
            'password' => 'required|max:255',
        ]);

        $result = User::where('username', $request->username)
            ->where('password', $request->password)
            ->first();

        if ($result == null) {
            return redirect('/logIn')->with('error', 'Wrong username and/or password!');
        } 
        // elseif(){

        // }
        else {

            session_start();

            $_SESSION['user'] = 1;

            $_SESSION['user_id'] = $result->id;

            return redirect('/home')->with('success', 'You are successfuly logged in!');
        }
    }

    public function adminTryLogin(Request $request)
    {

        $request->validate([
            'username' => 'required|max:255',
            'password' => 'required|max:255',
        ]);

        
        $result = User::where('username', $request->username)
                        ->where('password', $request->password)
                        ->where('is_admin', 1)
                        ->first();
        
        // dd($result);    

        if ($result == null) {
            return redirect('/adminlogin')->with('error', 'Wrong username and/or password!');
        } else {
            session_start();
            $_SESSION['admin'] = 1;

            return redirect('/adminhome')->with('success', 'You are successfuly logged in!');
        }
    }

    public function signUp(){

        return view('signUp');

    }

    public function trySignUp(Request $request)
    {

        $request->validate([
            'username' => 'required|max:255',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
            'email' => 'email|required|max:255|unique:users',
        ]);

        // dd($request);

        $user = new User();

        $user->username = $request->username;
        $user->password = $request->password;
        $user->email = $request->email;

        $email_hash = self::generateRandomString(16);

        $user->email_hash = $email_hash;

        $user->save();

        //dd($user);

        $responce = self::sendEmailConfirmEmail($user);

        return redirect('/')->with('success', 'Please confirm your email. It was sent to '.$request->email);

    }

    public static function generateRandomString($length = 10) {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function sendEmailConfirmEmail($user)
    {
        // dd($user);

        $to      = $user->email;
        $subject = 'Your access to Quizes';

        $message = 'Click link below to confirm your email: '; 

        $email_link = env('APP_URL');
        $email_link .= '/confirmEmail/'.$user->email_hash;

        $feedback = ['message' => $message, 
                     'subject' => env('WEBSITE_NAME').' - Confirm your email',
                     'email_link' => $email_link,
                     'email_template' => 'confirm_email'
                    ];

        $responce = Mail::to($user->email)->send(new GeneralMail($feedback));

        //dd($responce);
    
        return $responce;
    }

    public static function confirmEmail($email_hash)
    {

        $user = User::where('email_hash', $email_hash)->first();

        //dd($user);

        if(!empty($user)){

            // dd($user[0]);
            //dd('here');

            $user->confirmed_email = 1;

            $user->save();

            return redirect('/logIn')->with('success', 'Email has been confirmed.');

        }
        else{
            dd('error');
            return redirect('/logIn')->with('error', 'Wrong code.');
        }

    }

    public function logIn()
    {
        // dd('login');
        return view('logIn');
    }

}
