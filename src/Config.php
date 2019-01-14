<?php

namespace Yurderi\Provisioner;

use Symfony\Component\Yaml\Yaml;

class Config
{
    
    /**
     * @var array
     */
    protected $data;
    
    public function __construct ($filename)
    {
        $this->data = Yaml::parseFile($filename);
    }
    
    public function get($key = '', $default = null)
    {
        $nodes   = explode('.', $key);
        $current = $this->data;
        
        foreach ($nodes as $node)
        {
            if (isset($current[$node]))
            {
                $current = $current[$node];
            }
            else
            {
                return $default;
            }
        }
        
        return $current;
    }
    
}