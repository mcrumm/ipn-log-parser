<?php

namespace Gctrl\IpnLogParser\Command\Log;

use Cilex\Command\Command;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * CombineCommand
 *
 * @author Michael Crumm <mike.crumm@groundctrl.com>
 */
class CombineCommand extends Command
{
    private $files;

    private $output;

    /**
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     *
     * @var \Symfony\Component\Console\Helper\DialogHelper
     */
    private $dialog;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('log:combine')
            ->setDefinition(array(
                new InputArgument('paths', InputArgument::OPTIONAL|InputArgument::IS_ARRAY, 'The path(s) to the log file(s).', null),
                new InputOption('outfile', 'o', InputArgument::OPTIONAL, 'Where to write the combined log file.', null)
            ))
            ->setDescription('Combine multiple logs into a single file.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output     = $output;
        $this->filesystem = new Filesystem();
        $this->dialog     = $this->getHelperSet()->get('dialog');

        $paths   = $input->getArgument('paths');
        $outFile = $input->getOption('outfile');

        if (empty($paths)) {
            $this->doFilePromptLoop();
        } else {
            $this->validateInputPaths($paths);
        }

        if (null === $outFile) {
            $outFile = $this->promptForOutFile();
        }

        $bytes = $this->combineLogs($outFile);

        $output->writeln(sprintf('<info>%d total bytes written to %s</info>', $bytes, $outFile));
    }

    public function doFilePromptLoop()
    {
        do {
            $file = $this->promptForFile();

            if (null !== $file) {
                $this->files[] = $file;
                continue;
            }

            if (empty($this->files)) {
                throw new \RuntimeException('You must add at least one file.');
            }

        } while(null !== $file);
    }

    public function validateInputPaths(array $paths)
    {
        foreach ($paths as $path) {

            if (!$this->isRealFile($path)) {
               throw new \InvalidArgumentException(sprintf('The file "%s" does not exist.', $path)); 
            }

            $this->files[] = $path;
        }
    }

    public function promptForFile()
    {
        $question = '<question>Enter a filename:</question> ';
        return $this->dialog->askAndValidate($this->output, $question, array($this, 'isRealFile'));
    }

    public function promptForOutFile()
    {
        $default  = $this->getRandomFilename();
        $question = '<question>Save the combined file to: ['.$default.']</question> ';
        return $this->dialog->ask($this->output, $question, $default);
    }

    public function getRandomFilename()
    {
        return 'logs/' . time() . '.log';
    }

    public function combineLogs($outFile)
    {
        $contents = '';

        foreach ($this->files as $file) {
            $contents .= file_get_contents($file);
        }

        $this->filesystem->dumpFile($outFile, $contents);

        return filesize($outFile);
    }

    public function isRealFile($filename)
    {
        if (null === $filename) {
            return;
        }

        if ($this->filesystem->exists($filename)) {
            return $filename;
        }

        throw new \InvalidArgumentException(sprintf(
            'The file "%s" does not exist.', $filename));
    }
}
