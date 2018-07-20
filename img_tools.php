<?php
/*	Модуль, содержащий функции для удобной работы с изображениями.
	Требует imagemagick.
*/


/*	Проверить изображение на валидность. Поддерживается масса форматов (примерно ~200).
		$content - бинарное содержимое
	Вернет true, если изображение корректное.	
*/
function img_valid($content)
{
	try {
		$im = new Imagick();
		$im->readImageBlob($content);
		$size = $im->getImageGeometry();
		$res = (bool)$size['width'];
	} catch (Exception $e) {
		return false;
	}
	return $res;
}

/*	Генератор картинок: создает уникальные высококачественные изображения.
		$save_to - путь для сохранения картинки. Формат сохраняемого изображения задаётся через расширение файла (.jpg, .png или .gif).
			В роли $save_to также может быть передан готовый объект типа Imagick - он будет модифицирован, сохранения файла при этом не производится.
		$width, $height - размеры изображения
		$density - плотность зарисовки (0.01 - очень низкая, 0.15 - средняя, 1.0 - очень высокая)
		$blur_level - степень сглаживания (0 - отключить, 3 - средняя, 5 - сильная, 10 - очень сильная). Чем выше - тем медленнее.
		$distort - разрешить искажения
		$quality - качество сохраняемого изображения
		$background_color - Фоновый цвет в формате RGBA ('#aabbccff'), где последняя компонента это прозрачность.
	Вернет true при успехе.
*/
function img_generate(&$save_to, $width = 600, $height = 600, $density = 0.15, $blur_level = 3, $distort = false, $quality = 90, $background_color = '#ffffffFF')
{
	static $system_fonts = [];
	if (!$system_fonts)
	{$system_fonts = imagick::queryFonts("*");}
	if (is_a($save_to, 'Imagick'))
	{
		$im = &$save_to;
		$is_obj = true;
	}
		else
	{$im = new Imagick();}
	$im->newImage($width, $height, new ImagickPixel($background_color));
	$label_count = ceil(sqrt($width*$height)*$density);
	$colors = explode('|', '003366|336699|3366CC|003399|000099|0000CC|000066|006666|006699|0099CC|0066CC|0033CC|0000FF|3333FF|333399|669999|009999|33CCCC|00CCFF|0099FF|0066FF|3366FF|3333CC|666699|'.'339966|00CC99|00FFCC|00FFFF|33CCFF|3399FF|6699FF|6666FF|6600FF|6600CC|339933|00CC66|00FF99|66FFCC|66FFFF|66CCFF|99CCFF|9999FF|9966FF|9933FF|9900FF|006600|00CC00|00FF00|'.	'66FF99|99FFCC|CCFFFF|CCCCFF|CC99FF|CC66FF|CC33FF|CC00FF|9900CC|003300|009933|33CC33|66FF66|99FF99|CCFFCC|FFFFFF|FFCCFF|FF99FF|FF66FF|FF00FF|CC00CC|660066|336600|009900|'.	'66FF33|99FF66|CCFF99|FFFFCC|FFCCCC|FF99CC|FF66CC|FF33CC|CC0099|993399|333300|669900|99FF33|CCFF66|FFFF99|FFCC99|FF9999|FF6699|FF3399|CC3399|990099|666633|99CC00|CCFF33|'.'FFFF66|FFCC66|FF9966|FF6666|FF0066|CC6699|993366|999966|CCCC00|FFFF00|FFCC00|FF9933|FF6600|FF5050|CC0066|660033|996633|CC9900|FF9900|CC6600|FF3300|FF0000|CC0000|990033|'.'663300|996600|CC3300|993300|990000|800000|993333');
	$sz = 7;
	$colors = array_slice($colors,rand(0,count($colors)-1-$sz),$sz);
	for ($i=1;$i<=$label_count;$i++)
	{
		$a = range('a','z');
		shuffle($a);
		$text = implode('', array_slice($a,0,rand(1,13)));
		$color = $colors[array_rand($colors)];
		$font = $system_fonts[rand(0,count($system_fonts)-1)];
		$draw = new \ImagickDraw();
		$draw->setFont($font);
		$draw->setFontSize(rand(32,140));
		$draw->setFillColor('#'.$color);
		$draw->translate(floor($width/2), floor($height/2));
		$draw->rotate(rand(0,360));
		$draw->annotation(floor(rand(-$width/2, $width/2)), floor(rand(-$height/2,$height/2)), $text);
		$im->drawImage($draw);
		if ($blur_level && ($i % floor($label_count/2+1))==0)
		{$im->blurImage(0,$blur_level);}
	}
	if ($distort)
	{$im->distortImage(imagick::DISTORTION_SHEPARDS, [rand(0,$width), rand(0,$height), rand(0,$width), rand(0,$height), rand(0,$width), rand(0,$height), rand(0,$width), rand(0,$height)], false);}
	if (!$is_obj)
	{
		preg_match('#\.(\w+)$#', $save_to, $m);
		$ext = strtolower($m[1]);
		if ($ext=='jpg') $ext = 'jpeg';
		$im->setImageFormat($ext);
		$im->setImageCompressionQuality($quality);
		return $im->writeImage($save_to);
	}
	return true;
}

