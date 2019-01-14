<?php

namespace Yurderi\Provisioner\Provisioner\Apache;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yurderi\Provisioner\Config;
use Yurderi\Provisioner\Provisioner\ProvisionerInterface;

class Provisioner implements ProvisionerInterface
{
    
    const APACHE_DIR = '/etc/apache2';
    
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;
    
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;
    
    /**
     * @var \Yurderi\Provisioner\Config
     */
    protected $config;
    
    protected $sslQueue = [];
    
    public function __construct (InputInterface $input, OutputInterface $output)
    {
        $this->input   = $input;
        $this->output  = $output;
    }
    
    /**
     * @param string $filename
     *
     * @throws \Exception
     */
    public function execute ($filename)
    {
        // Check for correct directories
        if (!is_dir(self::APACHE_DIR))
        {
            $this->output->writeln('ERROR: apache2 location not found.');
            return;
        }
        
        $this->config = new Config($filename);
        
        // Clean directories
        $this->cleanDirectory(self::APACHE_DIR  . '/sites-available', false, true);
        $this->cleanDirectory(self::APACHE_DIR  . '/sites-enabled', false, true);
    
        // Write hosts
        $defaultHost  = $this->config->get('default');
        $allHosts     = $this->config->get('hosts');
    
        foreach ($allHosts as $hostname => $hostConfig)
        {
            $hostConfig = array_replace_recursive($defaultHost, $hostConfig);
            $hosts      = [
                $hostname
            ];
        
            foreach ($hostConfig['alias'] as $hostAlias)
            {
                $hosts[] = $hostAlias;
            }
        
            foreach ($hosts as $host)
            {
                $this->writeHost($host, $hostConfig, $host === $hostname);
            }
        }
    }
    
    protected function reloadApache ()
    {
        `service apache2 reload`;
    }
    
    protected function obtainCertificate ($hostname)
    {
        $command = sprintf('certbot certonly --apache -n -d %s', $hostname);
    
        `$command`;
    }
    
    /**
     * Creates a vhost file based on the given configuration
     *
     * Available options in $data
     *  - default
     *  - root
     *  - active
     *  - ssl
     *  - rules
     *  - alias
     *
     * @param string $hostname
     * @param array  $data
     * @param bool   $isMain
     *
     * @throws \Exception
     */
    protected function writeHost ($hostname, $data, $isMain)
    {
        // Write host file
        $confFilename = self::APACHE_DIR . '/sites-available/' . $hostname . '.conf';
        
        $loader = new \Twig_Loader_Filesystem([__DIR__ . '/../../Views']);
        $twig   = new \Twig_Environment($loader);
        
        $content = $twig->render('vhost.twig', [
            'default'        => $isMain && $data['default'],
            'documentRoot'   => $data['root'],
            'serverName'     => $hostname,
            'rules'          => $data['rules'],
            'additionalText' => '',
            'ssl'            => false
        ]);
        
        file_put_contents($confFilename, $content);
        
        // Symlink enabled hosts
        if ($data['active'])
        {
            $linkFilename = self::APACHE_DIR . '/sites-enabled/' . $hostname . '.conf';
            
            if (!is_link($linkFilename))
            {
                symlink($confFilename, $linkFilename);
            }
        }
        
        // Configure ssl
        if ($data['ssl'])
        {
            $this->reloadApache();
            $this->obtainCertificate($hostname);
    
            $content = $twig->render('vhost.twig', [
                'default'        => $isMain && $data['default'],
                'documentRoot'   => $data['root'],
                'serverName'     => $hostname,
                'rules'          => $data['rules'],
                'additionalText' => '',
                'ssl'            => true
            ]);
    
            file_put_contents($confFilename, $content);
        }
    }
    
    /**
     * Removes files in a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     * @param bool   $ensureDir
     */
    protected function cleanDirectory ($directory, $recursive = false, $ensureDir = false)
    {
        if (!is_dir($directory) && $ensureDir)
        {
            mkdir ($directory, 0777, true);
        }
        
        $iterator = new \IteratorIterator(new \DirectoryIterator($directory));
        
        foreach ($iterator as $item)
        {
            $filename = $item->getRealPath();
            
            if ($item->isDot())
            {
                continue;
            }
            
            if ($item->isFile())
            {
                unlink($filename);
            }
            else if ($item->isDir() && $recursive)
            {
                $this->cleanDirectory($filename, true);
            }
        }
    }
    
}