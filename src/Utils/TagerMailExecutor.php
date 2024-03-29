<?php

namespace OZiTAG\Tager\Backend\Mail\Utils;

use OZiTAG\Tager\Backend\Mail\Enums\MailStatus;
use OZiTAG\Tager\Backend\Mail\Exceptions\TagerMailInvalidMessageException;
use OZiTAG\Tager\Backend\Mail\Jobs\ProcessSendingRealMailJob;
use OZiTAG\Tager\Backend\Mail\Repositories\MailLogRepository;

class TagerMailExecutor
{
    private ?array $recipients = null;
    private ?array $cc = null;
    private ?array $bcc = null;

    private ?string $subject = null;

    private ?string $body = null;

    private ?string $fromEmail = null;

    private ?string $fromName = null;

    private bool $fromConfiguredManual = false;

    private ?TagerMailAttachments $attachments = null;

    private ?string $template = null;

    private array $templateFields = [];

    private TagerMailTemplateFactory $templateFactory;

    private MailLogRepository $logRepository;

    public function __construct(TagerMailTemplateFactory $templateFactory, MailLogRepository $mailLogRepository)
    {
        $this->templateFactory = $templateFactory;

        $this->logRepository = $mailLogRepository;

        $this->fromName = config('mail.from.name');

        $this->fromEmail = config('mail.from.address');
    }

    public function setRecipients($recipients)
    {
        $this->recipients = is_array($recipients) ? $recipients : (is_null($recipients) ? $recipients : [$recipients]);
    }

    public function setCc($cc)
    {
        $this->cc = is_array($cc) ? $cc : (is_null($cc) ? $cc : [$cc]);
    }

    public function setBcc($bcc)
    {
        $this->bcc = is_array($bcc) ? $bcc : (is_null($bcc) ? $bcc : [$bcc]);
    }

    public function setSubject($value)
    {
        $this->subject = $value;
    }

    public function setBody($value)
    {
        $this->body = $value;
    }

    public function setAttachments(?TagerMailAttachments $attachments = null)
    {
        $this->attachments = $attachments;
    }

    public function setFrom(?string $fromEmail, ?string $fromName)
    {
        $this->fromEmail = $fromEmail;

        $this->fromName = $fromName;

        $this->fromConfiguredManual = true;
    }

    public function setTemplate($template, $templateFields)
    {
        $this->template = $template;
        $this->templateFields = $templateFields;

        $template = $this->templateFactory->getTemplate($this->template);
        if (!$template) {
            throw new TagerMailInvalidMessageException('Template not found');
        }

        if ($this->fromConfiguredManual == false && (!empty($template->getFromEmail()) || !empty($template->getFromName()))) {
            $this->setFrom($template->getFromEmail(), $template->getFromName());
        }

        if ($template->getCc()) {
            $this->setCc($template->getCc());
        }
        if ($template->getBcc()) {
            $this->setBcc($template->getBcc());
        }
    }

    /**
     * @return array|array[]
     */
    private function getRecipients()
    {
        if (!$this->recipients) {
            if ($this->template) {
                return $this->getTemplateInstance()->getRecipients();
            }
            return [];
        }

        if (is_array($this->recipients)) {
            return $this->recipients;
        }

        return [$this->recipients];
    }

    private function prepareSubject($subject, $templateFields = [])
    {
        $subjectTemplate = TagerMailConfig::getSubjectTemplate();

        if (!$subjectTemplate) {
            return $this->subject;
        }

        $result = \str_replace('{subject}', $subject, $subjectTemplate);

        if (is_array($templateFields)) {
            foreach ($templateFields as $param => $value) {
                $result = str_replace('{{' . $param . '}}', $value, $result);
            }
        }

        return $result;
    }

    private function prepareBody($body, $templateFields = [])
    {
        if (is_array($templateFields)) {
            foreach ($templateFields as $param => $value) {
                $body = str_replace('{{' . $param . '}}', $value, $body);
            }
        }

        return $body;
    }

    /**
     * @return TagerMailTemplate
     * @throws TagerMailInvalidMessageException
     */
    private function getTemplateInstance()
    {
        if (!$this->template) {
            return null;
        }

        $template = $this->templateFactory->getTemplate($this->template);
        if (!$template) {
            throw new TagerMailInvalidMessageException('Template not found');
        }

        return $template;
    }

    private function getSubject()
    {
        if (!$this->template) {
            $result = $this->subject;
        } else {
            $result = $this->getTemplateInstance()->getSubject();
        }

        return $this->prepareSubject($result, $this->templateFields);
    }

    private function getBody()
    {
        if (!$this->template) {
            $result = $this->body;
        } else {
            $result = $this->getTemplateInstance()->getBody();
        }

        return $this->prepareBody($result, $this->templateFields);
    }

    private function createLogItem($status = MailStatus::Created)
    {
        if (TagerMailConfig::hasDatabase() == false) {
            return null;
        }

        $templateInstance = $this->getTemplateInstance();

        $body = $serviceTemplate = $serviceTemplateParams = null;

        if ($templateInstance && $templateInstance->getServiceTemplate()) {
            $serviceTemplate = $templateInstance->getServiceTemplate();
            $serviceTemplateParams = $this->templateFields;
        } else {
            $body = $this->getBody();
        }

        $subject = $this->getSubject();

        $this->logRepository->reset();
        return $this->logRepository->fillAndSave([
            'recipient' => implode(', ', $this->getRecipients()),
            'cc' => $this->cc && !empty($this->cc) ? implode(',', $this->cc) : null,
            'bcc' => $this->bcc && !empty($this->bcc) ? implode(',', $this->bcc) : null,
            'subject' => $subject,
            'body' => $body,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'status' => $status,
            'template_id' => $templateInstance ? $templateInstance->getDatabaseId() : null,
            'template' => $templateInstance ? $templateInstance->getTemplateName() : null,
            'service_template' => $serviceTemplate,
            'service_template_params' => $serviceTemplateParams ? json_encode($serviceTemplateParams) : null,
            'attachments' => $this->attachments ? $this->attachments->getLogString() : null
        ]);
    }

    private function send()
    {
        if (TagerMailConfig::isDisabled()) {
            $this->createLogItem(MailStatus::Disabled);
            return;
        }

        $log = $this->createLogItem(MailStatus::Created);

        dispatch(new ProcessSendingRealMailJob(
            $this->getRecipients(),
            $this->cc ?? [],
            $this->bcc ?? [],
            $this->getSubject(),
            $this->getBody(),
            $this->template ? $this->getTemplateInstance()->getServiceTemplate() : null,
            $this->templateFields,
            $log->id,
            $this->attachments,
            $this->fromEmail,
            $this->fromName
        ));
    }

    private function validate()
    {
        if ($this->getTemplateInstance() == null || $this->getTemplateInstance()->getServiceTemplate() == null) {
            $subject = $this->getSubject();
            if (empty($subject)) {
                throw new TagerMailInvalidMessageException('Subject is empty');
            }
        }
    }

    public function run()
    {
        $this->validate();

        $this->send();
    }
}
