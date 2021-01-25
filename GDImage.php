<?php
class GDImage
{	
	private $img;
	private $width;
	private $height;

	/**
	*	Create new GDImage with a specified size or a default size of 1x1 pixels. 
	*	@param int $width
	*	@param int $height 
	*/
	function __construct($width=1,$height=1)
	{
		$this->width = intval($width);
		$this->height = intval($height);
		$this->clear();
	}
	function __destruct()
	{
		$this->reset();
	}
	
	private function reset()
	{
		if($this->isImage()) imagedestroy($this->img);
	}
	/**
	*	Delete the old image and replace with blank one.
	*/	
	public function clear()
	{
		$this->reset();
		$this->setImage(imagecreatetruecolor($this->width,$this->height));
	}
	
	/**
	*	Proper way to set the image resource. This function will validate the image and clean the memory from the old one before 
	*	setting the new image in its place.  It will also maintain the integrity of the image size and other class metadata.
	*	@param resource $width
	*/	
	public function setImage($resource)
	{
		if(!$this->isImage($resource)) throw new Exception("Invalid image resource");
		$this->reset();
		$this->img = $resource;		
		$this->refreshDimensions();	
	}

	/**
	*	Proper way to set the image resource. This function will validate the image and clean the memory from the old one before 
	*	setting the new image in its place.  It will also maintain the integrity of the image size and other class metadata.
	*	@param resource $imgData
	*	@return bool 	Return true/false based on whether the class or passed resource is an image 
	*/		
	public function isImage($imgData=null)
	{
		if(empty($imgData)) $imgData = $this->img;
		
		return (is_resource($imgData) && get_resource_type($imgData) === "gd");		
	}
	
	/**
	*	Update the image size information to ensure that the metadata is up-to-date. Rarely/never needed by end-user
	*/	
	public function refreshDimensions()
	{
		if(!$this->isImage()) throw new Exception("Image is corrupt/invalid"); //TODO
		$this->width = imagesx($this->img);
		$this->height = imagesy($this->img);
	}
	
	/**
	*	Load an image from  a  file of specified name. 
	*	@param string $filename Relative/Absolute location of image file
	*	@exception Throws exception if image type not supported
	*	@see save()
	*	@see getImage()
	*/	
	public function load($filename)
	{
		$this->reset();
		$matches = array();
		if(preg_match("/.*\.(gif|jpe?g|png|bmp)$/i",$filename,$matches))
			$this->setImage($this->loadImageFromFile($filename, $this->getTypeFromExtension($matches[1])));
	}
	
	/**
	*	Save image to disk in specified format.
	*	@param string $filename Relative/Absolute location of image file
	*	@param int $type A GD constant to be used as the export type 
	*	@exception Throws exception if image type not supported
	*	@see load()
	*	@see getImage()
	*/		
	public function save($filename, $type=IMAGETYPE_JPEG)
	{
		if(!(imagetypes() && $type)) throw new Exception("Type not supported in PHP Installation ($type)");		
		switch($type)
		{	
			case IMAGETYPE_GIF     : return imagegif($this->img,$filename);
            case IMAGETYPE_JPEG    : return imagejpeg($this->img,$filename);
            case IMAGETYPE_PNG     : return imagepng($this->img,$filename);
			case IMAGETYPE_WBMP    : return imagewbmp($this->img,$filename);
			default : throw new Exception("Type not currently supported in GDImage Library ($type)");
		}			
	}

