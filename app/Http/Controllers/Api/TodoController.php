<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $fileName = 'date_' . now()->format('Y-m-d') . '.csv';
        $todos = Todo::query();

        if ($request->has('title')) {
            $todos->where('title', 'like', '%' . $request->title . '%');
        }
        if ($request->has('assignee')) {
            $assignees = explode(',', $request->assignee);
            $todos->whereIn('assignee', $assignees);
        }
        if ($request->has('due_date_start') && $request->has('due_date_end')) {
            $todos->whereBetween('due_date', [$request->due_date_start, $request->due_date_end]);
        }
        if ($request->has('time_tracked_min') && $request->has('time_tracked_max')) {
            $todos->whereBetween('time_tracked', [$request->time_tracked_min, $request->time_tracked_max]);
        }
        if ($request->has('status')) {
            $statuses = explode(',', $request->status);
            $todos->whereIn('status', $statuses);
        }
        if ($request->has('priority')) {
            $priorities = explode(',', $request->priority);
            $todos->whereIn('priority', $priorities);
        }

        $todos = $todos->get();

        $response = new StreamedResponse(function () use ($todos) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Title', 'Assignee', 'Due Date', 'Time Tracked', 'Status', 'Priority']);

            foreach ($todos as $todo) {
                fputcsv($handle, [
                    $todo->title,
                    $todo->assignee,
                    $todo->due_date,
                    $todo->time_tracked,
                    $todo->status,
                    $todo->priority,
                ]);
            }

            fputcsv($handle, ['', '', '', 'Total Time Tracked:', $todos->sum('time_tracked')]);

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
