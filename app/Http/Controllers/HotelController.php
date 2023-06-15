<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HotelService as HotelService;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\PutHotelRequest;
use App\Imports\HotelsImport;
use App\Exports\HotelsExport;
use App\Exports\Utils as UtilsExport;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Models\Export;

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
        return $this->hotelService->deleteHotel($id);
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
        $exportFile = UtilsExport::exportFile($request->all(), 'hotéis');

        (new HotelsExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }
}
