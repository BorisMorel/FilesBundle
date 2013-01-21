<?php

namespace IMAG\FilesBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Process\ProcessBuilder;

class IMAGFilesBundle extends Bundle
{
    public function boot()
    {
        /**
         * ps2pdf exists ?
         */
        $builder = new ProcessBuilder(array('which', 'ps2pdf'));
        $process = $builder->getProcess();
        $process->run();

        if ($process->getExitCode() != 0) {
            throw new \RuntimeException('You need install ps2pdf');
        }

        /**
         * pdf2ps exists ?
         */
        $builder = new ProcessBuilder(array('which', 'pdf2ps'));
        $process = $builder->getProcess();
        $process->run();

        if ($process->getExitCode() != 0) {
            throw new \RuntimeException('You need install pdf2ps');
        }

        /**
         * ZendPdf exists ?
         */
        if (false === class_exists('\ZendPdf\PdfDocument')) {
            throw new \RuntimeException('You need install ZendPdf');
        }
    }
    
}
