<?php

if ( ! defined('SHORTEEN_ADDON_NAME'))
{
	define('SHORTEEN_ADDON_NAME',         'Shorteen');
	define('SHORTEEN_ADDON_VERSION',      '0.5.1');
}

$config['name'] = SHORTEEN_ADDON_NAME;
$config['version'] = SHORTEEN_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/41';