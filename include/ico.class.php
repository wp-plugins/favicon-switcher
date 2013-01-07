<?php

class icoTransform {

	var $gd_image_array ;
	
	function icoTransform() {
		$this->gd_image_array = array() ; 
		return true ;
	}
	
	function loadImage($path) {
		$info = pathinfo($path) ; 
		$val = FALSE ; 
		// On importe
		if (strtolower($info['extension'])=="png") {
			$val = @imagecreatefrompng($path); 
		}
		if (strtolower($info['extension'])=="gif") {
			$val = @imagecreatefromgif($path); 
		}
		if (strtolower($info['extension'])=="jpg") {
			$val = @imagecreatefromjpeg($path); 
		}
		if (strtolower($info['extension'])=="bmp") {
			$val = @imagecreatefrombmp($path); 
		}
		
		if ($val!==FALSE) {	
			//Multiple resolution
			$resolution = array(16, 32, 48, 64, 128) ; 
			foreach ($resolution as $r) {
				$imageResized = imagecreatetruecolor($r, $r);   
				imagecopyresampled($imageResized, $val, 0, 0, 0, 0, $r, $r, imagesx($val), imagesy($val));  
				$this->gd_image_array[] = $imageResized  ; 
			}
			return true ;
		}
		
		return false ; 
	}	