	/**
	*	Render the image in the response. This is useful for live thumbnails.
	*	@param int $type A GD constant to be used as the export type (default:IMAGETYPE_JPEG)
	*	@param bool $includeHeaders Output headers along with the image (note:without headers the image won't display properly)
	*	@exception Throws exception if image type not supported
	*	@see save()
	*/		
	public function getImage($type=IMAGETYPE_JPEG,$includeHeaders=true)
	{
		if(!(imagetypes() && $type)) throw new Exception("Type not supported in PHP Installation ($type)");
		if($includeHeaders) header("Content-Type: ". image_type_to_mime_type($type));
		switch($type)
		{	
			case IMAGETYPE_GIF     : return imagegif($this->img);
            case IMAGETYPE_JPEG    : return imagejpeg($this->img);
            case IMAGETYPE_PNG     : return imagepng($this->img);
			case IMAGETYPE_WBMP    : return imagewbmp($this->img);
			default : throw new Exception("Type not currently supported in GDImage Library ($type)");
		}
	}
	
	private function loadImageFromFile($filename,$type)
	{
		if(!(imagetypes() && $type)) throw new Exception("Type not supported in PHP Installation ($type)");
		
		switch($type)
		{	
			case IMAGETYPE_GIF     : return imagecreatefromgif($filename);
            case IMAGETYPE_JPEG    : return imagecreatefromjpeg($filename);
            case IMAGETYPE_PNG     : return imagecreatefrompng($filename);
			case IMAGETYPE_WBMP     : return imagecreatefromwbmp($filename);
			default : throw new Exception("Type not currently supported in GDImage Library ($type)");
		}	
	}
	
	/*
		ALTERATIONS
	*/
	/**
	*	Get a resized version of class image using a percentage scale.	
	*	@param float $factor Percent (>0) change in size. Values greater than 1 increase size.
	*	@see resize()
	*/	
	public function getResize($factor)
	{
		$factor = floatval($factor);
		
		$tempX = max(intval($this->width * $factor),1);
		$tempY = max(intval($this->height * $factor),1);
		
		$temp = imagecreatetruecolor($tempX, $tempY);
		imagecopyresized($temp, $this->img, 0, 0, 0, 0, $tempX, $tempY, $this->width, $this->height);
		return $temp;
	}
	/**
	*	Get a resized version of class image using limits. Cannot resize larger. 	
	*	@param int $xMax Maximum width of new image
	*	@param int $yMax Maximum height of new image
	*	@see resizeUseMax()
	*/
	public function getResizeUseMax($xMax=null,$yMax=null)
	{
		if($xMax === null && $yMax === null) return;
		
		$tempX = $this->width;
		$tempY = $this->height;
		if($tempX > $xMax)
		{
		   $tempY = $tempY * ($xMax / $tempX);
		   $tempX = $xMax;
		}
		if($tempY > $yMax)
		{
		   $tempX = $tempX * ($yMax / $tempY);	
		   $tempY = $yMax;
		}		
		$tempX = intval($tempX); $tempY = intval($tempY);
		
		if($tempX >= $this->width && $tempY >= $this->height) return;
				
		$temp = imagecreatetruecolor($tempX, $tempY);
		imagecopyresized($temp, $this->img, 0, 0, 0, 0, $tempX, $tempY, $this->width, $this->height);	
		return $temp;
	}
		
	/**
	*	Return a skewed version of the image. Default behavior is to skew by pulling on the right corner downward, negating the factor will flip this to the left side. 
	*	To perform a horizontal skew (pull from the top right to the right, or negate factor for left) simply change the necessary flag. Background color fills the 'whitespace' left by skew action'
	*	@param float $factor	The degree of skew. Essentially, the angle of skew from the right side
	*	@param  bool $isVerticalSkew	Should the skew be vertical, sloping the top and bottom edges
	*	@param int $backgroundColor	Fill Color (0-255) to be used as whitespace filler (White and greys currently supported with 1 color value)
	*	@return resource The skewed version of the class image
	*	@see skew()
	*	@todo Allow for any color background. 
	*	@todo Make the transparency remember so exporting png's will allow for transparent background where jpeg will take class background color
	*/
			
