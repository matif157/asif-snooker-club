<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\Customer;
use App\Models\Table as TableModel;

class TournamentController extends Controller
{
    public function index(): void
    {
        if (!user_can('tournaments.view')) {
            $this->error('You do not have permission to view tournaments.', 403);
        }

        $this->view('tournaments/index', [
            'tournaments' => Tournament::allWithStats(),
        ]);
    }

    public function create(): void
    {
        if (!user_can('tournaments.manage')) {
            $this->error('You do not have permission to manage tournaments.', 403);
        }

        $this->view('tournaments/create', []);
    }

    public function store(): void
    {
        if (!user_can('tournaments.manage')) {
            $this->error('You do not have permission to manage tournaments.', 403);
        }

        $data = Request::all();

        $errors = $this->validate($data, ['name' => 'required']);
        if (!empty($errors)) {
            flash('error', 'Tournament name is required.');
            Response::redirect('/tournaments/create');
        }

        $status = (string) ($data['status'] ?? 'draft');
        if (!in_array($status, Tournament::STATUSES, true)) {
            $status = 'draft';
        }

        $id = Tournament::create([
            'name'          => trim((string) $data['name']),
            'entry_fee'     => (float) ($data['entry_fee'] ?? 0),
            'prize_details' => trim((string) ($data['prize_details'] ?? '')),
            'best_of'       => max(1, (int) ($data['best_of'] ?? 3)),
            'status'        => $status,
            'start_date'    => ($data['start_date'] ?? '') !== '' ? $data['start_date'] : null,
            'end_date'      => ($data['end_date'] ?? '') !== '' ? $data['end_date'] : null,
            'notes'         => trim((string) ($data['notes'] ?? '')),
            'created_by'    => \App\Core\Auth::id(),
        ]);

        Response::redirect('/tournaments/' . $id);
    }

    public function show(int $id): void
    {
        if (!user_can('tournaments.view')) {
            $this->error('You do not have permission to view tournaments.', 403);
        }

        $tournament = Tournament::find($id);
        if ($tournament === null) {
            Response::error('Tournament not found', 404);
        }

        $activePlayers = Tournament::activePlayers($id);
        $registeredCount = count($activePlayers);

        $this->view('tournaments/show', [
            'tournament'     => $tournament,
            'players'        => Tournament::players($id),
            'bracket'        => Tournament::bracket($id),
            'tables'         => TableModel::activeTables(),
            'customers'      => Customer::search(''),
            'registeredCount'=> $registeredCount,
        ]);
    }

    public function updateStatus(int $id): void
    {
        if (!user_can('tournaments.manage')) {
            $this->error('You do not have permission to manage tournaments.', 403);
        }

        $tournament = Tournament::find($id);
        if ($tournament === null) {
            Response::error('Tournament not found', 404);
        }

        $status = (string) Request::input('status');
        if (!in_array($status, Tournament::STATUSES, true)) {
            flash('error', 'Invalid tournament status.');
            Response::redirect('/tournaments/' . $id);
        }

        if ($status === 'in_progress' && Tournament::matchCount($id) === 0) {
            if (!Tournament::generateBracket($id)) {
                flash('error', 'Register at least 2 players before starting the tournament.');
                Response::redirect('/tournaments/' . $id);
            }
        }

        $tournament->update(['status' => $status]);
        Tournament::advance($id);

        flash('success', 'Tournament status updated.');
        Response::redirect('/tournaments/' . $id);
    }

