<?php

namespace App\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;

abstract class Controller
{




    public function responseErrors($errors = ['Errors'])
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $errors
        ], 400));
    }
}
