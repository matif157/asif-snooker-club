<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Tournament extends BaseModel
{
    protected string $table = 'tournaments';

    public const STATUSES = ['draft', 'open', 'in_progress', 'completed', 'cancelled'];

    public static function allWithStats(): array
    {
        return Database::query(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM tournament_players tp
                      WHERE tp.tournament_id = t.id
                        AND tp.status IN ('registered','active')) AS player_count,
                    (SELECT COUNT(*) FROM tournament_matches tm
                      WHERE tm.tournament_id = t.id AND tm.status = 'completed') AS matches_played,
                    (SELECT COUNT(*) FROM tournament_matches tm
                      WHERE tm.tournament_id = t.id) AS total_matches,
                    (SELECT c.name FROM customers c WHERE c.id = t.champion_id) AS champion_name
             FROM tournaments t
             ORDER BY t.updated_at DESC"
        );
    }

    public static function players(int $tournamentId): array
    {
        return Database::query(
            "SELECT tp.*, c.name AS current_name, c.phone AS current_phone
             FROM tournament_players tp
             LEFT JOIN customers c ON c.id = tp.customer_id
             WHERE tp.tournament_id = ?
             ORDER BY tp.seed ASC, tp.registered_at ASC, tp.id ASC",
            [$tournamentId]
        );
    }

    public static function activePlayers(int $tournamentId): array
    {
        return Database::query(
            "SELECT tp.*
             FROM tournament_players tp
             WHERE tp.tournament_id = ?
               AND tp.status IN ('registered','active')
             ORDER BY tp.seed ASC, tp.registered_at ASC, tp.id ASC",
            [$tournamentId]
        );
    }

    public static function matchCount(int $tournamentId): int
    {
        return (int) (Database::fetchOne(
            'SELECT COUNT(*) AS c FROM tournament_matches WHERE tournament_id = ?',
            [$tournamentId]
        )['c'] ?? 0);
    }

    /**
     * All matches grouped by round, with player display names.
     *
     * @return array<int, array<int, array>>
     */
    public static function bracket(int $tournamentId): array
    {
        $rows = Database::query(
            "SELECT tm.*,
                    ph.name AS home_name,
                    pa.name AS away_name,
                    ch.name AS winner_name,
                    th.number AS table_number
             FROM tournament_matches tm
             LEFT JOIN tournament_players ph ON ph.id = tm.player_home_id
             LEFT JOIN tournament_players pa ON pa.id = tm.player_away_id
             LEFT JOIN tournament_players ch ON ch.id = tm.winner_id
             LEFT JOIN tables th ON th.id = tm.table_id
             WHERE tm.tournament_id = ?
             ORDER BY tm.round_num ASC, tm.match_no ASC",
            [$tournamentId]
        );

        $byRound = [];
        foreach ($rows as $row) {
            $byRound[(int) $row['round_num']][] = $row;
        }
        ksort($byRound);

        return $byRound;
    }

    /**
     * Standard seeded order so that adjacent pairs are round-1 fixtures:
     * n=8  -> [1,8,4,5,2,7,3,6]
     * n=16 -> [1,16,8,9,4,13,5,12,2,15,7,10,3,14,6,11]
     */
    public static function seededOrder(int $count): array
    {
        if ($count <= 2) {
            return [1, 2];
        }
        $half = intdiv($count, 2);
        $order = [];
        foreach (self::seededOrder($half) as $seed) {
            $order[] = $seed;
            $order[] = $count + 1 - $seed;
        }
        return $order;
    }

    public static function generateBracket(int $tournamentId): bool
    {
        $players = self::activePlayers($tournamentId);
        $count = count($players);
        if ($count < 2) {
            return false;
        }

        Database::execute('DELETE FROM tournament_matches WHERE tournament_id = ?', [$tournamentId]);

        // Next power of two (gives byes for non-full fields).
        $needed = 2;
        while ($needed < $count) {
            $needed *= 2;
        }
        $order = self::seededOrder($needed);

        for ($i = 0; $i < $needed; $i += 2) {
            $homeIdx = $order[$i] - 1;
            $awayIdx = $order[$i + 1] - 1;

            if ($homeIdx >= $count && $awayIdx >= $count) {
                continue;
            }

            Database::insert(
                "INSERT INTO tournament_matches
                    (tournament_id, round_num, match_no, player_home_id, player_away_id)
                 VALUES (:tournament_id, 1, :match_no, :home, :away)",
                [
                    'tournament_id' => $tournamentId,
                    'match_no'      => (int) ($i / 2) + 1,
                    'home'          => $homeIdx < $count ? (int) $players[$homeIdx]['id'] : null,
                    'away'          => $awayIdx < $count ? (int) $players[$awayIdx]['id'] : null,
                ]
            );
        }

        // Auto-win byes (exactly one player present) so the bracket connects.
        Database::execute(
            "UPDATE tournament_matches
             SET status = 'completed', winner_id = COALESCE(player_home_id, player_away_id)
             WHERE tournament_id = ?
               AND (player_home_id IS NULL) <> (player_away_id IS NULL)",
            [$tournamentId]
        );

        self::advance($tournamentId);

        return true;
    }

    /**
     * Propagate winners into subsequent rounds, creating them as needed.
     * Called after every completed match.
     */
    public static function advance(int $tournamentId): void
    {
        $round = 1;
        while (true) {
            $rows = Database::query(
                'SELECT id, match_no, status, winner_id, player_home_id
                 FROM tournament_matches
                 WHERE tournament_id = ? AND round_num = ?
                 ORDER BY match_no ASC',
                [$tournamentId, $round]
            );

            if ($rows === []) {
                break;
            }

            // A round advances only when every match is completed.
            $complete = array_reduce($rows, fn($carry, $m) => $carry && $m['status'] === 'completed', true);
            if (!$complete) {
                break;
            }

            // Winner of the final match is the tournament champion.
            if (count($rows) === 1) {
                $winner = (int) ($rows[0]['winner_id'] ?? 0);
                if ($winner > 0) {
                    Database::execute(
                        'UPDATE tournaments SET status = ?, champion_id = ? WHERE id = ?',
                        ['completed', $winner, $tournamentId]
                    );
                    Database::execute(
                        'UPDATE tournament_players SET status = ? WHERE id = ?',
                        ['champion', $winner]
                    );
                    Database::execute(
                        "UPDATE tournament_players SET status = 'active' WHERE tournament_id = ? AND id != ?",
                        [$tournamentId, $winner]
                    );
                }
                break;
            }

            $nextRound = $round + 1;
            $expected  = (int) ceil(count($rows) / 2);

            $nextRows = Database::query(
                'SELECT id, match_no, status FROM tournament_matches
                 WHERE tournament_id = ? AND round_num = ?
                 ORDER BY match_no ASC',
                [$tournamentId, $nextRound]
            );

            for ($i = 0; $i < $expected; $i++) {
                $homeMatch = $rows[$i * 2];
                $awayMatch = $rows[$i * 2 + 1] ?? null;
                $homeWin   = (int) ($homeMatch['winner_id'] ?? 0);
                $awayWin   = $awayMatch ? (int) $awayMatch['winner_id'] : null;

                if ($homeWin <= 0 || ($awayMatch && ($awayWin ?? 0) <= 0)) {
                    // Waiting on a decision — leave next round as-is.
                    continue;
                }

                if (isset($nextRows[$i])) {
                    // Never clobber an already-played match (e.g. when this
                    // helper re-runs over a settled round).
                    if ($nextRows[$i]['status'] === 'completed') {
                        continue;
                    }
                    Database::execute(
                        'UPDATE tournament_matches SET player_home_id = ?, player_away_id = ?, status = ? WHERE id = ?',
                        [$homeWin, $awayWin, 'pending', (int) $nextRows[$i]['id']]
                    );
                } else {
                    Database::insert(
                        "INSERT INTO tournament_matches
                            (tournament_id, round_num, match_no, player_home_id, player_away_id)
                         VALUES (:tid, :round_num, :match_no, :home, :away)",
                        ['tid' => $tournamentId, 'round_num' => $nextRound, 'match_no' => $i + 1, 'home' => $homeWin, 'away' => $awayWin]
                    );
                }
            }

            $round = $nextRound;
        }
    }
}