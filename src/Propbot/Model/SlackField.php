<?php

namespace Propbot\Model;

/**
 * Class for constructing a simple Slack attachment.
 */
class SlackField
{
    /**
     * @param bool $short
     *
     * @return array|bool
     */
    public static function create(string $title, ?string $value, $short = true)
    {
        if (empty($value)) {
            return false;
        }

        return [
            'title' => $title,
            'short' => $short,
            'value' => $value,
        ];
    }
}
