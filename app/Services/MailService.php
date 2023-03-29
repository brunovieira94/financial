<?php

namespace App\Services;

use App\Models\Mail;

class MailService
{
    private $mail;
    public function __construct(Mail $mail)
    {
        $this->mail = $mail;
    }

    public function getAllMail($requestInfo)
    {
        $mail = Utils::search($this->mail, $requestInfo);
        return Utils::pagination($mail, $requestInfo);
    }

    public function getMail($id)
    {
        return $this->mail->findOrFail($id);
    }

    public function postMail($requestInfo)
    {
        if (array_key_exists('email_address', $requestInfo)) {
            if (!Mail::where('email_address', $requestInfo['email_address'])->exists()) {
                $mail = new Mail;
                return $mail->create($requestInfo);
            }
        }
    }

    public function putMail($id, $requestInfo)
    {
        $mail = $this->mail->findOrFail($id);
        $mail->fill($requestInfo)->save();
        return $mail;
    }

    public function deleteMail($id)
    {
        $this->mail->findOrFail($id)->delete();
        return true;
    }
}
