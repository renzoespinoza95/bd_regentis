<?php

Class File
{

    private $handle;

    private $file;

    public function load($file_url)
    {
        $this->file = $file_url;
        //if ($this->handle = fopen($file_url, 'c+'))
        if ($this->handle = fopen($file_url, 'a'))
        {
            return $this;
        }
    }

        public function only_read($file_url)
    {
        $this->file = $file_url;
        if ($this->handle = fopen($file_url, 'c+'))        
        {
            return $this;
        }
    }


    public function write($text)
    {
        if (is_array($text)) {
            // Si $text es un array, lo convertimos a JSON
            $text = json_encode($text, JSON_PRETTY_PRINT);
        } else {
            // Convertimos cualquier otro valor a string
            $text = (string)$text;
        }

        $nl = "\n";
        $text = $nl . $text . $nl;

        if ($this->handle) {
            if (fwrite($this->handle, $text) === false) {
                fclose($this->handle);
                return false;
            }
            fclose($this->handle);
            return true;
        }
        return false;
    }

    
    public function read($nl2br = false)
    {
        if ($read = fread($this->handle, filesize($this->file)))
        {
            if ($nl2br == true)
            {
                fclose($this->handle);
                return nl2br($read);
            }

            fclose($this->handle);
            return $read;
        }
        else
        {
            fclose($this->handle);
            return false;
        }
    }
   
    public function delete()
    {
        fclose($this->handle);

        if (file_exists($this->file))
        {
            if (unlink($this->file))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

// ++++++++++++
// | END CLASS|
// ++++++++++++

}

/* HOW TO USE 

// Sample text
$text = <<<file
Name: Ashwin Pathak
Age: 15 years
Country: India
Blog Url: http://codicious.blogspot.com
file;

// Creating an Instance
$file = new File;

// writing
if ($file->load('text.txt')->write($text))
{
    echo '<b>Status: </b>Wrote successfully!<br /><br />';
}

// reading
if ($read = $file->load('text.txt')->read(true))
{
    echo '<b>Status: Reading</b><br />' . $read;
}

// deleting
if ($file->load('text.txt')->delete())
{
    echo '<br /><br /><b>Status: </b>Deleted successfully!';
}
*/