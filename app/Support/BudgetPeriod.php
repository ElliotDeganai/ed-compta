<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Periode budgetaire : un cycle complet, d'un jour de depart au meme jour du
 * mois suivant, exclu.
 *
 * Le cycle calendaire classique est le cas particulier ou le jour de depart
 * vaut 1. Avec un salaire verse le 25, le cycle va du 25 au 24 : une paie
 * finance exactement une periode, et le budget ne promet jamais un argent qui
 * n'est pas encore arrive.
 *
 * Quand le decalage est actif, la borne glisse au jour ouvre suivant : le
 * versement n'ayant pas lieu, le cycle ne doit pas s'ouvrir non plus. La borne
 * de fin suit automatiquement le depart du cycle suivant, donc les periodes
 * restent jointives et sans recouvrement, meme quand les deux se decalent
 * differemment.
 *
 * La cle reste au format 'Y-m' et designe le mois ou la periode commence.
 */
final class BudgetPeriod
{
    public const MAX_START_DAY = 28;

    private function __construct(
        public readonly string $key,
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly int $startDay,
        public readonly bool $shifted,
    ) {
    }

    public static function startDay(): int
    {
        $day = (int) Setting::get('cycle_start_day', 1);

        return max(1, min($day, self::MAX_START_DAY));
    }

    public static function shiftEnabled(): bool
    {
        return (bool) Setting::get('cycle_shift_to_business_day', false);
    }

    /**
     * Date d'ouverture du cycle ancre sur ce mois, decalage applique.
     */
    public static function opensOn(Carbon $anchor, ?int $startDay = null, ?bool $shift = null): Carbon
    {
        $startDay = $startDay ?? self::startDay();
        $shift = $shift ?? self::shiftEnabled();

        $date = $anchor->copy()
            ->startOfMonth()
            ->setDay(min($startDay, $anchor->copy()->startOfMonth()->daysInMonth))
            ->startOfDay();

        return $shift ? BusinessDay::next($date) : $date;
    }

    public static function fromKey(string $key, ?int $startDay = null, ?bool $shift = null): self
    {
        $startDay = $startDay ?? self::startDay();
        $shift = $shift ?? self::shiftEnabled();

        $anchor = Carbon::parse($key.'-01')->startOfMonth();

        $start = self::opensOn($anchor, $startDay, $shift);
        $end = self::opensOn($anchor->copy()->addMonth(), $startDay, $shift)->subDay()->startOfDay();

        $theoretical = $anchor->copy()->setDay(min($startDay, $anchor->daysInMonth))->startOfDay();

        return new self($anchor->format('Y-m'), $start, $end, $startDay, ! $start->equalTo($theoretical));
    }

    /**
     * Periode qui contient la date donnee. Avant l'ouverture, on est encore
     * dans la periode ouverte le mois precedent.
     */
    public static function containing(Carbon $date, ?int $startDay = null, ?bool $shift = null): self
    {
        $anchor = $date->copy()->startOfMonth();

        if ($date->copy()->startOfDay()->lt(self::opensOn($anchor, $startDay, $shift))) {
            $anchor->subMonth();
        }

        return self::fromKey($anchor->format('Y-m'), $startDay, $shift);
    }

    public static function current(?int $startDay = null, ?bool $shift = null): self
    {
        return self::containing(Carbon::today(), $startDay, $shift);
    }

    public function previous(): self
    {
        return self::fromKey(Carbon::parse($this->key.'-01')->subMonth()->format('Y-m'), $this->startDay);
    }

    public function next(): self
    {
        return self::fromKey(Carbon::parse($this->key.'-01')->addMonth()->format('Y-m'), $this->startDay);
    }

    public function contains(Carbon $date): bool
    {
        return $date->copy()->startOfDay()->betweenIncluded($this->start, $this->end);
    }

    public function isCurrent(): bool
    {
        return $this->contains(Carbon::today());
    }

    public function startsAt(): string
    {
        return $this->start->toDateString();
    }

    public function endsAt(): string
    {
        return $this->end->toDateString();
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    public function label(): string
    {
        if ($this->startDay === 1 && ! $this->shifted) {
            return $this->start->locale('fr')->isoFormat('MMMM YYYY');
        }

        $sameYear = $this->start->year === $this->end->year;

        return $this->start->locale('fr')->isoFormat($sameYear ? 'D MMM' : 'D MMM YYYY')
            .' – '
            .$this->end->locale('fr')->isoFormat('D MMM YYYY');
    }

    public function shortLabel(): string
    {
        if ($this->startDay === 1 && ! $this->shifted) {
            return $this->start->locale('fr')->isoFormat('MMMM YYYY');
        }

        return $this->start->locale('fr')->isoFormat('D MMM')
            .' – '
            .$this->end->locale('fr')->isoFormat('D MMM YYYY');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label(),
            'short_label' => $this->shortLabel(),
            'start' => $this->startsAt(),
            'end' => $this->endsAt(),
            'days' => $this->days(),
            'start_day' => $this->startDay,
            'shifted' => $this->shifted,
            'is_current' => $this->isCurrent(),
        ];
    }
}
