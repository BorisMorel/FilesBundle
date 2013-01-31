# FilesBundle

This bundle can create/manage PDFs files. it can create a Pdf from a html page and append many Pdfs files on the document and zip final document. 
It use KnpSnappyBundle to create PDF from Html and ZendPdf to append pdfs files.


## Install

1. Download FilesBundle
2. Enable the bundle
3. Configure the KnpSnappyBundle

### How get the bundle

### Composer
Modify your composer.json on your project root

``` json
// {root}/composer.json

{
    [...],
    "require": {
        [...],
        "imag/files-bundle": "dev-master"
    }
}
```

### Enable the Bundle

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
    // ...
    new IMAG\FilesBundle\IMAGFilesBundle(),
    );
}
```

### Configure KnpSnappyBundle

Please read the official KnpSnappyBundle documentation
https://github.com/KnpLabs/KnpSnappyBundle

## Usage

### Create PDF from Html

``` php
<?php

$pdf = $this->get('imag_files.pdf')
    ->setTemplate("pdf.html.twig", array('include', $includeTemplate))
    ->setPdfPrefix(uniqId(mt_rand()))
    ->htmlToPdf($var)
    ;

```

### Append pdf file to final document

``` php
<?php

$pdf = $this->get('imag_files.pdf')
    ->setPdfPath($finalPdf)
    ->appendFiles(array($file1, $files2))
    ;

```

### Adding files into zip archive

``` php
<?php

$zip = $this->get('imag_files.zip')
    ->add($pdf)
    ;

```