<?php

namespace ManoCode\AIHelper\Services;

use ManoCode\AIHelper\AiHelperServiceProvider;
use Illuminate\Support\Facades\Validator;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Services\AdminCodeGeneratorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AIHelperService
{

    public function create($data)
    {
        $data = $this->check($data);
        app(AdminCodeGeneratorService::class)->store($data);
        return true;
    }

    public function request($prompt, $gen = true)
    {
        // 设置最大执行时间为300秒
        set_time_limit(300);
        $app_url = AiHelperServiceProvider::setting('app_url');
        $app_key = AiHelperServiceProvider::setting('app_key');

        admin_abort_if(empty($app_key), '秘钥不存在，请先在插件配置中设置！');
        admin_abort_if(empty($app_url), '请求地址不存在，请先在插件配置中设置！');
        $system = $gen
            ? file_get_contents(base_path('vendor/mano-code/ai-helper/prompt.md'))
            : $this->getNonGenSystemPrompt();
        $message = [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
            'stream' => true
        ];
        if ($gen) {
            $message['response_format'] = ['type' => "json_object"];
        }
        return response()->stream(function () use ($appUrl, $appKey, $message) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $appKey
            ])->withOptions([
                'stream' => true
            ])->post($appUrl . "/v1/chat/completions", $message);

            foreach ($response->getBody() as $chunk) {
                $messages = $this->parseEventStreamData($chunk);
                foreach ($messages as $message) {
                    foreach ($message['choices'] ?? [] as $choice) {
                        $str = $choice['delta']['content'] ?? '';
                        echo $str;
                        ob_flush();
                        flush();
                    }
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no'
        ]);
    }
    private function getNonGenSystemPrompt(): string
    {
        return "你以高级产品经理的角色为我生成需求详细内容，我给你一句话，你帮我设计产品的详细字段、类型等详细的需求拆解，
例如：
用户提问：生成一个用户管理系统：
你应该回答：

生成一个商家管理模块，详细字段和组件应该如下所述：
1. 商家名称
   - 字段类型：文本
   - 描述：商家或店铺的名称
2. 商家Logo
   - 字段类型：图片组件
   - 描述：商家或店铺的标志，用于标识和品牌宣传";
    }


    # 验证生成的格式是否合法
    private function check(array $data)
    {
        $validator = Validator::make($data,
            [
                'title' => 'required|string',
                'table_name' => 'required|string',
                'primary_key' => 'required|string',
                'model_name' => 'required|string',
                'controller_name' => 'required|string',
                'service_name' => 'required|string',
                'columns.*.name' => 'required|string',
                'columns.*.type' => 'required|in:integer,unsignedInteger,tinyInteger,unsignedTinyInteger,smallInteger,unsignedSmallInteger,mediumInteger,unsignedMediumInteger,date,time,dateTime,tempstamp,float,double,decimal,string,char,text,mediumText,longText',
                'columns.*.default' => 'nullable',
                'columns.*.nullable' => 'required|boolean',
                'columns.*.comment' => 'required|string',
                'columns.*.index' => 'nullable|in:unique',
                'columns.*.action_scope' => 'required|array',
                'columns.*.action_scope.*' => 'in:list,detail,create,edit',
                'columns.*.file_column' => 'required|boolean',
                'columns.*.form_component_type' => 'required|in:TextControl,DateTimeControl,SwitchControl,SelectControl,TextareaControl,InputCityControl,RatingControl,ImageControl',
                'columns.*.form_component_property' => 'nullable|array',
                'columns.*.list_component_type' => 'nullable|in:TableColumn,ImageControl',
                'columns.*.list_component_property' => 'nullable|array',
                'columns.*.detail_component_type' => 'nullable|in:StaticExactControl,ImageControl',
                'columns.*.detail_component_property' => 'nullable|array',
                'need_timestamps' => 'required|boolean',
                'soft_delete' => 'required|boolean',
                'needs' => 'required|array',
                'menu_info.enabled' => 'required|boolean',
                'menu_info.parent_id' => 'required|numeric',
                'menu_info.icon' => 'required|string',
                'menu_info.route' => 'required|string',
                'menu_info.title' => 'required|string',
                'page_info.dialog_form' => 'required|boolean',
                'page_info.row_actions' => 'required|array',
                'page_info.dialog_size' => 'required|string',
            ],
            [
                'title.required' => '标题字段是必需的。',
                'title.string' => '标题必须是字符串。',
                'table_name.required' => '表名称字段是必需的。',
                'table_name.string' => '表名称必须是字符串。',
                'primary_key.required' => '主键字段是必需的。',
                'primary_key.string' => '主键必须是字符串。',
                'model_name.required' => '模型名称字段是必需的。',
                'model_name.string' => '模型名称必须是字符串。',
                'controller_name.required' => '控制器名称字段是必需的。',
                'controller_name.string' => '控制器名称必须是字符串。',
                'service_name.required' => '服务名称字段是必需的。',
                'service_name.string' => '服务名称必须是字符串。',
                'columns.*.name.required' => '列名称字段是必需的。',
                'columns.*.name.string' => '列名称必须是字符串。',
                'columns.*.type.required' => '列类型字段是必需的。',
                'columns.*.type.in' => '列类型无效。',
                'columns.*.nullable.required' => '列是否可为空字段是必需的。',
                'columns.*.nullable.boolean' => '列是否可为空必须是布尔值。',
                'columns.*.comment.required' => '列注释字段是必需的。',
                'columns.*.comment.string' => '列注释必须是字符串。',
                'columns.*.index.in' => '列索引类型无效。',
                'columns.*.action_scope.required' => '操作范围字段是必需的。',
                'columns.*.action_scope.array' => '操作范围必须是一个数组。',
                'columns.*.action_scope.*.in' => '操作范围值无效。',
                'columns.*.file_column.required' => '文件列标识字段是必需的。',
                'columns.*.file_column.boolean' => '文件列标识必须是布尔值。',
                'columns.*.form_component_type.required' => '表单组件类型字段是必需的。',
                'columns.*.form_component_type.in' => '表单组件类型无效。',
                'need_timestamps.required' => '是否需要时间戳字段是必需的。',
                'need_timestamps.boolean' => '是否需要时间戳必须是布尔值。',
                'soft_delete.required' => '是否软删除字段是必需的。',
                'soft_delete.boolean' => '是否软删除必须是布尔值。',
                'needs.required' => '需求字段是必需的。',
                'needs.array' => '需求必须是一个数组。',
                'menu_info.enabled.required' => '菜单启用字段是必须的。',
                'menu_info.enabled.boolean' => '菜单启用必须是布尔值。',
                'menu_info.parent_id.required' => '菜单父 ID 是必须的。',
                'menu_info.parent_id.numeric' => '菜单父 ID 必须是一个数字。',
                'menu_info.icon.required' => '菜单图标字段是必须的。',
                'menu_info.icon.string' => '菜单图标必须是一个字符串。',
                'menu_info.route.required' => '菜单路由字段是必须的。',
                'menu_info.route.string' => '菜单路由必须是一个字符串。',
                'menu_info.title.required' => '菜单标题字段是必须的。',
                'menu_info.title.string' => '菜单标题必须是一个字符串。',
                'page_info.dialog_form.required' => '对话框表单字段是必须的。',
                'page_info.dialog_form.boolean' => '对话框表单必须是布尔值。',
                'page_info.row_actions.required' => '行动作字段是必须的。',
                'page_info.row_actions.array' => '行动作必须是一个数组。',
                'page_info.dialog_size.required' => '对话框大小字段是必须的。',
                'page_info.dialog_size.string' => '对话框大小必须是一个字符串。',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors();
            // 初始化一个空数组来存储错误消息
            $errorMessages = [];
            // 遍历所有错误字段
            foreach ($errors->getMessages() as $field => $message) {
                // 将字段名和其对应的错误消息添加到数组中
                $errorMessages[] = $field . ': ' . implode(', ', $message);
            }
            // 将错误消息数组转换为单个字符串，每个错误消息之间用分号和空格隔开
            $errorMessageString = implode('; ', $errorMessages);
            // 输出错误消息
            throw new \Exception($errorMessageString);
        }
        foreach ($data['columns'] as &$item) {
            if ($item['name'] == 'status') {
                $item['name'] = 'state';
            }
            foreach ($item['form_component_property'] as &$property) {
                if (is_array($property['value'])) {
                    $property['value'] = json_encode($property['value'], 256);
                }
            }
        }
        return $data;
    }
//    private static function parseEventStreamData($data)
//    {
//        // 如果$data为空，返回一个空数组
//        if (empty($data)) {
//            return [];
//        }
//        // 解析事件流数据
//        $messages = [];
//        $lines = explode("\n", $data);
//        foreach ($lines as $line) {
//            $line = trim($line);
//            if (!empty($line)) {
//                $messages[] = json_decode($line, true);
//            }
//        }
//        return $messages;
//    }
    private function parseEventStreamData($response): array
    {
        $data = [];
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            if ($name == 'data') {
                $data[] = json_decode(trim($value), true);
            }
        }
        return $data;
    }
}