    public function register(int $id): void
    {
        if (!user_can('tournaments.manage')) {
            $this->error('You do not have permission to manage tournaments.', 403);
        }

        $data  = Request::all();
        $customerId = (int) ($data['customer_id'] ?? 0);

        if ($customerId <= 0 && trim((string) ($data['name'] ?? '')) === '') {
            flash('error', 'Choose a registered customer or enter a walk-in name.');
            Response::redirect('/tournaments/' . $id);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($customerId > 0) {
            $customer = Customer::find($customerId);
            if ($customer === null) {
                flash('error', 'Customer not found.');
                Response::redirect('/tournaments/' . $id);
            }
            $name  = $name !== '' ? $name : $customer->name;
            $phone = $phone !== '' ? $phone : $customer->phone;

            $dup = Database::fetchOne(
                'SELECT id FROM tournament_players WHERE tournament_id = ? AND customer_id = ?',
                [$id, $customerId]
            );
            if ($dup !== null) {
                flash('error', 'This customer is already registered.');
                Response::redirect('/tournaments/' . $id);
            }
        }

        $maxSeed = (int) (Database::fetchOne(
            'SELECT COALESCE(MAX(seed), 0) AS s FROM tournament_players WHERE tournament_id = ?',
            [$id]
        )['s'] ?? 0);

        Database::insert(
            'INSERT INTO tournament_players (tournament_id, customer_id, name, phone, seed)
             VALUES (:tid, :customer_id, :name, :phone, :seed)',
            [
                'tid'         => $id,
                'customer_id' => $customerId > 0 ? $customerId : null,
                'name'        => $name,
                'phone'       => $phone !== '' ? $phone : null,
                'seed'        => $maxSeed + 1,
            ]
        );

        flash('success', $name . ' registered.');
        Response::redirect('/tournaments/' . $id);
    }

    public function withdraw(int $id, int $playerId): void
    {
        if (!user_can('tournaments.manage')) {
            $this->error('You do not have permission to manage tournaments.', 403);
        }

        Database::execute(
            'UPDATE tournament_players SET status = ? WHERE id = ? AND tournament_id = ?',
            ['withdrew', $playerId, $id]
        );

        flash('success', 'Player withdrew from the tournament.');
        Response::redirect('/tournaments/' . $id);
    }

    public function bracket(int $id): void
    {
        if (!user_can('tournaments.manage')) {
            $this->error('You do not have permission to manage tournaments.', 403);
        }

        if (!Tournament::generateBracket($id)) {
            flash('error', 'Need at least 2 registered players to build a bracket.');
        } else {
            flash('success', 'Bracket generated.');
        }

        Response::redirect('/tournaments/' . $id);
    }

    public function score(int $id, int $matchId): void
    {
        if (!user_can('tournaments.manage')) {
            $this->error('You do not have permission to manage tournaments.', 403);
        }

        $match = Database::fetchOne(
            'SELECT * FROM tournament_matches WHERE id = ? AND tournament_id = ?',
            [$matchId, $id]
        );
        if ($match === null) {
            Response::error('Match not found', 404);
        }

        $home = (int) Request::input('score_home');
        $away = (int) Request::input('score_away');
        $home = max(0, min($home, 255));
        $away = max(0, min($away, 255));

        $winnerId = null;
        if ($home > $away) {
            $winnerId = $match['player_home_id'];
        } elseif ($away > $home) {
            $winnerId = $match['player_away_id'];
        }

        if ($winnerId === null) {
            flash('error', 'Scores are tied — adjust so there is a winner.');
            Response::redirect('/tournaments/' . $id);
        }

        $tableId = (int) Request::input('table_id');
        $scheduledAt = (string) (Request::input('scheduled_at') ?? '');

        Database::execute(
            'UPDATE tournament_matches
             SET score_home = ?, score_away = ?, winner_id = ?, status = ?,
                 table_id = ?, scheduled_at = ?, notes = ?
             WHERE id = ?',
            [
                $home,
                $away,
                $winnerId,
                'completed',
                $tableId > 0 ? $tableId : null,
                $scheduledAt !== '' ? $scheduledAt : null,
                trim((string) Request::input('notes')),
                $matchId,
            ]
        );

        Tournament::advance($id);

        flash('success', 'Match result recorded.');
        Response::redirect('/tournaments/' . $id);
    }

    public function destroy(int $id): void
    {
        if (!user_can('tournaments.manage')) {
            $this->error('You do not have permission to manage tournaments.', 403);
        }

        $tournament = Tournament::find($id);
        if ($tournament !== null) {
            $tournament->delete();
        }

        flash('success', 'Tournament deleted.');
        Response::redirect('/tournaments');
    }
}