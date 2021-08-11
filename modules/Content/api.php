<?php

/**
 *
 * @OA\Tag(
 *   name="content",
 *   description="Content module",
 * )
 */

$this->on('restApi.config', function($restApi) {

    /**
     * @OA\Get(
     *     path="/content/item/{model}",
     *     tags={"content"},
     *     @OA\Parameter(
     *         description="Model name",
     *         in="path",
     *         name="model",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *    @OA\Parameter(
     *         description="Return content for specified locale",
     *         in="query",
     *         name="locale",
     *         required=false,
     *         @OA\Schema(type="String")
     *     ),
     *     @OA\Parameter(
     *         description="Url encoded filter json",
     *         in="query",
     *         name="filter",
     *         required=false,
     *         @OA\Schema(type="json")
     *     ),
     *     @OA\Parameter(
     *         description="Url encoded fields projection as json",
     *         in="query",
     *         name="fields",
     *         required=false,
     *         @OA\Schema(type="json")
     *     ),
     *     @OA\Parameter(
     *         description="Populate item with linked content items.",
     *         in="query",
     *         name="populate",
     *         required=false,
     *         @OA\Schema(type="int")
     *     ),
     *     @OA\OpenApi(
     *         security={
     *             {"api_key": {}}
     *         }
     *     ),
     *     @OA\Response(response="200", description="Get model item"),
     *     @OA\Response(response="404", description="Model not found"),
     *     @OA\Response(response="401", description="Unauthorized")
     * )
     */

    $restApi->addEndPoint('/content/item/{model}', [

        'GET' => function($params, $app) {

            $model = $params['model'];

            if (!$app->module('content')->model($model)) {
                $app->response->status = 404;
                return ["error" => "Model <{$model}> not found"];
            }

            if (!$app->helper('acl')->isAllowed("content/{$model}/read", $app->helper('auth')->getUser('role'))) {
                $app->response->status = 412;
                return ['error' => 'Permission denied'];
            }

            $process = [
                'locale' => $app->param('locale:string', 'default')
            ];

            $filter = $app->param('filter:string');
            $fields = $app->param('fields:string', null);
            $populate = $app->param('populate:int', null);

            if ($filter) {
                try {
                    $filter && json5_decode($filter, true);
                } catch(\Throwable $e) {
                    $app->response->status = 400;
                    return ['error' => "<filter> is not valid json"];
                }
            }

            if ($fields) {
                try {
                    $fields && json5_decode($fields, true);
                } catch(\Throwable $e) {
                    $app->response->status = 400;
                    return ['error' => "<fields> is not valid json"];
                }
            }

            if ($populate) {
                $process['populate'] = $populate;
            }

            $item = $app->module('content')->item($model, $filter ? $filter : [], $fields, $process);

            return $item ?? false;
        }
    ]);


    /**
     * @OA\Get(
     *     path="/content/items/{model}",
     *     tags={"content"},
     *     @OA\Parameter(
     *         description="Model name",
     *         in="path",
     *         name="model",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *    @OA\Parameter(
     *         description="Return content for specified locale",
     *         in="query",
     *         name="locale",
     *         required=false,
     *         @OA\Schema(type="String")
     *     ),
     *     @OA\Parameter(
     *         description="Url encoded filter json",
     *         in="query",
     *         name="filter",
     *         required=false,
     *         @OA\Schema(type="json")
     *     ),
     *     @OA\Parameter(
     *         description="Url encoded sort json",
     *         in="query",
     *         name="sort",
     *         required=false,
     *         @OA\Schema(type="json")
     *     ),
     *     @OA\Parameter(
     *         description="Url encoded fields projection as json",
     *         in="query",
     *         name="fields",
     *         required=false,
     *         @OA\Schema(type="json")
     *     ),
     *     @OA\Parameter(
     *         description="Max amount of items to return",
     *         in="query",
     *         name="limit",
     *         required=false,
     *         @OA\Schema(type="int")
     *     ),
     *     @OA\Parameter(
     *         description="Amount of items to skip",
     *         in="query",
     *         name="skip",
     *         required=false,
     *         @OA\Schema(type="int")
     *     ),
     *     @OA\Parameter(
     *         description="Populate items with linked content items.",
     *         in="query",
     *         name="populate",
     *         required=false,
     *         @OA\Schema(type="int")
     *     ),
     *     @OA\OpenApi(
     *         security={
     *             {"api_key": {}}
     *         }
     *     ),
     *     @OA\Response(response="200", description="Get list of published model items"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="404", description="Model not found")
     * )
     */

    $restApi->addEndPoint('/content/items/{model}', [

        'GET' => function($params, $app) {

            $model = $params['model'];

            if (!$app->module('content')->model($model)) {
                $app->response->status = 404;
                return ["error" => "Model <{$model}> not found"];
            }

            if (!$app->helper('acl')->isAllowed("content/{$model}/read", $app->helper('auth')->getUser('role'))) {
                $app->response->status = 412;
                return ['error' => 'Permission denied'];
            }

            $options = [];
            $process = ['locale' => $app->param('locale', 'default')];

            $limit = $app->param('limit:int', null);
            $skip = $app->param('skip:int', null);
            $populate = $app->param('populate:int', null);
            $filter = $app->param('filter:string', null);
            $sort = $app->param('sort:string', null);
            $fields = $app->param('fields:string', null);

            if (!is_null($filter)) $options['filter'] = $filter;
            if (!is_null($sort)) $options['sort'] = $sort;
            if (!is_null($fields)) $options['fields'] = $fields;
            if (!is_null($limit)) $options['limit'] = $limit;
            if (!is_null($skip)) $options['skip'] = $skip;

            foreach (['filter', 'fields', 'sort'] as $prop) {
                if (isset($options[$prop])) {
                    try {
                        $options[$prop] = json5_decode($options[$prop], true);
                    } catch(\Throwable $e) {
                        $app->response->status = 400;
                        return ['error' => "<{$prop}> is not valid json"];
                    }
                }
            }

            if ($populate) {
                $process['populate'] = $populate;
            }

            if (!isset($options['filter']) || !is_array($options['filter'])) {
                $options['filter'] = [];
            }

            $options['filter']['_state'] = 1;

            $items = $app->module('content')->items($model, $options, $process);

            if (isset($options['skip'], $options['limit'])) {
                return [
                    'data' => $items,
                    'meta' => [
                        'total' => $app->module('content')->count($model, $options['filter'] ?? [])
                    ]
                ];
            }

            return $items;
        }
    ]);
});

$this->on('graphql.config', function($gql) {
    $app = $this;
    include(__DIR__.'/graphql/content.php');
    include(__DIR__.'/graphql/models.php');
});