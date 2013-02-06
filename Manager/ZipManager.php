<?php

namespace IMAG\FilesBundle\Manager;

class ZipManager
{
    private
        $logger,
        $zipPrefix = 'file',
        $fileDirPrefix = 'file',
        $_archive = null
        ;

    public function __construct(\Symfony\Bridge\Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }

    public function init()
    {
        $this->_archive = new \ZipArchive();
        $zipPath = '/tmp/'.$this->zipPrefix.'-'.uniqId().'.zip';
        $this->_archive->open($zipPath, \ZIPARCHIVE::CREATE);
        
        return $this;
    }

    public function setZipPrefix($prefix)
    {
        $this->zipPrefix = $prefix;

        return $this;
    }

    public function getZipPrefix()
    {
        return $this->zipPrefix;
    }

    public function addFileDirectoryPrefix($dirname)
    {
        $this->fileDirPrefix = trim($dirname, '/');
    }

    public function add($path)
    {
        if (null === $this->_archive) {
            $this->init();
        }

        $this->logger->info(sprintf('Adding file %s to zip archive', $path));

        
        $this->_archive->addFile($path, $this->fileDirPrefix.'/'.basename($path));

        return $this;
    }

    public function getZip()
    {
        $filename = $this->_archive->filename;
        $this->_archive->close();

        return $filename;
    }
}