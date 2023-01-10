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

class SessionController extends Controller
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

    // Manage users

    public static function addSession()
    {
        $users = User::all();
        $quizes = Quize::all();

        return view('admin.addSession')
                                    ->with('users', $users)
                                    ->with('quizes', $quizes);
    }

    public static function doAddSession(Request $request)
    {
        
        $request->validate([
            'quiz_id' => 'required|max:255',
            'user_id' => 'required|max:255',
        ]);
        
        // dd($request);

        $session = new Session();

        $session->quiz_id = $request->quiz_id;
        $session->user_id = $request->user_id;

        $session->save();

        return redirect('/adminhome')->with('success', 'Session Added');
    }

    public function deleteSession(Request $request)
    {
        // dd($request->id);

        Session::find($request->id)->delete();

        return redirect('/adminhome')->with('success', 'Session has been deleted!');
    }

    // helper function

    public static function getQuizName($quiz_id){

        $quiz = Quize::find($quiz_id);

        return $quiz;
    }

    public static function getUserName($user_id){

        $user = User::find($user_id);
        
        return $user;
    }

}
