<?php

namespace ManoCode\AIHelper;

use Slowlyo\OwlAdmin\Renderers\TextControl;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;

class AiHelperServiceProvider extends ServiceProvider
{

    protected $menu = [
        [
            'parent'   => 0,
            'title'    => 'AI工具',
            'url'      => '/ai-helper',
            'url_type' => '1',
            'icon'     => 'fluent-emoji:robot',
        ],
    ];

	public function settingForm()
	{
	    return $this->baseSettingForm()->body([
            amis()->TextControl()->label('请求地址')->name('app_url')->editorState('default')->showCounter('')->validations([])->validationErrors([])->description('openapi的地址'),
            amis()->TextControl()->label('秘钥')->name('app_key')->type('input-password')->editorState('default')->showCounter('')->validations([])->validationErrors([])->description('在api平台申请的aikey'),
            amis()->RadiosControl()->label('引擎类型')->name('engine_type')->options([
                [
                    'label' => 'GPT-4o',
                    'value' => '1',
                ]
            ])->value('1'),
	    ]);
	}
}
