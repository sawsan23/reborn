<?php

namespace Reborn\Route;

use Reborn\Filesystem\File;
use Reborn\Filesystem\Directory as Dir;
use Reborn\Module\ModuleManager as Module;

/**
 * Controller Map Class
 *
 * @package Reborn\Route
 * @author Myanmar Links Web Development Team
 **/
class ControllerMap
{

	/**
	 * Controller Folder variable
	 *
	 * @var string
	 **/
	protected $ctrlFolder = 'Controller';

	/**
	 * Modules path list
	 *
	 * @var array
	 **/
	protected $mod_paths = array();

	/**
	 * Controller Map Cache File Path
	 *
	 * @var string
	 **/
	protected $cache_path;

	/**
	 * Controller Map Cache File Name
	 *
	 * @var string
	 **/
	protected $cache_file = 'controllers.json';

	/**
	 * Controller Map Data from Cache File
	 *
	 * @var array
	 **/
	protected $map = array();

	/**
	 * Construct method for ControllerMap
	 *
	 * @return void
	 **/
	public function __construct()
	{
		$modules = Module::getAll();

		$this->cache_path = STORAGES.'maps'.DS;

		$this->setModulePath($modules);

		if(File::is($this->cache_path.$this->cache_file)) {
			$data = File::getContent($this->cache_path.$this->cache_file);
			$this->map = (array) json_decode($data);
		}
	}

	/**
	 * Find the Controller File from Map.
	 *
	 * @param string $key Module Key Name
	 * @param string $filename Controller File Name
	 * @return string|null
	 **/
	public function find($key, $filename)
	{
		if (isset($this->map[$key])) {
			foreach ($this->map[$key] as $file) {
				if (true == strpos($file, $filename)) {
					return $file;
				}
			}
		}

		return null;
	}

	/**
	 * Make the Controller Map.
	 *
	 * @return void
	 **/
	public function make()
	{
		if (File::is($this->cache_path.$this->cache_file)) {
			return true;
		}

		$lists = array();

		foreach ($this->mod_paths as $uri => $path) {
			$path = $path.$this->ctrlFolder.DS.'*';
			$lists[$uri] = $this->search($path);
		}

		if (! Dir::is($this->cache_path) ) {
			Dir::make($this->cache_path);
		}

		File::write(rtrim($this->cache_path, DS), $this->cache_file, json_encode($lists));
	}

	/**
	 * Search the Controller files from Path.
	 *
	 * @param string $path Controller Folder Path
	 * @param array $ctrls Controller File lists
	 * @return array
	 **/
	public function search($path, $ctrls = array())
	{
		$ctrls = $ctrls;
		$files = Dir::get($path);

		if ( !empty($files) ) {
			foreach ($files as $file) {
				if (Dir::is($file)) {
					$subs = (array) $this->search($file.DS.'*', $ctrls);
					foreach ($subs as $f) {
						$ctrls[] = $f;
					}
				} else {
					$ctrls[] = $file;
				}
			}
		}

		return $ctrls;
	}

	/**
	 * Destroy the Controller Map Cache File.
	 *
	 * @return void
	 **/
	public function destroy()
	{
		if (File::is($this->cache_path.$this->cache_file)) {
			File::delete($this->cache_path.$this->cache_file);
		}
	}

	/**
	 * Refresh the Controller Map.
	 * Use to rebuild the Controller Map.
	 *
	 * @return void
	 **/
	public function refresh()
	{
		$this->destroy();

		$this->make();
	}

	/**
	 * Set the Module Path.
	 *
	 * @param array $modules Module lists array
	 * @return void
	 **/
	protected function setModulePath($modules)
	{
		foreach ($modules as $name => $mod) {
			$this->mod_paths[$mod['uri']] = $mod['path'];
		}
	}

} // END class ControllerMap
