<?php

namespace App\Services;

use App\Models\PlutoTableState;
use App\Models\PlutoTableStateHasColumn;


class PlutoTableStateService
{
    private $plutoTableState;
    private $plutoTableStateHasColumn;

    public function __construct(PlutoTableState $plutoTableState, PlutoTableStateHasColumn $plutoTableStateHasColumn)
    {
        $this->plutoTableState = $plutoTableState;
        $this->plutoTableStateHasColumn = $plutoTableStateHasColumn;
    }

    public function getState($info)
    {
        if (!array_key_exists('route', $info) || !array_key_exists('user_id', $info)) {
            return response()->json([], 204);
        }

        $table = $this->plutoTableState->with('columns_states')
            ->where('route', $info['route'])
            ->where('user_id', $info['user_id']);

        if (array_key_exists('name', $info) && isset($info['name'])) {
            $table = $table->where('name', $info['name']);
            return $table->get()->first();
        }

        return $table->get();
    }

    public function saveState($requestInfo)
    {
        if (!array_key_exists('name', $requestInfo) || !array_key_exists('user_id', $requestInfo) || !array_key_exists('route', $requestInfo)) {
            return response()->json(['error' => 'Não é possível salvar o estado da tabela.'], 422);
        }

        $table = $this->getOrCreateTable($requestInfo);

        if (array_key_exists('rows_per_page', $requestInfo)) {
            $table->update(['rows_per_page' => $requestInfo['rows_per_page']]);
        }

        if (array_key_exists('table_size_type', $requestInfo)) {
            $table->update(['table_size_type' => $requestInfo['table_size_type']]);
        }

        $this->saveColumnsStates($requestInfo, $table);

        return response()->json(['success' => 'Estado salvo com sucesso!'], 200);
    }

    private function getOrCreateTable($requestInfo)
    {
        $table = $this->plutoTableState
            ->where('name', $requestInfo['name'])
            ->where('route', $requestInfo['route'])
            ->where('user_id', $requestInfo['user_id'])
            ->get();

        $table = $table->first->get();

        if (is_null($table)) {
            $table = $this->plutoTableState->create([
                'name' => $requestInfo['name'],
                'route' => $requestInfo['route'],
                'user_id' => $requestInfo['user_id']
            ]);
        }

        return $table;
    }

    private function saveColumnsStates($requestInfo, $table)
    {
        if (!array_key_exists('columns_states', $requestInfo) || !is_array($requestInfo['columns_states'])) {
            return;
        }

        foreach ($requestInfo['columns_states'] as $columnState) {
            if (!array_key_exists('field', $columnState)) {
                continue;
            }

            $this->saveSingleColumnState($columnState, $table);
        }
    }

    private function saveSingleColumnState($columnState, $table)
    {
        $columnState['pluto_table_state_id'] = $table->id;

        $state = $table->columns_states->where('field', $columnState['field']);
        $state = $state->first->get();

        if (is_null($state)) {
            $state = $this->plutoTableStateHasColumn->create($columnState);
        } else {
            $state->update($columnState);
        }

        return $state;
    }
}