// Случайный яркий и выразительный цвет. Спектр возвращаемых цветов довольно велик (сотни тысяч).
function img_rcolor()
{
	$prev_diff = 0;
	$count = 50;
	for ($j=0;$j<$count;$j++)
	{
		$xr = rand(0x00, 0xff);
		$xg = rand(0x00, 0xff);
		$xb = rand(0x00, 0xff);
		$diff = abs(max($xr,$xg,$xb)-min($xr,$xg,$xb));
		if ($diff>$prev_diff)
		{
			$r = $xr;
			$g = $xg;
			$b = $xb;
		}
		$prev_diff = $diff;
	}
	return str_pad(dechex($r),2,'0',STR_PAD_LEFT).str_pad(dechex($g),2,'0',STR_PAD_LEFT).str_pad(dechex($b),2,'0',STR_PAD_LEFT);
}

/*	Открыть изображение.
		$filename - путь к файлу
	Вернет объект Imagick.
*/
function img_open($filename)
{return new imagick($filename);}

/*	Сохранить изображение.
		$im - объект Imagick
		$filename - путь к файлу
*/
function img_save($im, $filename)
{return $im->writeImage($filename);}

/*	Получить размеры изображения.
		$im - объект Imagick
	Вернет массив вида:
		['width' => 640, 'height' => 480]
*/
function img_size($im)
{
	$sz = $im->getImageGeometry();
	extract($sz);
	return compact('width', 'height');
}

/*	Ресайз изображения в произвольные размеры.
		$im - объект Imagick
		$width - ширина
		$height - высота
*/
function img_resize($im, $width, $height)
{$im->resizeImage($width, $height, imagick::FILTER_BOX, 1.0, false);}

/*	Ресайзнуть изображение так, чтобы оно уместилось в заданную ширину с сохранением пропорций.
		$im - объект Imagick
		$width - требуемая ширина
*/
function img_resize_w($im, $width)
{
	$sz = $im->getImageGeometry();
	img_resize($im, $width, round($width*($sz['height']/$sz['width'])));
}

/*	Ресайзнуть изображение так, чтобы оно уместилось в заданную высоту с сохранением пропорций.
		$im - объект Imagick
		$height - требуемая высота
*/
function img_resize_h($im, $height)
{
	$sz = $im->getImageGeometry();
	img_resize($im, round($height*($sz['width']/$sz['height'])), $height);
}

/*	Изменить качество изображения. Применимо к JPG.
		$im - объект Imagick
		$quality - число в интервале 0...100
*/
function img_quality($im, $quality)
{$im->setImageCompressionQuality($quality);}

