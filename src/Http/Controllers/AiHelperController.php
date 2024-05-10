<?php

namespace ManoCode\AIHelper\Http\Controllers;

use ManoCode\AIHelper\Services\AIHelperService;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use Slowlyo\OwlAdmin\Services\AdminCodeGeneratorService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiHelperController extends AdminController
{
    public function index()
    {
        $page = $this->aiPage();

        return $this->response()->success($page);
    }

    public function gen()
    {
        $response = new StreamedResponse(function () {
            app(AIHelperService::class)->request(request()->json('prompt'), request()->json('gen'));
        });
        // Set headers for streaming
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        return $response;
    }

    public function import()
    {
        try {
            $data = json_decode(request()->getContent(),true);
            app(AIHelperService::class)->create($data);
        } catch (\Throwable $e) {
            return Admin::response()->fail($e->getMessage());
        }
        return Admin::response()->successMessage('生成成功');
    }
    public function aiPage()
    {

        return amis()
            ->Page()
            ->id('terminal_dialog')
            ->title('')
            ->body([
                amis()->Grid()->columns([
                    amis()->GridColumn()->sm(6)->body([
                        amis()->TextControl()->name('prompt')->label('需求输入')->type('textarea')->size('full'),

                        amis()->Button()->label('生成需求')->onEvent([
                            'click' => [
                                'weight' => '0',
                                'actions' => [
                                    [
                                        'script' => 'const url = "' . admin_url('ai-helper/gen', true) . '"; // 后端服务URL
const decoder = new TextDecoder(\'utf-8\');
let textContent = "";
fetch(url,{
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "Authorization": `Bearer ' . request()->bearerToken() . '`
  },
  body: JSON.stringify({
  prompt: event.data.prompt,
  gen:false
})
  }).then(response => {
    const reader = response.body.getReader();
    return new ReadableStream({
      async start(controller) {
        while (true) {
          const {done, value} = await reader.read();
          if (done) {
            controller.close();
            break;
          }
          const chunkText = decoder.decode(value, {stream: true});
          doAction({
              actionType: \'setValue\',
              componentId: "ai_optimization",
              args:{
                 "value":textContent +=chunkText
              }
          });
          controller.enqueue(value);
        }
      }
    });
  }).then(stream => new Response(stream))
    .catch(err => console.error(err));',
                                        'actionType' => 'custom',
                                    ],
                                ],
                            ],
                        ])->mode('inline')->columnClassName('v-middle'),
                        amis()->Divider(),
                        amis()->TextControl()->type('textarea')->id("ai_optimization")->name('ai_optimization')->label('优化结果'),
                        amis()->Button()->label('生成json代码')->onEvent([
                            'click' => [
                                'weight' => '0',
                                'actions' => [
                                    [
                                        'script' => 'const url = "' . admin_url('ai-helper/gen', true) . '"; // 后端服务URL
const decoder = new TextDecoder(\'utf-8\');
let textContent = "";
fetch(url,{
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "Authorization": `Bearer ' . request()->bearerToken() . '`
  },
  body: JSON.stringify({
  prompt: event.data.ai_optimization,
  gen:true
})
  }).then(response => {
    const reader = response.body.getReader();
    return new ReadableStream({
      async start(controller) {
        while (true) {
          const {done, value} = await reader.read();
          if (done) {
            controller.close();
            break;
          }
          const chunkText = decoder.decode(value, {stream: true});
          doAction({
              actionType: \'setValue\',
              componentId: "gen_code",
              args:{
                 "value":textContent +=chunkText
              }
          });
          controller.enqueue(value);
        }
      }
    });
  }).then(stream => new Response(stream))
    .catch(err => console.error(err));',
                                        'actionType' => 'custom',
                                    ],
                                ],
                            ],
                        ])->mode('inline')->columnClassName('v-middle'),
                    ]),
                    amis()->GridColumn()->sm(6)->body([
                        amis()->TextControl()->type('textarea')->id("gen_code")->name('gen_code')->label('生成代码'),
                        amis()->VanillaAction()->label('导入到代码生成器')->onEvent([
                            'click' => [
                                'weight' => '0',
                                'actions' => [
                                    [
                                        'ignoreError' => '',
                                        'outputVar' => 'responseResult',
                                        'actionType' => 'ajax',
                                        'options' => [],
                                        'api' => [
                                            'url' => admin_url('ai-helper/import', false),
                                            'method' => 'post',
                                            'messages' => [],
                                            'dataType' => 'json',
                                            'data' => '${gen_code}',
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                    ])
                ])
            ]);
    }
}
