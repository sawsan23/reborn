<?php

namespace Theme;

class ThemeInfo extends \Reborn\Module\AbstractInfo
{
	protected $name = 'Theme';

	protected $displayName = array(
		'en' => 'Theme', 
		'my' => 'ဝက်ဘ်ဆိုဒ် အသွင်ပုံသဏ္ဍာန်'
	);

	protected $version = '1.0';

	protected $description = array(
		'en' => 'Allows admins and staff to switch themes, upload new themes, and manage theme options.', 
		'my' => 'ဝက်ဆိုဒ် အသွင်ပုံသဏ္ဍာန် ပြောင်းလဲခြင်း၊ အသစ်ထည့်သွင်းခြင်း အစရှိသည်များကို လုပ်ဆောင်နိုင်ပါသည်။'
	);

	protected $author = 'K';

	protected $authorUrl = 'http://khaynote.com';

	protected $authorEmail = 'khayusaki@gmail.com';

	protected $frontendSupprot = true;

	protected $backendSupport = true;

	protected $uriPrefix = 'theme';

	protected $allowToChangeUriPrefix = false;

	protected $useAsDefaultModule = true;

	protected $roles = array(
		'theme.upload' => 'Upload',
		'theme.activate' => 'Activate',
		'theme.delete' => 'Delete',
		'theme.editor' => 'Editor',
	);
}