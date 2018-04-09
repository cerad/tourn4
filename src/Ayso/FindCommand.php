<?php
/**
 * Created by PhpStorm.
 * User: ahundiak
 * Date: 4/7/18
 * Time: 12:50 PM
 */

namespace App\Ayso;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindCommand extends Command
{
    private $finder;

    public function __construct(AysoFinder $finder)
    {
        $this->finder = $finder;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('ayso:find');
        $this->setDescription('Find AYSO Vol Info.');
        $this->addArgument('search', InputArgument::REQUIRED, 'Vol id or name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $search = $input->getArgument('search');

        $output->writeln('Find ' . $search);

    }
}
