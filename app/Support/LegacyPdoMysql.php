<?php

namespace App\Support;

/**
 * Compatibility alias for Laravel's framework configuration on PHP < 8.4.
 */
final class LegacyPdoMysql
{
    public const ATTR_SSL_CA = \PDO::MYSQL_ATTR_SSL_CA;
}
