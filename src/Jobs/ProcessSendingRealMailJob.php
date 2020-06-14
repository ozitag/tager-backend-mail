<?php

namespace OZiTAG\Tager\Backend\Mail\Jobs;

use OZiTAG\Tager\Backend\Core\QueueJob;
use OZiTAG\Tager\Backend\Mail\Enums\TagerMailStatus;
use OZiTAG\Tager\Backend\Mail\Exceptions\TagerMailSenderException;
use OZiTAG\Tager\Backend\Mail\Models\TagerMailLog;
use App\Models\Product;
use App\Repositories\Interfaces\IProductReviewRepository;
use OZiTAG\Tager\Backend\Mail\Repositories\MailLogRepository;
use OZiTAG\Tager\Backend\Mail\Senders\SenderFactory;
use OZiTAG\Tager\Backend\Mail\Utils\TagerMailAttachments;
use OZiTAG\Tager\Backend\Mail\Utils\TagerMailConfig;
use OZiTAG\Tager\Backend\Mail\Utils\TagerMailSender;

class ProcessSendingRealMailJob
{
    /** @var string */
    private $to;

    /** @var string */
    private $subject;

    /** @var string */
    private $body;

    /** @var integer */
    private $logId;

    /** @var TagerMailAttachments|null */
    private $attachments = null;

    public function __construct($to, $subject, $body, $logId, ?TagerMailAttachments $attachments = null)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->logId = $logId;
        $this->attachments = $attachments;
    }
    
    public function handle(MailLogRepository $mailLogRepository, TagerMailConfig $tagerMailConfig, TagerMailSender $sender)
    {
        $logModel = $mailLogRepository->find($this->logId);
        if (!$logModel) {
            return;
        }

        $logModel->status = TagerMailStatus::Sending;
        $logModel->save();

        try {
            $sender->send($this->to, $this->subject, $this->body, $this->attachments, ['logId' => $logModel->id]);
        } catch (\Exception $exception) {
            $logModel->status = TagerMailStatus::Failure;
            $logModel->error = $exception->getMessage();
        }

        $logModel->save();
    }
}
