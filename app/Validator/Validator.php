<?php

namespace App\Validator;

use DB;
use Illuminate\Validation\Validator as BaseValidator;

class Validator extends BaseValidator
{
    public function validateCheckUserRole($attribute, $value, $parameters, $validator) {
        $datas = DB::table('users')
                    ->select('roles')
                    ->where('id', $value)
                    ->get();
                    
        if($datas->count()){
            $array = json_decode($datas[0]->roles);
            if(in_array($parameters[0], $array)) return true;
            else return false;
        }
        else return false;
    }
}