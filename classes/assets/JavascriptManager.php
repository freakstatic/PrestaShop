<?php

/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class JavascriptManagerCore extends AbstractAssetManager
{
    protected $list;

    protected $valid_position = ['head', 'bottom'];

    public function register($id, $relativePath, $position = self::DEFAULT_JS_POSITION, $priority = self::DEFAULT_PRIORITY, $inline = false)
    {
        if ($fullPath = $this->getFullPath($relativePath)) {
            $this->add($id, $fullPath, $position, $priority, $inline);
        }
    }

    protected function getDefaultList()
    {
        $default = [];
        foreach ($this->valid_position as $position) {
            $default[$position] = [
                'external' => [],
                'inline' => [],
            ];
        }

        return $default;
    }

    protected function add($id, $fullPath, $position, $priority, $inline)
    {
        if (filesize($fullPath) === 0) {
            return;
        }

        $priority = is_int($priority) ? $priority : self::DEFAULT_PRIORITY;
        $position = $this->getSanitizedPosition($position);
        $type = ($inline) ? 'inline' : 'external';

        $this->list[$position][$type][$id] = array(
            'id' => $id,
            'type' => $type,
            'path' => $fullPath,
            'uri' => $this->getFQDN().$this->getUriFromPath($fullPath),
            'priority' => $priority,
        );
    }

    public function getList()
    {
        $this->sortList();
        $this->addInlinedJavascriptContent();

        return $this->list;
    }

    private function addInlinedJavascriptContent()
    {
        foreach ($this->valid_position as $position) {
            foreach ($this->list[$position]['inline'] as &$item) {
                $item['content'] =
                    '/* ---- '.$item['id'].' @ '.$item['path'].' ---- */'."\r\n".
                    file_get_contents($item['path']);
            }
        }
    }

    private function getSanitizedPosition($position)
    {
        return in_array($position, $this->valid_position, true) ? $position : self::DEFAULT_JS_POSITION;
    }

    private function sortList()
    {
        foreach ($this->valid_position as $position) {
            foreach ($this->list[$position] as $type => $items) {
                uasort($items, function ($a, $b) {
                    if ($a['priority'] === $b['priority']) {
                        return 0;
                    }
                    return ($a['priority'] < $b['priority']) ? -1 : 1;
                });
                $this->list[$position][$type] = $items;
            }
        }
    }
}
