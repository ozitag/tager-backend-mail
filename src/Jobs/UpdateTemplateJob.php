<?php

namespace OZiTAG\Tager\Backend\Mail\Jobs;

use OZiTAG\Tager\Backend\Core\Jobs\Job;
use OZiTAG\Tager\Backend\Mail\Models\TagerMailTemplate;
use OZiTAG\Tager\Backend\Mail\Repositories\MailTemplateRepository;
use OZiTAG\Tager\Backend\Mail\Requests\UpdateTemplateRequest;

class UpdateTemplateJob extends Job
{
    /**
     * @var UpdateTemplateRequest
     */
    private $request;

    /**
     * @var Object
     */
    private $model;

    public function __construct(UpdateTemplateRequest $request, ?TagerMailTemplate $model = null)
    {
        $this->request = $request;

        $this->model = $model;
    }

    public function handle(MailTemplateRepository $mailTemplateRepository)
    {
        $mailTemplateRepository->set($this->model);

        return $mailTemplateRepository->fillAndSave([
            'subject' => $this->request->subject,
            'body' => $this->request->body,
            'service_template' => $this->request->serviceTemplate,
            'recipients' => $this->request->recipients ? implode(',', $this->request->recipients) : null,
            'changed_by_admin' => true
        ]);
    }
}
