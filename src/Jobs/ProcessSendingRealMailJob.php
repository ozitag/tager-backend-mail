<?php

namespace OZiTAG\Tager\Backend\Mail\Jobs;

use OZiTAG\Tager\Backend\Core\Jobs\QueueJob;
use OZiTAG\Tager\Backend\Mail\Enums\TagerMailStatus;
use OZiTAG\Tager\Backend\Mail\Utils\TagerMailAttachments;
use OZiTAG\Tager\Backend\Mail\Utils\TagerMailConfig;
use OZiTAG\Tager\Backend\Mail\Utils\TagerMailSender;

class ProcessSendingRealMailJob extends QueueJob
{
    /** @var string */
    private $to;

    /** @var string */
    private $subject;

    /** @var string */
    private $body;

    /** @var string */
    private $serviceTemplate;

    /** @var null|integer */
    private $logId;

    /** @var TagerMailAttachments|null */
    private $attachments = null;

    public function __construct($to, $subject, $body, $serviceTemplate = null, $logId = null, ?TagerMailAttachments $attachments = null)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->logId = $logId;
        $this->serviceTemplate = $serviceTemplate;
        $this->attachments = $attachments;
    }

    private function setLogStatus($status, $error = null)
    {
        dispatch(new SetLogStatusJob($this->logId, $status, $error));
    }

    /**
     * @return bool
     */
    private function isRecipientAllowed()
    {
        $validEmails = TagerMailConfig::getAllowedEmails();
        return $validEmails == '*' || in_array($this->to, $validEmails);
    }

    public function handle(TagerMailSender $sender)
    {
        if ($this->isRecipientAllowed() == false) {
            $this->setLogStatus(TagerMailStatus::Skip);
            return;
        }

        $this->setLogStatus(TagerMailStatus::Sending);

        try {
            if ($this->serviceTemplate) {
                $sender->sendUsingServiceTemplate($this->to, $this->serviceTemplate, null, $this->attachments, $this->logId);
            } else {
                $sender->send($this->to, $this->subject, $this->body, $this->attachments, $this->logId);
            }

        } catch (\Exception $exception) {
            $this->setLogStatus(TagerMailStatus::Failure, $exception->getMessage());
        }
    }
}