/*	Обрезка изображения.
		$im - объект Imagick
		$x - координата X левого верхнего угла обрезаемой части
		$y - координата Y левого верхнего угла обрезаемой части
		$width - ширина остающейся части
		$height - высота остающейся части
*/
function img_crop($im, $x, $y, $width, $height)
{$im->cropImage($width,$height,$x,$y);}

/*	Нанести отдельное изображение ("ватермарку") поверх фонового изображения ($im).
		$im - объект Imagick фона
		$wm - объект Imagick ватермарки
		$params - массив параметров:
			'pos' - позиция. По-умолчанию 'center'. Возможные значения: 
				'lefttop', 'top', 'righttop', 'left', 'center', 'right', 'leftbottom', 'bottom', 'rightbottom'
			'off_x' - смещение ватермарки относительно позиции (всегда положительное)
			'off_y'
			'width' - в каком размере нанести ватермарку. При значении 0 она будет своих размеров. При значении -1 она будет нанесена по размерам исходного изображения (например, чтобы "смартфон" показывал "фотку").
			'height'
			'angle' - угол поворота ватермарки (0...360), по-умолчанию 0
*/
function img_draw($im, $wm, $params = [])
{
	extract($params);
	$gravity = ['lefttop' => \Imagick::GRAVITY_NORTHWEST, 'top' => \Imagick::GRAVITY_NORTH, 'righttop' => \Imagick::GRAVITY_NORTHEAST, 'left' => \Imagick::GRAVITY_WEST, 'center' => \Imagick::GRAVITY_CENTER, 'leftbottom' => \Imagick::GRAVITY_SOUTHWEST, 'bottom' => \Imagick::GRAVITY_SOUTH, 'rightbottom' => \Imagick::GRAVITY_SOUTHEAST, 'right' => \Imagick::GRAVITY_EAST];
	if ($gravity[$pos]=='') $pos = 'center';
	$pos = $gravity[$pos];
	if ($width==-1 || $height==-1)
	{
		$sz = $im->getImageGeometry();
		$width = $sz['width']; $height = $sz['height'];
	}
	elseif (!$width || !$height)
	{
		$sz = $wm->getImageGeometry();
		$width = $sz['width']; $height = $sz['height'];
	}
	$draw = new ImagickDraw();
	$draw->setGravity($pos);
	if ($angle) $draw->rotate($angle);
    $draw->composite(\Imagick::COMPOSITE_DEFAULT, (int)$off_x, (int)$off_y, (int)$width, (int)$height, $wm);
	$im->drawImage($draw);
	$im->setImagePage(0, 0, 0, 0); 
}

