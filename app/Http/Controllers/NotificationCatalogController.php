<?php

namespace App\Http\Controllers;

use App\Http\Requests\PutNotificationCatalogRequest;
use App\Services\NotificationCatalogService as NotificationCatalogService;
use Illuminate\Http\Request;

class NotificationCatalogController extends Controller
{

    private $notificationCatalogService;

    public function __construct(NotificationCatalogService $notificationCatalogService)
    {
        $this->notificationCatalogService = $notificationCatalogService;
    }

    public function index(Request $request)
    {
        return $this->notificationCatalogService->getAllNotificationCatalog($request->all());
    }

    public function show($id)
    {
        return $this->notificationCatalogService->getNotificationCatalogById($id);
    }

    public function update(PutNotificationCatalogRequest $request, $id)
    {
        return $this->notificationCatalogService->putNotificationCatalog($id, $request->all());
    }

    public function status(PutNotificationCatalogRequest $request)
    {
        return $this->notificationCatalogService->putNotificationCatalogStatus($request->all());
    }

    /* public function teste()
    {
        return $this->notificationCatalogService->getTeste();
    } */
}
