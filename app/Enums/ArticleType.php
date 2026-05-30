<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Article content type — drives render behavior (FAQ block toggle,
 * HowTo schema eligibility, sort priority on /blog) and the SEO
 * checklist warnings in Filament.
 *
 * Values are stored as varchar in DB (project SQLite-compat rule);
 * the enum lives on the model cast.
 */
enum ArticleType: string
{
    case Pillar = 'pillar';
    case Guide = 'guide';
    case Comparison = 'comparison';
    case News = 'news';
    case CaseStudy = 'case';

    public function label(): string
    {
        return match ($this) {
            self::Pillar => 'Pillar (обзор)',
            self::Guide => 'Guide (how-to)',
            self::Comparison => 'Comparison (X vs Y)',
            self::News => 'News (новости)',
            self::CaseStudy => 'Case (кейс)',
        };
    }

    /**
     * Whether FAQ schema is encouraged for this type. Editor still
     * decides whether real Q&A justify the schema — this is just a
     * UI hint, not a hard gate.
     */
    public function suggestsFaq(): bool
    {
        return in_array($this, [self::Pillar, self::Guide, self::Comparison], true);
    }

    /**
     * HowTo schema makes sense for guides describing step-by-step
     * procedures (как монтировать колодец итд). For all other types
     * it's a force-fit.
     */
    public function suggestsHowTo(): bool
    {
        return $this === self::Guide;
    }
}
