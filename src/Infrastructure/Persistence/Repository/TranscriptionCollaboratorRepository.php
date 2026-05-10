<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository;

use Fisharebest\Webtrees\DB;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\CollaborationRole;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema\SchemaManager;

final class TranscriptionCollaboratorRepository
{
    private const string TABLE = SchemaManager::TABLE_COLLABORATORS;

    public function setInitiator(int $transcription_id, int $user_id): void
    {
        $this->upsert($transcription_id, $user_id, CollaborationRole::INITIATOR, $user_id);
    }

    /**
     * @param array<int,int> $user_ids
     */
    public function setCollaborators(int $transcription_id, array $user_ids, int $invited_by_user_id): void
    {
        $user_ids = array_values(array_unique(array_map('intval', $user_ids)));

        foreach ($user_ids as $user_id) {
            if ($user_id > 0) {
                $this->upsert($transcription_id, $user_id, CollaborationRole::COLLABORATOR, $invited_by_user_id);
            }
        }
    }

    public function isActiveCollaborator(int $transcription_id, int $user_id): bool
    {
        return DB::table(self::TABLE)
            ->where('transcription_id', '=', $transcription_id)
            ->where('user_id', '=', $user_id)
            ->where('is_active', '=', true)
            ->exists();
    }

    public function roleForUser(int $transcription_id, int $user_id): ?string
    {
        $role = DB::table(self::TABLE)
            ->where('transcription_id', '=', $transcription_id)
            ->where('user_id', '=', $user_id)
            ->where('is_active', '=', true)
            ->value('role');

        return $role === null ? null : (string) $role;
    }

    public function isInitiator(int $transcription_id, int $user_id): bool
    {
        return DB::table(self::TABLE)
            ->where('transcription_id', '=', $transcription_id)
            ->where('user_id', '=', $user_id)
            ->where('role', '=', CollaborationRole::INITIATOR)
            ->where('is_active', '=', true)
            ->exists();
    }

    /**
     * @return array<int,int>
     */
    public function activeUserIds(int $transcription_id): array
    {
        return DB::table(self::TABLE)
            ->where('transcription_id', '=', $transcription_id)
            ->where('is_active', '=', true)
            ->orderBy('role')
            ->orderBy('user_id')
            ->pluck('user_id')
            ->map(static fn ($user_id): int => (int) $user_id)
            ->all();
    }

    /**
     * @return array<int,string>
     */
    public function activeRolesByUserId(int $transcription_id): array
    {
        $roles = [];

        foreach (DB::table(self::TABLE)
            ->where('transcription_id', '=', $transcription_id)
            ->where('is_active', '=', true)
            ->orderBy('role')
            ->orderBy('user_id')
            ->get() as $row) {
            $roles[(int) $row->user_id] = (string) $row->role;
        }

        return $roles;
    }

    private function upsert(int $transcription_id, int $user_id, string $role, int $invited_by_user_id): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            [
                'transcription_id' => $transcription_id,
                'user_id' => $user_id,
            ],
            [
                'role' => $role,
                'invited_by_user_id' => $invited_by_user_id,
                'is_active' => true,
            ]
        );
    }
}
