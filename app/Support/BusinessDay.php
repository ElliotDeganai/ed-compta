<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Jours ouvres bancaires, entierement parametres depuis l'administration.
 *
 * Trois reglages : les jours de la semaine consideres comme ouvres, les feries
 * a date fixe, et les feries mobiles calcules depuis Paques. Un versement
 * prevu un jour non ouvre glisse au jour ouvre suivant.
 *
 * Les valeurs par defaut correspondent a une banque traitant du mardi au
 * vendredi, avec les feries vaudois susceptibles de tomber ces jours-la.
 */
final class BusinessDay
{
    /**
     * Jours ouvres par defaut, au format ISO : 1 lundi, 7 dimanche.
     *
     * @var array<int, int>
     */
    public const DEFAULT_DAYS = [2, 3, 4, 5];

    /**
     * @var array<int, array<string, string>>
     */
    public const DEFAULT_FIXED = [
        ['label' => 'Nouvel An', 'date' => '01-01'],
        ['label' => 'Saint-Berchtold', 'date' => '01-02'],
        ['label' => 'Fête nationale', 'date' => '08-01'],
        ['label' => 'Noël', 'date' => '12-25'],
    ];

    /**
     * @var array<int, string>
     */
    public const DEFAULT_MOVABLE = ['good_friday', 'ascension'];

    /**
     * Feries mobiles disponibles, avec leur decalage en jours par rapport au
     * dimanche de Paques.
     *
     * @var array<string, array<string, mixed>>
     */
    public const MOVABLE = [
        'good_friday' => ['label' => 'Vendredi saint', 'offset' => -2],
        'easter_monday' => ['label' => 'Lundi de Pâques', 'offset' => 1],
        'ascension' => ['label' => 'Ascension', 'offset' => 39],
        'whit_monday' => ['label' => 'Lundi de Pentecôte', 'offset' => 50],
    ];

    /**
     * @var array<string, string>
     */
    public const WEEKDAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    /**
     * @return array<int, int>
     */
    public static function days(): array
    {
        $days = self::decode('business_days', self::DEFAULT_DAYS);

        $days = array_values(array_filter(
            array_map('intval', $days),
            fn (int $day) => $day >= 1 && $day <= 7
        ));

        // Un reglage vide rendrait tous les jours non ouvres et la recherche
        // du jour suivant tournerait dans le vide : on retombe sur la valeur
        // par defaut plutot que de bloquer les ecrans.
        return $days ?: self::DEFAULT_DAYS;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function fixedHolidays(): array
    {
        $fixed = self::decode('holidays_fixed', self::DEFAULT_FIXED);

        return array_values(array_filter(
            $fixed,
            fn ($entry) => is_array($entry)
                && isset($entry['date'])
                && preg_match('/^\d{2}-\d{2}$/', (string) $entry['date']) === 1
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function movableHolidays(): array
    {
        $keys = self::decode('holidays_movable', self::DEFAULT_MOVABLE);

        return array_values(array_filter(
            array_map('strval', $keys),
            fn (string $key) => isset(self::MOVABLE[$key])
        ));
    }

    public static function isBusinessDay(Carbon $date): bool
    {
        if (! in_array((int) $date->isoWeekday(), self::days(), true)) {
            return false;
        }

        return ! self::isHoliday($date);
    }

    /**
     * Premier jour ouvre a partir de la date donnee, celle-ci comprise.
     *
     * La borne de securite evite une boucle infinie si le parametrage devenait
     * incoherent : au-dela de trente jours, on rend la date telle quelle
     * plutot que de bloquer la page.
     */
    public static function next(Carbon $date): Carbon
    {
        $cursor = $date->copy()->startOfDay();

        for ($guard = 0; $guard < 30; $guard++) {
            if (self::isBusinessDay($cursor)) {
                return $cursor;
            }

            $cursor->addDay();
        }

        return $date->copy()->startOfDay();
    }

    public static function isHoliday(Carbon $date): bool
    {
        foreach (self::fixedHolidays() as $entry) {
            if ($date->format('m-d') === $entry['date']) {
                return true;
            }
        }

        return in_array($date->toDateString(), self::movableDates((int) $date->year), true);
    }

    /**
     * @return array<int, string>
     */
    public static function movableDates(int $year): array
    {
        $easter = self::easter($year);

        return array_map(
            fn (string $key) => $easter->copy()->addDays(self::MOVABLE[$key]['offset'])->toDateString(),
            self::movableHolidays()
        );
    }

    /**
     * Dimanche de Paques, algorithme gregorien anonyme.
     * easter_date() de PHP depend de l'extension calendar, pas toujours
     * presente : on calcule nous-memes plutot que d'en dependre.
     */
    public static function easter(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);

        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }

    /**
     * @param  array<int, mixed>  $default
     * @return array<int, mixed>
     */
    private static function decode(string $key, array $default): array
    {
        $raw = Setting::get($key);

        if (blank($raw)) {
            return $default;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : $default;
    }
}
