<?php

namespace Widgets;

class WidgetsInfo extends \Reborn\Module\AbstractInfo
{
	protected $name = 'Widgets';

	protected $displayName = array(
			'en'	=> 'Widgets',
			'my'	=> 'Widgets'
	);

	protected $version = '1.0';

	protected $description = array(
			'en'	=> 'Widgets Manager',
			'my'	=> 'Widget များစီမံခန့်ခွဲရန်'
	);

	protected $author = 'Li Jia Li';

	protected $authorUrl = 'http://dragonvirus.com';

	protected $authorEmail = 'limonster.li@gmail.com';

	protected $frontendSupport = false;

	protected $backendSupport = true;

	protected $useAsDefaultModule = false;

	protected $uriPrefix = 'widget';

	protected $allowToChangeUriPrefix = true;

}
