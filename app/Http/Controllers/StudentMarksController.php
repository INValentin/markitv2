<?php

namespace App\Http\Controllers;

use App\Models\Mark;
use App\Models\Module;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentMarksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function index(Student $student)
    {
        return response($student->marks()->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Student $student)
    {
        // $fields = $request->validate([
        //     'module_id' => "required|exists:modules,id",
        //     'semester' => "required|in:I,II",
        //     'formative' => "required|numeric|max:50",
        //     'summative' => "required|numeric|max:50",
        //     'academic_year' => 'required|regex:/^(\d){4}+ (-|\/) +(\d){4}$/'
        // ]);

        // $fields['total'] = (int) $fields['formative'] + (int) $fields['summative'];
        // $fields['decision'] = $fields['total'] >= 50 ? true : false;

        $validator = Validator::make(
            $request->all(),
            [
                // 'student_id' => 'required|exists:students,id',
                'module_id' => "required|exists:modules,id",
                'semester' => "required|in:I,II",
                'formative' => "required|numeric|max:50",
                'summative' => "required|numeric|max:50",
                'academic_year' => ['required', 'regex:/^(\d){4}+( |)(-|\/)( |)+(\d){4}$/']
            ],
        );


        $validator->after(function ($validator) use ($request, $student) {
            $student = Student::find($request->student_id);
            $module = Module::find($request->module_id);

            // check if student and module belongs to the same department
            if ($student && $module && $student->foculty_id !== $module->foculty_id) {
                $validator->errors()->add(
                    'module_id',
                    'Selected module don\'t exist student\'s department.'
                );
                return;
            }

            // check if marks was already recorded
            $marksExits = Mark::where(function (Builder $query) use ($request, $student) {
                $query->where('module_id', $request->module_id);
                $query->where('student_id', $student->id);
                $query->where('semester', $request->semester);
                $query->where('academic_year', $request->academic_year);
            })->count();

            if ($marksExits) {
                $validator->errors()->add(
                    'student_id',
                    'Marks for this student, module, academic year and semester already exists'
                );
            }

            // check if teacher teaches the module
            if (!$request->user()->tokenCan('user:teacher')) {
                // if current user is not a teacher
                return;
            }

            // if current user is a teacher
            // check if the current teacher is the module teacher
            if ($request->user()->id !== $module->teacher_id) {
                $validator->errors()->add('module_id', 'You are not allowed to record marks for this module');
            }
        });

        $fields = $validator->validate();

        $fields['total'] = (int) $fields['formative'] + (int) $fields['summative'];
        $fields['decision'] = $fields['total'] >= 50 ? true : false;

        // $marks = Mark::create($fields);

        $marks = $student->marks()->create($fields);

        return response($marks, 201);
    }
}
