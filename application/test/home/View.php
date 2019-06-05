<?php
namespace app\test\home;
use app\common\controller\Common;

class View extends Common
{
    public function index()
    {
        return $this->fetch('view');
    }


}