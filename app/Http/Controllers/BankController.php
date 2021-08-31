<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreBankRequest;
use App\Models\Bank;

class BankController extends Controller
{
    public function index()
    {
        $banks = Bank::get();
        return response()->json($banks);
    }

    public function store(StoreBankRequest $request)
    {
        try {
           $bank = Bank::firstOrCreate([
           'title' => $request->input('title')
           ]);
           return response()->json($bank, 201);
        }catch(\Exception $e){
             return response('', 500);
        }
    }

    public function update(StoreBankRequest $request, $id)
    {
     try {
        $bank = Bank::findOrFail($id);
        $bank->title = $request->input('title');
        $bank->save();
        return response()->json($bank);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             return response('',404);
    } catch(\Exception $e){
             return response('',409);
    }
}

    public function destroy($id)
    {
        try {
            $bank = Bank::findOrFail($id)->delete();
            return response('',200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response('',404);

        } catch(\Exception $e){
            return response('',500);
        }
    }
}
