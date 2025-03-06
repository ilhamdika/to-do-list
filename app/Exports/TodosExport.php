<?php
namespace App\Exports;

use App\Models\Todo;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class TodosExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Todo::query();

        if ($this->request->has('title')) {
            $query->where('title', 'like', '%' . $this->request->title . '%');
        }
        if ($this->request->has('assignee')) {
            $assignees = explode(',', $this->request->assignee);
            $query->whereIn('assignee', $assignees);
        }
        if ($this->request->has('due_date_start') && $this->request->has('due_date_end')) {
            $query->whereBetween('due_date', [$this->request->due_date_start, $this->request->due_date_end]);
        }
        if ($this->request->has('time_tracked_min') && $this->request->has('time_tracked_max')) {
            $query->whereBetween('time_tracked', [$this->request->time_tracked_min, $this->request->time_tracked_max]);
        }
        if ($this->request->has('status')) {
            $statuses = explode(',', $this->request->status);
            $query->whereIn('status', $statuses);
        }
        if ($this->request->has('priority')) {
            $priorities = explode(',', $this->request->priority);
            $query->whereIn('priority', $priorities);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return ['Title', 'Assignee', 'Due Date', 'Time Tracked', 'Status', 'Priority'];
    }

    public function map($todo): array
    {
        return [
            $todo->title,
            str_replace(' ', ', ', $todo->assignee),
            $todo->due_date,
            $todo->time_tracked,
            $todo->status,
            $todo->priority,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $todos = $this->collection();
                $totalTodos = $todos->count();
                $totalTimeTracked = $todos->sum('time_tracked');

                $lastRow = $todos->count() + 2;

                $event->sheet->setCellValue('C' . $lastRow, 'Total Todos:');
                $event->sheet->setCellValue('D' . $lastRow, $totalTodos);

                $event->sheet->setCellValue('C' . ($lastRow + 1), 'Total Time Tracked:');
                $event->sheet->setCellValue('D' . ($lastRow + 1), $totalTimeTracked);
            },
        ];
    }
}
