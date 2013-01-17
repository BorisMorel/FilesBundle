<?php

namespace IMAG\AdminCallsBundle\Manager;

use Symfony\Component\Process\ProcessBuilder;

use IMAG\CallsBundle\Form\Collection\Data\CollectionDataFileInterface,
    \IMAG\CallsBundle\Entity\Application
    ;

class PdfManager
{
    private
        $templating,
        $snappy,
        $logger,
        $template
        ;

    public function __construct(\Symfony\Bundle\TwigBundle\TwigEngine $templating,
                                \Knp\Snappy\GeneratorInterface $snappy,
                                \Symfony\Bridge\Monolog\Logger $logger)
    {
        $this->templating = $templating;
        $this->snappy = $snappy;
        $this->logger = $logger;
    }

    public function htmlToPdf()
    {
        if (!isset($this->templating)) {
            throw new \RuntimeException('Before to create a Pdf you need to set a template');
        }

        
    }

    public function setTemplate($template) 
    {
        if (!$this->templating->exists($template)) {
            throw new \InvalidArgumentException('Template %s not found', $template);
        }

        $this->template = $template;
    }
}