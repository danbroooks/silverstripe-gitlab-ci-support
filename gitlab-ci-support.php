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

	private function getRequires($file) {
		$composer = load_json($file);
		return array_key_exists('require', $composer) ? $composer['require'] : array();
	}

	private function addDepenanciesToComposer() {
		$composer = load_json('./composer.json');
		$composer['require'] = $this->getRequires('./composer.json');
		$composer['require'] += $this->getRequires('./module-under-test/composer.json');
		save_json('./composer.json', $composer);
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


