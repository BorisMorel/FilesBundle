# FilesBundle

This bundle can create/manage PDFs files. it can create a Pdf from a html page and append many Pdfs files on the document and zip final document.
It use KnpSnappyBundle to create PDF from Html and ZendPdf to append pdfs files.


## Install

1. Download FilesBundle
2. Enable the bundle
3. Configure the KnpSnappyBundle

### How get the bundle

**Caution:**
> The dev-master version have not backward compatibility with the 2.x version.

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
    ->setTemplate("foo.html.twig", array('includeVar', $includeVar))
    ->setPdfPrefix('foo-file') // Like : 'foo-file-uniqId().pdf'
    ->setPath('/home/foo/tmp') // Default sys_get_temp_dir()
    ->addParameter('name', $value)
    ->htmlToPdf()
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

## Example

### Twig template

``` html

<!DOCTYPE html>
<html>
  <head>
    <meta charset='utf-8'>
    {% include('NSFooBundle:Pdf/css:' ~ includeVar) %}
  </head>

  <body>
    <h1>{{ data.title }}</h1>
    <h2>{{ data.body }}</h2>
  </body>
</html>


```
