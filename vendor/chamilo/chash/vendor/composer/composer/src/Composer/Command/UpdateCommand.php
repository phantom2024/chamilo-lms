<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Command;

use Composer\Installer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class UpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Updates your dependencies to the latest version according to composer.json, and updates the composer.lock file.')
            ->setDefinition(array(
                new InputArgument('packages', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Packages that should be updated, if not provided all packages are.'),
                new InputOption('prefer-source', null, InputOption::VALUE_NONE, 'Forces installation from package sources when possible, including VCS information.'),
                new InputOption('prefer-dist', null, InputOption::VALUE_NONE, 'Forces installation from package dist even for dev versions.'),
                new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs the operations but will not execute anything (implicitly enables --verbose).'),
                new InputOption('dev', null, InputOption::VALUE_NONE, 'Enables installation of require-dev packages (enabled by default, only present for BC).'),
                new InputOption('no-dev', null, InputOption::VALUE_NONE, 'Disables installation of require-dev packages.'),
                new InputOption('lock', null, InputOption::VALUE_NONE, 'Only updates the lock file hash to suppress warning about the lock file being out of date.'),
                new InputOption('no-custom-installers', null, InputOption::VALUE_NONE, 'Disables all custom installers.'),
                new InputOption('no-scripts', null, InputOption::VALUE_NONE, 'Skips the execution of all scripts defined in composer.json file.'),
                new InputOption('no-progress', null, InputOption::VALUE_NONE, 'Do not output download progress.'),
                new InputOption('verbose', 'v|vv|vvv', InputOption::VALUE_NONE, 'Shows more details including new commits pulled in when updating packages.'),
                new InputOption('optimize-autoloader', 'o', InputOption::VALUE_NONE, 'Optimize autoloader during autoloader dump')
            ))
            ->setHelp(<<<EOT
The <info>update</info> command reads the composer.json file from the
current directory, processes it, and updates, removes or installs all the
dependencies.

<info>php composer.phar update</info>

To limit the update operation to a few packages, you can list the package(s)
you want to update as such:

<info>php composer.phar update vendor/package1 foo/mypackage [...]</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $composer->getDownloadManager()->setOutputProgress(!$input->getOption('no-progress'));
        $io = $this->getIO();
        $install = Installer::create($io, $composer);

        $preferSource = false;
        $preferDist = false;
        switch ($composer->getConfig()->get('preferred-install')) {
            case 'source':
                $preferSource = true;
                break;
            case 'dist':
                $preferDist = true;
                break;
            case 'auto':
            default:
                // noop
                break;
        }
        if ($input->getOption('prefer-source') || $input->getOption('prefer-dist')) {
            $preferSource = $input->getOption('prefer-source');
            $preferDist = $input->getOption('prefer-dist');
        }

        $install
            ->setDryRun($input->getOption('dry-run'))
            ->setVerbose($input->getOption('verbose'))
            ->setPreferSource($preferSource)
            ->setPreferDist($preferDist)
            ->setDevMode(!$input->getOption('no-dev'))
            ->setRunScripts(!$input->getOption('no-scripts'))
            ->setOptimizeAutoloader($input->getOption('optimize-autoloader'))
            ->setUpdate(true)
            ->setUpdateWhitelist($input->getOption('lock') ? array('lock') : $input->getArgument('packages'))
        ;

        if ($input->getOption('no-custom-installers')) {
            $install->disableCustomInstallers();
        }

        return $install->run() ? 0 : 1;
    }
}
