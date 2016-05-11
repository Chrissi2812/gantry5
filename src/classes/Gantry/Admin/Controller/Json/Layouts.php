<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Controller\JsonController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Layouts
 * @package Gantry\Admin\Controller\Json
 * @deprecated
 */
class Layouts extends JsonController
{
    protected $httpVerbs = [
        'GET' => [
            '/' => 'index'
        ],
        'POST' => [
            '/' => 'index'
        ]
    ];
    
    public function index()
    {
        $post = $this->request->request;

        $outline = $post['outline'];
        $type = $post['type'];
        $subtype = $post['subtype'];
        $inherit = $post['inherit'] === 'true' || $post['inherit'] == 1;
        $id = $post['section'] ?: $post['particle'];

        $this->container['configuration'] = $outline;

        $layout = Layout::instance($outline);
        $item = $layout->find($id);
        $type = isset($item->type) ? $item->type : $type;
        $title = isset($item->title) ? $item->title : '';
        $item->attributes = isset($item->attributes) ? (array) $item->attributes : [];
        $block = $layout->block($id);
        $block = isset($block->attributes) ? (array) $block->attributes : [];
        //$block->size = (float) ($post['block']['size'] ?: 100);
        //$block->fixed = (int) ($post['block']['fixed'] === 'true');

        $params = [
            'gantry'        => $this->container,
            'parent'        => 'settings',
            'route'         => "configurations.{$outline}.settings",
            'inherit'       => $inherit ? $outline : null,
        ];

        $prefix = "particles.{$subtype}";
        if (in_array($type, ['wrapper', 'section', 'container', 'grid', 'offcanvas'])) {
            $particle = false;
            $file = CompiledYamlFile::instance("gantry-admin://blueprints/layout/{$type}.yaml");
            $defaults = [];
            $blueprints = new BlueprintsForm($file->content());
            $file->free();
        } else {
            $particle = true;
            $defaults = $this->container['config']->get($prefix);
            $item->attributes = $item->attributes + $defaults;
            $blueprints = new BlueprintsForm($this->container['particles']->get($subtype));
        }

        $paramsParticle = [
            'title'         => $title,
            'blueprints'    => $blueprints->get('form'),
            'item'          => $item,
            'data'          => ['particles' => [$subtype => $item->attributes]],
            'defaults'      => ['particles' => [$subtype => $defaults]],
            'prefix'        => $prefix . '.',
            'editable'      => $particle,
            'overrideable'  => $particle,
            'skip'          => ['enabled']
        ] + $params;

        $html['g-settings-particle'] = $this->container['admin.theme']->render('@gantry-admin/pages/configurations/layouts/particle-card.html.twig',  $paramsParticle);

        $file = CompiledYamlFile::instance("gantry-admin://blueprints/layout/block.yaml");
        $blockBlueprints = new BlueprintsForm($file->content());
        $file->free();

        $paramsBlock = [
                'title' => $this->container['translator']->translate('GANTRY5_PLATFORM_BLOCK'),
                'blueprints' => $blockBlueprints->get('form'),
                'data' => ['block' => $block],
                'prefix' => 'block.',
                //'size_limits' => $post['size_limits'] ?: [100, 100]
            ] + $params;

        $html['g-settings-block-attributes'] = $this->container['admin.theme']->render('@gantry-admin/pages/configurations/layouts/particle-card.html.twig',  $paramsBlock);

        return new JsonResponse(['json' => $item, 'html' => $html]);
    }
}
