#Requirements
Ensure that the GD library is enabled in your PHP installation. You can usually enable it by uncommenting or adding the following line in your php.ini file:
extension=gd
extension=imagick

Before you can use Imagick in PHP, you need to ensure that the Imagick extension is installed and enabled on your server. If Imagick is not already installed, you can typically install it using package managers like apt (for Debian-based systems) or yum (for CentOS/RHEL-based systems). For example, on Ubuntu, you can install Imagick using the following command:

sudo apt-get install -y php-imagick

#Installation linux
    ```bash
        sudo apt-get install -y tesseract-ocr
        sudo apt-get install -y tesseract-ocr-script-arab
        sudo apt-get install -y pdftoppm
        sudo apt-get install -y ghostscript
        sudo apt-get install -y poppler-utils
        sudo apt-get install imagemagick
    ```
    after you install tesseract 
    run 
    ```bash
    export TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata
    ```
#Installation windows
     ```bash
    https://github.com/UB-Mannheim/tesseract/wiki
     ```
     after you install tesseract 
    run 
    ```bash
    set TESSDATA_PREFIX=C:\Program Files\Tesseract-OCR\tessdata
    ```
#Check
```bash
    tesseract --list-langs
 ```

#Usage
to extract emirates id details
```python
use Vmbcarabbacan\TeseractOcr\TesseractOcr;

#file can be jpg, png or pdf
$path = 'path/id/test.jpg'

$tesseract = new TesseractOcr();
return $tesseract->setpath($path)->emiratesId()->lang('Arabic')->generateFile();

#returns emirates id, name, dob and extracted string
```

to extract policy details
```python
use Vmbcarabbacan\TeseractOcr\TesseractOcr;

#file can be jpg, png or pdf
$path = 'path/id/policy.pdf'

$tesseract = new TesseractOcr();
return $tesseract->setpath($image)->policy()->lang('eng')->generateFile();

#returns policy number, policy start date, policy end date and extracted string
```

    
