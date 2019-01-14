<?php

namespace Yurderi\Provisioner\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{

    protected function configure ()
    {
        $this->setName('run');
        $this->setDescription('Executes a provisioner');
        
        $this->addArgument('provisioner', InputArgument::REQUIRED, 'The provisioner that should be executed.');
        $this->addArgument('filename', InputArgument::OPTIONAL, 'The filename which should be processed.');
    }
    
    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $provisioner = $input->getArgument('provisioner');
        $filename    = $input->getArgument('filename');
        
        if (!empty($filename))
        {
            $filename = realpath($filename);
            
            if (!is_file($filename))
            {
                $output->writeln('ERROR: File not found!');
            }
        }
        
        $class       = 'Yurderi\\Provisioner\\Provisioner\\' . ucfirst($provisioner) . '\\Provisioner';
        
        if (class_exists($class))
        {
            $output->writeln('Running ' . $provisioner . '-provisioner');
            
            /** @var \Yurderi\Provisioner\Provisioner\ProvisionerInterface $provisioner */
            $provisioner = new $class($input, $output);
            $provisioner->execute($filename);
        }
        else
        {
            $output->writeln('ERROR: Unknown provisioner.');
        }
    }
    
}