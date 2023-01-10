<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use File;

use App\Admin;
use App\User;

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

class HelperController extends Controller
{
    public function __construct()
    {
        // session_start();

        // if (empty($_SESSION['admin']) || $_SESSION['admin'] != 1) {

        //     session_destroy();

        //     header('Location: /adminlogin/');
        //     die();
        // }
    }

    public static function removeDir($id) {

        File::deleteDirectory(public_path()."/questions_images/q_44");
    }

}
