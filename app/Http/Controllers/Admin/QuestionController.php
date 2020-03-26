<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Option;
use App\Model\QuestionType;
use App\Model\StudentType;
use Illuminate\Http\Request;
use App\Model\Department;
use App\Model\Subject;
use App\Model\Question;
use App\Model\QuestionTemplate;
use App\Http\Controllers\Components\fileHandlerComponent;
use Str;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->perPage ?: 10;
        $keyword = $request->keyword;

        $questions = Question::with('template', 'questionType', 'subject', 'studentType');

        if($keyword){

            $keyword = '%'.$keyword.'%';

            $questions = $questions->where('question', 'like', $keyword)
                ->orWhere('description', 'like', $keyword)
                ->orWhereHas('studentType', function ($query) use ($keyword) {
                    $query->where('name', 'like', $keyword);
                });
        }

        $questions = $questions->latest()->paginate($perPage);

        return view('admin.question.index', compact('questions'));
    }

    public function create()
    {
        $options = [];
        $question_options = ['id' => ''];

        $questionTemplates = QuestionTemplate::all();
        $questionTypes = QuestionType::all();
        $subjects = Subject::all();
        $studentTypes = StudentType::latest()->get();

        return view('admin.question.create', compact('options', 'question_options', 'questionTemplates', 'questionTypes', 'studentTypes', 'subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required',
            'question_type_id' => 'required',
            'student_type_id'  => 'required'
        ]);

        //check question quantity of question template/exam
        if ($question_template_id = $request->question_template_id) {
            $question_template = QuestionTemplate::where('id', $question_template_id)->first();

            $request['subject_id'] = $question_template->subject_id;

            $total_questions = Question::where('question_template_id', $question_template_id)->count();

            if($question_template->total_questions <= $total_questions ){
                return back()->with('warning', 'Total number of question exceeded in selected exam.');
            }
        }

        if($request->img){
            $image = fileHandlerComponent::imageUpload($request->file('img'), 'img');
            $request['image'] = $image;
        }

        if(is_null($request->options)){
            return back()->with('warning', 'No option added!');
        }


        //store options
        $option_data = $this->storeOptions($request->options);

        $created_option_list = $option_data['created_option_list']; // ['name' => 'id']

        $request_options = is_null($request->options) ? [] : $request->options;


        // rearrange option_ids array in harmony with correct_ans array index
        $option_ids = [];
        foreach ($request_options as $option_id){

            if (array_key_exists($option_id, $created_option_list)){
                $option_ids[] = (int) $created_option_list[$option_id];
            }else{
                $option_ids[] = (int) $option_id;
            }
        }


        // correct_ans array value string to int
        $request_correct_ans = is_null($request->correct_ans) ? [] : $request->correct_ans;
        $correct_ans = array_map('intval', $request_correct_ans);

        // make array from option_ids and correct_ans for attach in option_question table
        // [ 105 = ['correct_answer' => 1] ]
        $attach_able_options = [];
        foreach ($option_ids as $index => $id){

            if (array_key_exists($index, $correct_ans)){
                $correct_answer = $correct_ans[$index];
            }else{
                $correct_answer = '';
            }

            $attach_able_options[$id] = ['correct_answer' => $correct_answer];
        }


        $request['slug'] = Str::slug($request->question);

        $question = Question::create($request->all());

        $question->options()->attach($attach_able_options);

        return redirect()->route('admin.questions.index')->with('successTMsg', 'Question save successfully');
    }

    public function show(Question $question)
    {
        $options = $question->options;
        $question_options = $options->count() > 0 ? $options : ['id' => ''];

        $question = Question::with('template', 'questionType')->first();

        return view('admin.question.view', compact('question', 'question_options'));
    }

    public function edit(Question $question)
    {
        $options = $question->options;
        $question_options = $options->count() > 0 ? $options : ['id' => ''];
        $questionTemplates = QuestionTemplate::all();
        $questionTypes = QuestionType::all();
        $subjects = Subject::all();
        $studentTypes = StudentType::latest()->get();


        return view('admin.question.edit', compact('question', 'options', 'question_options', 'subjects', 'questionTemplates', 'questionTypes', 'studentTypes'));
    }

    public function update(Request $request, Question $question)
    {
        $request->validate([
            'question'      => 'required',
            //'question_template_id' => 'required',
            'question_type_id' => 'required'
        ]);

        //check question quantity of question template/exam
        if ($question_template_id = $request->question_template_id) {

            $question_template = QuestionTemplate::where('id', $question_template_id)->first();

            $request['subject_id'] = $question_template->subject_id;

            $total_questions = Question::where('question_template_id', $question_template_id)
                ->where('id', '!=', $question->id)
                ->count();

            if($question_template->total_questions <= $total_questions ){
                return back()->with('warning', 'Total number of question exceeded in selected exam.');
            }
        }

        if($request->img){

            $image = fileHandlerComponent::imageUpload($request->file('img'), 'img');
            $request['image'] = $image;

            if($request->oldImage){
                fileHandlerComponent::imageDelete($request->oldImage);
            }
        }

        //store options
        $option_data = $this->storeOptions($request->options);

        $created_option_list = $option_data['created_option_list']; // ['name' => 'id']

        $request_options = is_null($request->options) ? [] : $request->options;


        // rearrange option_ids array in harmony with correct_ans array index
        $option_ids = [];
        foreach ($request_options as $option_id){

            if (array_key_exists($option_id, $created_option_list)){
                $option_ids[] = (int) $created_option_list[$option_id];
            }else{
                $option_ids[] = (int) $option_id;
            }
        }


        // correct_ans array value string to int
        $request_correct_ans = is_null($request->correct_ans) ? [] : $request->correct_ans;
        $correct_ans = array_map('intval', $request_correct_ans);

        // make array from option_ids and correct_ans for attach in option_question table
        // [ 105 = ['correct_answer' => 1] ]
        $attach_able_options = [];
        foreach ($option_ids as $index => $id){

            if (array_key_exists($index, $correct_ans)){
                $correct_answer = $correct_ans[$index];
            }else{
                $correct_answer = '';
            }

            $attach_able_options[$id] = ['correct_answer' => $correct_answer];
        }

        $request['slug'] = Str::slug($request->question);
        $question->update($request->all());

        $question->options()->sync($attach_able_options);


        return redirect(route('admin.questions.index'))->with('successTMsg', 'Question has been updated successfully');
    }

    public function destroy(Question $question)
    {
        $question->options()->detach();

        $question->delete();

        if ($question->image) {
            $this->fileHandler->imageDelete($question->image);
        }

        return back()->with('successTMsg', 'Question has been deleted successfully');
    }

    public function storeOptions($request_options = []){

        $request_options = is_null($request_options) ? [] : $request_options;

        $exist_ids = Option::whereIn('id', $request_options)->pluck('id')->toArray();

        // get options which option does not exists in database
        $store_able_options = array_merge(array_diff($request_options, $exist_ids), array_diff($exist_ids, $request_options));

        $created_ids = [];
        $created_option_list = [];

        // make new created option ids and list array (name, id)
        foreach ($store_able_options as $option){
            $create_option = Option::create(['option' => $option]);
            $created_ids[] = $create_option->id;
            $created_option_list[$create_option->option] = $create_option->id;
        }

        // return new created option list (name, id) and all requested option ids array
        return ['created_option_list' => $created_option_list, 'option_ids' => array_merge($exist_ids, $created_ids)];
    }

    public function getOptionList(){

        $term = request('term');

        $options = Option::where('is_active', 1)
            ->where('option', 'LIKE', "%$term%")
            ->select('option', 'id')
            ->take(20)->get();

        $new_options = [];

        foreach ($options as $option){
            $new_options[] = ['value' => $option->id, 'text' => $option->option];
        }

        return response()->json($new_options);
    }
}
