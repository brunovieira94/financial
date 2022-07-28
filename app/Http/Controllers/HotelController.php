<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HotelService as HotelService;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\PutHotelRequest;
use App\Imports\HotelsImport;
use App\Exports\HotelsExport;

class HotelController extends Controller
{
    private $hotelService;
    private $hotelImport;

    public function __construct(HotelService $hotelService, HotelsImport $hotelImport)
    {
        $this->hotelService = $hotelService;
        $this->hotelImport = $hotelImport;
    }

    public function index(Request $request)
    {
        return $this->hotelService->getAllHotel($request->all());
    }

    public function show($id)
    {
        return $this->hotelService->getHotel($id);
    }

    public function store(StoreHotelRequest $request)
    {
        return $this->hotelService->postHotel($request->all());
    }

    public function update(PutHotelRequest $request, $id)
    {
        return $this->hotelService->putHotel($id, $request->all());
    }

    public function destroy($id)
    {
        $this->hotelService->deleteHotel($id);
        return response('');
    }

    public function import()
    {
        $this->hotelImport->import(request()->file('import_file'));
        return response([
            'not_imported' => $this->hotelImport->not_imported,
            'imported' => $this->hotelImport->imported,
        ]);
    }

    public function export(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all()) && $request->all()['exportFormat'] == 'csv') {
            return (new HotelsExport($request->all()))->download('hotéis.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
        }
        return (new HotelsExport($request->all()))->download('hotéis.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
