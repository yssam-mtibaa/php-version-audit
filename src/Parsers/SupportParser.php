<?php
declare(strict_types=1);


namespace lightswitch05\PhpVersionAudit\Parsers;


use lightswitch05\PhpVersionAudit\CachedDownload;
use lightswitch05\PhpVersionAudit\DateHelpers;
use lightswitch05\PhpVersionAudit\PhpVersion;

final class SupportParser
{
    public static function run()
    {
        $supportDates = self::parseEol();
        $supportDates = array_merge($supportDates, self::parseSupportedVersions());
        uksort($supportDates, function($first, $second) {
            $firstVersion = PhpVersion::fromString($first . ".0");
            $secondVersion = PhpVersion::fromString($second . ".0");
            return $firstVersion->compareTo($secondVersion);
        });
        return $supportDates;
    }

    /**
     * @return \stdClass[]
     */
    private static function parseSupportedVersions(): array
    {
        $supportDatesByVersion = [];
        $dom = CachedDownload::dom('https://www.php.net/supported-versions.php');
        foreach ($dom->getElementsByTagName('tr') as $row) {
            $class = strtolower($row->getAttribute('class'));
            $cells = $row->getElementsByTagName('td');
            if (!in_array($class, ['security', 'stable']) || count($cells) < 6) {
                // all the rows we are interested in have either security or stable class names
                continue;
            }
            $version = trim($cells[0]->textContent);
            if (PhpVersion::fromString($version . ".0")) {
                $activeDate = DateHelpers::fromJMYToISO8601(trim($cells[3]->textContent));
                $securityDate = DateHelpers::fromJMYToISO8601(trim($cells[5]->textContent));
                $supportDatesByVersion[$version] = new \stdClass();
                $supportDatesByVersion[$version]->active = $activeDate;
                $supportDatesByVersion[$version]->security = $securityDate;
            }
        }
        return $supportDatesByVersion;
    }

    /**
     * @return \stdClass[]
     */
    private static function parseEol(): array
    {
        $supportDatesByVersion = [];
        $dom = CachedDownload::dom('https://www.php.net/eol.php');
        foreach ($dom->getElementsByTagName('tr') as $row) {
            $cells = $row->getElementsByTagName('td');
            if (count($cells) < 5) {
                continue;
            }
            $version = trim($cells[0]->textContent);
            if (PhpVersion::fromString($version . ".0")) {
                $supportDatesByVersion[$version] = new \stdClass();
                $supportDatesByVersion[$version]->active = null;
                $supportDatesByVersion[$version]->security = DateHelpers::fromJMYToISO8601(trim($cells[1]->textContent));
            }
        }
        return $supportDatesByVersion;
    }
}