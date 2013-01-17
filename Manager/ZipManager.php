<?php

namespace IMAG\FilesBundle\Manager;

class ZipManager
{
    private
        $logger,
        $_archive
        ;

    public function __construct(\Symfony\Bridge\Monolog\Logger $logger)
    {
        $this->logger = $logger;
        $this->_archive = new \ZipArchive();
                
        $zipPath = '/tmp/'.uniqId().'.zip';
        $this->_archive->open($zipPath, \ZIPARCHIVE::CREATE);
    }

    public function add($path)
    {
        $this->logger->info(sprintf('Adding file %s to zip archive', $path));

        $this->_archive->addFile($path, basename($path));
    }

    public function getZip()
    {
        $filename = $this->_archive->filename;
        $this->_archive->close();

        return $filename;
    }
}