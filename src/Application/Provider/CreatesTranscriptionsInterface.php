<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider;

use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\CreateTranscriptionCommand;

interface CreatesTranscriptionsInterface
{
    public function create(CreateTranscriptionCommand $command): int;
}
