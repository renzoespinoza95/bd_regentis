<?php
 
/*
* File: SimpleImage.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 08/11/06
* Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
*
*/
 
class SimpleImage {
 
   var $image;
   var $image_type;
 
// -- Cargar el archivo de imagen ---------------------

 function load($filename) {
 
      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
 
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
 
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
 
         $this->image = imagecreatefrompng($filename);
      }
   }
   
// -- Guardar la imagen ---------------------------------------

function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
 
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
 
         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {
 
         imagepng($this->image,$filename);
      }
      if( $permissions != null) {
 
         chmod($filename,$permissions);
      }
   }

// -- Imprimir la imagen en la pagina ------------------------------
   
function output($image_type=IMAGETYPE_JPEG) {
 
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
 
         imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {
 
         imagepng($this->image);
      }
   }

// --
   
function getWidth() {
 
      return imagesx($this->image);
   }
   function getHeight() {
 
      return imagesy($this->image);
   }
   function resizeToHeight($height) {
 
      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }
 
   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }
 
   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100;
      $this->resize($width,$height);
   }
 
//=== REDIMENSIONAR LA IMAGEN ===

   function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;
   }
   
// -- Cropping -------------------------------------------------------

function crop(
$crop_x, 
$crop_y, 
$crop_w, 
$crop_h,
$rotacion) 
{

    $new_image = ImageCreateTrueColor( $crop_w, $crop_h );
      
    $this->image = imagerotate($this->image, (int)$rotacion * (-1), 0);  

    imagecopyresampled(
      $new_image, 
      $this->image, 
      0, 
      0, 
  $_POST['x'],
  $_POST['y'],
	$crop_w,
  $crop_h,
  $_POST['w'],
  $_POST['h']
      );
     
      $this->image = $new_image;

}

/*
function crop(
$crop_x, 
$crop_y, 
$crop_w, 
$crop_h,
$rotacion) 
{

    $new_image = ImageCreateTrueColor( $crop_w, $crop_h );
      
    //$new_image = imagerotate($this->image, 0, 0);  
      
      
    
    
    imagecopyresampled(
      $new_image, 
      $this->image, 
      0, 
      0, 
  $_POST['x'],
  $_POST['y'],
	$crop_w,
  $crop_h,
  $_POST['w'],
  $_POST['h']
      );
     
      $this->image = $new_image;

}


 * 
 */ 


   
// extension de la imagen

public function extension_imagen(){
      
      if( $this->image_type == IMAGETYPE_JPEG ) {
 
         return "jpg";
         
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
 
         return "gif";
         
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
 
         return "png";
      }
      
   }
   
// tipo de imagen

   function tipo_de_imagen() {
      return $this->image_type;
   }
 
//+++++++++++++++
//+  FIN CLASS  +
//+++++++++++++++
}
