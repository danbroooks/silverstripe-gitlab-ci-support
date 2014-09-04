<?php

if (php_sapi_name() != 'cli') {
	header('HTTP/1.0 404 Not Found');
	exit;
}

class SilverStripeGitlabCiSupport {

	private $moduleTarget;
	private $supportDir;
	private $supportDirName;
	private $project = 'site';

	public function __construct($moduleTarget, $supportDir) {
		$this->moduleTarget = $moduleTarget;
		$this->supportDir = $supportDir;
		$this->supportDirName = basename($supportDir);
	}

	public function initialize(){
		$this->MoveModuleToSubfolder();
		$this->MoveComposerJSON();
		$this->CreateConfiguration();
	}

	private function MoveModuleToSubfolder(){
		$moduleTarget = $this->moduleTarget;
		mkdir($moduleTarget);
		$files = scandir('.');
		foreach($files as $file) {
			if ($this->isModuleItem($file)) {
				rename($file, $moduleTarget.'/'.$file);
			}
		}
	}

	private function isModuleItem($file) {
		$ignore = array('.', '..', $this->supportDirName, $this->moduleTarget);
		return !in_array($file, $ignore);
	}

	private function MoveComposerJSON(){
		rename($this->project.'/composer.json', './composer.json');
	}

	private function CreateConfiguration(){
		$project = $this->project;
		rename($this->supportDirName . "/" . $project, $project);
		$xml = file_get_contents($project.'/phpunit.xml');
		$xml = str_replace('{{MODULE_DIR}}', $this->moduleTarget, $xml);
		file_put_contents('phpunit.xml', $xml);
		unlink($project . '/phpunit.xml');
		$this->run_cmd('rm ' . $this->supportDirName . ' -fr');
	}

	private function run_cmd($cmd) {
		echo "+ $cmd\n";
		passthru($cmd, $returnVar);
		if($returnVar > 0) die($returnVar);
	}
}

$support = new SilverStripeGitlabCiSupport('module-under-test', __DIR__);
$support->initialize();


