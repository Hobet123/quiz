<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Admin;
use App\User;
use App\Session;
use App\Home;

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

class UserController extends Controller
{
    public function __construct()
    {
        $this->totalPerPart = 6;

        session_start();

        if (empty($_SESSION['user']) || $_SESSION['user'] != 1) {

            session_destroy();

            header('Location: /');

            die();
        }
    }

    public function index()
    {   
        $user_id = $_SESSION['user_id'];

        $user = User::find($user_id);

        $sessions = DB::select('SELECT s.id, s.quiz_id as quiz_id, s.user_id as user_id, q.quiz_name, q.cover_image as cover_image, u.username as username
                                FROM sessions s 
                                LEFT JOIN quizes q 
                                ON s.quiz_id = q.id
                                LEFT JOIN users u
                                ON s.user_id = u.id
                                WHERE s.user_id = :user_id
                                GROUP by s.id', ['user_id' => $user_id]);
            

        return view('home', ['user' => $user, 'sessions' => $sessions]);
    }

    public function quizHome(Request $request){

        // dd($request->id);

        $quiz = Quize::find($request->id);

        $_SESSION['quiz_id'] = $request->id;

        $questions = Question::where('qz_id', $quiz->id)
                                ->orderBy('q_order', 'asc')
                                ->get();

        $_SESSION['questions'] = $questions;

        $this->totalPerPart = $quiz->per_part;

        //dd($this->totalPerPar)

        $parts  = (int)(ceil(count($questions)/$this->totalPerPart));

        return view('quizHome', ['quiz' => $quiz, 'questions' => $questions, 'parts' => $parts, 'per_part' => $this->totalPerPart]);

    }

    public static function quizQuestion(Request $request){

        $quiz = Quize::find($_SESSION['quiz_id']);

        // dd($this->totalPerPart);

        // $this->totalPerPart = $quiz->per_part;

        // dd($this->totalPerPart);

        $qn_index = $request->id; // number in array of total < -------

        $qn_id = $_SESSION['questions'][$qn_index]->id;

        $question = Question::find($qn_id); // < ------- Find plz
        
        // dd("here")

        $answers = Answer::where('qn_id', $qn_id)->get(); // < ------ of all

        // dd($answers);

        $aferDot = $qn_index%$quiz->per_part;

        // dd($aferDot);

        if($aferDot == 0){

            $_SESSION['correct'] = 0; // <-------

            $_SESSION['qns_count'] = 1; // <-----

            $_SESSION['cur_qns'] = $cur_qns = $_SESSION['questions']->slice($qn_index, $quiz->per_part);

            ///////////////////////////////

            $_SESSION['total_qns'] = count($cur_qns); // <---important for count

            ////////////////////////////////////

        }
        else{
            ++$_SESSION['qns_count']; // <------
        }

        return view('quizQuestion', ['qn_index' => $qn_index, 'question' => $question, 'answers' => $answers]);

    }

    public function quizAnswer(Request $request){

        // dd($request->qn_index);

        $qn_index = $request->qn_index;

        $qn_id = $_SESSION['questions'][$qn_index]->id;

        $question = Question::find($qn_id); // < -------

        $answers = Answer::where('qn_id', $qn_id)->get();

        // dd($answers);

        foreach($answers as $answer){
            if($answer->a_correct == 1){
                $correct_a = $answer;
                break;
            }
        }

        // dd($correct_a->id." - ".$request->answer_id);

        $correct_a_flag = 0;

        if($correct_a->id == $request->answer_id){
            $correct_a_flag = 1;
            ++$_SESSION['correct'];
        }

        $final_flag = 0;

        if($_SESSION['qns_count'] == $_SESSION['total_qns']){
            $final_flag = 1;
        }

        // dd($correct_a->a_name);

        return view('quizAnswer', 
                ['qn_index' => $qn_index, 
                'question' => $question, 
                'correct_a' => $correct_a, 
                'correct_a_flag' => $correct_a_flag,
                'final_flag' => $final_flag,
                ]);

    }
    
    public function quizFinal(Request $request){

      return view('quizFinal');

    }

    public function logout(){

        session_destroy();

        return redirect('/');

    }
}
