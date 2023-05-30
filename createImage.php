<?php
function hex2rgb( $colour ) { 
    if ( $colour[0] == '#' ) { 
        $colour = substr( $colour, 1 ); 
    } 
    if ( strlen( $colour ) == 6 ) { 
        list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] ); 
    } elseif ( strlen( $colour ) == 3 ) { 
        list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] ); 
    } else { 
        return false; 
    } 
    $r = hexdec( $r ); 
    $g = hexdec( $g ); 
    $b = hexdec( $b ); 
    return array( 'red' => $r, 'green' => $g, 'blue' => $b ); 
} 

	define("WIDTH", 640);
    define("HEIGHT", 640);

    $data = explode(",",$_GET['values']);
	
	for ($i = 0; $i < count($data); $i++)
	{
		$piegraph_data[$i] = $data[$i]/ array_sum($data) * 100;
	}
	
    $img = imagecreate(WIDTH, HEIGHT);

    $background = $white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
    $black = imagecolorallocate($img, 0, 0, 0);
	$config_colors = array(
		"#5B8FF9",
		"#61DDAA",
		"#65789B",
		"#F6BD16",
		"#7262fd",
		"#78D3F8",
		"#9661BC",
		"#F6903D",
		"#008685",
		"#F08BB4",
	);
	$color = array(
	 imagecolorallocate($img, hex2rgb($config_colors[0])['red'], hex2rgb($config_colors[0])['green'], hex2rgb($config_colors[0])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[1])['red'], hex2rgb($config_colors[1])['green'], hex2rgb($config_colors[1])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[2])['red'], hex2rgb($config_colors[2])['green'], hex2rgb($config_colors[2])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[3])['red'], hex2rgb($config_colors[3])['green'], hex2rgb($config_colors[3])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[4])['red'], hex2rgb($config_colors[4])['green'], hex2rgb($config_colors[4])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[5])['red'], hex2rgb($config_colors[5])['green'], hex2rgb($config_colors[5])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[6])['red'], hex2rgb($config_colors[6])['green'], hex2rgb($config_colors[6])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[7])['red'], hex2rgb($config_colors[7])['green'], hex2rgb($config_colors[7])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[8])['red'], hex2rgb($config_colors[8])['green'], hex2rgb($config_colors[8])['blue'])
	,imagecolorallocate($img, hex2rgb($config_colors[9])['red'], hex2rgb($config_colors[9])['green'], hex2rgb($config_colors[9])['blue'])
	);
    $center_x = (int)WIDTH/2;
    $center_y = (int)HEIGHT/2;

    imagerectangle($img, 0, 0, WIDTH-1, HEIGHT-1, $black);

    $last_angle = 0;
	
    for ($i = 0; $i < count($piegraph_data); $i++)
	{
        $arclen = (360 * $piegraph_data[$i]) / 100;
		if ($arclen > 0)
		{
			imagefilledarc($img,
						   $center_x,
						   $center_y,
						   WIDTH-20,
						   HEIGHT-20,
						   $last_angle,
						   ($last_angle + $arclen),
						   $color[$i],
						   IMG_ARC_EDGED);
		}
        $last_angle += $arclen;
    }
    header("Content-Type: image/png");
    imagepng($img);
