<?php

namespace App\Http\Controllers;

use App\Exports\BillsToPayExport;
use App\Exports\PaymentRequestExport;
use App\Exports\PaymentRequestExportQueue;
use App\Exports\TestExport;
use App\Exports\Utils as ExportsUtils;
use App\Http\Resources\reports\RouteApprovalFlowByUserResource;
use App\Http\Resources\reports\RouteApprovedPaymentRequest;
use App\Http\Resources\reports\RouteBillToPayResource;
use App\Jobs\ExportJob;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\AccountsPayableApprovalFlowLog;
use App\Models\ApprovalFlow;
use App\Models\AttachmentLogDownload;
use App\Models\Export;
use App\Models\LogActivity;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasTax;
use App\Models\Provider;
use App\Models\TemporaryLogUploadPaymentRequest;
use App\Models\TypeOfTax;
use App\Models\User;
use App\Models\UserHasPaymentRequest;
use App\Services\NotificationService;
use App\Services\Utils;
use Artisan;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use DB;
use Exception;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;
use Storage;

class InfoController extends Controller
{
    private $paymentRequestCleanWith = ['installments', 'company', 'provider', 'cost_center', 'approval.approval_flow', 'currency', 'cnab_payment_request.cnab_generated'];
    private $accountsPayableApprovalFlowClean;
    private $paymentRequestClean;

    public function __construct(AccountsPayableApprovalFlowClean $accountsPayableApprovalFlowClean, PaymentRequestClean $paymentRequestClean)
    {
        $this->accountsPayableApprovalFlowClean = $accountsPayableApprovalFlowClean;
        $this->paymentRequestClean = $paymentRequestClean;
    }

