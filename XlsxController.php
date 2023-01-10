<?php

namespace App\Http\Controllers;

use App\Admin;
use App\Xlsx;
use App\Quize;
use App\Question;
use App\Answer;
use Illuminate\Http\Request;

use File;

use Illuminate\Database\Eloquent\Model;

class XlsxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public static function proccessQuiz($quiz_id, $xlsx_name)
    {

        $basic = new Xlsx;

        if ($xlsx = $basic::parse(public_path() . '/xlsx_files/' . $xlsx_name)) {

            $xl_arr = $xlsx->rows();

            if($xl_arr[0][0] != "question") {
                return false;
            }

            array_shift($xl_arr);

            //dd($xl_arr);

            $result = self::writeQAs($quiz_id, $xl_arr);

        } else {
           dd($basic::parseError());
        }

        File::delete(public_path().'/xlsx_files/'.$xlsx_name);

        return $result;
    }

    public static function writeQAs($quiz_id, $xl_arr)
    {

        $qn_id = NULL;

        $flag_a = 1;
        
        foreach ($xl_arr as $cur_line) {

            if ($cur_line[0] != NULL) {

                if($flag_a == 0){
                    return false;
                }

                $flag_a = 0;

                $cur_question = new Question;

                $cur_question->qz_id = $quiz_id;
                $cur_question->q_name = $cur_line[0];
                $cur_question->q_image = $cur_line[3];
                $cur_question->save();

                $qn_id = $cur_question->id;

            }
            elseif ($cur_line[0] == NULL && $cur_line[1] != NULL) {

                $cur_answer = new Answer;

                $cur_answer->qn_id = $qn_id;
                $cur_answer->a_name = $cur_line[1];
                $cur_answer->a_correct = $cur_line[2];

                if($cur_answer->a_correct == "1"){
                    $flag_a = 1;
                }

                $cur_answer->save();
            
            }
            else{
                return false;
            }
            
        }

        return true;
    }

}
