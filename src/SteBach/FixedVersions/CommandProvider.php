<?php
namespace SteBach\FixedVersions;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;

class CommandProvider implements CommandProviderCapability
{
    public static $composer;

    public function getCommands()
    {
        return array(new Command(CommandProvider::$composer));
    }
}

class Command extends BaseCommand
{
    private $composer;
    private $localPackages = [];
    private $rawData = [];

    public function __construct($composer) {
        parent::__construct();
        $this->composer = $composer;
    }

    protected function configure()
    {
        $this->setName('fixversions');
        $this->setDescription("Set fixed versions of your dependencies");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $repositoryManager = $this->composer->getRepositoryManager();

        $file = $this->composer->getConfig()->getConfigSource()->getName();
        $output->writeln("<fg=green>Checking ".$file."</>");
        $this->rawData = json_decode(file_get_contents($file),true);
        foreach ($repositoryManager->getRepositories() as $repository) {
            $this->checkPackages(
                $repositoryManager->getLocalRepository()
            );
        }

        $checkKeys = ['require','require-dev'];
        foreach ($checkKeys as $checkkey) {
            if (isset($this->rawData[$checkkey])) {
                $output->writeln("- " . $checkkey);
                foreach ($this->rawData[$checkkey] as $name => $version) {
                    if ($name == 'stebach/fixedversions') {
                        $this->rawData[$checkkey][$name] = '^1.0';
                    }
                    if (isset($this->localPackages[$name])) {
                        if ($version != $this->localPackages[$name]) {
                            $this->rawData[$checkkey][$name] = $this->localPackages[$name];
                            $output->writeln("  - package \"<fg=green>" . $name . "</>\" was changed  to version \"<fg=green>" . $this->localPackages[$name] . "</>\"");
                        } else {
                            $output->writeln("  - package \"<fg=yellow>" . $name . "</>\" was already set to version \"<fg=yellow>" . $version . "</>\"");
                        }
                    } else {
                        $output->writeln("  - package \"<fg=magenta>" . $name . "</>\" not found - version remains \"<fg=magenta>" . $version . "</>\"");
                    }
                }
            }
        }
        $output->writeln("");
        $output->writeln("Saving backup to " . $file . ".bak." . date("YmdHis") . " ...");
        copy($file,$file.".bak." . date("YmdHis"));
        $output->writeln("Writing new composer.json ...");
        file_put_contents($file, json_encode($this->rawData,JSON_PRETTY_PRINT));
        $output->writeln("");
        $output->writeln("<fg=green>Done!</>");

    }

    private function checkPackages($local) {
        foreach ($local->getPackages() as $package) {
            $this->localPackages[$package->getName()] = $package->getVersion();
        }
    }


}