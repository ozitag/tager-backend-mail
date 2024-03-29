<?php

namespace OZiTAG\Tager\Backend\Mail\Jobs;

use OZiTAG\Tager\Backend\Core\Jobs\Job;
use OZiTAG\Tager\Backend\Mail\Enums\MailStatus;
use OZiTAG\Tager\Backend\Mail\Repositories\MailLogRepository;

class SetLogStatusJob extends Job
{
    private $logId;

    protected MailStatus $status;

    private $error;

    public function __construct($logId, MailStatus $status, $error = null)
    {
        $this->logId = $logId;
        $this->status = $status;
        $this->error = $error;
    }

    public function handle(MailLogRepository $repository)
    {
        if (!$this->logId) {
            return;
        }

        $found = $repository->setById($this->logId);
        if (!$found) {
            return;
        }

        $repository->fillAndSave([
            'status' => $this->status->value,
            'error' => $this->error,
        ]);
    }
}
