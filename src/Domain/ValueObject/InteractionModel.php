<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject;

use Fisharebest\Webtrees\I18N;

final class InteractionModel
{
    public const string MANUAL_DIRECT = 'manual_direct';
    public const string AUTOMATED_ASYNC = 'automated_async';
    public const string CROWD_ASYNC = 'crowd_async';
    public const string INTERNAL_COLLABORATIVE = 'internal_collaborative';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::MANUAL_DIRECT => I18N::translate('Manual (direct editing)'),
            self::AUTOMATED_ASYNC => I18N::translate('Automated (asynchronous)'),
            self::CROWD_ASYNC => I18N::translate('Crowd-based (asynchronous)'),
            self::INTERNAL_COLLABORATIVE => I18N::translate('Internal collaboration'),
        ];
    }
}