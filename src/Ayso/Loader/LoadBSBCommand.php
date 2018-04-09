<?php
/**
 * Created by PhpStorm.
 * User: ahundiak
 * Date: 4/7/18
 * Time: 12:50 PM
 */

namespace App\Ayso\Loader;

use App\Ayso\AysoConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadBSBCommand extends Command
{
    private $conn;

    public function __construct(AysoConnection $conn)
    {
        $this->conn = $conn;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('ayso:load:bsb');
        $this->setDescription('Load AYSO from BSB.');
        $this->addArgument('filename', InputArgument::REQUIRED, 'Path to BSB file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');

        $output->writeln('Loading BSB Data ' . $filename);

    }
}
