<?php

namespace IMAG\FilesBundle\Manager;

class ZipManager extends AbstractManager
{
    private
        $fileDirPrefix = 'file',
        $_archive = null
        ;

    public function init()
    {
        $this->_archive = new \ZipArchive();
        $file =
            $this->path
            .'/'
            .$this->prefix
            ."-"
            .uniqId(mt_rand())
            .".zip"
            ;

        $this->_archive->open($file, \ZIPARCHIVE::CREATE);

        return $this;
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