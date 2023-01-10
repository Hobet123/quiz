<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use File;

use App\Admin;
use App\User;
use App\Home;

use App\Session;

use App\Xlsx;
use App\Quize;
use App\Question;
use App\Answer;

use ZipArchive;

use App\Http\Controllers\XlsxController;

use App\Http\Controllers\ManageUserControler;

// use Spatie\Geocoder\Facades\Geocoder;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Crypt;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct()
    {
        // server should keep session data for AT LEAST 1 hour
        ini_set('session.gc_maxlifetime', 3600);

        // each client should remember their session id for EXACTLY 1 hour
        session_set_cookie_params(3600);

        session_start();

        if (empty($_SESSION['admin']) || $_SESSION['admin'] != 1) {

            session_destroy();

            header('Location: /adminlogin/');
            
            die();
        }

        //ALTER TABLE tablename AUTO_INCREMENT = 1
    }

    public function index()
    {
        $quizes = Quize::all();

        $users = User::all();

        $sessions = Session::all();

        //dd($sessions);

        $data = [];
        $data[0] = $quizes;
        $data[1] = $users;
        $data[2] = $sessions;

        return view('admin.adminhome')->with('data', $data);
    }

    public function deleteQuiz(Request $request)
    {

        $quiz_id = $request->id;

        $qn_ids = Question::select('id')
            ->where('qz_id', $quiz_id)
            ->get();

        foreach ($qn_ids as $qn_id) {

            $qn_id = $qn_id->id;

            DB::delete('DELETE from answers WHERE qn_id = ?', [$qn_id]);

            Question::find($qn_id)->delete();

            //dd($qn_id);
        }

        $quiz = Quize::find($quiz_id);

        $cover_image = $quiz->cover_image;

        Quize::find($quiz_id)->delete();

        //delete files, folders of the quiz

        //delete all related sessions

        Session::where('quiz_id', $quiz_id)->delete();

        //Delete Cover Image and questions images/

        File::deleteDirectory(public_path()."/question_images/q_".$quiz_id);

        File::delete(public_path()."/cover_images/c_".$quiz_id);

        return redirect('/adminhome')->with('success', 'Quiz deleted');
    }

    public function uploadQuiz()
    {
        return view('admin.uploadquiz');
    }

    public function doUploadQuiz(Request $request)
    {

        $request->validate([
            'quiz_name' => 'required|max:255',
            'quiz_description' => 'max:1000',
            'cover_image' => 'image|mimes:jpeg, png, jpg, gif|max:20000',
            'xlsx' => 'required|mimes:xlsx, XLSX|max:20000',
            'questions_images' => 'mimes:zip, ZIP|max:20000',
            'per_part' => 'required|max:2',
        ]);

        /*
            create New Quiz
        */
        $new_quiz = new Quize;

        $new_quiz->quiz_name = $request->quiz_name;

        $new_quiz->quiz_description = $request->quiz_description;

        $new_quiz->per_part = $request->per_part;

        $new_quiz->save();

        $quiz_id = $new_quiz->id;
        /*
            Cover Image Upload
        */
        $cover_image = null;

        if ($request->cover_image != null) {

            $file = $request->file('cover_image');

            $cover_image = 'c_' . $quiz_id . '.' . $file->getClientOriginalExtension();
            $path = $request->cover_image->move(public_path() . '/cover_images', $cover_image);
        }

        $new_quiz->cover_image = $cover_image;
        $new_quiz->save();

        /*
            Questions Images ZIP FILE Upload
        */
        if ($request->questions_images != null) {

            $file = $request->file('questions_images');

            $questions_images = 'q_' . $quiz_id . '.' . $file->getClientOriginalExtension();
            $path = $request->questions_images->move(public_path() . '/questions_images', $questions_images);

            self::unzipQz($quiz_id, $questions_images);
        }

        /*
            MAIN XLSX UPLOAD
        */

        $file = $request->file('xlsx');

        $xlsx_name = 'x_' . $quiz_id . '.' . $file->getClientOriginalExtension();
        $path = $request->xlsx->move(public_path() . '/xlsx_files', $xlsx_name);

        /*
            PROCESS QUIZ put to DB
        */

        $result = XlsxController::proccessQuiz($quiz_id, $xlsx_name);

        /*

        */

        if ($result == true) {
            return redirect('/adminhome')->with('success', 'Quiz successfully uploaded');
        } else {
            return view('admin.uploadquiz')->with('error', 'Wrong XLSX format. Please check sructure');;
        }

        return view('admin.uploadquiz');

        //return redirect('/adminhome')->with('success', 'You are successfuly logged in as admin!');
    }

    public static function editQuiz($id)
    {

        $quiz = Quize::find($id);

        $_SESSION['quiz_id'] = $quiz->id;

        return view('admin.editQuiz', ['quiz' => $quiz]);

    }

    public function doEditQuiz(Request $request)
    {

        $request->validate([
            'quiz_name' => 'required|max:255',
            'quiz_description' => 'max:1000',
            'cover_image' => 'image|mimes:jpeg, png, jpg, gif|max:20000',
            'xlsx' => 'mimes:xlsx, XLSX|max:20000',
            'questions_images' => 'mimes:zip, ZIP|max:20000',
            'per_part' => 'required|max:2',
        ]);

        /*
            create New Quiz
        */
        $quiz_id = $request->quiz_id;

        $quiz = Quize::find($quiz_id);

        $quiz->quiz_name = $request->quiz_name;
        $quiz->quiz_description = $request->quiz_description;
        $quiz->per_part = $request->per_part;

        $quiz->save();

        /*
            Delete and Cover Image Upload
        */
        $cover_image = null;

        if ($request->cover_image != null) {

            File::delete(public_path()."/cover_images/c_".$quiz_id);

            $file = $request->file('cover_image');

            $cover_image = 'c_' . $quiz_id . '.' . $file->getClientOriginalExtension();
            $path = $request->cover_image->move(public_path() . '/cover_images', $cover_image);
        }

        $quiz->cover_image = $cover_image;
        $quiz->save();

        /*
            Questions Images ZIP FILE Upload
        */
        if ($request->questions_images != null) {

            File::deleteDirectory(public_path()."/question_images/q_".$quiz_id);

            $file = $request->file('questions_images');

            $questions_images = 'q_' . $quiz_id . '.' . $file->getClientOriginalExtension();
            $path = $request->questions_images->move(public_path() . '/questions_images', $questions_images);

            self::unzipQz($quiz_id, $questions_images);
        }

        /*
            MAIN XLSX UPLOAD
        */
        $result = true;

        if ($request->xlsx != null) {

            /* Delete From DB */

            $qn_ids = Question::select('id')
                ->where('qz_id', $quiz_id)
                ->get();

            foreach ($qn_ids as $qn_id) {

                $qn_id = $qn_id->id;

                DB::delete('DELETE from answers WHERE qn_id = ?', [$qn_id]);

                Question::find($qn_id)->delete();

            }

            /* Add new to db */

            $file = $request->file('xlsx');

            $xlsx_name = 'x_' . $quiz_id . '.' . $file->getClientOriginalExtension();
            $path = $request->xlsx->move(public_path() . '/xlsx_files', $xlsx_name);

            /*
                PROCESS QUIZ put to DB
            */

            $result = XlsxController::proccessQuiz($quiz_id, $xlsx_name);
        }

        if ($result == true) {
            return redirect('/adminhome')->with('success', 'Quiz successfully edited');
        } else {
            return view('admin.editQuiz')->with('error', 'Wrong XLSX format. Please check structure');
        }

        return view('admin.editQuiz');

    }

    public static function unzipQz($quiz_id, $zip_file) //q_24.zip
    {

        $zip = new ZipArchive();

        $parts = explode(".", $zip_file);

        $res = $zip->open(public_path() . '/questions_images/' . $zip_file);

        if ($res === TRUE) {

            mkdir(public_path() . '/questions_images/' . $parts[0], 0700);

            $zip->extractTo(public_path() . '/questions_images/' . $parts[0] . '/');

            $zip->close();

            File::delete(public_path() . '/questions_images/' . $zip_file);

        } 
        else {
            echo 'ZIP file was not extracted';
        }
    }

    // Man age users

    public static function addSession()
    {

        $quizes = Quize::all();

        //dd($quizes[0]->quiz_name);

        return view('admin.addsession')->with('quizes', $quizes);
    }

    public static function logout(){

        session_destroy();

        return redirect('/adminlogin');

    }

    public static function homeSetting()
    {
        $home = Home::find(1);
        
        // dd($home);

        return view('admin.homeSetting')->with('home', $home);

    }

    public static function doHomeSetting(Request $request)
    {
        // dd($request);

        $request->validate([
            'title' => 'required|max:255',
            'main_text' => 'max:1000',
            'copyright' => 'max:255',
        ]);

        $home_id = $request->home_id;

        $home = Home::find($home_id);

        $home->title = $request->title;

        $home->main_text = $request->main_text;

        $home->copyright = $request->copyright;

        $home->save();

        return redirect('/')->with('success', 'Updated!');

    }


}
