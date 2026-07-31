<?php

namespace Modules\Dgii\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Dgii\Services\DgiiRncValidatorService;

class DgiiRncController extends Controller
{
    public function validateRnc(Request $request, DgiiRncValidatorService $validator)
    {
        $rnc = $request->input('rnc', '');
        $result = $validator->validateAndLookup((string)$rnc);

        return response()->json($result);
    }
}
