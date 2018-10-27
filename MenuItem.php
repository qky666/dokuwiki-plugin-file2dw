<?php

namespace dokuwiki\plugin\file2dw;
use dokuwiki\Menu\Item\AbstractItem;

/**
 * Class MenuItem
 *
 * Implements the import button for DokuWiki's menu system
 *
 * @package dokuwiki\plugin\file2dw
 */
class MenuItem extends AbstractItem {
    /** @var string do action for this plugin */
    public $type = 'file2dw';
    /** @var string icon file */
    public $svg = __DIR__ . '/writer.svg';
    /**
     * MenuItem constructor.
     */
    public function __construct() {
        parent::__construct();
        global $REV;
        if($REV) $this->params['rev'] = $REV;
    }
    /**
     * Get label from plugin language file
     *
     * @return string
     */
    public function getLabel() {
        $hlp = plugin_load('action', 'file2dw');
        return $hlp->getLang('import_button');
    }
}