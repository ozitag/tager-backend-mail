<?php

namespace OZiTAG\Tager\Backend\Mail\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use OZiTAG\Tager\Backend\Core\Controller;
use OZiTAG\Tager\Backend\Core\SuccessResource;
use OZiTAG\Tager\Backend\Admin\Resources\ProfileResource;
use OZiTAG\Tager\Backend\Mail\Features\ListMailTemplatesFeature;
use OZiTAG\Tager\Backend\Mail\Features\UpdateMailTemplateFeature;
use OZiTAG\Tager\Backend\Mail\Features\ViewMailTemplateFeature;

class AdminTemplatesController extends Controller
{
    public function index(Request $request)
    {
        $template = $request->get('template');

        if (!empty($template)) {
            return $this->serve(ViewMailTemplateFeature::class, [
                'template' => $template
            ]);
        }

        return $this->serve(ListMailTemplatesFeature::class);
    }

    public function update(Request $request)
    {
        return $this->serve(UpdateMailTemplateFeature::class, [
            'template' => $request->get('template')
        ]);
    }
}