/*	Нанести надпись на изображение.
		$im - объект Imagick
		$params - массив параметров:
			'text' - текст надписи
			'pos' - позиция. Возможные значения: 
				'lefttop', 'top', 'righttop', 'left', 'center', 'right', 'leftbottom', 'bottom', 'rightbottom'
				(по-умолчанию 'center')
			'font' - имя шрифта (по-умолчанию 'Arial-Bold'). Также можно задать путь к .ttf файлу.
			'size' - размер шрифта (по-умолчанию 36)
			'color' - цвет надписи (по-умолчанию '#ffcffa')
			'off_x' - смещение надписи, может быть отрицательным (чтобы двигать в противоположную сторону)
			'off_y'
			'shadow_x' - смещение тени текста (если оба равны 0, то тень не рисуется). По-умолчанию 2.
			'shadow_y'
			'angle' - угол наклона надписи (0...360)
	Вернет размеры сделанной надписи в виде массива, например [200, 100].
*/
function img_text($im, $params = [])
{
	extract($params);
	if (!strlen($text)) $text = 'Sample Text';
	if ($color=='') $color = '#ffcffa';
	if ($font=='') $font = 'Arial-Bold';
	if (!$size) $size = 36;
	if (!isset($shadow_x) && !isset($shadow_y))
	{$shadow_x = 2; $shadow_y = 2;}
	$gravity = ['lefttop' => \Imagick::GRAVITY_NORTHWEST, 'top' => \Imagick::GRAVITY_NORTH, 'righttop' => \Imagick::GRAVITY_NORTHEAST, 'left' => \Imagick::GRAVITY_WEST, 'center' => \Imagick::GRAVITY_CENTER, 'leftbottom' => \Imagick::GRAVITY_SOUTHWEST, 'bottom' => \Imagick::GRAVITY_SOUTH, 'rightbottom' => \Imagick::GRAVITY_SOUTHEAST, 'right' => \Imagick::GRAVITY_EAST];
	if ($gravity[$pos]=='') $pos = 'center';
	$pos = $gravity[$pos];
	if (preg_match('#right#i', $pos))
	{
		$shadow_x *= -1;
		$off_x *= -1;
	}
	if (preg_match('#bottom#i', $pos))
	{
		$shadow_y *= -1;
		$off_y *= -1;
	}
	if ($shadow_x || $shadow_y)
	{
		$draw = new ImagickDraw();
		$draw->setFont($font);
		$draw->setFontSize($size);
		$draw->setFillColor('#000');
		$draw->setGravity($pos);
		$im->annotateImage($draw, $off_x+$shadow_x, $off_y+$shadow_y, (int)$angle, $text);
	}
	$draw = new ImagickDraw();
	$draw->setFont($font);
	$draw->setFontSize($size);
	$draw->setFillColor($color);
	$draw->setGravity($pos);
	$fm = $im->queryFontMetrics($draw, $text);
	$im->annotateImage($draw, (int)$off_x, (int)$off_y, (int)$angle, $text);
	$im->setImagePage(0, 0, 0, 0); 
	return [$fm['textWidth'], $fm['characterHeight']];
}

/*	Поворачивает картинку ($im), делает на ней надпись по центру и обрезает место с надписью, оставляя отступы.
	Пригодится для шапок, логотипов, и прочего.
		$im - объект Imagick (фоновая картинка)
		$params - массив параметров:
			'bg_angle' - угол наклона фоновой картинки (0...360)
			'bg_color' - цвет фона, например "#ffffff" (при вращении картинки возникают пустые области - цвет их заполняет)
			'text' - текст
			'font' - имя шрифта, например 'Arial-Bold'. Также можно задать путь к .ttf файлу.
			'size' - размер шрифта, например 15
			'color' - цвет текста, например "#000"
			'max_width' - максимальная ширина получаемого изображения
			'add_top' - отступы сверху, снизу, слева, справа
			'add_bottom'
			'add_left'
			'add_right'
			'shadow_x' - смещение тени текста (если оба равны 0, то тень не рисуется). По-умолчанию 2.
			'shadow_y'
*/
function img_logo($im, $params)
{
	extract($params);
	if ($bg_angle) $im->rotateImage($bg_color, $bg_angle);
	$im->setImagePage(0, 0, 0, 0); // видимо обнуляет какие-то смещения, т.к. без него не работает (похоже что это аналогично "+repage")
	$pos = 'center';
	$wh = img_text($im, compact('text', 'font', 'size', 'color', 'pos', 'shadow_x', 'shadow_y'));
	$sz = $im->getImageGeometry();
	$w2 = $sz['width']; $h2 = $sz['height'];
	list($w, $h) = $wh;
	if (!$max_width) $max_width = 9999;
	$im->cropImage(min($w + $add_right + $add_left, $max_width), $h+$add_bottom+$add_top, $w2/2 - $w/2 - $add_left, $h2/2 - $h/2 - $add_top);
	$im->setImagePage(0, 0, 0, 0); 
}

