<?php

namespace Modules\Dgii\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DgiiInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = DB::table('nexopos_dgii_invoices')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('Dgii::invoices', compact('invoices'));
    }
}
