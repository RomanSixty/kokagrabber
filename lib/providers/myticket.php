<?php

class myticket
{
    function getEvents()
    {
        $events = [];

        $data = $this -> getJSON ( 'https://www.myticket.de/de/api/tourfilter/all/berlin/all/all/0/1000' );

        if ( count ( $data [ 'tours' ] ) == 0 )
            return [];

        foreach ( $data [ 'tours' ] as $event )
        {
            list ( $dummy, $tour_id ) = explode ( '#', $event [ 'linkDetailPage' ] );

            // event details must be requested from tour API
            $ev_data = $this -> getJSON ( 'https://www.myticket.de/de/api/tour/' . $tour_id );

            if ( count ( $ev_data [ 'events' ] ) == 0 )
                continue;

            foreach ( $ev_data [ 'events' ] as $concert )
            {
                if ( $concert [ 'venueCity' ] != 'Berlin' )
                    continue;

                $date = substr ( $concert [ 'startDateTime' ], 0, 10 );

                list ( $y, $m, $d ) = explode ( '-', $date );

                $events [ $concert [ 'id' ]] = [
                    'link'      => $concert [ 'link' ],
                    'artist'    => utf8_encode ( $event [ 'artist' ] ),
                    'eventdate' => $d . '.' . $m . '.' . $y
                ];
            }
        }

        return $events;
    }

    private function getJSON ( $url )
    {
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
                'DNT: 1'
            ]);

            $res = curl_exec ( $ch );

            curl_close ( $ch );
        }
        catch ( Exception $e ) {
            return [];
        }

        if ( empty ( $res ) )
        {
            echo 'no MyTicket events found)';
            return [];
        }

        $data = json_decode ( $res, true );

        if ( empty ( $data ) )
        {
            echo 'error decoding MyTicket JSON';
            return [];
        }

        return $data;
    }
}