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

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Hash;

class Admin2Controller extends Controller
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
    }



    public function uploadDuQuiz()
    {
        return view('admin.uploadDuQuiz');
    }

    public function startDuQuiz(Request $request)
    {

        $request->validate([
            'quiz_name' => 'required|max:255',
            'quiz_description' => 'max:1000',
            'cover_image' => 'image|mimes:jpeg, png, jpg, gif|max:20000',
            'per_part' => 'required|max:2',
        ]);

        $new_quiz = new Quize;

        $new_quiz->quiz_name = $request->quiz_name;
        $new_quiz->quiz_description = $request->quiz_description;
        $new_quiz->per_part = $request->per_part;

        $new_quiz->save();

        $quiz_id = $new_quiz->id;

        $_SESSION['quiz_id'] = $quiz_id;

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

        return redirect('/admin/addDuQA')->with('quiz_id', $quiz_id);

    }

    public function addDuQA(){

        return view('admin.addDuQA');

    }

    public function doAddDuQA(Request $request)
    {
        $request->validate([
            'question' => 'required|max:255',
            'answer_1' => 'required|max:255',
            'answer_2' => 'required|max:255',
        ]);

        //dd($request);

        $question = new Question;

        $question->qz_id = $_SESSION['quiz_id'];
        $question->q_name = $request->question;

        $question->save();

        $question_id = $question->id;

        for($i = 1; $i < 20; $i++){

            $temp = "answer_".$i;

            if(isset($request->$temp)){
                
                $answer = new Answer;

                $answer->qn_id = $question_id;
                $answer->a_name = $request->$temp;

                if($request->correct_a == $i){
                    $answer->a_correct = 1;
                }

                $answer->save();

            }
            
        }

        if(isset($_SESSION['editing_quiz']) && $_SESSION['editing_quiz'] == 1){

            $quiz_id = $_SESSION['quiz_id'];

            unset($_SESSION['editing_quiz']);
            unset($_SESSION['quiz_id']);
            
            return redirect('/admin/editQuizQAs/'.$quiz_id)->with('success', 'Question added!');
        }
        else{
            if($request->submit == "Add Question"){
                return redirect('/admin/addDuQA')->with('success', 'Question added!');
            }
            if($request->submit == "Finish"){
                return redirect('/adminhome')->with('success', 'Quiz added!');
            }
        }
    }

    public static function editQuizQAs($id)
    {
        $quiz = Quize::find($id);

        $questions = Question::where('qz_id', $id)
                                    ->orderBy('q_order', 'asc')
                                    ->get();

        return view('admin.editQuizQAs', ['quiz' => $quiz, 'questions' => $questions]);

    }

    public static function deleteQuestion($quiz_id, $qn_id)
    {

        $r = Answer::where('qn_id', $qn_id)->delete();
        
        $r2 = Question::find($qn_id)->delete();

        return redirect('/admin/editQuizQAs/'.$quiz_id);

    }

    public static function questionsOrder(Request $request)
    {
        $input = $request->all();

        $i=0;

        foreach($input as $key => $value){

            if(strpos($key, "estion")){
                $question = Question::find($value);
                $question->q_order = $i++;
                $question->save();
            }

        }
        return redirect('/admin/editQuizQAs/'.$input['quiz_id']);
    }

    public static function addDuQATo($quiz_id)
    {

        $_SESSION['quiz_id'] = $quiz_id;
        $_SESSION['editing_quiz'] = 1;

        // dd($_SESSION['editing_quiz']);

        return view('admin.addDuQA');

    }

    public function editDuQA($question_id){

        // dd($question_id);

        $_SESSION['question_id'] = $question_id;

        $question = Question::find($question_id);

        $answers = Answer::where('qn_id', $question_id)->get();
        
        // dd($question);

        return view('admin.editDuQA', ['question' => $question, 'answers' => $answers]);
    }

    public function doEditDuQA(Request $request)
    {
        // dd($request);

        $request->validate([
            'question' => 'required|max:255',
            'answer_1' => 'required|max:255',
            'answer_2' => 'required|max:255',
        ]);

        $question = Question::find($request->question_id);

        $question->q_name = $request->question;

        $question->save();

        $question_id = $question->id;

        //remove old answers
        Answer::where('qn_id', $question_id)->delete();

        for($i = 1; $i < 20; $i++){

            $temp = "answer_".$i;

            if(isset($request->$temp)){
                
                $answer = new Answer;

                $answer->qn_id = $question_id;
                $answer->a_name = $request->$temp;

                if($request->correct_a == $i){
                    $answer->a_correct = 1;
                }

                $answer->save();

            }
            
        }

        return redirect('/admin/editQuiz/'.$_SESSION['quiz_id'])->with('success', 'Question edited!');

    }

    // main admin menu links

    public static function users()
    {
        
        $users = User::all();

        return view('admin.users')->with('users', $users);

    }

    public static function sessions()
    {
        
        $sessions = Session::all();

        return view('admin.sessions')->with('sessions', $sessions);

    }

    public static function quizzes()
    {

        $quizzes = Quize::all();

        return view('admin.quizzes')->with('quizzes', $quizzes);
        
    }


}
