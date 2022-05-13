<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use Hyperf\DbConnection\Db;

class IndexController extends AbstractController
{
    public function index()
    {
        $data = Db::table('onethink_action')->get()->toArray();
        var_dump($data);
//        $user = $this->request->input('user', 'Hyperf');
//        $method = $this->request->getMethod();
//
//        return [
//            'method' => $method,
//            'message' => "Hello {$user}.",
//        ];
    }
}
