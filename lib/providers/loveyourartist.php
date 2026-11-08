<?php

class loveyourartist extends koka_provider
{
    function factor()
    {
        return .2;
    }

    function updateFrequency ()
    {
        return 60;
    }

    function getEvents()
    {
        $events = [];

        $url = 'https://prod.loveyourartist.com/api/v1/store/events?pageSize=1000&filter[category][$in]=Concerts%20%26%20Festivals&filter[venue.location][$geoWithin][$centerSphere]=[[13.404954,52.52000659999999],0.007839325190887567]';

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
            echo 'no LYA events found';
            return [];
        }

        $data = json_decode ( $all_events, true );

        if ( empty ( $data ) )
        {
            echo 'error decoding LYA JSON';
            return [];
        }

        foreach ( $data as $event )
        {
            if ( !empty ( $event [ 'canceled' ] ) || $event [ 'venue' ][ 'city' ] != 'Berlin' )
                continue;

            foreach ( $event [ 'schedule' ] as $concert )
            {
                $date = substr ( $concert [ 'startAt' ], 0, 10 );

                list ( $y, $m, $d ) = explode ( '-', $date );

                $events [ $event [ 'slugId' ]] = [
                    'link'      => 'https://loveyourartist.com/de/events/' . $event [ 'slug' ],
                    'artist'    => utf8_encode ( $event [ 'name' ] ),
                    'eventdate' => $d . '.' . $m . '.' . $y
                ];
            }
        }

        return $events;
    }
}