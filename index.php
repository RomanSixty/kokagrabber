<?php
error_reporting ( E_ALL );
ini_set ( 'display_errors', 1 );

define ( 'DIR', dirname ( __FILE__ ) );

include ( DIR . '/lib/koka_update.php' );
include ( DIR . '/lib/koka_hilight.php' );
include ( DIR . '/lib/koka_view.php' );

$koka = new koka_update();

$koka -> update();


$view = new koka_view();

$view -> show();