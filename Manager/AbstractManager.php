<?php

namespace IMAG\FilesBundle\Manager;

abstract class AbstractManager
{
    protected $logger;

    protected $prefix;

    protected $path;

    public function __construct(\Symfony\Bridge\Monolog\Logger $logger)
    {
        $this->logger = $logger;

        $this->prefix = 'file';
        $this->path = sys_get_temp_dir();
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPrefix($prefix)
    {
        // See http://unicode.org/reports/tr15/#Norm_Forms
        $prefix = preg_replace(
            '/\p{M}/u',
            '',
            \Normalizer::normalize($prefix, \Normalizer::FORM_KD)
        );

        $this->prefix = $prefix;

        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }
}