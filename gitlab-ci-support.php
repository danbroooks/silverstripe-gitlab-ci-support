<?php

if (php_sapi_name() != 'cli') {
	header('HTTP/1.0 404 Not Found');
	exit;
}

function load_json($file) {
	return json_decode(file_get_contents($file), true);
}

function save_json($file, $obj) {
	file_put_contents($file, json_encode($obj));
}

class ComposerJSON {

	private $filename;
	private $config;

	public function __construct($file) {
		$this->filename = $file;
		$this->config = load_json($file);
	}

	public function save($path = NULL) {
		save_json($path?:$this->filename, $this->config);
	}

	public function getValue($key) {
		return array_key_exists($key, $this->config) ? $this->config[$key] : NULL;
	}

	public function mergeInto($key, $value) {
		$mergedValue = $this->getValue($key) ?: array();
		if ($value) $mergedValue += $value;
		$this->config[$key] = $mergedValue;
	}

}

class SilverStripeGitlabCiSupport {

	private $moduleFolder;
	private $supportFolder;
	private $ignoreFiles;
	private $project = 'site';
	private $dryrun = false;

	public function __construct($moduleFolder, $supportDir) {
		$this->moduleFolder = $moduleFolder;
		$this->supportFolder = basename($supportDir);

		$parent = basename(dirname($supportDir));
		if ( basename(getcwd()) != $parent ) {
			throw new Exception("Must run script from parent directory \"$parent\".");
		}

		$this->ignoreFiles = array('.', '..', '.git', $this->moduleFolder, $this->supportFolder, $this->project);
	}

	public function initialize(){
		$this->moveModuleIntoSubfolder();
		$this->moveProjectIntoRoot();
		$this->moveToRoot('composer.json');
		$this->moveToRoot('phpunit.xml');
		$this->replaceInFile('{{MODULE_DIR}}', $this->moduleFolder, './phpunit.xml');
		$this->run_cmd('rm ' . $this->supportFolder . ' -fr');
		$this->addDepenanciesToComposer();
	}

	private function addDepenanciesToComposer() {
		$testProjectJson = new ComposerJSON('./composer.json');
		$moduleJson = new ComposerJSON('./module-under-test/composer.json');
		$testProjectJson->mergeInto('repositories', $moduleJson->getValue('repositories'));
		$testProjectJson->mergeInto('require', $moduleJson->getValue('require'));
		$testProjectJson->save();
	}

	private function moveProjectIntoRoot() {
		$this->move('./'.$this->supportFolder.'/'.$this->project, './'.$this->project);
	}

	private function moveModuleIntoSubfolder(){
		$moduleFolder = $this->moduleFolder;
		$this->mkdir($moduleFolder);
		foreach(scandir('.') as $file) {
			if (!$this->ignore($file)) {
				$this->move($file, $moduleFolder . '/' . $file);
			}
		}
	}

	private function ignore($file) {
		return in_array($file, $this->ignoreFiles);
	}

	private function moveToRoot($file) {
		$this->move('./'.$this->project.'/'.$file, './'.$file);
	}

	private function replaceInFile($search, $replace, $file) {
		if (!$this->dryrun) {
			$contents = str_replace($search, $replace, file_get_contents($file));
			file_put_contents($file, $contents);
		} else {
			$this->writeln("replace $search -> $replace in $file");
		}
	}

	private function move($from, $to) {
		if (!$this->dryrun) {
			rename($from, $to);
		} else {
			$this->writeln( "mv $from -> $to" );
		}
	}

	private function mkdir($dir) {
		if (!$this->dryrun) {
			mkdir($dir);
		} else {
			$this->writeln( "mkdir $dir" );
		}
	}

	private function run_cmd($cmd) {
		if (!$this->dryrun) {
			passthru($cmd, $returnVar);
			if($returnVar > 0) die($returnVar);
		}

		$this->writeln( "+ $cmd" );
	}

	private function writeln($str = '') {
		echo $str . "\n";
	}
}

$support = new SilverStripeGitlabCiSupport('module-under-test', __DIR__);
$support->initialize();


