<?php

class tickets36 extends koka_provider
{
    function updateFrequency()
    {
        // 24 hours
        return 24 * 3600;
    }

    function getEvents()
    {
        $events = [];

        $url = 'https://www.36-tickets.de/produkte.json';

        try {
            $ch = curl_init();

            curl_setopt ( $ch, CURLOPT_URL, $url );

            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );

            curl_setopt ( $ch, CURLOPT_ENCODING, 'identity' );
            curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0' );

            curl_setopt ( $ch, CURLOPT_HTTPHEADER, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding: gzip, deflate, br, zstd',
                'Accept-Language: en-US,de-DE;q=0.8,en;q=0.5,de;q=0.3',
                'Content-Type: application/json',
                'DNT: 1'
            ]);

            $all_events = curl_exec ( $ch );

            curl_close ( $ch );
        }
        catch ( Exception $e ) {
            return [];
        }

        if ( empty ( $all_events ) )
        {
            echo 'no 36-tickets events found';
            return [];
        }

        $data = json_decode ( $all_events, true );

        if ( empty ( $data ) )
        {
            echo 'error decoding 36-tickets JSON';
            return [];
        }

        if ( empty ( $data [ 'products' ] ) )
            return [];

        foreach ( $data [ 'products' ] as $event )
        {
            if ( $event [ 'city' ] != 'Berlin' || !empty ( $event [ 'sold_out_status' ] ) )
                continue;

            list ( $y, $m, $d ) = explode ( '-', $event [ 'valid_start_on' ] );

            $events [ $event [ 'id' ]] = [
                'link'      => 'https://www.36-tickets.de' . $event [ 'url' ],
                'artist'    => utf8_encode ( $event [ 'title' ] ),
                'eventdate' => $d . '.' . $m . '.' . $y
            ];
        }

        return $events;
    }
}