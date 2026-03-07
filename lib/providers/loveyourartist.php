<?php

class loveyourartist
{
    function updateFrequency()
    {
        // 12 hours
        return 12 * 3600;
    }

    function getEvents()
    {
        $events = [];

        $url = 'https://prod.loveyourartist.com/api/v1/store/search';

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

            curl_setopt ( $ch, CURLOPT_POST, 1 );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ( [ 'maxHits' => 1000, 'query' => 'berlin', 'type' => 'FullSearch' ] ) );

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

        if ( empty ( $data [ 'events' ][ 'hits' ] ) )
            return [];

        foreach ( $data [ 'events' ][ 'hits' ] as $event )
        {
            if ( $event [ 'category' ] != 'Concerts & Festivals' || !empty ( $event [ 'canceled' ] ) || $event [ 'venue' ][ 'city' ] != 'Berlin' )
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