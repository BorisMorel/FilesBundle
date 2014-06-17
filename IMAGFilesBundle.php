<?php

namespace IMAG\FilesBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Process\ProcessBuilder;

class IMAGFilesBundle extends Bundle
{
    public function boot()
    {
        /**
         * pdftk exists ?
         */
        $builder = new ProcessBuilder(array('which', 'pdftk'));
        $process = $builder->getProcess();
        $process->run();

        if ($process->getExitCode() != 0) {
            throw new \RuntimeException('You need install pdftk');
        }

        /**
         * ZendPdf exists ?
         */
        if (false === class_exists('\ZendPdf\PdfDocument')) {
            throw new \RuntimeException('You need install ZendPdf');
        }
    }
    
}