/*	Добавить изображению текстурированную рамку и/или скруглить углы.
		$im - объект Imagick
		$params - массив параметров:
			'add_left' - размеры отступов (слева, справа, сверху, снизу)
			'add_right'
			'add_top'
			'add_bottom'
			'roundness' - скругленность углов (0....350)
			'tile_src' - (опционально) путь к файлу, которым замостится рамка. Если не задан, то возникнет альфа-канал и вместо рамки будет прозрачность.
			'tile_rand' - придавать случайный скролл текстуре рамки (true/false)
*/
function img_frame(&$im, $params = [])
{
	extract($params);
	$im->setImageFormat('png');
	$sz = $im->getImageGeometry();
	list($w, $h) = [$sz['width'], $sz['height']];
	
	// рисуется белый скругленный прямоугольник на черном фоне
	$draw = new ImagickDraw();
	$draw->setFillColor('#fff');
	$draw->setStrokeOpacity(0);
	$draw->setStrokeWidth(0);
	$draw->setStrokeWidth(0);
	$draw->roundRectangle((int)$add_left, (int)$add_top, $w-$add_right, $h-$add_bottom, (int)$roundness, (int)$roundness);
	$mask = new Imagick();
	$mask->newImage($w, $h, '#000');
	$mask->drawImage($draw);
	
	// нарисованная фигура берется в роли альфаканала
	$base_opacity = clone $im;
	$base_opacity->setImageAlphaChannel(Imagick::ALPHACHANNEL_EXTRACT);
	$base_opacity->compositeImage($mask, Imagick::COMPOSITE_MULTIPLY, 0 ,0);
	$im->compositeImage($base_opacity, Imagick::COMPOSITE_COPYOPACITY, 0 ,0);
	
	if ($tile_src!='')
	{
		$tile = new Imagick($tile_src);
		if ($tile_rand) 
		{
			$xx = rand(0,200);
			$yy = rand(0,200);
		}
		$im2 = new Imagick();
		$im2->newImage($w+$xx, $h+$yy, '#fff');
		$im2->setImageFormat('png');
		$tiled_bg = $im2->textureImage($tile);
		$p = ($tile_rand?['pos'=>'rightbottom']:[]);
		img_draw($tiled_bg, $im, $p);
		$im = $tiled_bg;
		if ($tile_rand) $im->cropImage($w, $h, $xx, $yy);
	}
}

/*	Повернуть изображение.
		$im - объект Imagick
		$degrees - угол поворота (в градусах). Оптимально: -5...5
		$color - цвет фона
*/
function img_rotate($im, $degrees = -5, $color = '#fff')
{$im->rotateImage($color, $degrees);}

/*	Перевернуть изображение по горизонтали.
		$im - объект Imagick
*/
function img_flop($im)
{$im->flopImage();}

/*	Перевернуть изображение по вертикали.
		$im - объект Imagick
*/
function img_flip($im)
{$im->flipImage();}

/*	Сгладить изображение.
		$im - объект Imagick
		$radius - радиус. Оптимальный интервал: 0.5 ... 1
*/
function img_blur($im, $radius = 1.0)
{$im->blurImage(0, $radius, imagick::CHANNEL_ALL);}

/*	Изменить яркость/контрастность изображения.
		$im - объект Imagick
		$brightness - значение в интервале -100...100
		$contrast - значение в интервале -100...100
*/
function img_bc($im, $brightness, $contrast)
{$im->brightnessContrastImage($brightness, $contrast);}

/*	Эффект: сепия изображения.
		$im - объект Imagick
*/
function img_sepia($im)
{$im->sepiaToneImage(80);}

/*	Эффект: скетч изображения.
		$im - объект Imagick
*/
function img_sketch($im)
{$im->sketchimage(5,1,45);}

/*	Эффект: тиснение изображения.
		$im - объект Imagick
*/
function img_emboss($im)
{$im->embossImage(1,0);}

/*	Эффект: оттенки серого для изображения.
		$im - объект Imagick
*/
function img_gray($im)
{$im->modulateImage(100,0,100);}

/*	Эффект: негатив изображения.
		$im - объект Imagick
*/
function img_negative($im)
{$im->negateImage(false);}

