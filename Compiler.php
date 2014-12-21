<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * The Compiler class compiles cli application into a phar
 */
class Compiler
{
    /**
     * Help command.
     */
    const COMMAND_HELP = 'help';

    /**
     * Compile command.
     */
    const COMMAND_COMPILE = 'compile';

    /**
     * Project directory.
     *
     * @var null
     */
    protected $rootDir = null;

    /**
     * Version
     *
     * @var string.
     */
    protected $version;

    /**
     * Date.
     *
     * @var string
     */
    protected $versionDate;

    /**
     * An InputInterface instance.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input = null;

    /**
     * An OutputInterface instance.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output = null;

    /**
     * Configures the input and output instances based on the user arguments and options.
     */
    protected function configureIO()
    {
        $definition = new InputDefinition(array(
            new InputOption('--help',        '-h', InputOption::VALUE_NONE, 'Display this help message.'),
            new InputOption('--quiet',       '-q', InputOption::VALUE_NONE, 'Do not output any message.'),
            new InputOption('--verbose',     '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug.'),
            new InputOption('--config',      'c', InputOption::VALUE_OPTIONAL, 'Config Directory'),
            new InputOption('--bin',         'b', InputOption::VALUE_OPTIONAL, 'Binary file'),
            new InputOption('--no-compress', null, InputOption::VALUE_OPTIONAL, 'Do not compress files'),
            new InputOption('--no-optimize', null, InputOption::VALUE_OPTIONAL, 'Do not optimize class loader'),
            new InputOption('--no-phar',     null, InputOption::VALUE_OPTIONAL, 'Do not add .phar extension'),
            new InputOption('--executable',  null, InputOption::VALUE_OPTIONAL, 'Create executable file'),
        ));

        $this->input->bind($definition);

        if (true === $this->input->hasParameterOption(array('--quiet', '-q'))) {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        } else {
            if ($this->input->hasParameterOption('-vvv') || $this->input->hasParameterOption('--verbose=3') || $this->input->getParameterOption('--verbose') === 3) {
                $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            } elseif ($this->input->hasParameterOption('-vv') || $this->input->hasParameterOption('--verbose=2') || $this->input->getParameterOption('--verbose') === 2) {
                $this->output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
            } elseif ($this->input->hasParameterOption('-v') || $this->input->hasParameterOption('--verbose=1') || $this->input->hasParameterOption('--verbose') || $this->input->getParameterOption('--verbose')) {
                $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
            }
        }
    }

    /**
     * Try to detect root directory.
     *
     * @return string
     *   Directory.
     */
    public function getRootDir()
    {
        static $rootDir = null;

        if (null == $rootDir) {

            $process = new Process('git rev-parse --show-toplevel', __DIR__ . '../../../../');

            if ($process->run() != 0) {
                throw new \RuntimeException('Can\'t run git rev-parse. You must ensure to run compile from a git repository clone and that git binary is available.');
            }

            $rootDir = trim($process->getOutput());
        }

        return $rootDir;
    }

    /**
     * Get version.
     *
     * @return string
     *   Version.
     */
    public function getVersion()
    {
        static $version = null;

        if (null == $version) {
            $process = new Process('git describe --tags HEAD');

            if ($process->run() == 0) {
                $version = trim($process->getOutput());
            } else {
                $process = new Process('git log --pretty="%H" -n1 HEAD', $this->rootDir);

                if ($process->run() != 0) {
                    throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from a git repository clone and that git binary is available.');
                }

                $version = 'rev-' . substr(trim($process->getOutput()), 0, 8);
            }
        }

        return $version;
    }

    /**
     * Get last commit date.
     *
     * @return string
     *   Date.
     */
    public function getVersionDate()
    {
        static $versionDate = null;

        if (null == $versionDate) {
            $process = new Process('git log -n1 --pretty=%ci HEAD', $this->rootDir);

            if ($process->run() != 0) {
                throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from a git repository clone and that git binary is available.');
            }

            $date = new \DateTime(trim($process->getOutput()));
            $date->setTimezone(new \DateTimeZone('UTC'));

            $versionDate = $date->format('Y-m-d H:i:s');
        }

        return $versionDate;
    }

