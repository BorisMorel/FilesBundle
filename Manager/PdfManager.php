<?php

namespace IMAG\FilesBundle\Manager;

use Symfony\Component\Process\ProcessBuilder;

class PdfManager
{
    private
        $templating,
        $snappy,
        $logger,
        $template,
        $pdfFilePath,
        $pdfPrefix = 'file'
        ;

    public function __construct(\Symfony\Bundle\TwigBundle\TwigEngine $templating,
                                \Knp\Snappy\GeneratorInterface $snappy,
                                \Symfony\Bridge\Monolog\Logger $logger)
    {
        $this->templating = $templating;
        $this->snappy = $snappy;
        $this->logger = $logger;
    }

    public function htmlToPdf($object)
    {
        $pdfFilePath = "/tmp/".$this->pdfPrefix."-".uniqId().".pdf";

        $this->logger->info(sprintf('Creating %s', $pdfFilePath));
        
        if (null === $this->template) {
            throw new \RuntimeException('Before to create a Pdf you need to set a template');
        }

        $html = $this->templating->render($this->template, array(
            'data' => $object,
        ));

        $this->snappy->generateFromHtml($html, $pdfFilePath);
        
        $this->pdfFilePath = $pdfFilePath;
        return $pdfFilePath;
    }
    
    public function appendFile($file)
    {
        if (null === $this->pdfFilePath) {
            throw new \RuntimeException('You need to set a original pdf to append file into');
        }

        $zendPdf = new \ZendPdf\PdfDocument($this->pdfFilePath, null, true);

        try {
            $this->logger->info('Processing %s', basename($file));
            $zendPdf->load($file);
        } catch (\Exception $e) {
            $this->logger->warn("Pdf file can't be loaded by Zend");
            
            try {
                $temp = $this->convertPdf($file);
                $pdf = $zendPdf->load($temp);
            } catch (\Exception $e) {
                $this->logger->err("Pdf can't be read");
                
                $pdf = $this->createErrorDocument($file);
            }
        }

        foreach ($pdf->pages as $page) {
            $zendPdf->pages[] = clone $page;
        }

        $zendPdf->save($this->pdfFilePath);
    }

    public function setTemplate($template) 
    {
        if (!$this->templating->exists($template)) {
            throw new \InvalidArgumentException('Template %s not found', $template);
        }

        $this->template = $template;

        return $this;
    }

    public function setPdfPath($path)
    {
        $this->pdfFilePath = $path;

        return $this;
    }

    public function setPdfPrefix($prefix)
    {
        $this->pdfPrefix = $prefix;

        return $this;
    }

    private function createErrorDocument($file)
    {
        $pdf = new \ZendPdf\PdfDocument();
        $pdf->pages[] = ($page1 = $pdf->newPage('A4'));

        $text = sprintf("Pdf file corrupted : %s", basename($file));

        $font = \ZendPdf\Font::fontWithName('Helvetica-Bold');
        
        $red =  \ZendPdf\Color\Html::color('#FF0000');
        $white = \ZendPdf\Color\Html::color('#FFFFFF');
        

        $height = $page1->getHeight();
        $width = $page1->getWidth();
                
        $rx1 = ($width/2) - 100;
        $rx2 = ($width/2) + 100;
        $ry1 = ($height/2) - 20;
        $ry2 = ($height/2) + 20;

        $page1
            ->setFont($font, 20)
            ->drawText($text, 10, ($height-$height/8))      
            ->setFillColor($red)
            ->setLineColor($white)
            ->setLineWidth(10)
            ->drawCircle($width/2, $height/2, 150)
            ->setFillColor($white)
            ->setLineWidth(0)
            ->drawRectangle($rx1, $ry1, $rx2, $ry2)
            ;

        return $pdf;
    }

    private function convertPdf($file)
    {
        $this->logger->info('Trying to convert pdf file');

        $psFile = sprintf("/tmp/temp-%s.ps", uniqId());
        $pdfTempFile = preg_replace("/([^\.]+)\.ps/", "$1.pdf", $psFile);

        try {
            $builder = new ProcessBuilder(array('pdf2ps', $file, $psFile));
            $builder->getProcess()->run();

            if (!$builder->getProcess()->isSuccessful()
                || $builder->getProcess()->getExitCode() != 0
                || !file_exists($psFile)) {
                $this->logger->warn('The conversion PDF to PS process has failed');
                throw new \Exception(sprintf('The conversion PDF to PS process has failed: %s', $builder->getProcess()->getErrorOutput()));
            }
            
            $builder = new ProcessBuilder(array('ps2pdf', $psFile, $pdfTempFile));
            $builder->getProcess()->run();

            if (!$builder->getProcess()->isSuccessful()
                || $builder->getProcess()->getExitCode() != 0
                || !file_exists($pdfTempFile)) {
                $this->logger->warn('The conversion PS to PDF process has failed');
                throw new \Exception(sprintf('The conversion PS to PDF process has failed: %s', $builder->getProcess()->getErrorOutput()));
            }
            
            unlink($psFile);

            return $pdfTempFile;
            
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

}