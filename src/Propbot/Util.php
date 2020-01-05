<?php

namespace Propbot;

class Util
{
    /**
     * Converts C# /Date(123456789)/ date formats into a Unix timestamp.
     *
     * @return mixed
     */
    public static function netDate(string $date)
    {
        preg_match("#/Date\((\d{10})\d{3}(.*?)\)/#", $date, $match);

        return $match[1];
    }

    /**
     * Builds a link to add a calendar event to Google Calendar.
     *
     * @return string
     */
    public static function calendarLink(string $text, string $location, string $description, string $start, string $end)
    {
        $params = [
            'action' => 'TEMPLATE',
            'output' => 'xml',
            'sf' => 'true',
            'text' => $text,
            'details' => $description,
            'location' => $location,
            'dates' => sprintf('%s/%s', date('Ymd\\THis', $start), date('Ymd\\THis', $end)),
        ];

        return sprintf('http://www.google.com/calendar/render?%s', http_build_query($params));
    }
}