    public function debug($text)
    {
        static $verbose = null;

        if (null === $verbose) {
            $verbose = $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
        }

        if (!$verbose) {
            return;
        }

        $this->output->writeln($text);
    }

    /**
     * Run compiler.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   An InputInterface instance.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   An OutputInterface instance.
     *
     * @return int
     *   Return code.
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        $this->input = $input;
        $this->output = $output;
        $this->configureIO();

        if (true === $this->input->hasParameterOption(array('--help', '-h'))) {
            $command = static::COMMAND_HELP;
        } else {
            $command = static::COMMAND_COMPILE;
        }

        return $this->{$command}();
    }

    /**
     * Compiles cli application into a single phar file.
     *
     * @param  string $pharFile
     *   The full path to the file to create.
     *
     * @throws \RuntimeException
     */
    public function compile()
    {
        // Detect environment.
        $this->rootDir = realpath($this->getRootDir()) . '/';
        $this->version = $this->getVersion();
        $this->versionDate = $this->getVersionDate();

        // Detect configuration directory.
        $configDir = $this->input->getOption('config');

        if (empty($configDir)) {
            // Auto detect config directory.
            foreach (['conf', 'config', 'settings'] as $dir) {
                $dir = $this->rootDir . $dir . '/';

                if (is_dir($dir)) {
                    $configDir = $dir;
                    break;
                }
            }

            if (empty($configDir)) {
                throw new \UnexpectedValueException('Could not find config directory');
            }
        } else {
            $realConfigDir = realpath($configDir);

            if (!$realConfigDir) {
                throw new \UnexpectedValueException($configDir . ' is not a directory');
            } elseif (strpos($realConfigDir, $this->rootDir) !== 0) {
                throw new \UnexpectedValueException('Config directory is not in project');
            }

            $configDir = $realConfigDir;
        }

        // Detect bin file.
        $binFile = $this->input->getOption('bin');

        if (empty($binFile)) {
            throw new \UnexpectedValueException('You have to specify bin file');
        } elseif (!is_file($binFile)) {
            throw new \UnexpectedValueException($binFile . ' is not a file');
        }

        $realBinFile = realpath($binFile);

        if (strpos($realBinFile, $this->rootDir) !== 0) {
            throw new \UnexpectedValueException('Bin file is not in project');
        }

        $binFile = $realBinFile;
        $binFileName = basename($binFile);
        $pharFileName = $binFileName . '.phar';
        $pharFile = $this->rootDir . $pharFileName;

        if (file_exists($pharFile)) {
            unlink($pharFile);

            if (file_exists($pharFile)) {
                throw new \RuntimeException('Could not remove ' . $pharFileName);
            }
        }

        // Optimize classmap.
        if (!$this->input->hasParameterOption('--no-optimize')) {
            $this->optimizeClassmap();
        }

        // Create phar file.
        $phar = new \Phar($pharFile, 0, $pharFileName);
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        // Add php files.
        $finder = new Finder();
        $finder->files()
            // Ignore version control system folder
            ->ignoreVCS(true)
            // Only add php file
            ->name('*.php')
            // Do not add tests folder
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('composer.bak')
            // Exclude compiler itself.
            ->notName('Compiler.php')
            // Search in project folder
            ->in($this->rootDir);

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        // Add config files.
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.yml')
            ->in($configDir);

        foreach ($finder as $file) {
            $this->addFile($phar, $file, false);
        }

        // Add the cli application.
        $this->addBinFile($phar, $binFile);

        // Stubs
        $phar->setStub($this->getStub($binFile, $pharFileName));

        $phar->stopBuffering();

        // Try to compress files.
        if (!$this->input->hasParameterOption('--no-compress') && (extension_loaded('zlib') || extension_loaded('bzip2'))) {
            $phar->compressFiles(\Phar::GZ);
        }

        // Add license file if exists.
        $licenseFile = $this->rootDir . 'LICENSE';

        if (file_exists($licenseFile)) {
            $this->addFile($phar, new \SplFileInfo($licenseFile), false);
        }

        unset($phar);

        if ($this->input->hasParameterOption('--executable')) {
            $process = new Process('chmod +x "' . $pharFile . '"');
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Error occured while chmod phar file');
            }
        }