	function transformToICO($newPath="") {
	
		foreach ($this->gd_image_array as $key => $gd_image) {

			$ImageWidths[$key]  = ImageSX($gd_image);
			$ImageHeights[$key] = ImageSY($gd_image);
			$bpp[$key]          = ImageIsTrueColor($gd_image) ? 32 : 24;
			$totalcolors[$key]  = ImageColorsTotal($gd_image);

			$icXOR[$key] = '';
			for ($y = $ImageHeights[$key] - 1; $y >= 0; $y--) {
				for ($x = 0; $x < $ImageWidths[$key]; $x++) {
					$argb = $this->GetPixelColor($gd_image, $x, $y);
					$a = round(255 * ((127 - $argb['alpha']) / 127));
					$r = $argb['red'];
					$g = $argb['green'];
					$b = $argb['blue'];

					if ($bpp[$key] == 32) {
						if (isset($icXOR[$key])) {
							$icXOR[$key] .= chr($b).chr($g).chr($r).chr($a);
						} else {
							$icXOR[$key] = chr($b).chr($g).chr($r).chr($a);
						}
					} elseif ($bpp[$key] == 24) {
						if (isset($icXOR[$key])) {
							$icXOR[$key] .= chr($b).chr($g).chr($r);
						} else {
							$icXOR[$key] = chr($b).chr($g).chr($r);
						}
					}

					if ($a < 128) {
						if (isset($icANDmask[$key][$y])) {
							$icANDmask[$key][$y] .= '1';
						} else {
							$icANDmask[$key][$y] = '1';
						}
					} else {
						if (isset($icANDmask[$key][$y])) {
							$icANDmask[$key][$y] .= '0';
						} else {
							$icANDmask[$key][$y] = '0';
						}
					}
				}
				// mask bits are 32-bit aligned per scanline
				while (strlen($icANDmask[$key][$y]) % 32) {
					$icANDmask[$key][$y] .= '0';
				}
			}
			$icAND[$key] = '';
			foreach ($icANDmask[$key] as $y => $scanlinemaskbits) {
				for ($i = 0; $i < strlen($scanlinemaskbits); $i += 8) {
					if (isset($icAND[$key])) {
						$icAND[$key] .= chr(bindec(str_pad(substr($scanlinemaskbits, $i, 8), 8, '0', STR_PAD_LEFT)));
					} else {
						$icAND[$key] = chr(bindec(str_pad(substr($scanlinemaskbits, $i, 8), 8, '0', STR_PAD_LEFT)));
					}
				}
			}
		}

		foreach ($this->gd_image_array as $key => $gd_image) {
			$biSizeImage = $ImageWidths[$key] * $ImageHeights[$key] * ($bpp[$key] / 8);

			// BITMAPINFOHEADER - 40 bytes
			$BitmapInfoHeader[$key]  = '';
			$BitmapInfoHeader[$key] .= "\x28\x00\x00\x00";                              // DWORD  biSize;
			$BitmapInfoHeader[$key] .= $this->LittleEndian2String($ImageWidths[$key], 4);      // LONG   biWidth;
			// The biHeight member specifies the combined
			// height of the XOR and AND masks.
			$BitmapInfoHeader[$key] .= $this->LittleEndian2String($ImageHeights[$key] * 2, 4); // LONG   biHeight;
			$BitmapInfoHeader[$key] .= "\x01\x00";                                      // WORD   biPlanes;
	   		$BitmapInfoHeader[$key] .= chr($bpp[$key])."\x00";                          // wBitCount;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                              // DWORD  biCompression;
			$BitmapInfoHeader[$key] .= $this->LittleEndian2String($biSizeImage, 4);            // DWORD  biSizeImage;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                              // LONG   biXPelsPerMeter;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                              // LONG   biYPelsPerMeter;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                              // DWORD  biClrUsed;
			$BitmapInfoHeader[$key] .= "\x00\x00\x00\x00";                              // DWORD  biClrImportant;
		}


		$icondata  = "\x00\x00";                                      // idReserved;   // Reserved (must be 0)
		$icondata .= "\x01\x00";                                      // idType;       // Resource Type (1 for icons)
		$icondata .= $this->LittleEndian2String(count($this->gd_image_array), 2);  // idCount;      // How many images?

		$dwImageOffset = 6 + (count($this->gd_image_array) * 16);
		foreach ($this->gd_image_array as $key => $gd_image) {
			// ICONDIRENTRY   idEntries[1]; // An entry for each image (idCount of 'em)

			$icondata .= chr($ImageWidths[$key]);                     // bWidth;          // Width, in pixels, of the image
			$icondata .= chr($ImageHeights[$key]);                    // bHeight;         // Height, in pixels, of the image
			$icondata .= chr($totalcolors[$key]);                     // bColorCount;     // Number of colors in image (0 if >=8bpp)
			$icondata .= "\x00";                                      // bReserved;       // Reserved ( must be 0)

			$icondata .= "\x01\x00";                                  // wPlanes;         // Color Planes
			$icondata .= chr($bpp[$key])."\x00";                      // wBitCount;       // Bits per pixel

			$dwBytesInRes = 40 + strlen($icXOR[$key]) + strlen($icAND[$key]);
			$icondata .= $this->LittleEndian2String($dwBytesInRes, 4);       // dwBytesInRes;    // How many bytes in this resource?

			$icondata .= $this->LittleEndian2String($dwImageOffset, 4);      // dwImageOffset;   // Where in the file is this image?
			$dwImageOffset += strlen($BitmapInfoHeader[$key]);
			$dwImageOffset += strlen($icXOR[$key]);
			$dwImageOffset += strlen($icAND[$key]);
		}

		foreach ($this->gd_image_array as $key => $gd_image) {
			$icondata .= $BitmapInfoHeader[$key];
			$icondata .= $icXOR[$key];
			$icondata .= $icAND[$key];
		}
		if ($newPath=="") {
			return $icondata ;
		} else {
			if (is_file($newPath)) {
				unlink($newPath) ;
			}
			return @file_put_contents($newPath, $icondata) ;
		}	
	}
	
	
	static function GetPixelColor(&$img, $x, $y) {
		if (!is_resource($img)) {
			return false;
		}
		return @ImageColorsForIndex($img, @ImageColorAt($img, $x, $y));
	}
	
	static function LittleEndian2String($number, $minbytes=1) {
		$intstring = '';
		while ($number > 0) {
			$intstring = $intstring.chr($number & 255);
			$number >>= 8;
		}
		return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
	}
}

?>