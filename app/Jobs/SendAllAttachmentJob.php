<?php

namespace App\Jobs;

use App\Models\AttachmentLogDownload;
use App\Models\AttachmentReport;
use App\Models\Mail;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Services\NotificationService;
use App\Services\Utils;
use Aws\S3\Exception\S3Exception;
use Error;
use Exception;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Zip as ZipStream;
use Storage;
use Throwable;

class SendAllAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $request;
    public $maxExceptions = 1000;
    public $timeout = 14800; //3 hours

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function handle()
    {
        $folder = uniqid(date('HisYmd'));
        $requestInfo = $this->request;
        $paymentRequests = PaymentRequestClean::withOutGlobalScopes();
        $zip = ZipStream::create($folder . '.zip');

        if (array_key_exists('from', $requestInfo)) {
            $paymentRequests = $paymentRequests->where('created_at', '>=', $requestInfo['from']);
        }
        if (array_key_exists('to', $requestInfo)) {
            $paymentRequests = $paymentRequests->where('created_at', '<=', $requestInfo['to']);
        }

        $paymentRequests = $paymentRequests->with(['attachments', 'installments'])->get();

        foreach ($paymentRequests as $paymentRequest) {
            $type = ($paymentRequest->payment_type == 0) ? 'NF_' : (($paymentRequest->payment_type == 3) ? 'IN_' : (($paymentRequest->payment_type == 2) ? 'AV_' : (($paymentRequest->payment_type == 1) ? 'BL_' : null)));
            $zip = self::generateZipAws('invoice', $paymentRequest->invoice_file, $paymentRequest->id, $type, null, $zip);
            foreach ($paymentRequest->attachments as $attachments) {
                $zip = self::generateZipAws('attachment-payment-request', $attachments->attachment, $paymentRequest->id, $type, null, $zip);
            }
            foreach ($paymentRequest->installments as $installment) {
                $zip = self::generateZipAws('billet', $installment->billet_file, $paymentRequest->id, $type, $installment->parcel_number, $zip);
            }
        }

        $zip->saveTo('s3://' . env('AWS_BUCKET') . '/all-attachment-temporary/' . $folder);
        $link = Storage::disk('s3')->temporaryUrl('all-attachment-temporary/' . $folder . '/' . $folder . '.zip', now()->addDay(7));

        AttachmentReport::where('id', $requestInfo['attachment-id'])
            ->update([
                'link' => $link,
                'path' => $folder,
                'status' => 1
            ]);

        self::notifyUsers($requestInfo['mails'], $requestInfo['from'], $requestInfo['to'], $link);
    }

    public static function generateZipAws($defaultFolder, $nameArchive = null, $paymentRequestID, $type, $parcelNumber = null, $zip)
    {
        if ($parcelNumber == null) {
            $newNameArchive = $type . $paymentRequestID . '_' . uniqid(date('HisYmd'));
        } else {
            $newNameArchive = $type . $paymentRequestID . '_' . $parcelNumber . '_' . uniqid(date('HisYmd'));
        }
        if ($nameArchive != null) {
            $nameArchive = explode('.' , $nameArchive);
            $nameArchive[0] = Utils::replaceCharacterUpload($nameArchive[0]);
            $nameArchive = implode('.', $nameArchive);
            if (Storage::disk('s3')->exists($defaultFolder . '/' . $nameArchive)) {
                $extension = explode('.', $nameArchive);
                try {
                    $zip->add('s3://' . env('AWS_BUCKET') . '/' . $defaultFolder . '/' . $nameArchive, $newNameArchive . '.' . end($extension));
                } catch (Exception $e) {
                    AttachmentLogDownload::create([
                        'archive' => $nameArchive,
                        'payment_request_id' => $paymentRequestID,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        return $zip;
    }

    public function notifyUsers($mails, $from, $to, $link)
    {
        $dataSendMail = NotificationService::generateDataSendRedisAttachment($mails, 'send-file-accounting', $link, $from, $to);
        NotificationService::sendEmail($dataSendMail);
    }

    public function failed(Throwable $exception): void
    {
        AttachmentReport::where('id', $this->request['attachment-id'])
            ->update([
                'status' => 2,
                'error' => $exception->getMessage(),
            ]);
    }
}
