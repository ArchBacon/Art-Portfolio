<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class BasenameExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('basename', [$this, 'basenameFilter'], ['is_safe' => ['html']]),
        ];
    }

    public static function basenameFilter(string $value, string $suffix = ''): string
    {
        return basename($value, $suffix);
    }
}
