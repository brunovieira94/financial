<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MailService;

class MailController extends Controller
{

    private $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function index(Request $request)
    {
        return $this->mailService->getAllMail($request->all());
    }

    public function show($id)
    {
        return $this->mailService->getMail($id);
    }

    public function store(Request $request)
    {
        return $this->mailService->postMail($request->all());
    }

    public function update(Request $request, $id)
    {
        return $this->mailService->putMail($id, $request->all());
    }

    public function destroy($id)
    {
        $this->mailService->deleteMail($id);
        return response('');
    }
}
