<?php

namespace App\Utils;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\ServiceTaker;

class ValueParser
{
    /**
     * Cache de lookup para evitar queries repetidas.
     */
    protected static array $artistCache = [];
    protected static array $bookerCache = [];
    protected static array $costCenterCache = [];
    protected static array $serviceTakerCache = [];

    /**
     * Encontra um artista pelo nome.
     */
    public static function findArtist(string $name): ?Artist
    {
        $normalizedName = self::normalizeName($name);

        if (isset(self::$artistCache[$normalizedName])) {
            return self::$artistCache[$normalizedName];
        }

        $artist = Artist::whereRaw('LOWER(name) = ?', [$normalizedName])->first();
        self::$artistCache[$normalizedName] = $artist;

        return $artist;
    }

    /**
     * Encontra um booker pelo nome.
     */
    public static function findBooker(string $name): ?Booker
    {
        $normalizedName = self::normalizeName($name);

        if (isset(self::$bookerCache[$normalizedName])) {
            return self::$bookerCache[$normalizedName];
        }

        $booker = Booker::whereRaw('LOWER(name) = ?', [$normalizedName])->first();
        self::$bookerCache[$normalizedName] = $booker;

        return $booker;
    }

    /**
     * Encontra um centro de custo pelo nome.
     */
    public static function findCostCenter(string $name): ?CostCenter
    {
        $normalizedName = self::normalizeName($name);

        if (isset(self::$costCenterCache[$normalizedName])) {
            return self::$costCenterCache[$normalizedName];
        }

        $costCenter = CostCenter::whereRaw('LOWER(name) = ?', [$normalizedName])->first();
        self::$costCenterCache[$normalizedName] = $costCenter;

        return $costCenter;
    }

    /**
     * Encontra um tomador de serviço pelo nome/organização.
     */
    public static function findServiceTaker(string $name): ?ServiceTaker
    {
        $normalizedName = self::normalizeName($name);

        if (isset(self::$serviceTakerCache[$normalizedName])) {
            return self::$serviceTakerCache[$normalizedName];
        }

        $serviceTaker = ServiceTaker::whereRaw('LOWER(organization) = ?', [$normalizedName])->first();
        self::$serviceTakerCache[$normalizedName] = $serviceTaker;

        return $serviceTaker;
    }

    /**
     * Normaliza um nome para uso como chave de busca.
     */
    public static function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }
}
