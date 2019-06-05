<?php
// +----------------------------------------------------------------------
// | HisiPHP框架[基于ThinkPHP5开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.hisiphp.com
// +----------------------------------------------------------------------
// | HisiPHP承诺基础框架永久免费开源，您可用于学习和商用，但必须保留软件版权信息。
// +----------------------------------------------------------------------
// | Author: 橘子俊 <364666827@qq.com>，开发者QQ群：50304283
// +----------------------------------------------------------------------
namespace app\developer\admin;
use app\system\admin\Admin;
use app\system\model\SystemModule as ModuleModel;
use app\developer\model\DeveloperVersions as VersionsModel;
use hisi\Dir;
use hisi\PclZip;
use Env;

class Module extends Admin
{
    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();

        $this->tabData = [
            [
                'title' => '模块列表',
                'url' => 'developer/module/index',
            ],
            [
                'title' => '生成新模块',
                'url' => 'developer/module/build',
            ],
        ];
    }

    public function index()
    {
        if (ROOT_DIR != '/') {
            return $this->error('要使用开发者工具，必须将HisiPHP安装到网站根目录');
        }

        $modules = ModuleModel::where('system', 0)->where('app_id', 0)->order('sort,id')->column('id,title,author,intro,icon,default,system,app_id,identifier,config,name,version,status');

        $this->assign('hisiTabData', ['menu' => $this->tabData, 'current' => 'developer/module/index']);
        $this->assign('hisiTabType', 3);
        $this->assign('data_list', array_values($modules));
        return $this->fetch();
    }

    /**
     * 生成模块
     * @author 橘子俊 <364666827@qq.com>
     * @return mixed
     */
    public function build()
    {
        if ($this->request->isPost()) {
            $model = new ModuleModel();
            if (!$model->design($this->request->post())) {
                return $this->error($model->getError());
            }

            // 执行模块自动安装
            $name   = $this->request->post('name');
            $module = $model->where('name', $name)->find();
            $result = action('system/module/execInstall', ['id' => $module->id, 'clear' => 1], 'admin');
            if ($result !== true) {
                return $this->success('模块已生成并安装成功', url('index'));
            } else {
                return $this->success('模块已自动生成完毕', url('system/module/index', ['status' => 0]));
            }
        }

        $this->assign('hisiTabData', ['menu' => $this->tabData, 'current' => 'developer/module/build']);
        $this->assign('hisiTabType', 3);
        return $this->fetch();
    }

    /**
     * 模块设计
     * @author 橘子俊 <364666827@qq.com>
     * @return mixed
     */
    public function design()
    {
        $_id = get_num();

        $module = ModuleModel::where('id', $_id)->find();
        if (!$module) {
            return $this->error('模块不存在');
        }

        if ($module['app_id'] > 0) {
            return $this->error('禁止修改此模块');
        }

        if (!is_file(Env::get('app_path').$module['name'].'/info.php')) {
            return $this->error('模块配置文件丢失');
        }
        
        if ($this->request->isPost()) {
            $data = $post = $this->request->post();

            unset($data['id']);
            $data['config'] = $data['module_depend'] = $data['plugin_depend'] = $data['hooks'] = $data['tables'] = '[]';
            // 模块配置重组
            $config = [];
            if (isset($post['config'])) {
                foreach ($post['config']['sort'] as $k => $v) {
                    $arr = [];
                    $arr['sort'] = $v;
                    $arr['title'] = $post['config']['title'][$k];
                    $arr['name'] = $post['config']['name'][$k];
                    $arr['type'] = $post['config']['type'][$k];
                    $arr['options'] = parse_attr($post['config']['options'][$k]);
                    $arr['value'] = $post['config']['value'][$k];
                    $arr['tips'] = $post['config']['tips'][$k];
                    $config[$v] = $arr;
                }
                ksort($config);
                $configStr = '';
                foreach ($config as $k => $v) {
                    $v['options'] = array_filter($v['options']);
                    if ($v['options']) {
                        $options = "[\n";
                        foreach ($v['options'] as $kk => $vv) {
                            $options .= "                '{$kk}'=> '{$vv}',\n";
                        }
                        $options .= "            ]";
                    } else {
                        $options = "''";
                    }
                    $configStr .= "        [\n            'sort' => {$k}, \n            'title' => '{$v['title']}', \n            'name' => '{$v['name']}', \n            'type' => '{$v['type']}', \n            'options' => {$options}, \n            'value' => '{$v['value']}', \n            'tips' => '{$v['tips']}',\n        ], \n";
                }
                $data['config'] = "[\n    {$configStr}    ]";
            }

            // 模块依赖重组
            if (isset($post['module_depend'])) {
                $moduleDepend = '';
                foreach ($post['module_depend']['name'] as $k => $v) {
                    $moduleDepend .= "        ['{$v}', '{$post['module_depend']['identifier'][$k]}', '{$post['module_depend']['version'][$k]}', '".htmlspecialchars_decode($post['module_depend']['type'][$k])."'],\n";
                }
                $data['module_depend'] = "[\n{$moduleDepend}    ]";
            }

            // 插件依赖重组
            if (isset($post['plugin_depend'])) {
                $pluginDepend = '';
                foreach ($post['plugin_depend']['name'] as $k => $v) {
                    $pluginDepend .= "        ['{$v}', '{$post['plugin_depend']['identifier'][$k]}', '{$post['plugin_depend']['version'][$k]}', '".htmlspecialchars_decode($post['plugin_depend']['type'][$k])."'],\n";
                }
                $data['plugin_depend'] = "[\n{$pluginDepend}    ]";
            }

            // 预埋钩子重组
            if (isset($post['hooks'])) {
                $hooks = '';
                foreach ($post['hooks']['key'] as $k => $v) {
                    $hooks .= "        '{$v}' => '{$post['hooks']['desc'][$k]}',\n";
                }
                $data['hooks'] = "[\n{$hooks}    ]";
            }

            // 数据表重组
            if (isset($post['tables'])) {
                $tables = '';
                foreach ($post['tables'] as $k => $v) {
                    $tables .= "        '{$v}',\n";
                }
                $data['tables'] = "[\n{$tables}    ]";
            }

            if (!$this->mkInfo($data)) {
                return $this->error('配置文件更新失败');
            }
            
            $sqlmap = [];
            $sqlmap['title'] = $data['title'];
            $sqlmap['identifier'] = $data['identifier'];
            $sqlmap['icon'] = $data['icon'];
            $sqlmap['intro'] = $data['intro'];
            $sqlmap['author'] = $data['author'];
            $sqlmap['url'] = $data['url'];
            $sqlmap['version'] = $data['version'];
            $sqlmap['config'] = '';
            
            // 将配置更新到数据库
            if ($config) {
                $old_config_arr = [];
                if ($module['config']) {
                    // 重组旧配置，以方便后面找值
                    $old_config = json_decode($module['config'], 1);
                    foreach ($old_config as $k => $v) {
                        $old_config_arr[$v['name']] = $v;
                    }
                }
                
                // 将旧配置的值赋值到新配置
                foreach ($config as $k => &$v) {
                    if (isset($old_config_arr[$v['name']])) {
                        $v['value'] = $old_config_arr[$v['name']]['value'];
                    }
                }
                $sqlmap['config'] = json_encode($config, 1);
            }

            $res = ModuleModel::where('id', $_id)->update($sqlmap);
            if ($res === false) {
                return $this->error('保存失败');
            }
            return $this->success('保存成功');
        }

        $moduleInfo = include_once Env::get('app_path').$module['name'].'/info.php';
        $module['db_prefix'] = $moduleInfo['db_prefix'];

        $tab_data['menu'] = [
            [
                'title' => '基本信息',
            ], [
                'title' => '模块配置',
            ], [
                'title' => '依赖模块',
            ], [
                'title' => '依赖插件',
            ], [
                'title' => '数据库',
            ], [
                'title' => '预埋钩子',
            ],
        ];

        $this->assign('hisiTabData', $tab_data);
        $this->assign('hisiTabType', 2);
        $this->assign('module_info', $moduleInfo);
        $this->assign('formData', $module);
        return $this->fetch();
    }

    /**
     * 模块图标上传
     * @author 橘子俊 <364666827@qq.com>
     * @return mixed
     */
    public function icon()
    {
        $id = get_num();

        $module = ModuleModel::where('id', $id)->find();
        if (!$module) {
            return $this->error('参数传递错误');
        }

        $file = request()->file('file');
        if (!$file->checkExt('png')) {
            return $this->error('只允许上传PNG图标');
        }
        
        if (!$file->checkSize(102400)) {
            return $this->error('图标大小超过系统限制(100KB)');
        }

        $imagePath = Env::get('root_path') . 'public/upload/temp/';
        $file->rule('')->move($imagePath, $module['name'] . '.png');
        $image = getimagesize($imagePath.$module['name'] . '.png');
        if ($image[0] !== 200 || $image[1] !== 200 ) {
            unlink($imagePath.$module['name'] . '.png');
            return $this->error('图标尺寸不符合要求(200px * 200px)');
        }

        // 移动图标
        copy($imagePath . $module['name'] . '.png', Env::get('root_path').'public/static/'.$module['name'].'/'.$module['name'].'.png');
        return $this->success('/static/'.$module['name'].'/'.$module['name'].'.png?v='.time());
    }

    /**
     * 版本记录
     * @author 橘子俊 <364666827@qq.com>
     */
    public function versions()
    {
    	$appName = $this->request->param('app_name');

    	$data = [];
    	$data['list'] = VersionsModel::where('app_name', $appName)->where('type', 1)->select();

    	$this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 添加版本
     * @author 橘子俊 <364666827@qq.com>
     */
    public function addVersion()
    {
        if ($this->request->isPost()) {
            $mod = new VersionsModel();
            if ($mod->save($this->request->post()) === false) {
                return $this->error($mod->getError());
            }
            return $this->success('保存成功');
        }
        return $this->fetch('form');
    }

    /**
     * 修改版本
     * @author 橘子俊 <364666827@qq.com>
     */
    public function editVersion()
    {
        $id = $this->request->param('id');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            unset($data['id']);
            $mod = new VersionsModel();
            if ($mod->save($data, ['id' => $id]) === false) {
                return $this->error($mod->getError());
            }
            return $this->success('保存成功');
        }
        $row = VersionsModel::get($id);
        $this->assign('formData', $row);
        return $this->fetch('form');
    }

    /**
     * 删除版本
     * @author 橘子俊 <364666827@qq.com>
     */
    public function delVersion()
    {
        $id = $this->request->param('id');
        $row = VersionsModel::get($id);
        if (!$row) {
            return $this->error('记录不存在');
        }

        // 删除文件
        if (file_exists('.'.$row['install_package'])) {
            unlink('.'.$row['install_package']);
        }
        if (file_exists('.'.$row['upgrade_package'])) {
            unlink('.'.$row['upgrade_package']);
        }
        return $this->success('删除成功');
    }

    /**
     * 补丁打包
     * @author 橘子俊 <364666827@qq.com>
     */
    public function package()
    {
        $id     = get_num();
        $mod    = new VersionsModel();
        $row    = $mod->get($id);
        $pathPrefix = '.'.ROOT_DIR;

        if ($row['update_file']) {
           $row['update_file'] = parse_attr($row['update_file']); 
        }

        if ($row['delete_file']) {
           $row['delete_file'] = parse_attr($row['delete_file']); 
        }

        if ($row['update_log']) {
           $row['update_log'] = parse_attr($row['update_log']); 
        }

        // 临时打包目录
        $tempPath = Env::get('root_path').'/public/upload/temp/'.$row['app_name'].'_'.$row['app_version'];
        if (!is_dir($tempPath)) {
            Dir::create($tempPath, 0777, true);
        }

        // 将模块配置文件加入升级文件列表
        if ($row['update_file']) {
            if (!in_array('/application/'.$row['app_name'].'/info.php', $row['update_file'])) {
                return $this->error('更新文件缺失[/application/'.$row['app_name'].'/info.php]');
            }
        }

        $info = include_once Env::get('app_path').$row['app_name'].'/info.php';
        if (!isset($info['version']) || $info['version'] !== $row['app_version']) {
            return $this->error('模块配置文件版本号不一致[/application/'.$row['app_name'].'/info.php]');
        }

        // 将升级文件复制到打包临时目录
        if (is_array($row['update_file'])) {
            foreach ($row['update_file'] as $v) {
                if (is_file(Env::get('root_path').ltrim($v, '/'))) {
                    $targetPath = $tempPath.'/upload'.str_replace(basename($v), '', $v);
                    if (!is_dir($targetPath)) {
                        Dir::create($targetPath, 0777, true);
                    }
                    if (!copy(Env::get('root_path').ltrim($v, '/'), $targetPath.basename($v))) {
                        Dir::delDir($tempPath);
                        return $this->error('文件复制失败！['.$v.']');
                    }
                }
            }
        }
        
        // 生成升级文件
        $data['update'] = $row['update_file'];
        $data['delete'] = $row['delete_file'];
        $upgrade = array_filter($data);
        $upgrade = var_export($upgrade, true);
        $upgrade = str_replace(['array (', ')'], ['[', ']'], $upgrade);
        $upgrade = preg_replace("/(\s*?\r?\n\s*?)+/", "\n", $upgrade);
        $str = "<?php\nreturn ".$upgrade.";\n";
        file_put_contents($tempPath.'/upgrade.php', $str);
        if (!is_file($tempPath.'/upgrade.php')) {
            Dir::delDir($tempPath);
            return $this->error('生成升级文件upgrade.php失败！');
        }

        // 生成SQL
        if (!empty($row['update_sql'])) {
            $sql_str = "/**\n * 手动导入SQL，请将表前缀\"hisiphp_\"替换成您的数据库表前缀\n */\n";
            file_put_contents($tempPath.'/database.sql', $sql_str.$row['update_sql']);
            if (!is_file($tempPath.'/database.sql')) {
                Dir::delDir($tempPath);
                return $this->error('生成database.sql失败！');
            }
        }

        // 生成升级必读
        $logStr = "************************************\n    升级前，请务必备份好站点和数据库\n************************************\n\n1.请将upload目录下面的文件覆盖到您的站点根目录\n2.将database.sql导入到数据库【手动导入SQL，请将表前缀\"hisiphp_\"替换成您的数据库表前缀】\n3.如果升级后出现系统无法访问，请手动删除runtime目录下面的cache和temp文件夹。\n\n当前版本升级内容如下：\n-".implode("\n-", $row['update_log'])."
                    ";
        file_put_contents($tempPath.'/README.txt', $logStr);

        // 执行打包
        $packageSavePath = $pathPrefix.'upload/package/module/'.$row['app_name'].'/'.$row['app_version'];
        if (!is_dir($packageSavePath)) {
            Dir::create($packageSavePath, 0777, true);
        }
        $upgradeZip = $packageSavePath.'/'.$row['app_name'].'_upgrade_'.$row['app_version'].'.zip';
        if (is_file($upgradeZip)) {
            unlink($upgradeZip);
        }
        $archive = new PclZip();
        $archive->PclZip($upgradeZip);
        if ($archive->create($tempPath, PCLZIP_OPT_REMOVE_PATH, $tempPath.'/') === 0) {
            Dir::delDir($tempPath);
            return $this->error('升级补丁打包失败！');
        }
        if (!file_exists($upgradeZip)) {
            return $this->error('升级补丁打包保存失败！');
        }
        // 删除缓存目录
        Dir::delDir($tempPath);
        
        // 生成完整的应用安装包
        // 临时打包目录
        $tempPath = $pathPrefix.'upload/temp/'.$row['app_name'].'_'.$row['app_version'];
        // 复制app
        Dir::copyDir(Env::get('root_path').'application/'.$row['app_name'], $tempPath.'/upload/application/'.$row['app_name']);
        // 复制static
        if (is_dir($pathPrefix.'static/'.$row['app_name'])) {
            Dir::copyDir($pathPrefix.'static/'.$row['app_name'], $tempPath.'/upload/public/static/'.$row['app_name']);
        }
        // 复制theme
        if (is_dir($pathPrefix.'theme/'.$row['app_name'])) {
            Dir::copyDir($pathPrefix.'theme/'.$row['app_name'], $tempPath.'/upload/public/theme/'.$row['app_name']);
        }
        // 执行打包
        $packageSavePath = $pathPrefix.'upload/package/module/'.$row['app_name'].'/'.$row['app_version'];
        if (!is_dir($packageSavePath)) {
            Dir::create($packageSavePath, 0777, true);
        }
        $installZip = $packageSavePath.'/'.$row['app_name'].'_install_'.$row['app_version'].'.zip';
        if (is_file($installZip)) {
            unlink($installZip);
        }
        $archive = new PclZip();
        $archive->PclZip($installZip);
        if ($archive->create($tempPath, PCLZIP_OPT_REMOVE_PATH, $tempPath.'/') === 0) {
            Dir::delDir($tempPath);
            return $this->error('完整安装包打包失败！');
        }
        if (!is_file($installZip)) {
            return $this->error('完整安装包保存失败！');
        }
        // 删除缓存目录
        Dir::delDir($tempPath);
        VersionsModel::update(['install_package' => substr($installZip, 1), 'upgrade_package' => substr($upgradeZip, 1)],['id' => $id]);
        clearstatcache();
        return $this->success('打包完成');
    }

    /**
     * 生成模块信息文件
     * @author 橘子俊 <364666827@qq.com>
     */
    private function mkInfo($data = [])
    {
        // 配置内容
        $config = <<<INFO
<?php
/**
 * 模块基本信息
 */
return [
    // 模块名[必填]
    'name'        => '{$data['name']}',
    // 模块标题[必填]
    'title'       => '{$data['title']}',
    // 模块唯一标识[必填]，格式：模块名.[应用市场ID].module.[应用市场分支ID]
    'identifier'  => '{$data['identifier']}',
    // 主题模板[必填]，默认default
    'theme'        => 'default',
    // 模块图标[选填]
    'icon'        => '{$data['icon']}',
    // 模块简介[选填]
    'intro' => '{$data['intro']}',
    // 开发者[必填]
    'author'      => '{$data['author']}',
    // 开发者网址[选填]
    'author_url'  => '{$data['url']}',
    // 版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
    // 主版本号【位数变化：1-99】：当模块出现大更新或者很大的改动，比如整体架构发生变化。此版本号会变化。
    // 次版本号【位数变化：0-999】：当模块功能有新增或删除，此版本号会变化，如果仅仅是补充原有功能时，此版本号不变化。
    // 修订版本号【位数变化：0-999】：一般是 Bug 修复或是一些小的变动，功能上没有大的变化，修复一个严重的bug即发布一个修订版。
    'version'     => '{$data['version']}',
    // 模块依赖[可选]，格式[[模块名, 模块唯一标识, 依赖版本, 对比方式]]
    'module_depend' => {$data['module_depend']},
    // 插件依赖[可选]，格式[[插件名, 插件唯一标识, 依赖版本, 对比方式]]
    'plugin_depend' => {$data['plugin_depend']},
    // 模块数据表[有数据库表时必填,不包含表前缀]
    'tables' => {$data['tables']},
    // 原始数据库表前缀,模块带sql文件时必须配置
    'db_prefix' => '{$data['db_prefix']}',
    // 模块预埋钩子[非系统钩子，必须填写]
    'hooks' => {$data['hooks']},
    // 模块配置，格式['sort' => '100','title' => '配置标题','name' => '配置名称','type' => '配置类型','options' => '配置选项','value' => '配置默认值', 'tips' => '配置提示'],各参数设置可参考管理后台->系统->系统功能->配置管理->添加
    'config' => {$data['config']},
];
INFO;
        return file_put_contents(Env::get('app_path'). $data['name'] . '/info.php', $config);
    }
}