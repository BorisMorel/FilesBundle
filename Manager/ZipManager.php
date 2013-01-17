<?php

namespace IMAG\FilesBundle\Manager;

class ZipManager
{
    private
        $logger,
        $_archive
        ;

    public function __construct()
    {
        $this->_archive = new \ZipArchive();
        
        $zipPath = '/tmp/'.uniqId().'.zip';
        $this->_archive->open($zipPath, \ZIPARCHIVE::CREATE);
    }

    public function add($path)
    {
        $this->_archive->addFile($path, basename($path));
    }

    public function getZip()
    {
        $filename = $this->_archive->filename;
        $this->_archive->close();

        return $filename;
    }
}