<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Admin;
use App\User;
use App\Session;

use App\Xlsx;
use App\Quize;
use App\Question;
use App\Answer;

use ZipArchive;

use App\Http\Controllers\XlsxController;

// use Spatie\Geocoder\Facades\Geocoder;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Crypt;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Hash;

use Mail;

use App\Mail\FeedbackMail;
use App\Mail\GeneralMail;

class ManageUserController extends Controller
{
    public function __construct()
    {
        session_start();

        if (empty($_SESSION['admin']) || $_SESSION['admin'] != 1) {

            session_destroy();

            header('Location: /adminlogin/');
            die();
        }
    }

    // Man age users

    public static function createUser()
    {

        return view('admin.createUser');

    }

    public function editUser(Request $request)
    {
        $user = User::find($request->id);

        return view('admin.editUser')->with('user', $user);
    }

    public static function sendUserEmail($user_id)
    {

        $user = User::find($user_id);

        $to      = $user->email;
        $subject = 'Your access to Quizes';

        $message = 'Visit '.env('WEBSITE_NAME').' to access your quizzes: '; 
        $username = $user->username;
        $password = $user->password;

        $feedback = ['message' => $message, 
                     'subject' => env('WEBSITE_NAME').' - Your access to Quizes',
                     'username' => $username,
                     'password' => $password,
                     'email_template' => 'access_to_quizzes'
                    ];

        Mail::to($user->email)->send(new GeneralMail($feedback));

        return redirect('/admin/editUser/'.$user->id)->with('success', 'Email has been sent to user!');

    }

    public static function doCreateUser(Request $request)
    {
        //dd($request);

        $request->validate([
            'username' => 'required|max:255',
            'email' => ['required', 'unique:users', 'max:255'],
            'password' => 'required|max:255',
        ]);

        $user = new User();

        $user->username = $request->username;
        $user->password = $request->password;
        $user->email = $request->email;
        $user->is_admin = $request->is_admin;
        $user->save();

        return redirect('/adminhome')->with('success', 'User Created');
    }

    public static function doEditUser(Request $request)
    {
        // dd($request->user_id);

        $request->validate([
            'username' => 'required|max:255',
            'email' => 'max:255',
            'password' => 'required|max:255',
        ]);

        $user = User::find($request->user_id);

        $user->username = $request->username;
        $user->password = $request->password;
        $user->email = $request->email;
        $user->is_admin = $request->is_admin;
        $user->save();

        return redirect('/adminhome')->with('success', 'User Edited!');
    }


    public function deleteUser(Request $request)
    {
        User::find($request->id)->delete();

        Session::where('user_id', $request->id)->delete();

        return redirect('/adminhome')->with('success', 'User Deleted!');
    }
    ///adminhome
    ///admin/logout/
}
