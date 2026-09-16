<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class TournamentMatch extends BaseModel
{
    protected string $table = 'tournament_matches';

    public static function score(array $data): void
    {
        $id = (int) $data['match_id'];

        Database::execute(
            'UPDATE tournament_matches
             SET score_home = ?, score_away = ?, winner_id = ?, status = ?, notes = ?
             WHERE id = ?',
            [
                (int) $data['home'],
                (int) $data['away'],
                $data['winner_id'] ?? null,
                'completed',
                $data['notes'] ?? null,
                $id,
            ]
        );
    }
}