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
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

/**
 * @Controller
 */
class IndexController extends AbstractController
{
    /**
     * @GetMapping(path="/")
     */
    public function index()
    {
        $data = Db::table('onethink_action')->get()->toArray();
    }

    /**
     * @GetMapping(path="/test")
     */
    public function test()
    {
        $data = Db::connection('test')->table('onethink_action')->get()->toArray();
    }
}
