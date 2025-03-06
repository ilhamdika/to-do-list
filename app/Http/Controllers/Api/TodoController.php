<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TodosExport;
class TodoController extends Controller
{
    public function createTodo(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string',
                'assignee' => 'nullable|string',
                'due_date' => 'required|date|after_or_equal:' . Carbon::today(),
                'time_tracked' => 'nullable|numeric',
                'status' => ['nullable', Rule::in(['pending', 'open', 'in_progress', 'completed'])],
                'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            ]);

            if (!isset($validatedData['status'])) {
                $validatedData['status'] = 'pending';
            }

            $todo = Todo::create($validatedData);

            return response()->json([
                'message' => 'Todo created successfully',
                'data' => $todo
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        return Excel::download(new TodosExport($request), 'date_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function getChart(Request $request)
    {
        try {
            $type = $request->query('type');

            if ($type === 'status') {
                $data = [
                    'status_summary' => Todo::selectRaw('status, COUNT(*) as count')
                        ->groupBy('status')
                        ->pluck('count', 'status')
                ];
            } elseif ($type === 'priority') {
                $data = [
                    'priority_summary' => Todo::selectRaw('priority, COUNT(*) as count')
                        ->groupBy('priority')
                        ->pluck('count', 'priority')
                ];
            } elseif ($type === 'assignee') {
                $data = [
                    'assignee_summary' => Todo::selectRaw('assignee, COUNT(*) as total_todos, 
                            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as total_pending_todos,
                            SUM(CASE WHEN status = "completed" THEN time_tracked ELSE 0 END) as total_timetracked_completed_todos')
                        ->groupBy('assignee')
                        ->get()
                        ->keyBy('assignee')
                ];
            } else {
                return response()->json(['error' => 'Parameter type tidak valid'], 400);
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
