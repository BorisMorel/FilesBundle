<?php

namespace IMAG\FilesBundle\Manager;

use Symfony\Component\Process\ProcessBuilder;

class PdfManager extends AbstractManager
{
    private
        $templating,
        $snappy,
        $template,
        $parameters = array()
        ;

    public function __construct(\Symfony\Bridge\Monolog\Logger $logger,
                                \Symfony\Bundle\TwigBundle\TwigEngine $templating,
                                \Knp\Snappy\GeneratorInterface $snappy)
    {
        parent::__construct($logger);
        $this->templating = $templating;
        $this->snappy = $snappy;
    }

    public function htmlToPdf()
    {
        $file =
            $this->path
            .'/'
            .$this->prefix
            ."-"
            .uniqId(mt_rand())
            .".pdf"
            ;

        $this->logger->info(sprintf('Creating %s', $file));

        if (null === $this->template) {
            throw new \RuntimeException('Before to create a Pdf you need to set a template');
        }

        $html = $this->templating->render($this->template, $this->parameters);

        $this->snappy->generateFromHtml($html, $file);

        $this->logger->info('Pdf from html created');

        return $file;
    }


    public function appendFiles($pdfPath, array $files)
    {
        if (true === file_exists($pdfPath) && 0 != filesize($pdfPath)) {
            $tempPath = sys_get_temp_dir()."/temp-".uniqId().".pdf";
            $this->logger->info(sprintf('Rename %s -> %s', $pdfPath, $tempPath));
            rename($pdfPath, $tempPath);

            array_unshift($files, $tempPath);
        }

        try {
            $builder = new ProcessBuilder();
            $builder
                ->setPrefix('pdftk')
                ;

            foreach ($files as $file) {

                if (false === file_exists($file)) {
                    throw new \InvalidArgumentException(sprintf('File not exists : %s', $file));
                }

                $builder->add($file);
            }

            $builder
                ->add('output')
                ->add($pdfPath)
                ;

            $process = $builder->getProcess();
            $process->run();

            $this->logger->info(sprintf('Start Pdftk process'));

            if (!$process->isSuccessful()
                || $process->getExitCode() != 0
            ) {

                throw new \RuntimeException(sprintf('command line failed [%s] : %s', $process->getCommandLine(), $process->getErrorOutput()));
            }
        }  catch (\InvalidArgumentException $e) {
            $this->logger->err($e->getMessage());
            $errorPdf = $this->createErrorDocument($file);
            $files = $this->replaceErrorFile($files, $file, $errorPdf);

            $this->appendFiles($pdfPath, $files);
        } catch (\RuntimeException $e) {
            $this->logger->err("Pdftk Failed. Now error process");
            $this->logger->debug($e->getMessage());

            throw $e;
        }

        isset($tempPath) ? unlink($tempPath) : '';
        isset($errorPdf) ? unlink($errorPdf) : '';

        return $pdfPath;
    }

    public function addWaterMark($pdfPath, $pdfMark)
    {
        if (false === file_exists($pdfPath) || false === file_exists($pdfMark)) {
            throw new \RuntimeException(sprintf('To add a watermark, the files need to be exists: %s : %s', $pdfPath, $pdfMark));
        }

        $tempPath = sys_get_temp_dir()."/temp-".uniqId().".pdf";
        $this->logger->info(sprintf('Rename %s -> %s', $pdfPath, $tempPath));
        rename($pdfPath, $tempPath);

        try {
            $builder = new ProcessBuilder();
            $builder
                ->setPrefix('pdftk')
                ->add($tempPath)
                ->add('background')
                ->add($pdfMark)
                ->add('output')
                ->add($pdfPath)
                ;

            $process = $builder->getProcess();
            $process->run();
        } catch (\Exception $e) {
            $this->logger->err("Pdftk watermark Failed.");
            $this->logger->debug($e->getMessage());
            rename($tempPath, $pdfPath);
        }

        unlink($tempPath);

        return $pdfPath;
    }

    public function setTemplate($template)
    {
        if (!$this->templating->exists($template)) {
            throw new \InvalidArgumentException(sprintf('Template %s not found', $template));
        }

        $this->template = $template;

        return $this;
    }

    public function addParameter($name, $param)
    {
        $this->parameters[$name] = $param;

        return $this;
    }

    public function setParameters(array $array)
    {
        $this->parameters = $array;

        return $this;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    private function createErrorDocument($file)
    {
        $this->logger->err('.......... Create a special error pdf');

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

        $fileName = "/tmp/error-".basename($file);
        $pdf->save($fileName);

        $this->logger->err(sprintf('.......... Pdf saved: %s', $fileName));

        return $fileName;
    }

    public function replaceErrorFile($files, $errorFile, $replacementFile)
    {
        $this->logger->err('.......... Trying to replace error file with special pdf');

        $key = array_search($errorFile, $files, true);

        if (false === $key) {
            throw new \RuntimeException(sprintf('.......... Pdf file for replacement not found: %s', $errorFile));
        }

        $files[$key] = $replacementFile;

        $this->logger->err(sprintf('.......... %s replaced by %s', $errorFile, $replacementFile));

        return $files;
    }

}