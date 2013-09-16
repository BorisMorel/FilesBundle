<?php

namespace IMAG\FilesBundle\Manager;

use Symfony\Component\Process\ProcessBuilder;

class PdfManager
{
    private
        $templating,
        $snappy,
        $logger,
        $template = array(),
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

        $html = $this->templating->render($this->template['template'], array_merge(
            $this->template['params'],
            array('data' => $object)
        ));

        $this->snappy->generateFromHtml($html, $pdfFilePath);
        
        $this->pdfFilePath = $pdfFilePath;
        return $pdfFilePath;
    }
    
    public function appendFiles(array $files)
    {
        if (null === $this->pdfFilePath) {
            throw new \RuntimeException('You need to set a original pdf to append file into');
        }

        $zendPdf = new \ZendPdf\PdfDocument($this->pdfFilePath, null, true);

        foreach($files as $file) {
            try {
                $this->logger->info(sprintf('Processing %s', $file));
                $pdf = \ZendPdf\PdfDocument::load($file);
                try {
                    $this->logger->info('Dereference pages');
                    $pages = $this->dereferencePages($pdf);

                } catch (\ZendPdf\Exception\RuntimeException $e) {
                    $this->logger->err($e->getMessage());
                    throw $e;

                }
            } catch (\ZendPdf\Exception\ExceptionInterface $e) {
                $this->logger->warn("Pdf file can't be loaded by Zend");
                
                try {
                    try {
                        $temp = $this->convertPdf($file);
                        $pdf = \ZendPdf\PdfDocument::load($temp);
                        unlink($temp);

                    } catch (\RuntimeException $e) {
                        $this->logger->debug($e->getMessage());
                        throw $e;

                    } catch (\ZendPdf\Exception\ExceptionInterface $e) {
                        $this->logger->debug($e->getMessage());
                        throw $e;

                    } 
                } catch (\Exception $e) {
                    $this->logger->err("Pdf can't be read. Adding to final pdf 1 page with the error");
                    $pdf = $this->createErrorDocument($file); 
                
                }

                $pages = $this->dereferencePages($pdf);
            }

            $zendPdf->pages = array_merge($zendPdf->pages, $pages);
        }

        $zendPdf->save($this->pdfFilePath, true);
    }

    public function setTemplate($template, array $params = array()) 
    {
        if (!$this->templating->exists($template)) {
            throw new \InvalidArgumentException(sprintf('Template %s not found', $template));
        }

        $this->template = array(
            'template' => $template,
            'params' => $params,
        );

        return $this;
    }

    public function setPdfPath($path)
    {
        $this->pdfFilePath = $path;

        return $this;
    }

    public function setPdfPrefix($prefix)
    {
        // See http://unicode.org/reports/tr15/#Norm_Forms
        $prefix = preg_replace(
            '/\p{M}/u',
            '',
            \Normalizer::normalize($prefix, \Normalizer::FORM_KD)
        );

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
            ->flush()
            ;

        return $pdf;
    }

    private function convertPdf($file)
    {
        $psFile = sprintf("/tmp/temp-%s.ps", uniqId());
        $pdfTempFile = preg_replace("/([^\.]+)\.ps/", "$1.pdf", $psFile);

        try {
            $builder = new ProcessBuilder(array('pdf2ps', $file, $psFile));
            $process = $builder->getProcess();
            $process->run();

            if (!$process->isSuccessful()
                || $process->getExitCode() != 0
                || !file_exists($psFile)) {

                throw new \RuntimeException(sprintf('The conversion PDF to PS process has failed: %s', $process->getErrorOutput()));
            }
            
            $builder = new ProcessBuilder(array('ps2pdf', $psFile, $pdfTempFile));
            $process = $builder->getProcess();
            $process->run();

            if (!$process->isSuccessful()
                || $process->getExitCode() != 0
                || !file_exists($pdfTempFile)) {

                throw new \RuntimeException(sprintf('The conversion PS to PDF process has failed: %s', $process->getErrorOutput()));
            }
            
            return $pdfTempFile;
            
        } catch (\Exception $e) {
            throw $e;
        } finally {
            unlink($psFile);

        }
    }

    private function dereferencePages($file)
    {
        $extractor = new \ZendPdf\Resource\Extractor();
        
        $res = array();

        foreach ($file->pages as $page) {
            $res[] = $extractor->clonePage($page);
        }

        return $res;
    }
}