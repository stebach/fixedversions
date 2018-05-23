<?php
namespace SteBach\FixedVersions;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;

class Plugin implements PluginInterface, Capable {
	public function activate(Composer $composer, IOInterface $io) {
		CommandProvider::$composer = $composer;
	}
  public function getCapabilities()
    {
        return array(
            'Composer\Plugin\Capability\CommandProvider' => 'SteBach\FixedVersions\CommandProvider',
        );
    }
}