    public function duplicateInformationSystem(Request $request)
    {
        $resume = DB::select("SELECT provider_id, invoice_number, count(*) total FROM payment_requests where invoice_number is not null AND deleted_at IS NULL group by provider_id, invoice_number having count(*) > 1;");
        $details = DB::select("SELECT id, provider_id, invoice_number, created_at, user_id, cost_center_id, amount
                FROM payment_requests
                where concat(provider_id, '-', invoice_number) IN (
                SELECT concat(provider_id, '-', invoice_number)
                FROM payment_requests where invoice_number is not null AND deleted_at IS NULL group by provider_id, invoice_number having count(*) > 1 );");

        $taxDuplicate = DB::select("SELECT * FROM type_of_tax;");
        $qtdLogsOLD = DB::select("SELECT count(*) FROM activity_log;");
        $qtdLogsNEW = DB::select("SELECT count(*) FROM accounts_payable_approval_flows_log;");

        return response()->json([
            'old-logs' => $qtdLogsOLD,
            'new-logs' => $qtdLogsNEW,
            'tax' => $taxDuplicate
        ], 200);
    }

    public function temporaryLogUploadPaymentRequest(Request $request)
    {
        return TemporaryLogUploadPaymentRequest::orderBy('id', 'desc')->limit(10)->get();
    }

    public function storageUpload(Request $request)
    {
        $nameFiles = [];
        $archives = $request->archive;
        $folder = 'teste';

        if (!is_array($archives)) {
            $archives = [
                $archives
            ];
        }

        foreach ($archives as $archive) {
            $generatedName = null;
            $data = uniqid(date('HisYmd'));
            if (is_array($archive)) {
                $archive = $archive['attachment'];
            }
            $originalName  = explode('.', $archive->getClientOriginalName());
            $extension = $originalName[count($originalName) - 1];
            $generatedName = "{$originalName[0]}_{$data}.{$extension}";
            //$upload = $archive->storeAs($folder, $generatedName);
            $s3Client = new S3Client([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => '2006-03-01'
            ]);
            $bucket = env('AWS_BUCKET');
            $key = $folder . '/' . $generatedName;
            try {
                // Using stream instead of file path
                $source = fopen($archive, 'rb');
                $uploader = new ObjectUploader(
                    $s3Client,
                    $bucket,
                    $key,
                    $source
                );
                $uploader->upload();
                array_push($nameFiles, $generatedName);
            } catch (Exception $e) {
                TemporaryLogUploadPaymentRequest::create([
                    'error' => $e->getMessage(),
                    'folder' => $folder
                ]);
                error_log($e->getMessage());
            }
        }
        return Storage::disk('s3')->temporaryUrl("teste/{$nameFiles[0]}", now()->addMinutes(30));
    }

    public function taxDelete(Request $request)
    {
        PaymentRequestHasTax::whereIn('type_of_tax_id', [9])->update(['type_of_tax_id' => 2]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [10])->update(['type_of_tax_id' => 3]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [12])->update(['type_of_tax_id' => 5]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [14])->update(['type_of_tax_id' => 7]);
        TypeOfTax::destroy([9, 10, 12, 14]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [11, 4])->update(['type_of_tax_id' => 15]);
        TypeOfTax::destroy([11, 4]);

        return response()->json([
            'sucess' => 'taxas deletadas e atualizadas'
        ], 200);
    }

    public function alterTableLogs(Request $request)
    {
        $paymentRequests = PaymentRequest::withTrashed()->withoutGlobalScopes();
        if (array_key_exists('reset', $request->all())) {
            AccountsPayableApprovalFlowLog::truncate();
            DB::table('activity_log')->update(['integration' => false]);
        }
        if (array_key_exists('id', $request->all())) {
            $paymentRequests = $paymentRequests->where('id', $request->all()['id']);
        } else if (array_key_exists('to', $request->all()) && array_key_exists('from', $request->all())) {
            $paymentRequests = $paymentRequests->where('id', '>=', $request->all()['to'])->where('id', '<=', $request->all()['from']);
        }
        $counter = 0;
        foreach ($paymentRequests->orderBy('id', 'desc')->get() as $paymentRequest) {
            $id = $paymentRequest['id'];
            if (AccountsPayableApprovalFlow::where('payment_request_id', $id)->exists()) {
                $approvalFlow = AccountsPayableApprovalFlow::where('payment_request_id', $id)->first();
                $logPaymentRequest =  LogActivity::where([
                    ['log_name', 'payment_request'],
                    ['subject_id', $id],
                    ['integration', false]
                ])->orWhere(function ($q) use ($approvalFlow) {
                    return $q->where('log_name', 'accounts_payable_approval_flows')->where('subject_id', $approvalFlow->id)->where('integration', false);
                })->orderBy('created_at', 'asc')->get();
            } else {
                $logPaymentRequest =  LogActivity::where([
                    ['log_name', 'payment_request'],
                    ['subject_id', $id],
                    ['integration', false]
                ])->orderBy('created_at', 'asc')->get();
            }

            $retorno = [];

            $paymentRequestID = $paymentRequest['id'];

            foreach ($logPaymentRequest as $log) {
                if ($log['log_name'] == 'accounts_payable_approval_flows') {
                    $status = '';
                    switch ($log['properties']['attributes']['status']) {

                        case 0:
                            $status = 'approved';
                            break;
                        case 2:
                            $status = 'rejected';
                            break;
                        case 3:
                            $status = 'canceled';
                            break;
                        case 1:
                            $status = 'approved';
                            break;
                        case 8:
                            $status = 'multiple-approval';
                            break;
                        case 9:
                            $status = 'transfer-approval';
                            break;
                        default:
                            $status = 'default';
                    }

                    $reason = null;
                    $concatenate = false;
                    $motive = null;
                    $description = null;

                    if (array_key_exists('reason_to_reject_id', $log['properties']['attributes']) && $log['properties']['attributes']['reason_to_reject_id'] != null && array_key_exists('reason_to_reject', $log['properties']['attributes']) && $log['properties']['attributes']['reason_to_reject'] != null) {
                        $reason = $log['properties']['attributes']['reason_to_reject']['title'];
                        $concatenate = true;
                        $motive = $log['properties']['attributes']['reason_to_reject']['title'];
                    }
                    if (array_key_exists('reason', $log['properties']['attributes']) && $log['properties']['attributes']['reason'] != null) {
                        if ($concatenate) {
                            $reason = $reason . ' - ' . $log['properties']['attributes']['reason'];
                            $description = $log['properties']['attributes']['reason'];
                        } else {
                            $reason = $log['properties']['attributes']['reason'];
                            $description = $log['properties']['attributes']['reason'];
                        }
                    }

                    $retorno[] = [
                        'id' => $log['id'],
                        'type' => $status,
                        'createdAt' => $log['created_at'] ?? '',
                        'description' => $log['description'] ?? '',
                        'causerUser' => $log['causer_object']['name'] ?? '',
                        'causerUserID' => $log['causer_object']['id'] ?? '',
                        'causerUserRole' => $log['causer_object']['role']['title'] ?? '',
                        'createdUser' => $log['properties']['attributes']['payment_request']['user']['name'] ?? '',
                        'motive' => $motive,
                        'description' => $description,
                        'stage' => isset($log['properties']['old']['order']) ? $log['properties']['old']['order'] : null, //front exibe a etapa com adição de 1
                    ];
                } else if ($log['log_name'] == 'payment_request') {
                    if ($log['description'] == 'created') {
                        $stage = 0;
                    } else {
                        $stage = null;
                    }
                    $retorno[] = [
                        'id' => $log['id'],
                        'type' => $log['description'],
                        'createdAt' => $log['created_at'],
                        'description' => $log['description'],
                        'causerUser' => $log['causer_object']['name'],
                        'causerUserID' => $log['causer_object']['id'],
                        'causerUserRole' => $log['causer_object']['role']['title'],
                        'createdUser' => $log['properties']['attributes']['user']['name'],
                        'motive' => null,
                        'description' => null,
                        'stage' => $stage,
                    ];
                }
            }
            foreach ($retorno as $individualLog) {
                try {
                    Utils::createLogApprovalFlowLogPaymentRequest($paymentRequestID, $individualLog['type'], $individualLog['motive'], $individualLog['description'], $individualLog['stage'], $individualLog['causerUserID'], $individualLog['motive'], $individualLog['createdAt']);
                    $logModel = Activity::findOrFail($individualLog['id']);
                    $logModel->integration = true;
                    $logModel->save();
                } catch (Exception $e) {
                    return response()->json([
                        'error' => $e->getMessage(),
                        'payment_id' => $paymentRequestID,
                        'log_id' => $individualLog['id'],
                    ], 500);
                }
            }
            $counter++;
        }
        return response()->json([
            'total' => $counter,
        ], 200);
    }

    public function redisExample(Request $request)
    {
        $id = uniqid();
        Redis::hSet($id, 'name', 'message-job');
        Redis::hSet($id, 'data', json_encode($request->all()));
        Redis::hSet($id, 'opts', '{}');
        Redis::hSet($id, 'delay', 0);
        Redis::hSet($id, 'processedOn', 'null');
        Redis::hSet($id, 'timestamp', 'null');
        Redis::hSet($id, 'priority', 0);
        Redis::rpush('active', $id);
    }

    public function redisClean(Request $request)
    {
        $requestInfo = $request->all();
        Redis::del($requestInfo['key']);
    }

    public function failedJob(Request $request)
    {
        if (array_key_exists('id', $request->all())) {
            return DB::select("SELECT * FROM failed_jobs WHERE id  = " . $request->id);
        }
        return DB::select("SELECT * FROM failed_jobs ORDER BY id DESC LIMIT " . ($request->limit ?? 1));
    }

    public function archiveDownloadLog(Request $request)
    {
        return AttachmentLogDownload::orderBy('id', 'desc')->limit(30)->get();
    }


    public function scheduling(Request $request)
    {
        Artisan::call($request->command);
        return true;
    }

    public function sendMailTest(Request $request)
    {
        NotificationService::mailTest([$request->mail]);
        return true;
    }

    public function getLastJob(Request $request)
    {
        return DB::select("SELECT * FROM jobs ORDER BY id DESC LIMIT 1");
    }

    public function getAllJob(Request $request)
    {
        return DB::select("SELECT * FROM jobs ORDER BY id DESC");
    }

    public function getAllAccountsForApproval(Request $request)
    {
        $requestInfo = $request->all();
        // auth()->user()->id = auth()->user()->logged_user_id == null ? auth()->user()->id : auth()->user()->logged_user_id;
        $approvalFlowUserOrder = ApprovalFlow::where('role_id', 1)->get(['order', 'group_approval_flow_id']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $paymentRequest = Utils::search(new PaymentRequestClean, $requestInfo, ['order']);
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);

        $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
            $arrayStatus = Utils::statusApprovalFlowRequest($requestInfo);
            $query->whereIn('status', $arrayStatus)
                ->where('deleted_at', '=', null);
        });
        $idsPaymentRequestOrder = [];
        foreach ($approvalFlowUserOrder as $approvalOrder) {
            $accountApprovalFlow = AccountsPayableApprovalFlowClean::where('order', $approvalOrder['order'])
                ->where('group_approval_flow_id', $approvalOrder['group_approval_flow_id'])
                ->get('payment_request_id');
            $idsPaymentRequestOrder = array_merge($idsPaymentRequestOrder, $accountApprovalFlow->pluck('payment_request_id')->toArray());
        }
        $multiplePaymentRequest = UserHasPaymentRequest::where('user_id', $requestInfo['uid'])->where('status', 0)->get('payment_request_id');
        //$paymentRequest = $paymentRequest->orWhere(function ($query) use ($multiplePaymentRequest, $requestInfo) {
        $ids = $multiplePaymentRequest->pluck('payment_request_id')->toArray();
        $paymentRequestMultiple = PaymentRequestClean::withoutGlobalScopes()->whereIn('id', $ids);
        $paymentRequestMultiple = Utils::baseFilterReportsPaymentRequest($paymentRequestMultiple, $requestInfo);
        $paymentRequestMultiple->get('id');
        $ids = $paymentRequestMultiple->pluck('id')->toArray();
        //union ids payment request
        $paymentRequestIDs = $paymentRequest->get('id');
        $paymentRequestIDs = $paymentRequest->pluck('id')->toArray();
        $ids = array_merge($ids, $paymentRequestIDs);
        $paymentRequest = PaymentRequestClean::withoutGlobalScopes()->whereIn('id', $ids)->with($this->paymentRequestCleanWith);
        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'id';
        return RouteApprovalFlowByUserResource::collection(Utils::pagination($paymentRequest, $requestInfo)); //;
    }

    public function getUsers(Request $request)
    {
        return User::get();
    }

    public function laravelLog(Request $request)
    {
        if (array_key_exists('clear', $request->all()) && $request->clear == true) {
            $logFilePath = storage_path('logs/laravel.log');
            if (file_exists($logFilePath)) {
                unlink($logFilePath);
                return response('log apagado!');
            }
        }
        return response()->download(storage_path('logs/laravel.log'));
    }

    public function exportTest(Request $request)
    {
        $exportFile = ExportsUtils::exportFile($request->all(), 'testExport', true);
        ExportsUtils::convertExportFormat($exportFile);
        $exportFileDB = Export::findOrFail($exportFile['id']);

        (new BillsToPayExport($request->all(), $exportFileDB->name))
            ->queue($exportFileDB->path, 's3')
            ->allOnQueue('default')
            ->chain([
                new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
            ]);
        // }

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function exportTestGet(Request $request)
    {
        return Export::where('test', true)->orderBy('id', 'DESC')->limit(20)->get();
    }

    public function getProvider(Request $request)
    {
        $provider = new Provider;
        $provider = Utils::search($provider, $request->all());
        return Utils::pagination($provider->with(['bank_account', 'provider_category', 'user', 'chart_of_account', 'cost_center', 'city', 'attachments']), $request->all());
    }

    public function approvedPaymentRequest(Request $request)
    {
        $requestInfo = $request->all();
        $paymentRequest = $this->paymentRequestClean->query();
        $paymentRequest = $paymentRequest->with($this->paymentRequestCleanWith);
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);
        $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
            $query = $query->where('status', 1);
        });

        //whereDate("due_date", "<=", Carbon::now().subDays($days_late))
        return RouteApprovedPaymentRequest::collection(Utils::pagination($paymentRequest, $requestInfo));
    }
}
