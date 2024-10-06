<?php

namespace Abivia\Geocode\CacheHandler;

use Abivia\Geocode\CacheHandler\CacheHandler;
use Abivia\Geocode\GeocodeResult\GeocodeResult;
use IPLib\Address\AddressInterface;
use PDO;
use PDOException;

class PdoCache extends AbstractCache implements CacheHandler
{
    protected PDO $db;
    protected string $dbIpTable = 'geocoder_cache_ip';
    protected string $dbSubnetTable = 'geocoder_cache_subnet';
    protected string $dbOptionsTable = 'geocoder_cache_options';
    protected bool $hit;

    /**
     * Create a database cache.
     *
     * @param PDO $db The database connection.
     * @param string|null $dbIpTable Name of the IP table, if it is to be overridden.
     * @param string|null $dbSubnetTable Name of the subnet mapping table, if it is to be overridden.
     */
    public function __construct(
        PDO $db,
        ?string $dbIpTable = null,
        ?string $dbSubnetTable = null,
        ?string $dbOptionsTable = null
    )
    {
        $this->hitTime = 30 * 24 * 3600;
        $this->db = $db;
        if ($dbIpTable) {
            $this->dbIpTable = $dbIpTable;
        }
        if ($dbSubnetTable) {
            $this->dbSubnetTable = $dbSubnetTable;
        }
        if ($dbOptionsTable) {
            $this->dbOptionsTable = $dbOptionsTable;
        }
        $this->loadCache();
    }

    /**
     * @inheritDoc
     */
    public function get(AddressInterface $address): ?GeocodeResult
    {
        $this->hit = false;
        static $fetchStatement = null;
        if (!$fetchStatement) {
            $sql = "SELECT `data` FROM `$this->dbIpTable` WHERE `ip`=:ip AND `expires`>=:stale";
            $fetchStatement = $this->db->prepare($sql);
        }
        if (
            $fetchStatement->execute([
                ':ip' => $address->getComparableString(),
                ':stale' => time(),
            ])
        ) {
            $rows = $fetchStatement->fetchAll(PDO::FETCH_COLUMN, 0);
            if (count($rows)) {
                $this->hit = true;
                if ($rows[0]) {
                    $result = unserialize($rows[0]);
                    $result->cached(true);
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSubnet(AddressInterface $address): ?GeocodeResult
    {
        $this->hit = false;
        static $fetchStatement = null;
        if (!$fetchStatement) {
            $sql = "SELECT `data` FROM `$this->dbSubnetTable` AS `sn`"
                . " INNER JOIN `$this->dbIpTable` as `c`"
                . " ON `sn`.`mapped` = `c`.`ip`"
                . " WHERE `subnet`=:subnet AND `expires`>=:stale";
            $fetchStatement = $this->db->prepare($sql);
        }
        if (
            $fetchStatement->execute([
                ':subnet' => $this->subnetAddress($address),
                ':stale' => time(),
            ])
        ) {
            $rows = $fetchStatement->fetchAll(PDO::FETCH_COLUMN, 0);
            if (count($rows)) {
                $this->hit = true;
                if ($rows[0]) {
                    $result = unserialize($rows[0]);
                    $result->cached(true);
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Ensure that the database has tables required for operating the cache.
     * @return void
     */
    private function loadCache(): void
    {
        // Make sure our tables are there
        $sql = "CREATE TABLE IF NOT EXISTS `$this->dbIpTable` ("
            . " `ip` VARCHAR(64) PRIMARY KEY"
            . ", `data` TEXT"
            . ", `expires` INTEGER"
            . ")";
        $this->db->query($sql);
        $sql = "CREATE INDEX IF NOT EXISTS `{$this->dbIpTable}_expires`"
            . " ON `$this->dbIpTable` (`expires`)";
        $this->db->query($sql);
        $sql = "CREATE TABLE IF NOT EXISTS `$this->dbSubnetTable` ("
            . " `subnet` VARCHAR(64) PRIMARY KEY"
            . ", `mapped` VARCHAR(64)"
            . ")";
        $this->db->query($sql);
        $sql = "CREATE TABLE IF NOT EXISTS `$this->dbOptionsTable` ("
            . " `key` VARCHAR(255) PRIMARY KEY"
            . ", `value` VARCHAR(255)"
            . ")";
        $this->db->query($sql);
        $this->purgeExpiredCache();
    }

    private function purgeExpiredCache(): void
    {
        // Check for a session variable
        $sessionKey = self::class . '::last_ip_purge_time';
        $lastPurge = $_SESSION[$sessionKey] ?? false;
        if ($lastPurge === false) {
            // Fetch the time of the last purge
            $lastQuery = $this->db->prepare(
                "SELECT `value` FROM `$this->dbOptionsTable` WHERE `key`=:key"
            );
            $lastQuery->execute([':key' => 'last_ip_purge_time']);
            $lastPurge = $lastQuery->fetchColumn();

            // If there's still no last purge time, create it and it set to zero
            if ($lastPurge === false) {
                $this->db->query(
                    "INSERT INTO `$this->dbOptionsTable` (`key`,`value`)"
                    . "VALUES ('last_ip_purge_time', 0)"
                );
                $lastPurge = 0;
                $_SESSION[$sessionKey] = 0;
            }
        }

        // Do we need to run a purge cycle?
        $now = time();
        if ($lastPurge + $this->purgeTime < $now) {
            $this->db->query("DELETE FROM `$this->dbIpTable` WHERE `expires`<$now");
            $this->db->query(
                "UPDATE `$this->dbOptionsTable` SET `value`=$now"
                . " WHERE `key`='last_ip_purge_time'"
            );
            $_SESSION[$sessionKey] = $now;
        }
    }

    /**
     * @inheritDoc
     */
    public function set(AddressInterface $address, ?GeocodeResult $data): void
    {
        static $mainUp = null;
        static $subUp = null;
        if (!$mainUp) {
            $sql = "REPLACE INTO `$this->dbIpTable`"
                . " (`ip`,`data`,`expires`) VALUES (:ip, :data, :expires)";
            $mainUp = $this->db->prepare($sql);
            $sql = "REPLACE INTO `$this->dbSubnetTable`"
                . " (`subnet`,`mapped`) VALUES (:subnet,:mapped)";
            $subUp = $this->db->prepare($sql);
        }
        if ($data === null) {
            $expires = time() + $this->missTime;
            $serial = null;
        } else {
            $expires = time() + $this->hitTime;
            $serial = serialize($data);
        }
        $fullAddress = $address->getComparableString();
        $mainUp->execute([
            ':ip' => $fullAddress,
            ':data' => $serial,
            ':expires' => $expires,
        ]);
        if ($serial) {
            $subUp->execute([
                ':subnet' => $this->subnetAddress($address),
                ':mapped' => $fullAddress,
            ]);
        }
    }
}
