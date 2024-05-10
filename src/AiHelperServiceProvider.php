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
            amis()->TextControl()->label('秘钥')->name('app_key')->editorState('default')->showCounter('')->validations([])->validationErrors([])->maxLength('32')->description('在api平台申请的aikey'),
            amis()->RadiosControl()->label('引擎类型')->name('engine_type')->options([
                [
                    'label' => 'GPT4',
                    'value' => '1',
                ]
            ])->value('1'),
	    ]);
	}
}
