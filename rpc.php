<?php

error_reporting ( E_ALL );
ini_set ( 'display_errors', 1 );

define ( 'DIR', dirname ( __FILE__ ) );

switch ( $_GET [ 'action' ] )
{
	case 'hilight':
	case 'unhilight':
		include ( DIR . '/lib/koka_hilight.php' );

		$kh = new koka_hilight();
		$kh -> action ( $_GET [ 'id' ], ( $_GET [ 'action' ] == 'unhilight' ) );

		break;
}