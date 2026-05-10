<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider;

use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\OpenCollaborationCommand;

interface OpensCollaborationInterface
{
    public function openCollaboration(OpenCollaborationCommand $command): void;
}
