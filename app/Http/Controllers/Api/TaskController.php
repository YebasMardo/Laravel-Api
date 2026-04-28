<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Auth;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return response()->json(
            // Auth::user()->tasks
            Task::all()->where('user_id', Auth::id())
        );
    }

    public function store(Request $request)
    {
        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'statut' => $request->statut ?? 'pending',
            'user_id' => Auth::id()
        ]);

        return response()->json($task, 201);
    }

    public function show(int $id)
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($id);

        return response()->json($task);
    }

    public function update(Request $request, int $id)
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($id);

        $task->update($request->only([
            'title',
            'description',
            'statut'
        ]));

        return response()->json($task);
    }

    public function destroy(int $id)
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($id);

        $task->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