	public function getSkew($factor,$isVerticalSkew=true,$backgroundColor=255)
	{
		$backgroundColor = max(min(255, $backgroundColor),0);
		$imgdest = null;
		if($isVerticalSkew) $imgdest = imagecreatetruecolor($this->width, $this->height+($this->width*abs($factor)));
		else $imgdest = imagecreatetruecolor($this->width+($this->height*abs($factor)), $this->height);
		//echo $this->width . " and " . ($this->width+($this->width*abs($factor))) ."\n";
		
		$trans = imagecolorallocate($imgdest,$backgroundColor,$backgroundColor,$backgroundColor);
		imagefill($imgdest,0,0,$trans);

		//Do I go from right to left or left to right, and top to bottom for y
		$xStart = ( $factor >= 0 ) ? 0:$this->width;
		$xStop  = ( $factor >= 0 ) ? $this->width:0;
		$xIncr  = ( $factor >= 0 ) ? 1 : -1;
		
		$yStart = ( $factor >= 0 ) ? 0:$this->height;
		$yStop  = ( $factor >= 0 ) ? $this->height:0;
		$yIncr  = ( $factor >= 0 ) ? 1 : -1;
		$step = 0;
		$factor = abs($factor);

		for($x=$xStart ; $x != $xStop ; $x += $xIncr)
		{
			for($y=$yStart; $y != $yStop ; $y += $yIncr)
			{
				$myX = $isVerticalSkew ? $x : $x + $step;
				$myY = $isVerticalSkew ? $y + $step : $y;
				
			//	if($myX > $this->width+($this->width*abs($factor))) echo "X failed at $myX\n";
			//	if($myY > $this->height+($this->height*abs($factor)))echo "Y failed at $myY\n";
				imagecopy($imgdest, $this->img, $myX, $myY, $x, $y, 1, 1);
				if(!$isVerticalSkew) $step += $factor;
			}
			if($isVerticalSkew) $step += $factor;
			else($step = 0);
		}
	
		imagecolortransparent($imgdest,$trans);
		return $imgdest;
	}
	
	/**
	*	Resizes current image
	*	@see getResize()
	*/
	public function resize($factor)
	{
		$this->setImage($this->getResize($factor));
	}	
	/**
	*	Resizes current image using width/height limits.
	*	@see getResizeUseMax()
	*/	
	public function resizeUseMax($maxX,$maxY)
	{
		$this->setImage($this->getResizeUseMax($maxX,$maxY));		
	}
	/**
	*	Skews current image 
	*	@see getSkew()
	*/		
	public function skew($factor,$isVerticalSkew=true,$backgroundColor=255)
	{
		$this->setImage($this->getSkewed($factor,$isVerticalSkew,$backgroundColor));
	}
	
	
	/* FILTERS */
	
	
	/**
	*	 Applies a brightness filter. 
	*/	
	public function brighten($factor)
	{
		imagefilter($this->img, IMG_FILTER_BRIGHTNESS, $factor);
	}
	
	private function getTypeFromExtension($extension)
	{
		switch($extension)
        {
            case ('gif'): 	return (IMAGETYPE_GIF);
            case ('jpg'):	return (IMAGETYPE_JPEG);
			case ('jpeg'): 	return (IMAGETYPE_JPEG);
            case ('png'): 	return (IMAGETYPE_PNG);
            case ('swf'): 	return (IMAGETYPE_SWF);
            case ('psd'): 	return (IMAGETYPE_PSD);
            case ('wbmp'): 	return (IMAGETYPE_WBMP);
            case ('xbm'): 	return (IMAGETYPE_XBM);
            case ('tiff'): 	return (IMAGETYPE_TIFF_II);
            case ('tiff'): 	return (IMAGETYPE_TIFF_MM);
            case ('aiff'): 	return (IMAGETYPE_IFF);
            case ('jb'): 	return (IMAGETYPE_JB2);
            case ('jpc'): 	return (IMAGETYPE_JPC);
            case ('jp'):	return (IMAGETYPE_JP2);
            case ('jpf'):	return (IMAGETYPE_JPX);
            case ('swc'): 	return (IMAGETYPE_SWC);
            default : 		return false;
        }
	}
}
?>