<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
}