        if ($this->input->hasParameterOption('--no-phar')) {
            $noPharFile = substr($pharFile, 0, -5);
            $process = new Process('mv "' . $pharFile . '" "' . $noPharFile . '"');
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Error occured while chmod phar file');
            }

            $pharFile = $noPharFile;
        }

        $this->output->writeln('<comment>Built file</comment> ' . $pharFile);
    }

    private function addFile($phar, $file, $strip = true)
    {
        $path = strtr(str_replace($this->rootDir, '', $file->getRealPath()), '\\', '/');
        $content = file_get_contents($file);

        $this->debug('<comment>Adding file</comment> ' . $path);

        switch (true) {
            case $strip:
                $content = $this->stripWhitespace($content);
                break;

            case ('LICENSE' === basename($file)):
                $content = "\n" . $content . "\n";
                break;

            case (pathinfo($file, PATHINFO_EXTENSION) == 'yml'):
                $content = str_replace(['@version', '@build'], [$this->version, $this->versionDate], $content);
                break;
        }

        $phar->addFromString($path, $content);
    }

    private function addBinFile($phar, $binFile)
    {
        $relativeBinFile = str_replace($this->rootDir, '', $binFile);
        $this->debug('<comment>Adding file</comment> ' . $relativeBinFile);

        $content = file_get_contents($binFile);
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString($relativeBinFile, $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line
     * numbers.
     *
     * @param string $source
     *   A PHP string.
     *
     * @return string
     *   The PHP string with the whitespace removed.
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';

        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    /**
     * Optimize classmap
     *
     * @return boolean
     *   True or False.
     */
    private function optimizeClassmap()
    {
        // Check composer.
        $process = new Process('which composer');
        $process->run();

        if (!$process->isSuccessful()) {
            $this->debug('<comment>Could not found composer command</comment>');
            return false;
        }

        // Optimize.
        $process = new Process('composer dump-autoload -o', $this->rootDir);
        $process->run();

        $result = $process->isSuccessful();

        if ($result) {
            $this->debug('<comment>Optimized classmap</comment>');
        } else {
            $this->debug('<comment>Could not optimize classmap</comment>');
        }

        return $result;
    }

    private function getStub($binFile, $pharFileName)
    {
        $relativeBinFile = str_replace($this->rootDir, '', $binFile);

        $stub = <<<EOF
#!/usr/bin/env php
<?php
/*
 * This file is compiled by IN2PIRE CLI (https://github.com/in2pire/in2pire-cli)
 *
 * For the full copyright and license information, please view the license that
 * is located at the bottom of this file.
 */

Phar::mapPhar('$pharFileName');

EOF;

        return $stub . <<<EOF
require 'phar://$pharFileName/$relativeBinFile';

__HALT_COMPILER();
EOF;
    }

    /**
     * Backup composer directory.
     *
     * @param string $composerDir
     *   Directory.
     *
     * @return boolean
     *   True or False.
     */
    public static function backupComposer($composerDir)
    {
        $composerBak = $composerDir . '.bak';

        // Back up class loaders.
        if (is_dir($composerBak)) {
            $process = new Process('rm -rf "' . $composerBak . '"');
            $process->run();

            if (!$process->isSuccessful()) {
                return false;
            }
        }

        // Backup
        $process = new Process('cp -R "' . $composerDir . '" "' . $composerBak . '"');
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Restore composer directory.
     *
     * @param string $composerDir
     *   Directory.
     *
     * @return boolean
     *   True or False.
     */
    public static function restoreComposer($composerDir)
    {
        $composerBak = $composerDir . '.bak';

        // Restore class loaders.
        if (!is_dir($composerBak)) {
            return false;
        }

        // Remove current one.
        $process = new Process('rm -rf "' . $composerDir . '"');
        $process->run();

        if (!$process->isSuccessful()) {
            return false;
        }

        // Restore
        $process = new Process('mv "' . $composerBak . '" "' . $composerDir . '"');
        $process->run();

        return $process->isSuccessful();
    }
}
