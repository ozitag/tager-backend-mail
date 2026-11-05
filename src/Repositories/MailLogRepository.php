<?php

namespace OZiTAG\Tager\Backend\Mail\Repositories;

use Illuminate\Contracts\Database\Eloquent\Builder;
use OZiTAG\Tager\Backend\Core\Repositories\EloquentRepository;
use OZiTAG\Tager\Backend\Core\Repositories\ISearchable;
use OZiTAG\Tager\Backend\Mail\Enums\MailStatus;
use OZiTAG\Tager\Backend\Mail\Models\TagerMailLog;

class MailLogRepository extends EloquentRepository implements ISearchable
{
    public function __construct(TagerMailLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param $days
     * @return TagerMailLog[]
     */
    public function findSkipForLastDays($days)
    {
        $startDateTime = date('Y-m-d H:i:s', strtotime(date('Y-m-d', time() - ($days - 1) * 86400)));

        return $this->model
            ->where('created_at', '>=', $startDateTime)
            ->where('status', '=', MailStatus::Skip)
            ->get();
    }

    public function searchByQuery(?string $query, ?Builder $builder = null): ?Builder
    {
        $builder = $builder ? $builder : $this->model;

        return $builder
            ->orWhere('recipient', 'LIKE', '%' . $query . '%')
            ->orWhere('subject', 'LIKE', '%' . $query . '%');
    }
}
