<?php

class koka_provider
{
    function factor()
    {
        return .8;
    }

    function updateFrequency()
    {
        // 12 hours
        return 12*3600;
    }

    function getEvents()
    {
        return [];
    }
}